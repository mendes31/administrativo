<?php

declare(strict_types=1);

namespace App\adms\Models\Services;

use App\adms\Helpers\GenerateLog;
use App\adms\Helpers\LgpdAuditHelper;
use App\adms\Models\Repository\LgpdConsentimentosRepository;
use App\adms\Models\Repository\LgpdTermosRepository;
use App\adms\Models\Repository\RhCandidatosRepository;
use App\adms\Models\Repository\RhCandidaturaHistoricoRepository;
use App\adms\Models\Repository\RhVagasRepository;
use App\adms\Models\Repository\UsersRepository;
use Exception;
use PDO;

/**
 * Candidatura autenticada a vaga interna/ambas (portal do colaborador).
 */
final class RhCandidaturaInternaService
{
    public const LGPD_TERMO_TIPO = 'curriculo_candidato';

    public const ORIGEM_CANDIDATO = 'portal_interno';

    /**
     * @param array<string, mixed> $input mensagem opcional + lgpd_consent=1
     * @return array{candidato_id: int, created_candidato: bool, vinculo_id: int}
     */
    public function candidatar(int $vagaId, int $userId, array $input): array
    {
        if ($userId <= 0) {
            throw new Exception('Usuário não autenticado.');
        }

        $vaga = (new RhVagasRepository())->getInternaById($vagaId);
        if ($vaga === null) {
            throw new Exception('Esta vaga não está disponível para candidatura interna.');
        }

        $user = (new UsersRepository())->getUser($userId);
        if (!is_array($user)) {
            throw new Exception('Não foi possível carregar seus dados de colaborador.');
        }

        $nome = trim((string) ($user['name'] ?? ''));
        $email = strtolower(trim((string) ($user['email'] ?? '')));
        $telefone = trim((string) ($user['celular'] ?? $user['user_telephone'] ?? $user['telefone'] ?? ''));
        $mensagem = trim((string) ($input['mensagem'] ?? ''));

        if ($nome === '') {
            throw new Exception('Seu cadastro está sem nome. Atualize o perfil ou contate o RH.');
        }
        if ($email === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            throw new Exception('Seu cadastro está sem e-mail válido. Contate o RH.');
        }
        if (empty($input['lgpd_consent']) || (string) $input['lgpd_consent'] !== '1') {
            throw new Exception('É obrigatório aceitar o termo de consentimento LGPD.');
        }

        $termo = (new LgpdTermosRepository())->getTermoAtivoPorTipo(self::LGPD_TERMO_TIPO);
        if ($termo === null) {
            throw new Exception(
                'Candidatura temporariamente indisponível: termo LGPD não configurado. Tente mais tarde.'
            );
        }

        $candRepo = new RhCandidatosRepository();
        $existing = $candRepo->findActiveByAdmsUserId($userId);
        if ($existing === null) {
            $existing = $candRepo->findActiveByEmail($email);
        }

        if ($existing !== null) {
            $candidatoId = (int) $existing['id'];
            if ($candRepo->hasVinculoComVaga($candidatoId, $vagaId)) {
                throw new Exception('Você já se candidatou a esta vaga.');
            }
        }

        $pdo = $candRepo->getConnection();
        $pdo->beginTransaction();

        try {
            $consentId = $this->registrarConsentimento($nome, $email, $termo, $vagaId, $userId);
            if ($consentId <= 0) {
                throw new Exception('Não foi possível registrar o consentimento LGPD.');
            }

            $createdCandidato = false;
            if ($existing === null) {
                $newId = $candRepo->create([
                    'nome' => $nome,
                    'email' => $email,
                    'telefone' => $telefone !== '' ? $telefone : null,
                    'cidade' => null,
                    'estado' => null,
                    'area_interesse' => (string) ($vaga['area_nome'] ?? 'Movimentação interna'),
                    'graduacao' => null,
                    'ultima_experiencia' => $mensagem !== '' ? mb_substr($mensagem, 0, 2000) : null,
                    'origem' => self::ORIGEM_CANDIDATO,
                    'status_processo' => 'candidatado',
                    'observacoes' => 'Candidatura interna pelo portal do colaborador (user_id=' . $userId . ').',
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
                $candRepo->touchLgpdConsent($candidatoId, (int) ($termo['id'] ?? 0), $consentId);
            }

            $candRepo->vincularUsuarioConversao($candidatoId, $userId);

            $vinculoId = $this->vincularNaTransacao(
                $pdo,
                $vagaId,
                $candidatoId,
                $mensagem !== '' ? mb_substr($mensagem, 0, 1000) : null
            );

            $novoStatus = $candRepo->calcularStatusGeralPorVinculos($candidatoId);
            if ($novoStatus) {
                $candRepo->atualizarStatusProcessoSimples($candidatoId, $novoStatus);
            }

            $pdo->commit();

            return [
                'candidato_id' => $candidatoId,
                'created_candidato' => $createdCandidato,
                'vinculo_id' => $vinculoId,
            ];
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            GenerateLog::generateLog('error', 'Falha na candidatura interna.', [
                'vaga_id' => $vagaId,
                'user_id' => $userId,
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
        int $vagaId,
        int $userId
    ): int {
        $auditHelper = new LgpdAuditHelper();
        $auditData = $auditHelper::collectTechnicalData();

        $consentData = [
            'titular_nome' => $nome,
            'titular_email' => $email,
            'finalidade' => 'Recrutamento e seleção — candidatura interna (colaborador autenticado).',
            'canal' => 'vagas_internas_portal',
            'data_consentimento' => date('Y-m-d H:i:s'),
            'status' => 'Ativo',
            'versao_termo' => $termo['versao'] ?? '1.0',
            'lgpd_termo_id' => (int) ($termo['id'] ?? 0),
            'created_by_user_id' => $userId,
            'collection_method' => 'authenticated_web_form',
        ];
        $consentData = array_merge($consentData, $auditData);
        $origin = trim((string) ($consentData['origin_url'] ?? ''));
        $consentData['origin_url'] = $origin === ''
            ? ('vagas-internas/' . $vagaId)
            : ($origin . ' (vaga_id=' . $vagaId . ')');

        $consentData['consent_hash'] = $auditHelper::generateConsentHash($consentData);

        $id = (new LgpdConsentimentosRepository())->create($consentData);

        return $id !== false ? (int) $id : 0;
    }

    private function vincularNaTransacao(
        PDO $pdo,
        int $vagaId,
        int $candidatoId,
        ?string $observacoes
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
            throw new Exception('Você já se candidatou a esta vaga.');
        }

        $stmt = $pdo->prepare(
            'INSERT INTO rh_candidatos_vagas
                (rh_candidato_id, rh_vaga_id, status, data_candidatura, observacoes, created_at)
             VALUES
                (:candidato_id, :vaga_id, :status, NOW(), :observacoes, NOW())'
        );
        $stmt->bindValue(':candidato_id', $candidatoId, PDO::PARAM_INT);
        $stmt->bindValue(':vaga_id', $vagaId, PDO::PARAM_INT);
        $stmt->bindValue(':status', 'candidatado', PDO::PARAM_STR);
        $stmt->bindValue(
            ':observacoes',
            $observacoes,
            $observacoes !== null ? PDO::PARAM_STR : PDO::PARAM_NULL
        );
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
            'observacoes' => $observacoes !== null
                ? ('[interna] ' . $observacoes)
                : '[interna]',
        ]);

        return $candidaturaId;
    }
}
