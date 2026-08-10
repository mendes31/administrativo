<?php

declare(strict_types=1);

namespace App\adms\Models\Repository;

use App\adms\Helpers\GenerateLog;
use App\adms\Models\Services\DbConnection;
use App\adms\Models\Services\LogAlteracaoService;
use Exception;
use PDO;
use PDOException;

class TiAcessoRepository extends DbConnection
{
    public const STATUS_ATIVO = 'ativo';
    public const STATUS_REVOGADO = 'revogado';

    /**
     * @return list<array<string, mixed>>
     */
    public function listByUser(int $userId, ?string $status = null): array
    {
        if ($userId <= 0) {
            return [];
        }
        $sql = 'SELECT a.*,
                       s.nome AS sistema_nome,
                       s.tipo AS sistema_tipo,
                       s.localizacao AS sistema_localizacao,
                       s.equipamento_tag AS sistema_equipamento_tag,
                       s.status AS sistema_status,
                       COALESCE(NULLIF(b.nome_fantasia, \'\'), b.name) AS sistema_filial_nome
                FROM ti_acessos a
                INNER JOIN ti_sistemas s ON s.id = a.ti_sistema_id
                LEFT JOIN adms_branches b ON b.id = s.adms_branch_id
                WHERE a.adms_user_id = :uid';
        if ($status !== null) {
            $sql .= ' AND a.status = :status';
        }
        $sql .= ' ORDER BY a.status ASC, s.nome ASC, s.equipamento_tag ASC';

        try {
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
            if ($status !== null) {
                $stmt->bindValue(':status', $status, PDO::PARAM_STR);
            }
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            GenerateLog::generateLog('error', 'TiAcessoRepository::listByUser', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listBySistema(int $sistemaId, ?string $status = null): array
    {
        if ($sistemaId <= 0) {
            return [];
        }
        $sql = 'SELECT a.*,
                       u.name AS usuario_nome,
                       u.email AS usuario_email,
                       u.status AS usuario_status
                FROM ti_acessos a
                INNER JOIN adms_users u ON u.id = a.adms_user_id
                WHERE a.ti_sistema_id = :sid';
        if ($status !== null) {
            $sql .= ' AND a.status = :status';
        }
        $sql .= ' ORDER BY a.status ASC, u.name ASC';

        try {
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':sid', $sistemaId, PDO::PARAM_INT);
            if ($status !== null) {
                $stmt->bindValue(':status', $status, PDO::PARAM_STR);
            }
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            GenerateLog::generateLog('error', 'TiAcessoRepository::listBySistema', ['error' => $e->getMessage()]);
            return [];
        }
    }

    public function countAtivosByUser(int $userId): int
    {
        if ($userId <= 0) {
            return 0;
        }
        try {
            $stmt = $this->getConnection()->prepare(
                "SELECT COUNT(*) FROM ti_acessos WHERE adms_user_id = :uid AND status = 'ativo'"
            );
            $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
            $stmt->execute();
            return (int) $stmt->fetchColumn();
        } catch (PDOException $e) {
            GenerateLog::generateLog('error', 'TiAcessoRepository::countAtivosByUser', ['error' => $e->getMessage()]);
            return 0;
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getById(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }
        try {
            $stmt = $this->getConnection()->prepare(
                'SELECT a.*,
                        s.nome AS sistema_nome,
                        s.equipamento_tag AS sistema_equipamento_tag,
                        u.name AS usuario_nome,
                        u.username AS usuario_username
                 FROM ti_acessos a
                 LEFT JOIN ti_sistemas s ON s.id = a.ti_sistema_id
                 LEFT JOIN adms_users u ON u.id = a.adms_user_id
                 WHERE a.id = :id LIMIT 1'
            );
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ?: null;
        } catch (PDOException $e) {
            GenerateLog::generateLog('error', 'TiAcessoRepository::getById', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findAtivo(int $userId, int $sistemaId): ?array
    {
        try {
            $stmt = $this->getConnection()->prepare(
                "SELECT * FROM ti_acessos
                 WHERE adms_user_id = :uid AND ti_sistema_id = :sid AND status = 'ativo'
                 LIMIT 1"
            );
            $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
            $stmt->bindValue(':sid', $sistemaId, PDO::PARAM_INT);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ?: null;
        } catch (PDOException $e) {
            GenerateLog::generateLog('error', 'TiAcessoRepository::findAtivo', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data, int $actorId): int|false
    {
        try {
            $sql = 'INSERT INTO ti_acessos
                        (adms_user_id, ti_sistema_id, login_externo, perfil_obs, status,
                         data_liberacao, liberado_por, observacoes, created_at)
                    VALUES
                        (:uid, :sid, :login, :perfil, :status, :data_lib, :liberado_por, :obs, NOW())';
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':uid', (int) $data['adms_user_id'], PDO::PARAM_INT);
            $stmt->bindValue(':sid', (int) $data['ti_sistema_id'], PDO::PARAM_INT);
            $login = trim((string) ($data['login_externo'] ?? ''));
            $stmt->bindValue(':login', $login !== '' ? $login : null, $login !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $perfil = trim((string) ($data['perfil_obs'] ?? ''));
            $stmt->bindValue(':perfil', $perfil !== '' ? $perfil : null, $perfil !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindValue(':status', self::STATUS_ATIVO, PDO::PARAM_STR);
            $dataLib = trim((string) ($data['data_liberacao'] ?? date('Y-m-d')));
            $stmt->bindValue(':data_lib', $dataLib !== '' ? $dataLib : date('Y-m-d'), PDO::PARAM_STR);
            $stmt->bindValue(':liberado_por', $actorId > 0 ? $actorId : null, $actorId > 0 ? PDO::PARAM_INT : PDO::PARAM_NULL);
            $obs = trim((string) ($data['observacoes'] ?? ''));
            $stmt->bindValue(':obs', $obs !== '' ? $obs : null, $obs !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->execute();
            $id = (int) $this->getConnection()->lastInsertId();
            if ($id > 0) {
                LogAlteracaoService::registrarAlteracao(
                    'ti_acessos',
                    $id,
                    $actorId > 0 ? $actorId : 1,
                    'INSERT',
                    [],
                    $data
                );
            }
            return $id > 0 ? $id : false;
        } catch (Exception $e) {
            GenerateLog::generateLog('error', 'TiAcessoRepository::create', ['error' => $e->getMessage()]);
            return false;
        }
    }

    public function revogar(int $id, int $actorId, ?string $dataRevogacao, ?string $observacoes): bool
    {
        $antes = $this->getById($id);
        if ($antes === null || ($antes['status'] ?? '') !== self::STATUS_ATIVO) {
            return false;
        }
        try {
            $data = trim((string) ($dataRevogacao ?: date('Y-m-d')));
            $sql = "UPDATE ti_acessos SET
                        status = 'revogado',
                        data_revogacao = :data_rev,
                        revogado_por = :revogado_por,
                        observacoes = CASE
                            WHEN :obs IS NULL OR :obs = '' THEN observacoes
                            WHEN observacoes IS NULL OR observacoes = '' THEN :obs
                            ELSE CONCAT(observacoes, '\n', :obs)
                        END
                    WHERE id = :id AND status = 'ativo'";
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':data_rev', $data, PDO::PARAM_STR);
            $stmt->bindValue(':revogado_por', $actorId > 0 ? $actorId : null, $actorId > 0 ? PDO::PARAM_INT : PDO::PARAM_NULL);
            $obs = trim((string) ($observacoes ?? ''));
            $stmt->bindValue(':obs', $obs !== '' ? $obs : null, $obs !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $ok = $stmt->execute() && $stmt->rowCount() > 0;
            if ($ok) {
                $depois = $this->getById($id) ?? [];
                LogAlteracaoService::registrarAlteracao(
                    'ti_acessos',
                    $id,
                    $actorId > 0 ? $actorId : 1,
                    'UPDATE',
                    $antes,
                    $depois
                );
            }
            return $ok;
        } catch (Exception $e) {
            GenerateLog::generateLog('error', 'TiAcessoRepository::revogar', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Atualiza login/perfil/observações e data de liberação de um acesso ativo.
     *
     * @param array<string, mixed> $data
     */
    public function updateDetalhes(int $id, array $data, int $actorId): bool
    {
        $antes = $this->getById($id);
        if ($antes === null || ($antes['status'] ?? '') !== self::STATUS_ATIVO) {
            return false;
        }

        try {
            $login = trim((string) ($data['login_externo'] ?? ''));
            $perfil = trim((string) ($data['perfil_obs'] ?? ''));
            $obs = trim((string) ($data['observacoes'] ?? ''));
            $dataLib = trim((string) ($data['data_liberacao'] ?? ''));
            if ($dataLib === '') {
                $dataLib = (string) ($antes['data_liberacao'] ?? date('Y-m-d'));
            }

            $sql = 'UPDATE ti_acessos SET
                        login_externo = :login,
                        perfil_obs = :perfil,
                        data_liberacao = :data_lib,
                        observacoes = :obs
                    WHERE id = :id AND status = \'ativo\'';
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':login', $login !== '' ? $login : null, $login !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindValue(':perfil', $perfil !== '' ? $perfil : null, $perfil !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindValue(':data_lib', $dataLib, PDO::PARAM_STR);
            $stmt->bindValue(':obs', $obs !== '' ? $obs : null, $obs !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $ok = $stmt->execute();
            if ($ok) {
                $depois = $this->getById($id) ?? [];
                LogAlteracaoService::registrarAlteracao(
                    'ti_acessos',
                    $id,
                    $actorId > 0 ? $actorId : 1,
                    'UPDATE',
                    $antes,
                    $depois
                );
            }

            return $ok;
        } catch (Exception $e) {
            GenerateLog::generateLog('error', 'TiAcessoRepository::updateDetalhes', ['error' => $e->getMessage()]);
            return false;
        }
    }
}
