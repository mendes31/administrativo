<?php

declare(strict_types=1);

namespace App\adms\Models\Services;

use App\adms\Helpers\GenerateLog;
use App\adms\Helpers\UserFormHelper;
use App\adms\Models\Repository\RhCandidatosRepository;
use App\adms\Models\Repository\RhCandidaturaHistoricoRepository;
use App\adms\Models\Repository\RhConversoesAdmissaoRepository;
use App\adms\Models\Repository\RhOfertasRepository;
use App\adms\Models\Repository\UsersRepository;
use Exception;
use PDO;

/**
 * Conversão auditável de oferta aceita → adms_users (fachada Pessoa/Conta).
 */
final class RhConversaoAdmissaoService
{
    /**
     * @param array<string, mixed> $input
     * @return array{conversao_id: int, adms_user_id: int, modo: string}
     */
    public function converter(int $ofertaId, array $input, int $actorId): array
    {
        $ofertasRepo = new RhOfertasRepository();
        $oferta = $ofertasRepo->getById($ofertaId);
        if ($oferta === null) {
            throw new Exception('Oferta não encontrada.');
        }
        if (($oferta['status'] ?? '') !== RhOfertasRepository::STATUS_ACEITA) {
            throw new Exception('Somente ofertas aceitas podem ser convertidas.');
        }
        if (!empty($oferta['rh_conversao_id'])) {
            throw new Exception('Esta oferta já foi convertida.');
        }
        if (!RhPermissionService::canManagePipelineByVagaId((int) $oferta['rh_vaga_id'])) {
            throw new Exception('Sem permissão para converter esta oferta.');
        }

        $convRepo = new RhConversoesAdmissaoRepository();
        if ($convRepo->getByOfertaId($ofertaId) !== null) {
            throw new Exception('Já existe conversão registrada para esta oferta.');
        }

        $this->assertDocumentosObrigatoriosAprovados($ofertasRepo, $ofertaId);

        $modo = (string) ($input['modo'] ?? RhConversoesAdmissaoRepository::MODO_CRIAR);
        if (!in_array($modo, [
            RhConversoesAdmissaoRepository::MODO_CRIAR,
            RhConversoesAdmissaoRepository::MODO_VINCULAR,
        ], true)) {
            throw new Exception('Modo de conversão inválido.');
        }

        $pdo = $ofertasRepo->getConnection();
        $pdo->beginTransaction();

        try {
            $usersRepo = new UsersRepository();
            if ($modo === RhConversoesAdmissaoRepository::MODO_VINCULAR) {
                $userId = $this->resolverUsuarioExistente($usersRepo, $input);
            } else {
                $userId = $this->criarUsuario($usersRepo, $oferta, $input);
            }

            $candidatoId = (int) $oferta['rh_candidato_id'];
            $candRepo = new RhCandidatosRepository();
            if (!$candRepo->vincularUsuarioConversao($candidatoId, $userId)) {
                throw new Exception('Falha ao vincular candidato ao usuário.');
            }
            if (!$candRepo->forcarStatusProcesso($candidatoId, 'contratado')) {
                throw new Exception('Falha ao marcar candidato como contratado.');
            }

            $conversaoId = $convRepo->create([
                'rh_oferta_id' => $ofertaId,
                'rh_candidatura_id' => (int) $oferta['rh_candidatura_id'],
                'rh_candidato_id' => $candidatoId,
                'rh_vaga_id' => (int) $oferta['rh_vaga_id'],
                'adms_user_id' => $userId,
                'modo' => $modo,
                'status' => RhConversoesAdmissaoRepository::STATUS_CONCLUIDA,
                'observacoes' => trim((string) ($input['observacoes'] ?? '')) ?: null,
                'converted_by_user_id' => $actorId > 0 ? $actorId : null,
            ]);
            if (!$conversaoId) {
                throw new Exception('Falha ao registrar a conversão.');
            }

            if (!$this->marcarOfertaConvertida($pdo, $ofertaId, (int) $conversaoId)) {
                throw new Exception('Falha ao atualizar a oferta convertida.');
            }

            (new RhCandidaturaHistoricoRepository())->registrar([
                'rh_candidatura_id' => (int) $oferta['rh_candidatura_id'],
                'rh_candidato_id' => $candidatoId,
                'rh_vaga_id' => (int) $oferta['rh_vaga_id'],
                'tipo_evento' => RhCandidaturaHistoricoRepository::TIPO_OFERTA,
                'status_anterior' => 'oferta_aceita',
                'status_novo' => 'conversao_concluida',
                'origem' => RhCandidaturaHistoricoRepository::ORIGEM_OFERTA,
                'observacoes' => 'Conversão para colaborador (usuário #' . $userId . ', modo ' . $modo . ').',
            ]);

            $onboarding = (new RhOnboardingService())->criarAPartirDaConversao(
                (int) $conversaoId,
                $userId,
                $candidatoId,
                $actorId
            );

            $dataAdmissao = trim((string) ($input['data_admissao'] ?? ''));
            $experiencia = (new RhExperienciaService())->criarAPartirDaConversao(
                (int) $conversaoId,
                $userId,
                $candidatoId,
                $onboarding['plano_id'],
                $actorId,
                $dataAdmissao !== '' ? $dataAdmissao : null
            );

            $pdo->commit();

            (new RhIdentidadeSyncService())->tentarSincronizar($userId, 'conversao');
            (new RhJornadaIntegracaoService())->aposAdmissao(
                $userId,
                (int) $conversaoId,
                $actorId,
                ['data_admissao' => $dataAdmissao !== '' ? $dataAdmissao : null]
            );

            return [
                'conversao_id' => (int) $conversaoId,
                'adms_user_id' => $userId,
                'modo' => $modo,
                'onboarding_plano_id' => $onboarding['plano_id'],
                'experiencia_id' => $experiencia['experiencia_id'],
            ];
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            GenerateLog::generateLog('error', 'Falha na conversão de admissão.', [
                'oferta_id' => $ofertaId,
                'error' => $e->getMessage(),
            ]);
            throw $e instanceof Exception ? $e : new Exception('Não foi possível concluir a conversão.');
        }
    }

    private function assertDocumentosObrigatoriosAprovados(
        RhOfertasRepository $repo,
        int $ofertaId
    ): void {
        $docs = $repo->listDocumentos($ofertaId);
        foreach ($docs as $doc) {
            if (empty($doc['obrigatorio'])) {
                continue;
            }
            if (($doc['status'] ?? '') !== 'aprovado') {
                throw new Exception(
                    'Aprove todos os documentos obrigatórios da pré-admissão antes de converter.'
                );
            }
        }
    }

    /**
     * @param array<string, mixed> $input
     */
    private function resolverUsuarioExistente(UsersRepository $usersRepo, array $input): int
    {
        $userId = (int) ($input['adms_user_id'] ?? 0);
        if ($userId <= 0) {
            throw new Exception('Informe o ID do usuário existente para vincular.');
        }
        $user = $usersRepo->getUser($userId);
        if (!$user) {
            throw new Exception('Usuário informado não encontrado.');
        }

        return $userId;
    }

    /**
     * @param array<string, mixed> $oferta
     * @param array<string, mixed> $input
     */
    private function criarUsuario(UsersRepository $usersRepo, array $oferta, array $input): int
    {
        $name = trim((string) ($input['name'] ?? $oferta['candidato_nome'] ?? ''));
        $email = strtolower(trim((string) ($input['email'] ?? $oferta['candidato_email'] ?? '')));
        $username = trim((string) ($input['username'] ?? ''));
        $password = (string) ($input['password'] ?? '');
        $departmentId = (int) ($input['user_department_id'] ?? 0);
        $positionId = (int) ($input['user_position_id'] ?? 0);
        $dataAdmissao = trim((string) ($input['data_admissao'] ?? ''));

        if ($name === '') {
            throw new Exception('Informe o nome do colaborador.');
        }
        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            throw new Exception('Informe um e-mail corporativo válido.');
        }
        if ($username === '') {
            $username = $this->suggestUsername($email);
        }
        if (strlen($password) < 8) {
            throw new Exception('A senha inicial deve ter pelo menos 8 caracteres.');
        }
        if ($departmentId <= 0 || $positionId <= 0) {
            throw new Exception('Informe departamento e cargo.');
        }
        if ($dataAdmissao === '') {
            $dataAdmissao = date('Y-m-d');
        }

        $existingId = $this->findUserIdByEmail($usersRepo, $email);
        if ($existingId !== null) {
            throw new Exception(
                'Já existe usuário com este e-mail (ID ' . $existingId
                . '). Use o modo Vincular existente.'
            );
        }

        $newId = $usersRepo->createUser(UserFormHelper::applyEmpresaContratanteToForm([
            'name' => $name,
            'email' => $email,
            'username' => $username,
            'password' => $password,
            'celular' => null,
            'user_department_id' => $departmentId,
            'user_position_id' => $positionId,
            'immediate_supervisor_id' => !empty($input['immediate_supervisor_id'])
                ? (int) $input['immediate_supervisor_id']
                : null,
            'status' => 'Ativo',
            'bloqueado' => 'Não',
            'modificar_senha_proximo_logon' => 'Sim',
            'enviar_boas_vindas_email' => !empty($input['enviar_boas_vindas_email']) ? 1 : 0,
            'enviar_boas_vindas_whatsapp' => 0,
            'data_admissao' => $dataAdmissao,
            'empresa_contratante' => trim((string) ($input['empresa_contratante'] ?? '')) ?: null,
            'matricula' => trim((string) ($input['matricula'] ?? '')) ?: null,
        ], $input['empresa_contratante'] ?? null));

        if (!$newId || !is_numeric($newId)) {
            throw new Exception('Não foi possível criar o usuário do colaborador.');
        }

        return (int) $newId;
    }

    private function suggestUsername(string $email): string
    {
        $local = explode('@', $email)[0] ?? 'colaborador';
        $local = preg_replace('/[^a-zA-Z0-9._-]/', '', $local) ?: 'colaborador';

        return mb_substr($local, 0, 50);
    }

    private function findUserIdByEmail(UsersRepository $usersRepo, string $email): ?int
    {
        try {
            $stmt = $usersRepo->getConnection()->prepare(
                'SELECT id FROM adms_users WHERE LOWER(TRIM(email)) = LOWER(:e) LIMIT 1'
            );
            $stmt->bindValue(':e', $email, PDO::PARAM_STR);
            $stmt->execute();
            $id = $stmt->fetchColumn();

            return $id !== false ? (int) $id : null;
        } catch (\Throwable) {
            return null;
        }
    }

    private function marcarOfertaConvertida(PDO $pdo, int $ofertaId, int $conversaoId): bool
    {
        $stmt = $pdo->prepare(
            'UPDATE rh_ofertas SET rh_conversao_id = :cid, updated_at = NOW() WHERE id = :id'
        );
        $stmt->bindValue(':cid', $conversaoId, PDO::PARAM_INT);
        $stmt->bindValue(':id', $ofertaId, PDO::PARAM_INT);

        return $stmt->execute();
    }
}
