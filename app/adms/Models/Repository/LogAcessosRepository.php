<?php

namespace App\adms\Models\Repository;

use App\adms\Models\Services\DbConnection;
use PDO;
use Exception;

class LogAcessosRepository extends DbConnection
{
    public function insert(array $data): int|bool
    {
        try {
            $sql = 'INSERT INTO adms_log_acessos (usuario_id, tipo_acesso, ip, hostname, user_agent, data_acesso, detalhes, criado_por) VALUES (:usuario_id, :tipo_acesso, :ip, :hostname, :user_agent, :data_acesso, :detalhes, :criado_por)';
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':usuario_id', $data['usuario_id']);
            $stmt->bindValue(':tipo_acesso', $data['tipo_acesso']);
            $stmt->bindValue(':ip', $data['ip']);
            $stmt->bindValue(':hostname', $data['hostname'] ?? null);
            $stmt->bindValue(':user_agent', $data['user_agent']);
            $stmt->bindValue(':data_acesso', $data['data_acesso']);
            $stmt->bindValue(':detalhes', $data['detalhes']);
            $stmt->bindValue(':criado_por', $data['criado_por']);
            $stmt->execute();
            return $this->getConnection()->lastInsertId();
        } catch (Exception $e) {
            return false;
        }
    }

    public function getAll($pagina = 1, $perPage = 10, $filtros = [])
    {
        $offset = ($pagina - 1) * $perPage;
        $where = [];
        $params = [];
        
        if (!empty($filtros['usuario_nome'])) {
            $where[] = 'usr.name LIKE :usuario_nome';
            $params[':usuario_nome'] = '%' . $filtros['usuario_nome'] . '%';
        }
        if (!empty($filtros['tipo_acesso'])) {
            $where[] = 'log.tipo_acesso = :tipo_acesso';
            $params[':tipo_acesso'] = $filtros['tipo_acesso'];
        }
        if (!empty($filtros['ip'])) {
            $where[] = 'log.ip LIKE :ip';
            $params[':ip'] = '%' . $filtros['ip'] . '%';
        }
        if (!empty($filtros['data_inicio'])) {
            $where[] = 'log.data_acesso >= :data_inicio';
            $params[':data_inicio'] = $filtros['data_inicio'] . ' 00:00:00';
        }
        if (!empty($filtros['data_fim'])) {
            $where[] = 'log.data_acesso <= :data_fim';
            $params[':data_fim'] = $filtros['data_fim'] . ' 23:59:59';
        }
        
        $sql = 'SELECT log.*, usr.name as usuario_nome, usr.email as usuario_email 
                FROM adms_log_acessos log 
                LEFT JOIN adms_users usr ON log.usuario_id = usr.id';
        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        // Ordenação padrão: mais recentes primeiro, usando o ID sequencial (DESC).
        // Isso garante que, sem filtros, os IDs apareçam de forma contínua na listagem.
        $sql .= ' ORDER BY log.id DESC LIMIT :limit OFFSET :offset';
        
        $stmt = $this->getConnection()->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countAll($filtros = [])
    {
        $where = [];
        $params = [];
        
        if (!empty($filtros['usuario_nome'])) {
            $where[] = 'usuario_id IN (SELECT id FROM adms_users WHERE name LIKE :usuario_nome)';
            $params[':usuario_nome'] = '%' . $filtros['usuario_nome'] . '%';
        }
        if (!empty($filtros['tipo_acesso'])) {
            $where[] = 'tipo_acesso = :tipo_acesso';
            $params[':tipo_acesso'] = $filtros['tipo_acesso'];
        }
        if (!empty($filtros['ip'])) {
            $where[] = 'ip LIKE :ip';
            $params[':ip'] = '%' . $filtros['ip'] . '%';
        }
        if (!empty($filtros['data_inicio'])) {
            $where[] = 'data_acesso >= :data_inicio';
            $params[':data_inicio'] = $filtros['data_inicio'] . ' 00:00:00';
        }
        if (!empty($filtros['data_fim'])) {
            $where[] = 'data_acesso <= :data_fim';
            $params[':data_fim'] = $filtros['data_fim'] . ' 23:59:59';
        }
        
        $sql = 'SELECT COUNT(*) as total FROM adms_log_acessos';
        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        
        $stmt = $this->getConnection()->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($result['total'] ?? 0);
    }

    public function getById(int $id): array|bool
    {
        $sql = 'SELECT log.*, usr.name as usuario_nome, usr.email as usuario_email FROM adms_log_acessos log LEFT JOIN adms_users usr ON log.usuario_id = usr.id WHERE log.id = :id';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Registra um acesso (login/logout)
     */
    public function registrarAcesso(int $usuarioId, string $tipoAcesso, string $ip, ?string $userAgent = null, ?string $detalhes = null, ?string $hostname = null): bool
    {
        $data = [
            'usuario_id' => $usuarioId,
            'tipo_acesso' => $tipoAcesso,
            'ip' => $ip,
            'hostname' => $hostname,
            'user_agent' => $userAgent,
            'data_acesso' => date('Y-m-d H:i:s'),
            'detalhes' => $detalhes,
            'criado_por' => $usuarioId
        ];
        
        return $this->insert($data) !== false;
    }

    /**
     * Lista usuários com data do último LOGIN (adms_log_acessos) ou sem registro (nunca acessou).
     *
     * @param array{usuario_nome?: string, status?: string, apenas_nunca?: string, sort?: string} $filtros
     * @return array<int, array<string, mixed>>
     */
    public function listUsersLastLogin(int $page, int $perPage, array $filtros = []): array
    {
        $offset = max(0, ($page - 1) * $perPage);
        [$whereSql, $params] = $this->buildUsersLastLoginWhere($filtros);
        $orderSql = $this->buildUsersLastLoginOrder($filtros);

        $sql = 'SELECT u.id AS user_id,
                       u.name AS user_name,
                       u.email AS user_email,
                       u.username AS user_username,
                       u.image AS user_image,
                       u.status AS user_status,
                       last_log.data_acesso AS ultimo_login,
                       last_log.ip AS ultimo_ip,
                       last_log.hostname AS ultimo_hostname
                FROM adms_users u
                LEFT JOIN (
                    SELECT l.usuario_id, l.data_acesso, l.ip, l.hostname
                    FROM adms_log_acessos l
                    INNER JOIN (
                        SELECT usuario_id, MAX(id) AS max_id
                        FROM adms_log_acessos
                        WHERE tipo_acesso = \'LOGIN\'
                        GROUP BY usuario_id
                    ) lm ON l.id = lm.max_id
                ) last_log ON last_log.usuario_id = u.id
                ' . $whereSql . '
                ORDER BY ' . $orderSql . '
                LIMIT :limit OFFSET :offset';

        $stmt = $this->getConnection()->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Exportação: todos os usuários que atendem aos filtros (sem paginação).
     *
     * @param array{usuario_nome?: string, status?: string, apenas_nunca?: string, sort?: string} $filtros
     * @return array<int, array<string, mixed>>
     */
    public function listAllUsersLastLogin(array $filtros = []): array
    {
        [$whereSql, $params] = $this->buildUsersLastLoginWhere($filtros);
        $orderSql = $this->buildUsersLastLoginOrder($filtros);

        $sql = 'SELECT u.id AS user_id,
                       u.name AS user_name,
                       u.email AS user_email,
                       u.username AS user_username,
                       u.status AS user_status,
                       last_log.data_acesso AS ultimo_login,
                       last_log.ip AS ultimo_ip,
                       last_log.hostname AS ultimo_hostname
                FROM adms_users u
                LEFT JOIN (
                    SELECT l.usuario_id, l.data_acesso, l.ip, l.hostname
                    FROM adms_log_acessos l
                    INNER JOIN (
                        SELECT usuario_id, MAX(id) AS max_id
                        FROM adms_log_acessos
                        WHERE tipo_acesso = \'LOGIN\'
                        GROUP BY usuario_id
                    ) lm ON l.id = lm.max_id
                ) last_log ON last_log.usuario_id = u.id
                ' . $whereSql . '
                ORDER BY ' . $orderSql;

        $stmt = $this->getConnection()->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * @param array{usuario_nome?: string, status?: string, apenas_nunca?: string} $filtros
     */
    public function countUsersLastLogin(array $filtros = []): int
    {
        [$whereSql, $params] = $this->buildUsersLastLoginWhere($filtros);

        $sql = 'SELECT COUNT(*) AS total
                FROM adms_users u
                LEFT JOIN (
                    SELECT l.usuario_id, l.data_acesso
                    FROM adms_log_acessos l
                    INNER JOIN (
                        SELECT usuario_id, MAX(id) AS max_id
                        FROM adms_log_acessos
                        WHERE tipo_acesso = \'LOGIN\'
                        GROUP BY usuario_id
                    ) lm ON l.id = lm.max_id
                ) last_log ON last_log.usuario_id = u.id
                ' . $whereSql;

        $stmt = $this->getConnection()->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return (int) ($row['total'] ?? 0);
    }

    /**
     * @param array{usuario_nome?: string, status?: string, apenas_nunca?: string} $filtros
     * @return array{0: string, 1: array<string, string>}
     */
    private function buildUsersLastLoginWhere(array $filtros): array
    {
        $where = [];
        $params = [];

        $nome = trim((string) ($filtros['usuario_nome'] ?? ''));
        if ($nome !== '') {
            $where[] = '(u.name LIKE :usuario_nome OR u.email LIKE :usuario_nome OR u.username LIKE :usuario_nome)';
            $params[':usuario_nome'] = '%' . $nome . '%';
        }

        $status = trim((string) ($filtros['status'] ?? ''));
        if ($status !== '' && in_array($status, ['Ativo', 'Inativo'], true)) {
            $where[] = 'u.status = :status';
            $params[':status'] = $status;
        }

        if (($filtros['apenas_nunca'] ?? '') === '1') {
            $where[] = 'last_log.data_acesso IS NULL';
        }

        $whereSql = $where !== [] ? 'WHERE ' . implode(' AND ', $where) : '';

        return [$whereSql, $params];
    }

    /**
     * @param array{sort?: string} $filtros
     */
    private function buildUsersLastLoginOrder(array $filtros): string
    {
        if (($filtros['sort'] ?? '') === 'ultimo_login') {
            return 'last_log.data_acesso DESC, u.name ASC';
        }

        return 'u.name ASC';
    }
} 