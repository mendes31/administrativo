<?php

namespace App\adms\Models\Repository;

use App\adms\Models\Services\DbConnection;
use App\adms\Models\Services\LogAlteracaoService;
use PDO;

class AdmsEmailConfigRepository extends DbConnection
{
    private static ?bool $hasRhEntrevistaToggle = null;

    public function getConfig(): array
    {
        $sql = 'SELECT * FROM adms_email_config ORDER BY id DESC LIMIT 1';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute();
        $config = $stmt->fetch(PDO::FETCH_ASSOC);
        return $config ?: [];
    }

    /**
     * Interruptor específico do envio automático de comunicações de entrevista (ATS).
     * Sem a coluna (migration pendente) o envio fica desligado.
     */
    public function isRhEntrevistaSendEnabled(): bool
    {
        if (!$this->hasRhEntrevistaToggleColumn()) {
            return false;
        }

        $config = $this->getConfig();

        return (int) ($config['rh_entrevista_send_enabled'] ?? 0) === 1;
    }

    public function saveConfig(array $data): bool
    {
        // Se já existe, faz update, senão faz insert
        $oldData = $this->getConfig();
        $isUpdate = $oldData && !empty($oldData['id']);
        $hasToggle = $this->hasRhEntrevistaToggleColumn();
        $toggleUpdate = $hasToggle ? ', rh_entrevista_send_enabled = :rh_entrevista_send_enabled' : '';
        if ($isUpdate) {
            $sql = 'UPDATE adms_email_config 
                       SET host = :host, 
                           username = :username, 
                           password = :password, 
                           port = :port, 
                           encryption = :encryption, 
                           from_email = :from_email, 
                           from_name = :from_name,
                           test_recipient = :test_recipient' . $toggleUpdate . ',
                           updated_at = NOW() 
                     WHERE id = :id';
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':id', $oldData['id'], PDO::PARAM_INT);
        } else {
            $toggleColumn = $hasToggle ? ', rh_entrevista_send_enabled' : '';
            $togglePlaceholder = $hasToggle ? ', :rh_entrevista_send_enabled' : '';
            $sql = 'INSERT INTO adms_email_config 
                        (host, username, password, port, encryption, from_email, from_name, test_recipient' . $toggleColumn . ', created_at, updated_at) 
                    VALUES 
                        (:host, :username, :password, :port, :encryption, :from_email, :from_name, :test_recipient' . $togglePlaceholder . ', NOW(), NOW())';
            $stmt = $this->getConnection()->prepare($sql);
        }
        $stmt->bindValue(':host', $data['host']);
        $stmt->bindValue(':username', $data['username']);
        $stmt->bindValue(':password', $data['password']);
        $stmt->bindValue(':port', $data['port']);
        $stmt->bindValue(':encryption', $data['encryption']);
        $stmt->bindValue(':from_email', $data['from_email']);
        $stmt->bindValue(':from_name', $data['from_name']);
        $stmt->bindValue(':test_recipient', $data['test_recipient'] ?? null);
        if ($hasToggle) {
            $stmt->bindValue(
                ':rh_entrevista_send_enabled',
                (int) ($data['rh_entrevista_send_enabled'] ?? 0) === 1 ? 1 : 0,
                PDO::PARAM_INT
            );
        }
        $result = $stmt->execute();

        if ($result) {
            $usuarioId = $_SESSION['user_id'] ?? 1;
            $newData = $this->getConfig();
            if ($isUpdate && $oldData) {
                LogAlteracaoService::registrarAlteracao(
                    'adms_email_config',
                    (int) $oldData['id'],
                    $usuarioId,
                    'UPDATE',
                    $oldData,
                    $newData
                );
            } elseif (!$isUpdate && $newData) {
                LogAlteracaoService::registrarAlteracao(
                    'adms_email_config',
                    (int) ($newData['id'] ?? 0),
                    $usuarioId,
                    'INSERT',
                    [],
                    $newData
                );
            }
        }

        return $result;
    }

    private function hasRhEntrevistaToggleColumn(): bool
    {
        if (self::$hasRhEntrevistaToggle !== null) {
            return self::$hasRhEntrevistaToggle;
        }

        try {
            $stmt = $this->getConnection()->query(
                "SHOW COLUMNS FROM adms_email_config LIKE 'rh_entrevista_send_enabled'"
            );
            self::$hasRhEntrevistaToggle = $stmt !== false && $stmt->fetch(PDO::FETCH_ASSOC) !== false;
        } catch (\Throwable) {
            self::$hasRhEntrevistaToggle = false;
        }

        return self::$hasRhEntrevistaToggle;
    }
} 