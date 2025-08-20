<?php

namespace App\adms\Models\Repository;

use App\adms\Models\Services\DbConnection;
use PDO;

class AdmsEmailConfigRepository extends DbConnection
{
    public function getConfig(): array
    {
        $sql = 'SELECT * FROM adms_email_config ORDER BY id DESC LIMIT 1';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute();
        $config = $stmt->fetch(PDO::FETCH_ASSOC);
        return $config ?: [];
    }

    public function saveConfig(array $data): bool
    {
        // Se já existe, faz update, senão faz insert
        $config = $this->getConfig();
        if ($config && !empty($config['id'])) {
            $sql = 'UPDATE adms_email_config SET host = :host, username = :username, password = :password, port = :port, encryption = :encryption, from_email = :from_email, from_name = :from_name, updated_at = NOW() WHERE id = :id';
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':id', $config['id'], PDO::PARAM_INT);
        } else {
            $sql = 'INSERT INTO adms_email_config (host, username, password, port, encryption, from_email, from_name, created_at, updated_at) VALUES (:host, :username, :password, :port, :encryption, :from_email, :from_name, NOW(), NOW())';
            $stmt = $this->getConnection()->prepare($sql);
        }
        $stmt->bindValue(':host', $data['host']);
        $stmt->bindValue(':username', $data['username']);
        $stmt->bindValue(':password', $data['password']);
        $stmt->bindValue(':port', $data['port']);
        $stmt->bindValue(':encryption', $data['encryption']);
        $stmt->bindValue(':from_email', $data['from_email']);
        $stmt->bindValue(':from_name', $data['from_name']);
        return $stmt->execute();
    }
} 