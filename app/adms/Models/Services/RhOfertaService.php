<?php

declare(strict_types=1);

namespace App\adms\Models\Services;

use App\adms\Helpers\GenerateLog;
use App\adms\Models\Repository\RhCandidaturaHistoricoRepository;
use App\adms\Models\Repository\RhOfertasRepository;
use App\adms\Models\Repository\RhVagasRepository;
use Exception;
use PDO;

/**
 * Oferta e pré-admissão (Expand Fase 3) — sem conversão Pessoa/Vínculo.
 */
final class RhOfertaService
{
    /**
     * @param array<string, mixed> $input
     * @return array{oferta_id: int}
     */
    public function criar(int $candidaturaId, array $input, int $userId): array
    {
        $vinculo = $this->getVinculo($candidaturaId);
        if ($vinculo === null) {
            throw new Exception('Candidatura não encontrada.');
        }

        $statusVinculo = (string) ($vinculo['status'] ?? '');
        if ($statusVinculo !== 'aprovado') {
            throw new Exception('Só é possível criar oferta para candidatura com status Aprovado.');
        }

        $vagaId = (int) $vinculo['rh_vaga_id'];
        if (!RhPermissionService::canManagePipelineByVagaId($vagaId)) {
            throw new Exception('Sem permissão para criar oferta nesta vaga.');
        }

        $repo = new RhOfertasRepository();
        if ($repo->findAtivaByCandidatura($candidaturaId) !== null) {
            throw new Exception('Já existe uma oferta ativa para esta candidatura.');
        }

        $salario = $this->parseSalario($input['salario_oferecido'] ?? null);

        $id = $repo->create([
            'rh_candidatura_id' => $candidaturaId,
            'rh_candidato_id' => (int) $vinculo['rh_candidato_id'],
            'rh_vaga_id' => $vagaId,
            'status' => RhOfertasRepository::STATUS_RASCUNHO,
            'salario_oferecido' => $salario,
            'tipo_contrato' => trim((string) ($input['tipo_contrato'] ?? '')) ?: null,
            'data_inicio_prevista' => trim((string) ($input['data_inicio_prevista'] ?? '')) ?: null,
            'validade_ate' => trim((string) ($input['validade_ate'] ?? '')) ?: null,
            'observacoes' => trim((string) ($input['observacoes'] ?? '')) ?: null,
            'created_by_user_id' => $userId > 0 ? $userId : null,
        ]);

        if (!$id) {
            throw new Exception('Não foi possível criar a oferta.');
        }

        $this->historico(
            (int) $vinculo['id'],
            (int) $vinculo['rh_candidato_id'],
            $vagaId,
            'oferta_rascunho',
            'Oferta criada em rascunho.'
        );

        return ['oferta_id' => (int) $id];
    }

    public function enviar(int $ofertaId): void
    {
        $oferta = $this->requireOfertaGerenciavel($ofertaId);
        if (($oferta['status'] ?? '') !== RhOfertasRepository::STATUS_RASCUNHO) {
            throw new Exception('Somente ofertas em rascunho podem ser marcadas como enviadas.');
        }

        $repo = new RhOfertasRepository();
        if (!$repo->updateStatus($ofertaId, RhOfertasRepository::STATUS_ENVIADA, null, true, false)) {
            throw new Exception('Falha ao marcar oferta como enviada.');
        }

        $this->historico(
            (int) $oferta['rh_candidatura_id'],
            (int) $oferta['rh_candidato_id'],
            (int) $oferta['rh_vaga_id'],
            'oferta_enviada',
            'Oferta marcada como enviada ao candidato.'
        );
    }

    public function aceitar(int $ofertaId, ?string $observacoes): void
    {
        $oferta = $this->requireOfertaGerenciavel($ofertaId);
        $status = (string) ($oferta['status'] ?? '');
        if (!in_array($status, [RhOfertasRepository::STATUS_RASCUNHO, RhOfertasRepository::STATUS_ENVIADA], true)) {
            throw new Exception('Esta oferta não pode ser aceita no status atual.');
        }

        $pdo = (new RhOfertasRepository())->getConnection();
        $pdo->beginTransaction();

        try {
            $repo = new RhOfertasRepository();
            if (!$repo->updateStatus(
                $ofertaId,
                RhOfertasRepository::STATUS_ACEITA,
                $observacoes,
                false,
                true
            )) {
                throw new Exception('Falha ao registrar aceite.');
            }

            $docs = $repo->listDocumentos($ofertaId);
            if ($docs === []) {
                if (!$repo->seedDocumentos($ofertaId, RhPreAdmissaoDocumentoCatalog::defaults())) {
                    throw new Exception('Falha ao gerar checklist de pré-admissão.');
                }
            }

            $this->historico(
                (int) $oferta['rh_candidatura_id'],
                (int) $oferta['rh_candidato_id'],
                (int) $oferta['rh_vaga_id'],
                'oferta_aceita',
                'Oferta aceita — pré-admissão iniciada (sem contratação automática).'
            );

            $pdo->commit();
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            GenerateLog::generateLog('error', 'Falha ao aceitar oferta.', [
                'oferta_id' => $ofertaId,
                'error' => $e->getMessage(),
            ]);
            throw $e instanceof Exception ? $e : new Exception('Não foi possível registrar o aceite.');
        }
    }

    public function recusar(int $ofertaId, ?string $observacoes): void
    {
        $oferta = $this->requireOfertaGerenciavel($ofertaId);
        $status = (string) ($oferta['status'] ?? '');
        if (!in_array($status, [RhOfertasRepository::STATUS_RASCUNHO, RhOfertasRepository::STATUS_ENVIADA], true)) {
            throw new Exception('Esta oferta não pode ser recusada no status atual.');
        }

        $repo = new RhOfertasRepository();
        if (!$repo->updateStatus(
            $ofertaId,
            RhOfertasRepository::STATUS_RECUSADA,
            $observacoes,
            false,
            true
        )) {
            throw new Exception('Falha ao registrar recusa.');
        }

        $this->historico(
            (int) $oferta['rh_candidatura_id'],
            (int) $oferta['rh_candidato_id'],
            (int) $oferta['rh_vaga_id'],
            'oferta_recusada',
            'Oferta recusada.'
        );
    }

    public function cancelar(int $ofertaId, ?string $observacoes): void
    {
        $oferta = $this->requireOfertaGerenciavel($ofertaId);
        $status = (string) ($oferta['status'] ?? '');
        if (in_array($status, [RhOfertasRepository::STATUS_ACEITA, RhOfertasRepository::STATUS_RECUSADA, RhOfertasRepository::STATUS_CANCELADA], true)) {
            throw new Exception('Oferta já finalizada não pode ser cancelada.');
        }

        $repo = new RhOfertasRepository();
        if (!$repo->updateStatus(
            $ofertaId,
            RhOfertasRepository::STATUS_CANCELADA,
            $observacoes,
            false,
            true
        )) {
            throw new Exception('Falha ao cancelar oferta.');
        }

        $this->historico(
            (int) $oferta['rh_candidatura_id'],
            (int) $oferta['rh_candidato_id'],
            (int) $oferta['rh_vaga_id'],
            'oferta_cancelada',
            'Oferta cancelada pelo RH.'
        );
    }

    public function atualizarDocumento(
        int $ofertaId,
        int $documentoId,
        string $status,
        ?string $observacoes,
        int $userId
    ): void {
        $oferta = $this->requireOfertaGerenciavel($ofertaId);
        if (($oferta['status'] ?? '') !== RhOfertasRepository::STATUS_ACEITA) {
            throw new Exception('Documentos só podem ser atualizados após o aceite da oferta.');
        }

        $repo = new RhOfertasRepository();
        if (!$repo->updateDocumentoStatus($documentoId, $ofertaId, $status, $observacoes, $userId > 0 ? $userId : null)) {
            throw new Exception('Não foi possível atualizar o documento.');
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function requireOfertaGerenciavel(int $ofertaId): array
    {
        $oferta = (new RhOfertasRepository())->getById($ofertaId);
        if ($oferta === null) {
            throw new Exception('Oferta não encontrada.');
        }
        if (!RhPermissionService::canManagePipelineByVagaId((int) $oferta['rh_vaga_id'])) {
            throw new Exception('Sem permissão para gerenciar esta oferta.');
        }

        return $oferta;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function getVinculo(int $candidaturaId): ?array
    {
        try {
            $stmt = (new RhVagasRepository())->getConnection()->prepare(
                'SELECT id, rh_candidato_id, rh_vaga_id, status
                 FROM rh_candidatos_vagas
                 WHERE id = :id
                 LIMIT 1'
            );
            $stmt->bindValue(':id', $candidaturaId, PDO::PARAM_INT);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            return is_array($row) ? $row : null;
        } catch (\Throwable $e) {
            GenerateLog::generateLog('error', 'Erro ao buscar vínculo para oferta.', [
                'candidatura_id' => $candidaturaId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    private function historico(
        int $candidaturaId,
        int $candidatoId,
        int $vagaId,
        string $statusNovo,
        string $observacoes
    ): void {
        (new RhCandidaturaHistoricoRepository())->registrar([
            'rh_candidatura_id' => $candidaturaId,
            'rh_candidato_id' => $candidatoId,
            'rh_vaga_id' => $vagaId,
            'tipo_evento' => RhCandidaturaHistoricoRepository::TIPO_OFERTA,
            'status_anterior' => null,
            'status_novo' => $statusNovo,
            'origem' => RhCandidaturaHistoricoRepository::ORIGEM_OFERTA,
            'observacoes' => $observacoes,
        ]);
    }

    private function parseSalario(mixed $raw): ?string
    {
        if ($raw === null || $raw === '') {
            return null;
        }
        if (is_numeric($raw)) {
            return number_format((float) $raw, 2, '.', '');
        }
        $s = trim((string) $raw);
        $s = str_replace(['R$', ' '], '', $s);
        if (str_contains($s, ',') && str_contains($s, '.')) {
            $s = str_replace('.', '', $s);
            $s = str_replace(',', '.', $s);
        } elseif (str_contains($s, ',')) {
            $s = str_replace(',', '.', $s);
        }
        if (!is_numeric($s)) {
            throw new Exception('Salário oferecido inválido.');
        }

        return number_format((float) $s, 2, '.', '');
    }
}
