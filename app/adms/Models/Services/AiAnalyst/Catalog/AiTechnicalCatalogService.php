<?php

declare(strict_types=1);

namespace App\adms\Models\Services\AiAnalyst\Catalog;

/**
 * Catálogo técnico: planilha SAP B1 9.3 + PDF BEAS + enriquecimento local.
 * Não consulta HANA nem sites externos em runtime.
 */
final class AiTechnicalCatalogService
{
    private const B1_DATA_FILE = __DIR__ . '/data/sap_b1_9_3_tables.json';
    private const BEAS_DATA_FILE = __DIR__ . '/data/beas_tables.json';

    /** @var list<array<string, mixed>>|null */
    private ?array $entries = null;

    /**
     * @return list<array<string, mixed>>
     */
    public function entries(): array
    {
        if ($this->entries !== null) {
            return $this->entries;
        }

        $byTable = [];
        foreach ($this->loadJsonEntries(self::B1_DATA_FILE) as $entry) {
            $table = strtoupper((string) ($entry['table_name'] ?? ''));
            if ($table !== '') {
                $byTable[$table] = $entry;
            }
        }
        foreach ($this->loadJsonEntries(self::BEAS_DATA_FILE) as $entry) {
            $table = strtoupper((string) ($entry['table_name'] ?? ''));
            if ($table === '') {
                continue;
            }
            $byTable[$table] = isset($byTable[$table])
                ? $this->mergeEntry($byTable[$table], $entry)
                : $entry;
        }

        foreach ($this->curatedEnrichment() as $extra) {
            $table = strtoupper((string) ($extra['table_name'] ?? ''));
            if ($table === '' || $table === 'BEAS_*') {
                $byTable['__' . ($extra['id'] ?? uniqid('extra', true))] = $extra;
                continue;
            }
            if (!isset($byTable[$table])) {
                $byTable[$table] = $extra;
                continue;
            }
            $byTable[$table] = $this->mergeEntry($byTable[$table], $extra);
        }

        $this->entries = array_values($byTable);

        return $this->entries;
    }

    /** @return array<string, mixed>|null */
    public function findByTableName(string $tableName): ?array
    {
        $needle = strtoupper(trim($tableName));
        if ($needle === '') {
            return null;
        }
        foreach ($this->entries() as $entry) {
            if (strtoupper((string) ($entry['table_name'] ?? '')) === $needle) {
                return $entry;
            }
        }

        return null;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function search(string $query, int $limit = 5): array
    {
        $query = mb_strtolower(trim($query));
        if ($query === '' || $limit < 1) {
            return [];
        }

        $scored = [];
        foreach ($this->entries() as $entry) {
            $score = $this->score($query, $entry);
            // Descarta coincidências fracas (ex.: token «tabela» em qualquer texto).
            if ($score >= 12) {
                $scored[] = ['score' => $score, 'entry' => $entry];
            }
        }

        usort($scored, static function (array $a, array $b): int {
            $byScore = $b['score'] <=> $a['score'];
            if ($byScore !== 0) {
                return $byScore;
            }

            return strcmp(
                (string) ($a['entry']['table_name'] ?? ''),
                (string) ($b['entry']['table_name'] ?? '')
            );
        });
        if ($scored === []) {
            return [];
        }

        $top = $scored[0]['score'];
        $scored = array_values(array_filter(
            $scored,
            static fn (array $row): bool => $row['score'] >= (int) max(12, floor($top * 0.45))
        ));

        return array_map(
            static fn (array $row): array => $row['entry'],
            array_slice($scored, 0, $limit)
        );
    }

    /** @return list<array<string, mixed>> */
    private function loadJsonEntries(string $path): array
    {
        if (!is_readable($path)) {
            return [];
        }

        $raw = file_get_contents($path);
        if ($raw === false || $raw === '') {
            return [];
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded) || !is_array($decoded['entries'] ?? null)) {
            return [];
        }

        $out = [];
        foreach ($decoded['entries'] as $entry) {
            if (!is_array($entry)) {
                continue;
            }
            $table = strtoupper(trim((string) ($entry['table_name'] ?? '')));
            if ($table === '') {
                continue;
            }
            $out[] = $entry;
        }

        return $out;
    }

    /**
     * Enriquecimento local: sinônimos em PT e relações úteis.
     * Não substitui a planilha; só melhora a busca e a explicação.
     *
     * @return list<array<string, mixed>>
     */
    private function curatedEnrichment(): array
    {
        return [
            [
                'id' => 'oitm',
                'table_name' => 'OITM',
                'title' => 'Itens / cadastro de produtos',
                'summary' => 'Tabela mestre de itens (Item Master Data). Object Type 4.',
                'key_fields' => ['ItemCode', 'ItemName', 'ItmsGrpCod', 'InvntItem', 'SellItem', 'PrchseItem'],
                'related' => ['OITW (estoque por depósito)', 'ITM1 (lista de preços)', 'OITB (grupo de itens)'],
                'synonyms' => ['item', 'itens', 'produto', 'produtos', 'material', 'materiais', 'sku', 'item master', 'artigo', 'artigos'],
            ],
            [
                'id' => 'oitw',
                'table_name' => 'OITW',
                'title' => 'Estoque do item por depósito',
                'synonyms' => ['estoque', 'saldo', 'depósito', 'deposito', 'warehouse'],
                'related' => ['OITM', 'OWHS'],
            ],
            [
                'id' => 'ocrd',
                'table_name' => 'OCRD',
                'title' => 'Parceiros de negócio',
                'synonyms' => ['cliente', 'clientes', 'fornecedor', 'fornecedores', 'parceiro', 'bp', 'business partner'],
                'related' => ['CRD1 (endereços)', 'OCPR (contatos)'],
            ],
            [
                'id' => 'oinv',
                'table_name' => 'OINV',
                'title' => 'Nota fiscal de saída (faturamento / A/R Invoice)',
                'summary' => 'Cabeçalho das notas fiscais de saída (faturamento de vendas). Linhas em INV1. Object Type 13.',
                'synonyms' => [
                    'fatura', 'faturas', 'nf', 'nota fiscal', 'notas fiscais', 'invoice',
                    'faturamento', 'venda', 'vendas', 'saída', 'saida',
                ],
                'related' => ['INV1 (linhas)', 'OCRD (cliente)', 'OITM (item)', 'ORDR (pedido de venda)'],
            ],
            [
                'id' => 'ordr',
                'table_name' => 'ORDR',
                'title' => 'Pedido de venda (Sales Order)',
                'summary' => 'Cabeçalho dos pedidos de venda. Linhas em RDR1. Object Type 17.',
                'synonyms' => [
                    'pedido', 'pedidos', 'pedido de venda', 'pedidos de venda',
                    'sales order', 'ordem de venda', 'venda', 'vendas',
                ],
                'related' => ['RDR1 (linhas)', 'OCRD (cliente)', 'OINV (nota fiscal de saída)'],
            ],
            [
                'id' => 'inv1',
                'table_name' => 'INV1',
                'title' => 'Linhas da nota fiscal de saída',
                'summary' => 'Itens e valores das linhas da NF de saída. Cabeçalho em OINV.',
                'synonyms' => ['linha da nf', 'linhas da fatura', 'itens da nf', 'itens da fatura'],
                'related' => ['OINV', 'OITM'],
            ],
            [
                'id' => 'rdr1',
                'table_name' => 'RDR1',
                'title' => 'Linhas do pedido de venda',
                'summary' => 'Linhas do pedido de venda. Cabeçalho em ORDR.',
                'synonyms' => ['linha do pedido', 'linhas do pedido', 'itens do pedido'],
                'related' => ['ORDR', 'OITM'],
            ],
            [
                'id' => 'opor',
                'table_name' => 'OPOR',
                'title' => 'Pedido de compra (Purchase Order)',
                'summary' => 'Cabeçalho dos pedidos de compra. Linhas em POR1.',
                'synonyms' => ['compra', 'compras', 'pedido de compra', 'purchase order'],
                'related' => ['POR1 (linhas)', 'OCRD (fornecedor)'],
            ],
            [
                'id' => 'odrf',
                'table_name' => 'ODRF',
                'title' => 'Rascunhos de documentos (Drafts)',
                'summary' => 'Cabeçalho dos documentos em rascunho no SAP B1. Object Type 112. Linhas em DRF1.',
                'synonyms' => [
                    'rascunho', 'rascunhos', 'draft', 'drafts', 'documento rascunho',
                    'esboço', 'esbocos', 'esboços',
                ],
                'related' => ['DRF1 (linhas do rascunho)'],
                'key_fields' => ['DocEntry', 'ObjType', 'DocNum'],
            ],
            [
                'id' => 'drf1',
                'table_name' => 'DRF1',
                'title' => 'Linhas dos rascunhos',
                'summary' => 'Linhas dos documentos em rascunho. Cabeçalho em ODRF.',
                'synonyms' => ['linha do rascunho', 'linhas do rascunho', 'draft lines'],
                'related' => ['ODRF'],
            ],
            [
                'id' => 'beas_overview',
                'title' => 'BEAS (produção / Boyum)',
                'object_type' => 'concept',
                'table_name' => 'BEAS_*',
                'module' => 'BEAS',
                'summary' => 'Addon de produção BEAS. Consulte tabelas BEAS_* no catálogo importado do PDF database-beas (ex.: BEAS_PERS, BEAS_APL_HEADER).',
                'key_fields' => [],
                'related' => ['OITM', 'BEAS_PERS', 'BEAS_APL_HEADER'],
                'synonyms' => ['beas', 'produção beas', 'producao beas', 'mrp beas', 'boyum'],
                'source' => 'curated_beas',
            ],
        ];
    }

    /**
     * @param array<string, mixed> $base
     * @param array<string, mixed> $extra
     * @return array<string, mixed>
     */
    private function mergeEntry(array $base, array $extra): array
    {
        foreach (['title', 'summary', 'module', 'object_type', 'sap_object_type', 'primary_key', 'source'] as $field) {
            if (!empty($extra[$field]) && (empty($base[$field]) || $field === 'title' || $field === 'summary')) {
                if ($field === 'summary' && !empty($base['summary']) && !empty($extra['summary'])) {
                    $base['summary'] = rtrim((string) $base['summary'], '.') . '. ' . $extra['summary'];
                } elseif ($field === 'title' && !empty($extra['title'])) {
                    $base['title'] = (string) $extra['title'];
                } elseif (!empty($extra[$field])) {
                    $base[$field] = $extra[$field];
                }
            }
        }

        foreach (['key_fields', 'related', 'synonyms'] as $listField) {
            $merged = array_values(array_unique(array_filter(array_merge(
                is_array($base[$listField] ?? null) ? $base[$listField] : [],
                is_array($extra[$listField] ?? null) ? $extra[$listField] : []
            ), static fn ($value): bool => is_string($value) && trim($value) !== '')));
            $base[$listField] = $merged;
        }

        $baseColumns = is_array($base['columns'] ?? null) ? $base['columns'] : [];
        $extraColumns = is_array($extra['columns'] ?? null) ? $extra['columns'] : [];
        if ($extraColumns !== []) {
            $byName = [];
            foreach (array_merge($baseColumns, $extraColumns) as $column) {
                if (!is_array($column)) {
                    continue;
                }
                $name = (string) ($column['name'] ?? '');
                if ($name === '') {
                    continue;
                }
                $byName[$name] = $column;
            }
            $base['columns'] = array_values($byName);
        }

        return $base;
    }

    /** @param array<string, mixed> $entry */
    private function score(string $query, array $entry): int
    {
        $haystack = mb_strtolower(implode(' ', [
            (string) ($entry['title'] ?? ''),
            (string) ($entry['table_name'] ?? ''),
            (string) ($entry['summary'] ?? ''),
            (string) ($entry['sap_object_type'] ?? ''),
            implode(' ', $entry['synonyms'] ?? []),
            implode(' ', $entry['key_fields'] ?? []),
        ]));

        $score = 0;
        $table = mb_strtolower((string) ($entry['table_name'] ?? ''));
        if ($table !== '' && $table !== 'beas_*' && preg_match('/(?<![\p{L}\d])' . preg_quote($table, '/') . '(?![\p{L}\d])/u', $query) === 1) {
            $score += 100;
        }

        $objectType = trim((string) ($entry['sap_object_type'] ?? ''));
        if ($objectType !== '' && preg_match('/\b(?:obj(?:ect)?\s*type|otype|objtype)\s*[:=]?\s*' . preg_quote($objectType, '/') . '\b/u', $query) === 1) {
            $score += 80;
        }

        foreach ($entry['synonyms'] ?? [] as $synonym) {
            $synonym = mb_strtolower(trim((string) $synonym));
            if ($synonym === '' || mb_strlen($synonym) < 3) {
                continue;
            }
            if (preg_match('/(?<![\p{L}\d])' . preg_quote($synonym, '/') . '(?![\p{L}\d])/u', $query) === 1) {
                $score += max(12, 40 - mb_strlen($synonym));
                continue;
            }
            // Aceita singular/plural simples (draft ↔ drafts, rascunho ↔ rascunhos).
            $stemQuery = preg_replace('/s$/u', '', $query) ?? $query;
            $stemSyn = preg_replace('/s$/u', '', $synonym) ?? $synonym;
            if (
                mb_strlen($stemSyn) >= 4
                && preg_match('/(?<![\p{L}\d])' . preg_quote($stemSyn, '/') . 's?(?![\p{L}\d])/u', $query) === 1
            ) {
                $score += max(12, 36 - mb_strlen($stemSyn));
            } elseif (
                mb_strlen($stemQuery) >= 4
                && str_contains($synonym, $stemQuery)
            ) {
                $score += 14;
            }
        }

        $stopwords = [
            'qual', 'quais', 'que', 'uma', 'uns', 'das', 'dos', 'pelo', 'pela', 'para',
            'tabela', 'tabelas', 'campo', 'campos', 'sap', 'hana', 'beas', 'b1',
            'business', 'one', 'the', 'and', 'for',
        ];
        foreach (preg_split('/\s+/u', $query) ?: [] as $token) {
            $token = trim($token, " \t\n\r\0\x0B?.,;:!");
            if (mb_strlen($token) < 4 || in_array($token, $stopwords, true)) {
                continue;
            }
            if (str_contains($haystack, $token)) {
                $score += 3;
            }
        }

        return $score;
    }
}
