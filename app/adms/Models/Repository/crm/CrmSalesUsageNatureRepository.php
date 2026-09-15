<?php

declare(strict_types=1);

namespace App\adms\Models\Repository\crm;

use App\adms\Models\Services\DbConnection;
use PDO;

/**
 * Catálogo local das utilizações SAP (OUSG) e a natureza comercial de cada uma.
 */
class CrmSalesUsageNatureRepository extends DbConnection
{
    public const NATURES = [
        'venda' => 'Venda',
        'bonificacao' => 'Bonificação',
        'brinde' => 'Brinde',
        'ignorar' => 'Ignorar (não entra nos KPIs)',
        'nao_classificada' => 'Não classificada (conta como venda)',
    ];

    public function tableExists(): bool
    {
        static $exists = null;
        if ($exists !== null) {
            return $exists;
        }
        try {
            $stmt = $this->getConnection()->query("SHOW TABLES LIKE 'crm_sales_usage_nature'");
            $exists = (bool) $stmt->fetchColumn();
        } catch (\Throwable) {
            $exists = false;
        }
        return $exists;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listAll(): array
    {
        if (!$this->tableExists()) {
            return [];
        }
        $stmt = $this->getConnection()->query(
            'SELECT usage_id, usage_name, natureza, updated_at
             FROM crm_sales_usage_nature
             ORDER BY natureza ASC, usage_name ASC, usage_id ASC'
        );
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function countUnclassified(): int
    {
        if (!$this->tableExists()) {
            return 0;
        }
        $stmt = $this->getConnection()->query(
            "SELECT COUNT(*) FROM crm_sales_usage_nature WHERE natureza = 'nao_classificada'"
        );
        return (int) $stmt->fetchColumn();
    }

    /**
     * Insere utilizações novas como não classificadas; só atualiza o nome se já existir.
     */
    public function upsertFromSync(int $usageId, string $usageName): void
    {
        if (!$this->tableExists()) {
            return;
        }
        $now = date('Y-m-d H:i:s');
        $sql = 'INSERT INTO crm_sales_usage_nature (usage_id, usage_name, natureza, created_at, updated_at)
                VALUES (:usage_id, :usage_name, \'nao_classificada\', :created_at, :updated_at)
                ON DUPLICATE KEY UPDATE
                    usage_name = VALUES(usage_name),
                    updated_at = VALUES(updated_at)';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute([
            ':usage_id' => $usageId,
            ':usage_name' => mb_substr($usageName, 0, 150),
            ':created_at' => $now,
            ':updated_at' => $now,
        ]);
    }

    public function updateNature(int $usageId, string $natureza): bool
    {
        if (!$this->tableExists() || !isset(self::NATURES[$natureza])) {
            return false;
        }
        $stmt = $this->getConnection()->prepare(
            'UPDATE crm_sales_usage_nature
             SET natureza = :natureza, updated_at = :updated_at
             WHERE usage_id = :usage_id'
        );
        $stmt->execute([
            ':natureza' => $natureza,
            ':updated_at' => date('Y-m-d H:i:s'),
            ':usage_id' => $usageId,
        ]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Utilizações cujo nome SAP começa com "E " são entradas (não o fluxo comercial de saída).
     */
    public static function isEntradaUsage(string $usageName): bool
    {
        return (bool) preg_match('/^E(\s|$)/u', trim($usageName));
    }

    /**
     * E Dev Venda permanece como venda (é devolução comercial já em ORIN).
     * Demais entradas E* devem ser ignoradas nos KPIs.
     */
    public static function suggestEntradasNature(string $usageName): ?string
    {
        if (!self::isEntradaUsage($usageName)) {
            return null;
        }
        if (preg_match('/dev\s*venda/iu', $usageName)) {
            return 'venda';
        }
        return 'ignorar';
    }

    public function applyEntradasSuggestion(): int
    {
        $updated = 0;
        foreach ($this->listAll() as $row) {
            $suggested = self::suggestEntradasNature((string) ($row['usage_name'] ?? ''));
            if ($suggested === null || $suggested === (string) ($row['natureza'] ?? '')) {
                continue;
            }
            if ($this->updateNature((int) $row['usage_id'], $suggested)) {
                $updated++;
            }
        }
        return $updated;
    }
}
