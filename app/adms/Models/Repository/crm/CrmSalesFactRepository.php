<?php

declare(strict_types=1);

namespace App\adms\Models\Repository\crm;

use App\adms\Models\Services\DbConnection;
use DateTimeImmutable;
use PDO;

/**
 * Cache MySQL do Dashboard de Vendas CRM (fatos diários + estado de sync).
 */
class CrmSalesFactRepository extends DbConnection
{
    public function tableExists(): bool
    {
        static $exists = null;
        if ($exists !== null) {
            return $exists;
        }
        try {
            $stmt = $this->getConnection()->query("SHOW TABLES LIKE 'crm_sales_fact_daily'");
            $exists = (bool) $stmt->fetchColumn();
        } catch (\Throwable) {
            $exists = false;
        }
        return $exists;
    }

    public function countRows(): int
    {
        if (!$this->tableExists()) {
            return 0;
        }
        $stmt = $this->getConnection()->query('SELECT COUNT(*) FROM crm_sales_fact_daily');
        return (int) $stmt->fetchColumn();
    }

    public function minMaxDates(): array
    {
        if (!$this->tableExists()) {
            return ['min' => null, 'max' => null];
        }
        $stmt = $this->getConnection()->query(
            'SELECT MIN(doc_date) AS min_d, MAX(doc_date) AS max_d FROM crm_sales_fact_daily'
        );
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        return [
            'min' => $row['min_d'] ?? null,
            'max' => $row['max_d'] ?? null,
        ];
    }

    /**
     * Remove fatos no intervalo (reescrita limpa antes do upsert do período).
     */
    public function deleteByDateRange(DateTimeImmutable $from, DateTimeImmutable $to): int
    {
        if (!$this->tableExists()) {
            return 0;
        }
        $stmt = $this->getConnection()->prepare(
            'DELETE FROM crm_sales_fact_daily WHERE doc_date >= :from AND doc_date <= :to'
        );
        $stmt->execute([
            ':from' => $from->format('Y-m-d'),
            ':to' => $to->format('Y-m-d'),
        ]);
        return $stmt->rowCount();
    }

    /**
     * @param list<array{
     *   grain_hash: string,
     *   doc_date: string,
     *   ano_mes: string,
     *   tipo_documento: string,
     *   card_code: string,
     *   cliente: string,
     *   vendedor: string,
     *   grupo_cliente: string,
     *   regiao: string,
     *   grupo_item: string,
     *   valor_liquido: float,
     *   qtd_linhas: int
     * }> $rows
     */
    public function upsertBatch(array $rows): int
    {
        if ($rows === [] || !$this->tableExists()) {
            return 0;
        }

        $now = date('Y-m-d H:i:s');
        $sql = 'INSERT INTO crm_sales_fact_daily (
                    grain_hash, doc_date, ano_mes, tipo_documento, card_code, cliente,
                    vendedor, grupo_cliente, regiao, grupo_item,
                    valor_liquido, qtd_linhas, synced_at, created_at, updated_at
                ) VALUES (
                    :grain_hash, :doc_date, :ano_mes, :tipo_documento, :card_code, :cliente,
                    :vendedor, :grupo_cliente, :regiao, :grupo_item,
                    :valor_liquido, :qtd_linhas, :synced_at, :created_at, :updated_at
                )
                ON DUPLICATE KEY UPDATE
                    cliente = VALUES(cliente),
                    valor_liquido = VALUES(valor_liquido),
                    qtd_linhas = VALUES(qtd_linhas),
                    synced_at = VALUES(synced_at),
                    updated_at = VALUES(updated_at)';

        $pdo = $this->getConnection();
        $stmt = $pdo->prepare($sql);
        $count = 0;

        $pdo->beginTransaction();
        try {
            foreach ($rows as $row) {
                $stmt->execute([
                    ':grain_hash' => $row['grain_hash'],
                    ':doc_date' => $row['doc_date'],
                    ':ano_mes' => $row['ano_mes'],
                    ':tipo_documento' => $row['tipo_documento'],
                    ':card_code' => $row['card_code'],
                    ':cliente' => $row['cliente'],
                    ':vendedor' => $row['vendedor'],
                    ':grupo_cliente' => $row['grupo_cliente'],
                    ':regiao' => $row['regiao'],
                    ':grupo_item' => $row['grupo_item'],
                    ':valor_liquido' => $row['valor_liquido'],
                    ':qtd_linhas' => $row['qtd_linhas'],
                    ':synced_at' => $now,
                    ':created_at' => $now,
                    ':updated_at' => $now,
                ]);
                $count++;
            }
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }

        return $count;
    }

    /**
     * @param array<string, string|null> $dims
     * @return array<string, mixed>
     */
    public function fetchKpis(string $from, string $to, array $dims, ?string $ignoreDim = null): array
    {
        [$where, $params] = $this->buildWhere($from, $to, $dims, $ignoreDim);
        $sql = "SELECT
                    SUM(CASE WHEN tipo_documento = 'Fatura' THEN valor_liquido ELSE 0 END) AS bruto,
                    SUM(CASE WHEN tipo_documento = 'Devolucao' THEN ABS(valor_liquido) ELSE 0 END) AS devolucoes,
                    SUM(valor_liquido) AS liquido,
                    COUNT(DISTINCT CASE WHEN tipo_documento = 'Fatura' THEN card_code END) AS clientes_ativos,
                    SUM(CASE WHEN tipo_documento = 'Devolucao' THEN qtd_linhas ELSE 0 END) AS qtd_devolucoes
                FROM crm_sales_fact_daily
                WHERE {$where}";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * @param array<string, string|null> $dims
     * @return list<array{label: string, valor: float}>
     */
    public function fetchGroupSum(
        string $from,
        string $to,
        array $dims,
        string $column,
        ?string $ignoreDim,
        bool $orderAsc = false
    ): array {
        $allowed = [
            'ano_mes' => 'ano_mes',
            'vendedor' => 'vendedor',
            'grupo_cliente' => 'grupo_cliente',
            'regiao' => 'regiao',
            'grupo_item' => 'grupo_item',
        ];
        if (!isset($allowed[$column])) {
            return [];
        }
        $col = $allowed[$column];
        [$where, $params] = $this->buildWhere($from, $to, $dims, $ignoreDim);
        $order = $orderAsc ? "{$col} ASC" : 'valor DESC';
        $sql = "SELECT {$col} AS label, SUM(valor_liquido) AS valor
                FROM crm_sales_fact_daily
                WHERE {$where}
                  AND {$col} IS NOT NULL
                  AND {$col} <> ''
                GROUP BY {$col}
                ORDER BY {$order}";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute($params);
        $out = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $label = trim((string) ($row['label'] ?? ''));
            if ($label === '') {
                continue;
            }
            $out[] = [
                'label' => $label,
                'valor' => (float) ($row['valor'] ?? 0),
            ];
        }
        return $out;
    }

    /**
     * @param array<string, string|null> $dims
     * @return list<array{cliente: string, grupo: string, liquido: float, devolucao: float}>
     */
    public function fetchTopClientes(string $from, string $to, array $dims, int $limit = 10): array
    {
        [$where, $params] = $this->buildWhere($from, $to, $dims, null);
        $limit = max(1, min(50, $limit));
        $sql = "SELECT
                    cliente,
                    MAX(grupo_cliente) AS grupo,
                    SUM(valor_liquido) AS liquido,
                    SUM(CASE WHEN tipo_documento = 'Devolucao' THEN ABS(valor_liquido) ELSE 0 END) AS devolucao
                FROM crm_sales_fact_daily
                WHERE {$where}
                GROUP BY cliente
                ORDER BY liquido DESC
                LIMIT {$limit}";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute($params);
        $out = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $out[] = [
                'cliente' => (string) ($row['cliente'] ?? ''),
                'grupo' => (string) ($row['grupo'] ?? ''),
                'liquido' => (float) ($row['liquido'] ?? 0),
                'devolucao' => (float) ($row['devolucao'] ?? 0),
            ];
        }
        return $out;
    }

    /**
     * @return array{vendedores: list<string>, grupos_cliente: list<string>, regioes: list<string>}
     */
    public function fetchFilterOptions(string $from, string $to): array
    {
        $pick = function (string $column) use ($from, $to): array {
            $sql = "SELECT DISTINCT {$column} AS label
                    FROM crm_sales_fact_daily
                    WHERE doc_date >= :from AND doc_date <= :to
                      AND {$column} IS NOT NULL AND {$column} <> ''
                    ORDER BY {$column}";
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->execute([':from' => $from, ':to' => $to]);
            $vals = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $v = trim((string) ($row['label'] ?? ''));
                if ($v !== '') {
                    $vals[] = $v;
                }
            }
            return $vals;
        };

        return [
            'vendedores' => $pick('vendedor'),
            'grupos_cliente' => $pick('grupo_cliente'),
            'regioes' => $pick('regiao'),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getSyncState(): ?array
    {
        try {
            $stmt = $this->getConnection()->query(
                'SELECT * FROM crm_sales_sap_sync_state WHERE id = 1 LIMIT 1'
            );
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ?: null;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    public function saveSyncState(array $data): void
    {
        $sql = 'INSERT INTO crm_sales_sap_sync_state (
                    id, last_mode, last_status, last_source, last_from_date, last_to_date,
                    last_success_at, rows_upserted, message, updated_at
                ) VALUES (
                    1, :last_mode, :last_status, :last_source, :last_from_date, :last_to_date,
                    :last_success_at, :rows_upserted, :message, :updated_at
                )
                ON DUPLICATE KEY UPDATE
                    last_mode = VALUES(last_mode),
                    last_status = VALUES(last_status),
                    last_source = VALUES(last_source),
                    last_from_date = VALUES(last_from_date),
                    last_to_date = VALUES(last_to_date),
                    last_success_at = VALUES(last_success_at),
                    rows_upserted = VALUES(rows_upserted),
                    message = VALUES(message),
                    updated_at = VALUES(updated_at)';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute([
            ':last_mode' => $data['last_mode'] ?? null,
            ':last_status' => $data['last_status'] ?? null,
            ':last_source' => $data['last_source'] ?? null,
            ':last_from_date' => $data['last_from_date'] ?? null,
            ':last_to_date' => $data['last_to_date'] ?? null,
            ':last_success_at' => $data['last_success_at'] ?? null,
            ':rows_upserted' => (int) ($data['rows_upserted'] ?? 0),
            ':message' => $data['message'] ?? null,
            ':updated_at' => $data['updated_at'] ?? date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function createSyncRun(array $data): int
    {
        $sql = 'INSERT INTO crm_sales_sap_sync_runs (
                    sync_mode, source, date_from, date_to,
                    rows_fetched, rows_upserted, status, error_log, started_at, finished_at
                ) VALUES (
                    :sync_mode, :source, :date_from, :date_to,
                    :rows_fetched, :rows_upserted, :status, :error_log, :started_at, :finished_at
                )';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute([
            ':sync_mode' => (string) ($data['sync_mode'] ?? 'incremental'),
            ':source' => $data['source'] ?? null,
            ':date_from' => $data['date_from'] ?? null,
            ':date_to' => $data['date_to'] ?? null,
            ':rows_fetched' => (int) ($data['rows_fetched'] ?? 0),
            ':rows_upserted' => (int) ($data['rows_upserted'] ?? 0),
            ':status' => (string) ($data['status'] ?? 'running'),
            ':error_log' => $data['error_log'] ?? null,
            ':started_at' => (string) ($data['started_at'] ?? date('Y-m-d H:i:s')),
            ':finished_at' => $data['finished_at'] ?? null,
        ]);
        return (int) $this->getConnection()->lastInsertId();
    }

    /**
     * @param array<string, mixed> $data
     */
    public function finishSyncRun(int $id, array $data): void
    {
        if ($id <= 0) {
            return;
        }
        $sql = 'UPDATE crm_sales_sap_sync_runs SET
                    source = :source,
                    date_from = :date_from,
                    date_to = :date_to,
                    rows_fetched = :rows_fetched,
                    rows_upserted = :rows_upserted,
                    status = :status,
                    error_log = :error_log,
                    finished_at = :finished_at
                WHERE id = :id';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute([
            ':id' => $id,
            ':source' => $data['source'] ?? null,
            ':date_from' => $data['date_from'] ?? null,
            ':date_to' => $data['date_to'] ?? null,
            ':rows_fetched' => (int) ($data['rows_fetched'] ?? 0),
            ':rows_upserted' => (int) ($data['rows_upserted'] ?? 0),
            ':status' => (string) ($data['status'] ?? 'completed'),
            ':error_log' => $data['error_log'] ?? null,
            ':finished_at' => $data['finished_at'] ?? date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * @param array<string, string|null> $dims
     * @return array{0: string, 1: array<string, string>}
     */
    private function buildWhere(string $from, string $to, array $dims, ?string $ignoreDim): array
    {
        $parts = ['doc_date >= :from', 'doc_date <= :to'];
        $params = [':from' => $from, ':to' => $to];
        $map = [
            'vendedor' => 'vendedor',
            'grupo_cliente' => 'grupo_cliente',
            'regiao' => 'regiao',
            'grupo_item' => 'grupo_item',
            'ano_mes' => 'ano_mes',
        ];
        foreach ($map as $key => $col) {
            if ($ignoreDim === $key) {
                continue;
            }
            $val = $dims[$key] ?? null;
            if ($val !== null && $val !== '') {
                $ph = ':' . $key;
                $parts[] = "{$col} = {$ph}";
                $params[$ph] = $val;
            }
        }
        return [implode(' AND ', $parts), $params];
    }
}
