<?php

declare(strict_types=1);

namespace App\adms\Models\Services;

use App\adms\Helpers\GenerateLog;
use App\adms\Helpers\LgpdAuditHelper;
use App\adms\Helpers\RhCandidatoOrigemHelper;
use App\adms\Models\Repository\LgpdConsentimentosRepository;
use App\adms\Models\Repository\LgpdTermosRepository;
use App\adms\Models\Repository\RhCandidatosRepository;
use App\adms\Models\Repository\RhCandidaturaHistoricoRepository;
use App\adms\Models\Repository\RhVagasRepository;
use Exception;
use PDO;

/**
 * Candidatura pública em vaga publicada (Expand Fase 3).
 * Inclui upload obrigatório de currículo no storage privado (ADR-0003).
 * Dedupe por e-mail + vaga.
 */
final class RhCandidaturaPublicaService
{
    public const LGPD_TERMO_TIPO = 'curriculo_candidato';
    /** @deprecated Preferir RhCandidatoOrigemHelper::FORM_TRABALHE_CONOSCO / fromUtmSource */
    public const ORIGEM_CANDIDATO = RhCandidatoOrigemHelper::FORM_TRABALHE_CONOSCO;

    /**
     * @param array<string, mixed> $input
     * @param array<string, mixed>|null $curriculoFile entrada de $_FILES['curriculo']
     * @return array{candidato_id: int, created_candidato: bool, vinculo_id: int, anexo_id: int}
     */
    public function candidatar(int $vagaId, array $input, ?array $curriculoFile = null): array
    {
        $vaga = (new RhVagasRepository())->getPublicadaById($vagaId);
        if ($vaga === null) {
            throw new Exception('Esta vaga não está disponível para candidatura.');
        }

        $nome = trim((string) ($input['nome'] ?? ''));
        $email = strtolower(trim((string) ($input['email'] ?? '')));
        $telefone = trim((string) ($input['telefone'] ?? ''));
        $cidade = trim((string) ($input['cidade'] ?? ''));
        $estado = strtoupper(trim((string) ($input['estado'] ?? '')));
        $areaInteresse = trim((string) ($input['area_interesse'] ?? ''));
        $mensagem = trim((string) ($input['mensagem'] ?? ''));
        $canalOrigem = RhCandidatoOrigemHelper::normalizeFromInput(
            $input['utm_source'] ?? $input['canal_origem'] ?? ''
        );

        if (mb_strlen($nome) < 3) {
            throw new Exception('Informe seu nome completo.');
        }
        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            throw new Exception('Informe um e-mail válido.');
        }
        if ($telefone === '') {
            throw new Exception('Informe um telefone para contato.');
        }
        if ($areaInteresse === '') {
            throw new Exception('Informe a área de interesse.');
        }
        if (empty($input['lgpd_consent']) || (string) $input['lgpd_consent'] !== '1') {
            throw new Exception('É obrigatório aceitar o termo de consentimento LGPD.');
        }
        // Honeypot
        if (trim((string) ($input['website'] ?? '')) !== '') {
            throw new Exception('Não foi possível enviar a candidatura.');
        }

        $file = is_array($curriculoFile) ? $curriculoFile : [];
        $phpError = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($phpError === UPLOAD_ERR_NO_FILE || trim((string) ($file['name'] ?? '')) === '') {
            throw new Exception('Anexe o currículo em PDF ou DOC/DOCX (máximo 10 MB).');
        }

        $termo = (new LgpdTermosRepository())->getTermoAtivoPorTipo(self::LGPD_TERMO_TIPO);
        if ($termo === null) {
            throw new Exception(
                'Candidatura temporariamente indisponível: termo LGPD não configurado. Tente mais tarde.'
            );
        }

        $candRepo = new RhCandidatosRepository();
        $existing = $candRepo->findActiveByEmail($email);
        if ($existing !== null) {
            $candidatoId = (int) $existing['id'];
            if ($candRepo->hasVinculoComVaga($candidatoId, $vagaId)) {
                throw new Exception(
                    'Já registramos uma candidatura com este e-mail para esta vaga.'
                );
            }
        }

        $pdo = $candRepo->getConnection();
        $pdo->beginTransaction();
        $storedPath = null;

        try {
            $consentId = $this->registrarConsentimento($nome, $email, $termo, $vagaId);
            if ($consentId <= 0) {
                throw new Exception('Não foi possível registrar o consentimento LGPD.');
            }

            $createdCandidato = false;
            if ($existing === null) {
                $newId = $candRepo->create([
                    'nome' => $nome,
                    'email' => $email,
                    'telefone' => $telefone,
                    'cidade' => $cidade !== '' ? $cidade : null,
                    'estado' => $estado !== '' ? mb_substr($estado, 0, 2) : null,
                    'area_interesse' => $areaInteresse,
                    'graduacao' => null,
                    'ultima_experiencia' => $mensagem !== '' ? mb_substr($mensagem, 0, 2000) : null,
                    'origem' => $canalOrigem,
                    'status_processo' => 'candidatado',
                    'observacoes' => 'Candidatura pelo portal público vagas-abertas ('
                        . RhCandidatoOrigemHelper::label($canalOrigem) . ').',
                    'lgpd_termo_id' => (int) ($termo['id'] ?? 0),
                    'lgpd_consentimento_id' => $consentId,
                    'lgpd_data_consentimento' => date('Y-m-d H:i:s'),
                ]);
                if (!$newId) {
                    throw new Exception('Não foi possível cadastrar a candidatura.');
                }
                $candidatoId = (int) $newId;
                $createdCandidato = true;
            } else {
                $candidatoId = (int) $existing['id'];
                // Atualiza evidência LGPD mais recente sem sobrescrever demais dados.
                $candRepo->touchLgpdConsent($candidatoId, (int) ($termo['id'] ?? 0), $consentId);
            }

            $obsVinculo = $mensagem !== '' ? mb_substr($mensagem, 0, 1000) : null;
            $vinculoId = $this->vincularNaTransacao(
                $pdo,
                $vagaId,
                $candidatoId,
                $obsVinculo,
                $canalOrigem
            );

            $stored = RhCandidatoAnexoService::storeCurriculo($candidatoId, $file);
            if (!($stored['ok'] ?? false)) {
                throw new Exception((string) ($stored['error'] ?? 'Erro ao salvar o currículo.'));
            }
            $storedPath = (string) ($stored['relative_path'] ?? '');
            $stmtAnexo = $pdo->prepare(
                'INSERT INTO rh_candidatos_anexos
                    (rh_candidato_id, tipo, arquivo_caminho, nome_original, created_at)
                 VALUES
                    (:candidato_id, :tipo, :caminho, :nome, NOW())'
            );
            $stmtAnexo->bindValue(':candidato_id', $candidatoId, PDO::PARAM_INT);
            $stmtAnexo->bindValue(':tipo', 'curriculo', PDO::PARAM_STR);
            $stmtAnexo->bindValue(':caminho', $storedPath, PDO::PARAM_STR);
            $stmtAnexo->bindValue(
                ':nome',
                (string) ($stored['original_name'] ?? 'curriculo'),
                PDO::PARAM_STR
            );
            if (!$stmtAnexo->execute()) {
                throw new Exception('Não foi possível registrar o currículo na candidatura.');
            }
            $anexoId = (int) $pdo->lastInsertId();
            if ($anexoId <= 0) {
                throw new Exception('Não foi possível registrar o currículo na candidatura.');
            }

            $novoStatus = $candRepo->calcularStatusGeralPorVinculos($candidatoId);
            if ($novoStatus) {
                $candRepo->atualizarStatusProcessoSimples($candidatoId, $novoStatus);
            }

            $pdo->commit();

            return [
                'candidato_id' => $candidatoId,
                'created_candidato' => $createdCandidato,
                'vinculo_id' => $vinculoId,
                'anexo_id' => (int) $anexoId,
            ];
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            if (is_string($storedPath) && $storedPath !== '') {
                RhCandidatoAnexoService::deletePhysicalFile($storedPath);
            }
            GenerateLog::generateLog('error', 'Falha na candidatura pública.', [
                'vaga_id' => $vagaId,
                'email_hash' => hash('sha256', $email),
                'error' => $e->getMessage(),
            ]);
            throw $e instanceof Exception
                ? $e
                : new Exception('Não foi possível concluir a candidatura. Tente novamente.');
        }
    }

    /**
     * @param array<string, mixed> $termo
     */
    private function registrarConsentimento(
        string $nome,
        string $email,
        array $termo,
        int $vagaId
    ): int {
        $auditHelper = new LgpdAuditHelper();
        $auditData = $auditHelper::collectTechnicalData();

        $consentData = [
            'titular_nome' => $nome,
            'titular_email' => $email,
            'finalidade' => 'Recrutamento e seleção — candidatura via portal público de vagas.',
            'canal' => 'vagas_abertas_portal',
            'data_consentimento' => date('Y-m-d H:i:s'),
            'status' => 'Ativo',
            'versao_termo' => $termo['versao'] ?? '1.0',
            'lgpd_termo_id' => (int) ($termo['id'] ?? 0),
            'created_by_user_id' => null,
            'collection_method' => 'web_form',
        ];
        $consentData = array_merge($consentData, $auditData);
        $origin = trim((string) ($consentData['origin_url'] ?? ''));
        $consentData['origin_url'] = $origin === ''
            ? ('vagas-abertas/' . $vagaId)
            : ($origin . ' (vaga_id=' . $vagaId . ')');

        $consentData['consent_hash'] = $auditHelper::generateConsentHash($consentData);

        $id = (new LgpdConsentimentosRepository())->create($consentData);

        return $id !== false ? (int) $id : 0;
    }

    private function vincularNaTransacao(
        PDO $pdo,
        int $vagaId,
        int $candidatoId,
        ?string $observacoes,
        string $canalOrigem = RhCandidatoOrigemHelper::FORM_TRABALHE_CONOSCO
    ): int {
        $stmtCheck = $pdo->prepare(
            'SELECT id FROM rh_candidatos_vagas
             WHERE rh_candidato_id = :candidato_id AND rh_vaga_id = :vaga_id
             LIMIT 1'
        );
        $stmtCheck->execute([
            ':candidato_id' => $candidatoId,
            ':vaga_id' => $vagaId,
        ]);
        if ($stmtCheck->fetch()) {
            throw new Exception('Já registramos uma candidatura com este e-mail para esta vaga.');
        }

        $stmt = $pdo->prepare(
            'INSERT INTO rh_candidatos_vagas
                (rh_candidato_id, rh_vaga_id, status, data_candidatura, observacoes, canal_origem, created_at)
             VALUES
                (:candidato_id, :vaga_id, :status, NOW(), :observacoes, :canal_origem, NOW())'
        );
        $stmt->bindValue(':candidato_id', $candidatoId, PDO::PARAM_INT);
        $stmt->bindValue(':vaga_id', $vagaId, PDO::PARAM_INT);
        $stmt->bindValue(':status', 'candidatado', PDO::PARAM_STR);
        $stmt->bindValue(
            ':observacoes',
            $observacoes,
            $observacoes !== null ? PDO::PARAM_STR : PDO::PARAM_NULL
        );
        $stmt->bindValue(':canal_origem', $canalOrigem, PDO::PARAM_STR);
        if (!$stmt->execute()) {
            throw new Exception('Erro ao vincular candidatura à vaga.');
        }

        $candidaturaId = (int) $pdo->lastInsertId();
        (new RhCandidaturaHistoricoRepository())->registrar([
            'rh_candidatura_id' => $candidaturaId,
            'rh_candidato_id' => $candidatoId,
            'rh_vaga_id' => $vagaId,
            'tipo_evento' => RhCandidaturaHistoricoRepository::TIPO_VINCULADA,
            'status_anterior' => null,
            'status_novo' => 'candidatado',
            'origem' => RhCandidaturaHistoricoRepository::ORIGEM_PORTAL,
            'observacoes' => trim(
                'Canal: ' . RhCandidatoOrigemHelper::label($canalOrigem)
                . ($observacoes !== null && $observacoes !== '' ? ' — ' . $observacoes : '')
            ),
        ]);

        return $candidaturaId;
    }
}
