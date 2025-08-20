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
            $sql = 'INSERT INTO adms_log_acessos (usuario_id, tipo_acesso, ip, user_agent, data_acesso, detalhes, criado_por) VALUES (:usuario_id, :tipo_acesso, :ip, :user_agent, :data_acesso, :detalhes, :criado_por)';
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':usuario_id', $data['usuario_id']);
            $stmt->bindValue(':tipo_acesso', $data['tipo_acesso']);
            $stmt->bindValue(':ip', $data['ip']);
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
        
        $sql = 'SELECT log.*, usr.name as usuario_nome, usr.email as usuario_email FROM adms_log_acessos log LEFT JOIN adms_users usr ON log.usuario_id = usr.id';
        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' ORDER BY log.data_acesso DESC LIMIT :limit OFFSET :offset';
        
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
    public function registrarAcesso(int $usuarioId, string $tipoAcesso, string $ip, ?string $userAgent = null, ?string $detalhes = null): bool
    {
        $data = [
            'usuario_id' => $usuarioId,
            'tipo_acesso' => $tipoAcesso,
            'ip' => $ip,
            'user_agent' => $userAgent,
            'data_acesso' => date('Y-m-d H:i:s'),
            'detalhes' => $detalhes,
            'criado_por' => $usuarioId
        ];
        
        return $this->insert($data) !== false;
    }
} 