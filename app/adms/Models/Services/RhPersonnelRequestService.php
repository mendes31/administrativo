<?php

declare(strict_types=1);

namespace App\adms\Models\Services;

use App\adms\Helpers\GenerateLog;
use App\adms\Models\Repository\RhPersonnelRequestsRepository;
use App\adms\Models\Repository\RhVagasRepository;
use App\adms\Models\Services\LogAlteracaoService;
use Exception;

/**
 * Casos de uso de requisição de pessoal (aprovação e conversão em vaga).
 */
final class RhPersonnelRequestService
{
    public function approve(int $id, int $actorId): void
    {
        $this->decide($id, $actorId, true, null);
    }

    public function reject(int $id, int $actorId, string $reason): void
    {
        $reason = trim($reason);
        if ($reason === '') {
            throw new Exception('Informe o motivo da rejeição.');
        }
        $this->decide($id, $actorId, false, $reason);
    }

    /**
     * Cria vaga a partir de requisição aprovada (transação única).
     *
     * @param array<string, mixed> $vagaOverrides titulo obrigatório; demais campos opcionais
     */
    public function convertToVaga(int $requestId, int $actorId, array $vagaOverrides): int
    {
        $repo = new RhPersonnelRequestsRepository();
        $vagasRepo = new RhVagasRepository();
        $pdo = $repo->getConnection();
        $owns = !$pdo->inTransaction();
        if ($owns) {
            $pdo->beginTransaction();
        }

        try {
            $req = $repo->lockById($requestId);
            if (!$req) {
                throw new Exception('Requisição não encontrada.');
            }
            if (($req['status'] ?? '') !== RhPersonnelRequestsRepository::STATUS_APPROVED) {
                throw new Exception('Somente requisições aprovadas podem gerar vaga.');
            }
            if (!empty($req['converted_vaga_id'])) {
                throw new Exception('Esta requisição já foi convertida em vaga.');
            }

            $titulo = trim((string) ($vagaOverrides['titulo'] ?? ''));
            if ($titulo === '') {
                throw new Exception('Informe o título da vaga.');
            }

            $vagaData = [
                'titulo' => $titulo,
                'descricao' => $vagaOverrides['descricao'] ?? $req['justificativa'],
                'requisitos' => $vagaOverrides['requisitos'] ?? null,
                'beneficios' => $vagaOverrides['beneficios'] ?? null,
                'area_id' => $req['area_id'] ?? null,
                'cargo_id' => $req['cargo_id'] ?? null,
                'tipo_contrato' => $req['tipo_contrato'] ?? 'CLT',
                'salario_min' => $req['salario_min'] ?? null,
                'salario_max' => $req['salario_max'] ?? null,
                'mostrar_salario' => 0,
                'status' => $vagaOverrides['status'] ?? 'pausada',
                'quantidade_vagas' => (int) ($req['quantidade'] ?? 1),
                'observacoes' => 'Gerada a partir da requisição de pessoal #' . $requestId,
                'responsavel_id' => $vagaOverrides['responsavel_id'] ?? $actorId,
                'personnel_request_id' => $requestId,
            ];

            $vagaId = $vagasRepo->create($vagaData);
            if (!$vagaId) {
                throw new Exception('Falha ao criar a vaga a partir da requisição.');
            }

            $antes = $req;
            if (!$repo->updateStatus(
                $requestId,
                RhPersonnelRequestsRepository::STATUS_CONVERTED,
                (int) ($req['approved_by'] ?? $actorId),
                null,
                (int) $vagaId
            )) {
                throw new Exception('Falha ao marcar requisição como convertida.');
            }

            if (!empty($_SESSION['user_id'])) {
                LogAlteracaoService::registrarAlteracao(
                    'rh_personnel_requests',
                    $requestId,
                    (int) $_SESSION['user_id'],
                    'UPDATE',
                    $antes,
                    array_merge($antes, [
                        'status' => RhPersonnelRequestsRepository::STATUS_CONVERTED,
                        'converted_vaga_id' => (int) $vagaId,
                    ])
                );
            }

            if ($owns) {
                $pdo->commit();
            }

            return (int) $vagaId;
        } catch (Exception $e) {
            if ($owns && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            GenerateLog::generateLog('error', 'Erro ao converter requisição em vaga.', [
                'request_id' => $requestId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    private function decide(int $id, int $actorId, bool $approve, ?string $reason): void
    {
        if ($actorId <= 0) {
            throw new Exception('Aprovador inválido.');
        }

        $repo = new RhPersonnelRequestsRepository();
        $pdo = $repo->getConnection();
        $owns = !$pdo->inTransaction();
        if ($owns) {
            $pdo->beginTransaction();
        }

        try {
            $req = $repo->lockById($id);
            if (!$req) {
                throw new Exception('Requisição não encontrada.');
            }
            if (($req['status'] ?? '') !== RhPersonnelRequestsRepository::STATUS_PENDING) {
                throw new Exception('Somente requisições pendentes podem ser decididas.');
            }
            if ((int) ($req['requester_id'] ?? 0) === $actorId) {
                throw new Exception('Não é permitido aprovar ou rejeitar a própria requisição.');
            }

            $newStatus = $approve
                ? RhPersonnelRequestsRepository::STATUS_APPROVED
                : RhPersonnelRequestsRepository::STATUS_REJECTED;

            if (!$repo->updateStatus($id, $newStatus, $actorId, $approve ? null : $reason)) {
                throw new Exception('Falha ao atualizar status da requisição.');
            }

            if (!empty($_SESSION['user_id'])) {
                LogAlteracaoService::registrarAlteracao(
                    'rh_personnel_requests',
                    $id,
                    (int) $_SESSION['user_id'],
                    'UPDATE',
                    $req,
                    array_merge($req, [
                        'status' => $newStatus,
                        'approved_by' => $actorId,
                        'rejection_reason' => $reason,
                    ])
                );
            }

            if ($owns) {
                $pdo->commit();
            }
        } catch (Exception $e) {
            if ($owns && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }
}
