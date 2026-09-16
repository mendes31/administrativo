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

    public function hasUsageColumns(): bool
    {
        static $has = null;
        if ($has !== null) {
            return $has;
        }
        if (!$this->tableExists()) {
            $has = false;
            return $has;
        }
        try {
            $stmt = $this->getConnection()->query("SHOW COLUMNS FROM crm_sales_fact_daily LIKE 'usage_id'");
            $has = (bool) $stmt->fetchColumn();
        } catch (\Throwable) {
            $has = false;
        }
        return $has;
    }

    public function hasItemColumns(): bool
    {
        static $has = null;
        if ($has !== null) {
            return $has;
        }
        if (!$this->tableExists()) {
            $has = false;
            return $has;
        }
        try {
            $stmt = $this->getConnection()->query("SHOW COLUMNS FROM crm_sales_fact_daily LIKE 'item_code'");
            $has = (bool) $stmt->fetchColumn();
        } catch (\Throwable) {
            $has = false;
        }
        return $has;
    }

    public function hasDocNumColumns(): bool
    {
        static $has = null;
        if ($has !== null) {
            return $has;
        }
        if (!$this->tableExists()) {
            $has = false;
            return $has;
        }
        try {
            $stmt = $this->getConnection()->query("SHOW COLUMNS FROM crm_sales_fact_daily LIKE 'doc_num'");
            $has = (bool) $stmt->fetchColumn();
        } catch (\Throwable) {
            $has = false;
        }
        return $has;
    }

    public function hasSerialColumn(): bool
    {
        static $has = null;
        if ($has !== null) {
            return $has;
        }
        if (!$this->tableExists()) {
            $has = false;
            return $has;
        }
        try {
            $stmt = $this->getConnection()->query("SHOW COLUMNS FROM crm_sales_fact_daily LIKE 'doc_serial'");
            $has = (bool) $stmt->fetchColumn();
        } catch (\Throwable $e) {
            $has = false;
        }
        return $has;
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
     * @param list<array<string, mixed>> $rows
     */
    public function upsertBatch(array $rows): int
    {
        if ($rows === [] || !$this->tableExists()) {
            return 0;
        }

        $now = date('Y-m-d H:i:s');
        $withUsage = $this->hasUsageColumns();
        $withItem = $this->hasItemColumns();
        $withDoc = $this->hasDocNumColumns();
        $withSerial = $withDoc && $this->hasSerialColumn();

        if ($withUsage && $withItem && $withDoc && $withSerial) {
            $sql = 'INSERT INTO crm_sales_fact_daily (
                        grain_hash, doc_date, ano_mes, tipo_documento, doc_num, doc_entry, doc_serial,
                        card_code, cliente,
                        vendedor, grupo_cliente, regiao, grupo_item, item_code, item_name,
                        usage_id, usage_name,
                        valor_liquido, quantidade, valor_bruto, valor_desconto, qtd_linhas,
                        synced_at, created_at, updated_at
                    ) VALUES (
                        :grain_hash, :doc_date, :ano_mes, :tipo_documento, :doc_num, :doc_entry, :doc_serial,
                        :card_code, :cliente,
                        :vendedor, :grupo_cliente, :regiao, :grupo_item, :item_code, :item_name,
                        :usage_id, :usage_name,
                        :valor_liquido, :quantidade, :valor_bruto, :valor_desconto, :qtd_linhas,
                        :synced_at, :created_at, :updated_at
                    )
                    ON DUPLICATE KEY UPDATE
                        cliente = VALUES(cliente),
                        item_name = VALUES(item_name),
                        usage_name = VALUES(usage_name),
                        doc_serial = VALUES(doc_serial),
                        valor_liquido = VALUES(valor_liquido),
                        quantidade = VALUES(quantidade),
                        valor_bruto = VALUES(valor_bruto),
                        valor_desconto = VALUES(valor_desconto),
                        qtd_linhas = VALUES(qtd_linhas),
                        synced_at = VALUES(synced_at),
                        updated_at = VALUES(updated_at)';
        } elseif ($withUsage && $withItem && $withDoc) {
            $sql = 'INSERT INTO crm_sales_fact_daily (
                        grain_hash, doc_date, ano_mes, tipo_documento, doc_num, doc_entry,
                        card_code, cliente,
                        vendedor, grupo_cliente, regiao, grupo_item, item_code, item_name,
                        usage_id, usage_name,
                        valor_liquido, quantidade, valor_bruto, valor_desconto, qtd_linhas,
                        synced_at, created_at, updated_at
                    ) VALUES (
                        :grain_hash, :doc_date, :ano_mes, :tipo_documento, :doc_num, :doc_entry,
                        :card_code, :cliente,
                        :vendedor, :grupo_cliente, :regiao, :grupo_item, :item_code, :item_name,
                        :usage_id, :usage_name,
                        :valor_liquido, :quantidade, :valor_bruto, :valor_desconto, :qtd_linhas,
                        :synced_at, :created_at, :updated_at
                    )
                    ON DUPLICATE KEY UPDATE
                        cliente = VALUES(cliente),
                        item_name = VALUES(item_name),
                        usage_name = VALUES(usage_name),
                        valor_liquido = VALUES(valor_liquido),
                        quantidade = VALUES(quantidade),
                        valor_bruto = VALUES(valor_bruto),
                        valor_desconto = VALUES(valor_desconto),
                        qtd_linhas = VALUES(qtd_linhas),
                        synced_at = VALUES(synced_at),
                        updated_at = VALUES(updated_at)';
        } elseif ($withUsage && $withItem) {
            $sql = 'INSERT INTO crm_sales_fact_daily (
                        grain_hash, doc_date, ano_mes, tipo_documento, card_code, cliente,
                        vendedor, grupo_cliente, regiao, grupo_item, item_code, item_name,
                        usage_id, usage_name,
                        valor_liquido, quantidade, valor_bruto, valor_desconto, qtd_linhas,
                        synced_at, created_at, updated_at
                    ) VALUES (
                        :grain_hash, :doc_date, :ano_mes, :tipo_documento, :card_code, :cliente,
                        :vendedor, :grupo_cliente, :regiao, :grupo_item, :item_code, :item_name,
                        :usage_id, :usage_name,
                        :valor_liquido, :quantidade, :valor_bruto, :valor_desconto, :qtd_linhas,
                        :synced_at, :created_at, :updated_at
                    )
                    ON DUPLICATE KEY UPDATE
                        cliente = VALUES(cliente),
                        item_name = VALUES(item_name),
                        usage_name = VALUES(usage_name),
                        valor_liquido = VALUES(valor_liquido),
                        quantidade = VALUES(quantidade),
                        valor_bruto = VALUES(valor_bruto),
                        valor_desconto = VALUES(valor_desconto),
                        qtd_linhas = VALUES(qtd_linhas),
                        synced_at = VALUES(synced_at),
                        updated_at = VALUES(updated_at)';
        } elseif ($withUsage) {
            $sql = 'INSERT INTO crm_sales_fact_daily (
                        grain_hash, doc_date, ano_mes, tipo_documento, card_code, cliente,
                        vendedor, grupo_cliente, regiao, grupo_item, usage_id, usage_name,
                        valor_liquido, quantidade, valor_bruto, valor_desconto, qtd_linhas,
                        synced_at, created_at, updated_at
                    ) VALUES (
                        :grain_hash, :doc_date, :ano_mes, :tipo_documento, :card_code, :cliente,
                        :vendedor, :grupo_cliente, :regiao, :grupo_item, :usage_id, :usage_name,
                        :valor_liquido, :quantidade, :valor_bruto, :valor_desconto, :qtd_linhas,
                        :synced_at, :created_at, :updated_at
                    )
                    ON DUPLICATE KEY UPDATE
                        cliente = VALUES(cliente),
                        usage_name = VALUES(usage_name),
                        valor_liquido = VALUES(valor_liquido),
                        quantidade = VALUES(quantidade),
                        valor_bruto = VALUES(valor_bruto),
                        valor_desconto = VALUES(valor_desconto),
                        qtd_linhas = VALUES(qtd_linhas),
                        synced_at = VALUES(synced_at),
                        updated_at = VALUES(updated_at)';
        } else {
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
        }

        $pdo = $this->getConnection();
        $stmt = $pdo->prepare($sql);
        $count = 0;

        $pdo->beginTransaction();
        try {
            foreach ($rows as $row) {
                $params = [
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
                ];
                if ($withDoc) {
                    $params[':doc_num'] = (int) ($row['doc_num'] ?? 0);
                    $params[':doc_entry'] = (int) ($row['doc_entry'] ?? 0);
                }
                if ($withSerial) {
                    $params[':doc_serial'] = (int) ($row['doc_serial'] ?? 0);
                }
                if ($withUsage) {
                    $params[':usage_id'] = (int) ($row['usage_id'] ?? 0);
                    $params[':usage_name'] = (string) ($row['usage_name'] ?? '');
                    $params[':quantidade'] = (float) ($row['quantidade'] ?? 0);
                    $params[':valor_bruto'] = (float) ($row['valor_bruto'] ?? 0);
                    $params[':valor_desconto'] = (float) ($row['valor_desconto'] ?? 0);
                }
                if ($withUsage && $withItem) {
                    $params[':item_code'] = (string) ($row['item_code'] ?? '');
                    $params[':item_name'] = (string) ($row['item_name'] ?? '');
                }
                $stmt->execute($params);
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
        $fromSql = $this->fromFactSql();
        $nat = $this->natureSql();

        if (!$this->hasUsageJoin()) {
            $sql = "SELECT
                        SUM(CASE WHEN f.tipo_documento = 'Fatura' THEN f.valor_liquido ELSE 0 END) AS bruto,
                        SUM(CASE WHEN f.tipo_documento = 'Devolucao' THEN ABS(f.valor_liquido) ELSE 0 END) AS devolucoes,
                        SUM(f.valor_liquido) AS liquido,
                        COUNT(DISTINCT CASE WHEN f.tipo_documento = 'Fatura' THEN f.card_code END) AS clientes_ativos,
                        SUM(CASE WHEN f.tipo_documento = 'Devolucao' THEN f.qtd_linhas ELSE 0 END) AS qtd_devolucoes,
                        0 AS itens_vendidos,
                        0 AS itens_faturados,
                        0 AS itens_devolvidos,
                        0 AS valor_bonificacoes,
                        0 AS valor_brindes,
                        0 AS qtd_bonificacoes,
                        0 AS qtd_brindes,
                        0 AS itens_bonificados,
                        0 AS itens_brindes,
                        0 AS desconto,
                        0 AS valor_bruto_venda
                    FROM {$fromSql}
                    WHERE {$where}";
        } else {
            $sql = "SELECT
                        SUM(CASE WHEN {$nat} = 'venda' AND f.tipo_documento = 'Fatura' THEN f.valor_liquido ELSE 0 END) AS bruto,
                        SUM(CASE WHEN {$nat} = 'venda' AND f.tipo_documento = 'Devolucao' THEN ABS(f.valor_liquido) ELSE 0 END) AS devolucoes,
                        SUM(CASE WHEN {$nat} = 'venda' THEN f.valor_liquido ELSE 0 END) AS liquido,
                        COUNT(DISTINCT CASE WHEN {$nat} = 'venda' AND f.tipo_documento = 'Fatura' THEN f.card_code END) AS clientes_ativos,
                        SUM(CASE WHEN {$nat} = 'venda' AND f.tipo_documento = 'Devolucao' THEN f.qtd_linhas ELSE 0 END) AS qtd_devolucoes,
                        SUM(CASE WHEN {$nat} = 'venda' THEN f.quantidade ELSE 0 END) AS itens_vendidos,
                        SUM(CASE WHEN {$nat} = 'venda' AND f.tipo_documento = 'Fatura' THEN f.quantidade ELSE 0 END) AS itens_faturados,
                        SUM(CASE WHEN {$nat} = 'venda' AND f.tipo_documento = 'Devolucao' THEN ABS(f.quantidade) ELSE 0 END) AS itens_devolvidos,
                        SUM(CASE WHEN {$nat} = 'bonificacao' THEN f.valor_liquido ELSE 0 END) AS valor_bonificacoes,
                        SUM(CASE WHEN {$nat} = 'brinde' THEN f.valor_liquido ELSE 0 END) AS valor_brindes,
                        SUM(CASE
                            WHEN {$nat} = 'bonificacao' AND f.tipo_documento = 'Fatura' THEN f.qtd_linhas
                            WHEN {$nat} = 'bonificacao' AND f.tipo_documento = 'Devolucao' THEN -f.qtd_linhas
                            ELSE 0
                        END) AS qtd_bonificacoes,
                        SUM(CASE
                            WHEN {$nat} = 'brinde' AND f.tipo_documento = 'Fatura' THEN f.qtd_linhas
                            WHEN {$nat} = 'brinde' AND f.tipo_documento = 'Devolucao' THEN -f.qtd_linhas
                            ELSE 0
                        END) AS qtd_brindes,
                        SUM(CASE WHEN {$nat} = 'bonificacao' THEN f.quantidade ELSE 0 END) AS itens_bonificados,
                        SUM(CASE WHEN {$nat} = 'brinde' THEN f.quantidade ELSE 0 END) AS itens_brindes,
                        SUM(CASE WHEN {$nat} = 'venda' THEN f.valor_desconto ELSE 0 END) AS desconto,
                        SUM(CASE WHEN {$nat} = 'venda' THEN f.valor_bruto ELSE 0 END) AS valor_bruto_venda
                    FROM {$fromSql}
                    WHERE {$where}";
        }

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
        $col = 'f.' . $allowed[$column];
        [$where, $params] = $this->buildWhere($from, $to, $dims, $ignoreDim);
        $order = $orderAsc ? "{$col} ASC" : 'valor DESC';
        $fromSql = $this->fromFactSql();
        $vendaOnly = $this->hasUsageJoin() ? " AND {$this->natureSql()} = 'venda'" : '';
        $nfsSelect = $this->hasDocNumColumns()
            ? ', ' . $this->nfsNetSql() . ' AS qtd_nfs'
            : '';
        $sql = "SELECT {$col} AS label, SUM(f.valor_liquido) AS valor{$nfsSelect}
                FROM {$fromSql}
                WHERE {$where}
                  {$vendaOnly}
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
            $item = [
                'label' => $label,
                'valor' => (float) ($row['valor'] ?? 0),
            ];
            if ($this->hasDocNumColumns()) {
                $item['qtd_nfs'] = (int) ($row['qtd_nfs'] ?? 0);
            }
            $out[] = $item;
        }
        return $out;
    }

    /**
     * Todos os clientes do recorte, ordenados por líquido (sem limite de linhas).
     *
     * @param array<string, string|null> $dims
     * @return list<array{card_code: string, cliente: string, grupo: string, liquido: float, devolucao: float, qtd_nfs?: int}>
     */
    public function fetchTopClientes(string $from, string $to, array $dims, ?string $ignoreDim = null): array
    {
        [$where, $params] = $this->buildWhere($from, $to, $dims, $ignoreDim);
        $fromSql = $this->fromFactSql();
        $vendaOnly = $this->hasUsageJoin() ? " AND {$this->natureSql()} = 'venda'" : '';
        $nfsSelect = $this->hasDocNumColumns()
            ? ', ' . $this->nfsNetSql() . ' AS qtd_nfs'
            : '';
        $sql = "SELECT
                    f.card_code,
                    MAX(f.cliente) AS cliente,
                    MAX(f.grupo_cliente) AS grupo,
                    SUM(f.valor_liquido) AS liquido,
                    SUM(CASE WHEN f.tipo_documento = 'Devolucao' THEN ABS(f.valor_liquido) ELSE 0 END) AS devolucao
                    {$nfsSelect}
                FROM {$fromSql}
                WHERE {$where}
                  {$vendaOnly}
                GROUP BY f.card_code
                ORDER BY liquido DESC";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute($params);
        $out = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $item = [
                'card_code' => (string) ($row['card_code'] ?? ''),
                'cliente' => (string) ($row['cliente'] ?? ''),
                'grupo' => (string) ($row['grupo'] ?? ''),
                'liquido' => (float) ($row['liquido'] ?? 0),
                'devolucao' => (float) ($row['devolucao'] ?? 0),
            ];
            if ($this->hasDocNumColumns()) {
                $item['qtd_nfs'] = (int) ($row['qtd_nfs'] ?? 0);
            }
            $out[] = $item;
        }
        return $out;
    }

    /**
     * Todos os itens do recorte, ordenados por líquido (sem limite de linhas).
     *
     * @param array<string, string|null> $dims
     * @return list<array{item_code: string, item_name: string, grupo: string, quantidade: float, liquido: float, devolucao: float, qtd_nfs?: int}>
     */
    public function fetchTopItens(
        string $from,
        string $to,
        array $dims,
        ?string $ignoreDim = null,
        string $natureza = 'venda'
    ): array
    {
        if (!$this->hasItemColumns()) {
            return [];
        }
        $natureza = $this->normalizeNatureza($natureza);
        [$where, $params] = $this->buildWhere($from, $to, $dims, $ignoreDim);
        $fromSql = $this->fromFactSql();
        $natureFilter = $this->natureFilterSql($natureza);
        $nfsSelect = $this->hasDocNumColumns()
            ? ', ' . $this->nfsNetSql() . ' AS qtd_nfs'
            : '';
        $sql = "SELECT
                    f.item_code,
                    MAX(f.item_name) AS item_name,
                    MAX(f.grupo_item) AS grupo,
                    SUM(f.quantidade) AS quantidade,
                    SUM(f.valor_liquido) AS liquido,
                    SUM(CASE WHEN f.tipo_documento = 'Devolucao' THEN ABS(f.valor_liquido) ELSE 0 END) AS devolucao
                    {$nfsSelect}
                FROM {$fromSql}
                WHERE {$where}
                  {$natureFilter}
                  AND f.item_code IS NOT NULL
                  AND f.item_code <> ''
                GROUP BY f.item_code
                ORDER BY liquido DESC";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute($params);
        $out = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $item = [
                'item_code' => (string) ($row['item_code'] ?? ''),
                'item_name' => (string) ($row['item_name'] ?? ''),
                'grupo' => (string) ($row['grupo'] ?? ''),
                'quantidade' => (float) ($row['quantidade'] ?? 0),
                'liquido' => (float) ($row['liquido'] ?? 0),
                'devolucao' => (float) ($row['devolucao'] ?? 0),
            ];
            if ($this->hasDocNumColumns()) {
                $item['qtd_nfs'] = (int) ($row['qtd_nfs'] ?? 0);
            }
            $out[] = $item;
        }
        return $out;
    }

    /**
     * Uma linha por nota (DocEntry + tipo), sem parcelas.
     * Valor/qtd são só das linhas da natureza (venda, bonificação ou brinde).
     * Com filtro de item, restringe ainda àquele SKU.
     *
     * @param array<string, string|list<string>|null> $dims
     * @return array{rows: list<array<string, mixed>>, total_rows: int, total_valor: float, total_quantidade: float}
     */
    public function fetchInvoices(
        string $from,
        string $to,
        array $dims,
        int $limit = 200,
        int $offset = 0,
        string $natureza = 'venda'
    ): array {
        $empty = [
            'rows' => [],
            'total_rows' => 0,
            'total_valor' => 0.0,
            'total_quantidade' => 0.0,
            'qtd_venda' => 0,
            'qtd_devolucao' => 0,
            'qtd_nfs' => 0,
        ];
        if (!$this->hasDocNumColumns()) {
            return $empty;
        }

        $natureza = $this->normalizeNatureza($natureza);
        [$where, $params] = $this->buildWhere($from, $to, $dims, null);
        $fromSql = $this->fromFactSql();
        $natureFilter = $this->natureFilterSql($natureza);
        $serialSelect = $this->hasSerialColumn()
            ? ', MAX(f.doc_serial) AS doc_serial'
            : ', 0 AS doc_serial';
        $grouped = "SELECT
                    f.tipo_documento,
                    f.doc_entry,
                    MAX(f.doc_num) AS doc_num
                    {$serialSelect},
                    MAX(f.doc_date) AS doc_date,
                    MAX(f.card_code) AS card_code,
                    MAX(f.cliente) AS cliente,
                    MAX(f.vendedor) AS vendedor,
                    SUM(f.valor_liquido) AS valor,
                    SUM(f.quantidade) AS quantidade
                FROM {$fromSql}
                WHERE {$where}
                  {$natureFilter}
                  AND f.doc_num > 0
                GROUP BY f.tipo_documento, f.doc_entry";

        $countSql = "SELECT COUNT(*) AS total_rows,
                            COALESCE(SUM(g.valor), 0) AS total_valor,
                            COALESCE(SUM(g.quantidade), 0) AS total_quantidade,
                            SUM(CASE WHEN g.tipo_documento = 'Fatura' THEN 1 ELSE 0 END) AS qtd_venda,
                            SUM(CASE WHEN g.tipo_documento = 'Devolucao' THEN 1 ELSE 0 END) AS qtd_devolucao
                     FROM ({$grouped}) g";
        $countStmt = $this->getConnection()->prepare($countSql);
        $countStmt->execute($params);
        $totals = $countStmt->fetch(PDO::FETCH_ASSOC) ?: [];

        $limit = max(1, min(500, $limit));
        $offset = max(0, $offset);
        $listSql = "{$grouped}
                    ORDER BY doc_date DESC, doc_num DESC, tipo_documento ASC
                    LIMIT {$limit} OFFSET {$offset}";
        $listStmt = $this->getConnection()->prepare($listSql);
        $listStmt->execute($params);
        $rows = [];
        while ($row = $listStmt->fetch(PDO::FETCH_ASSOC)) {
            $rows[] = [
                'tipo_documento' => (string) ($row['tipo_documento'] ?? ''),
                'doc_entry' => (int) ($row['doc_entry'] ?? 0),
                'doc_num' => (int) ($row['doc_num'] ?? 0),
                'doc_serial' => (int) ($row['doc_serial'] ?? 0),
                'doc_date' => (string) ($row['doc_date'] ?? ''),
                'card_code' => (string) ($row['card_code'] ?? ''),
                'cliente' => (string) ($row['cliente'] ?? ''),
                'vendedor' => (string) ($row['vendedor'] ?? ''),
                'valor' => (float) ($row['valor'] ?? 0),
                'quantidade' => (float) ($row['quantidade'] ?? 0),
            ];
        }

        $qtdVenda = (int) ($totals['qtd_venda'] ?? 0);
        $qtdDev = (int) ($totals['qtd_devolucao'] ?? 0);
        return [
            'rows' => $rows,
            'total_rows' => (int) ($totals['total_rows'] ?? 0),
            'total_valor' => (float) ($totals['total_valor'] ?? 0),
            'total_quantidade' => (float) ($totals['total_quantidade'] ?? 0),
            'qtd_venda' => $qtdVenda,
            'qtd_devolucao' => $qtdDev,
            'qtd_nfs' => $qtdVenda - $qtdDev,
        ];
    }

    public function fetchFilterOptions(string $from, string $to): array
    {
        $pick = function (string $column) use ($from, $to): array {
            $sql = "SELECT DISTINCT f.{$column} AS label
                    FROM {$this->fromFactSql()}
                    WHERE f.doc_date >= :from AND f.doc_date <= :to
                      AND f.{$column} IS NOT NULL AND f.{$column} <> ''
                    ORDER BY f.{$column}";
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

    private function hasUsageJoin(): bool
    {
        if (!$this->hasUsageColumns()) {
            return false;
        }
        $usageRepo = new CrmSalesUsageNatureRepository();
        return $usageRepo->tableExists();
    }

    private function fromFactSql(): string
    {
        if (!$this->hasUsageJoin()) {
            return 'crm_sales_fact_daily f';
        }
        return 'crm_sales_fact_daily f
                LEFT JOIN crm_sales_usage_nature n ON n.usage_id = f.usage_id';
    }

    /**
     * nao_classificada (e NULL) entram como venda até o cadastro classificar.
     * devolucao (E Dev Venda) entra no mesmo recorte de venda: ORIN no card Devoluções e no líquido.
     */
    private function natureSql(): string
    {
        return "CASE
            WHEN n.natureza = 'ignorar' THEN 'ignorar'
            WHEN n.natureza IN ('bonificacao', 'brinde') THEN n.natureza
            ELSE 'venda'
        END";
    }

    public function normalizeNatureza(mixed $value): string
    {
        $v = trim((string) $value);
        return in_array($v, ['venda', 'bonificacao', 'brinde'], true) ? $v : 'venda';
    }

    private function natureFilterSql(string $natureza): string
    {
        $natureza = $this->normalizeNatureza($natureza);
        if (!$this->hasUsageJoin()) {
            return $natureza === 'venda' ? '' : ' AND 1=0';
        }
        return " AND {$this->natureSql()} = '{$natureza}'";
    }

    /** Faturas distintas menos devoluções distintas (mesmo recorte de venda do líquido). */
    private function nfsNetSql(): string
    {
        return "(COUNT(DISTINCT CASE WHEN f.tipo_documento = 'Fatura' THEN f.doc_entry END)
            - COUNT(DISTINCT CASE WHEN f.tipo_documento = 'Devolucao' THEN f.doc_entry END))";
    }

    /**
     * @param array<string, string|list<string>|null> $dims
     * @return array{0: string, 1: array<string, string>}
     */
    private function buildWhere(string $from, string $to, array $dims, ?string $ignoreDim): array
    {
        $parts = ['f.doc_date >= :from', 'f.doc_date <= :to'];
        $params = [':from' => $from, ':to' => $to];
        $map = [
            'vendedor' => 'f.vendedor',
            'grupo_cliente' => 'f.grupo_cliente',
            'regiao' => 'f.regiao',
            'grupo_item' => 'f.grupo_item',
            'ano_mes' => 'f.ano_mes',
            'card_code' => 'f.card_code',
        ];
        if ($this->hasItemColumns()) {
            $map['item_code'] = 'f.item_code';
        }
        foreach ($map as $key => $col) {
            if ($ignoreDim === $key) {
                continue;
            }
            $clean = $this->normalizeDimValues($dims[$key] ?? null);
            if ($clean === []) {
                continue;
            }
            if (count($clean) === 1) {
                $ph = ':' . $key;
                $parts[] = "{$col} = {$ph}";
                $params[$ph] = $clean[0];
                continue;
            }
            $placeholders = [];
            foreach ($clean as $i => $item) {
                $ph = ':' . $key . '_' . $i;
                $placeholders[] = $ph;
                $params[$ph] = $item;
            }
            $parts[] = $col . ' IN (' . implode(', ', $placeholders) . ')';
        }
        return [implode(' AND ', $parts), $params];
    }

    /**
     * @return list<string>
     */
    private function normalizeDimValues(mixed $val): array
    {
        if ($val === null || $val === '' || $val === []) {
            return [];
        }
        if (!is_array($val)) {
            $val = [$val];
        }
        $clean = [];
        foreach ($val as $item) {
            if ($item === null || is_array($item)) {
                continue;
            }
            $s = trim((string) $item);
            if ($s !== '') {
                $clean[$s] = $s;
            }
        }
        return array_values($clean);
    }
}
