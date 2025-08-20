<?php

namespace App\adms\Helpers;

use App\adms\Models\Repository\LogsRepository;

class LogHelper
{
    /**
     * Registra um log padronizado no sistema
     * @param string $table Nome da tabela afetada
     * @param string $action Tipo de ação (inserção, atualização, deleção, etc)
     * @param int|string|null $recordId ID do registro afetado
     * @param string|null $description Descrição adicional
     * @param int|null $userId ID do usuário logado (opcional)
     */
    public static function log($table, $action, $recordId = null, $description = null, $userId = null)
    {
        $dataLogs = [
            'table_name' => $table,
            'action' => $action,
            'record_id' => $recordId,
            'description' => $description,
            'user_id' => $userId ?? ($_SESSION['user_id'] ?? null),
            'created_at' => date('Y-m-d H:i:s'),
        ];
        $logsRepo = new LogsRepository();
        $logsRepo->insertLogs($dataLogs);
    }

    /**
     * Registra um log detalhado de atualização, mostrando campos alterados, valor antigo e novo
     * @param string $table
     * @param int|string $recordId
     * @param array $oldData
     * @param array $newData
     * @param int|null $userId
     */
    public static function logUpdate($table, $recordId, $oldData, $newData, $userId = null)
    {
        $changes = [];
        foreach ($newData as $field => $newValue) {
            $oldValue = $oldData[$field] ?? null;
            if ($oldValue != $newValue) {
                $changes[$field] = ['old' => $oldValue, 'new' => $newValue];
            }
        }
        if ($changes) {
            $description = json_encode(['field_changes' => $changes], JSON_UNESCAPED_UNICODE);
            self::log($table, 'atualização', $recordId, $description, $userId);
        }
    }
} 