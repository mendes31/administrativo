<?php

declare(strict_types=1);

/**
 * Importa a planilha homologada de tabelas SAP B1 9.3 para JSON do catálogo técnico.
 *
 * Uso:
 *   php scripts/import_sap_b1_tables_catalog.php [caminho.xlsx]
 *
 * Fontes de referência (não baixadas em runtime):
 *   - Planilha local "TABLES SAP B1 9.3.xlsx"
 *   - https://community.sap.com/t5/enterprise-resource-planning-blog-posts-by-members/sap-business-one-tables/ba-p/13280709
 *   - https://help.sap.com/docs/SAP_BUSINESS_ONE/83196e2e80d941d798668e7c34568832/5a96195057e845719865ac24e46b9a95.html
 */

require dirname(__DIR__) . '/vendor/autoload.php';

$source = $argv[1] ?? (rtrim((string) getenv('USERPROFILE'), '\\/') . '/Downloads/TABLES SAP B1 9.3.xlsx');
$targetDir = dirname(__DIR__) . '/app/adms/Models/Services/AiAnalyst/Catalog/data';
$target = $targetDir . '/sap_b1_9_3_tables.json';

if (!is_readable($source)) {
    fwrite(STDERR, "Arquivo nao encontrado: {$source}\n");
    exit(1);
}

if (!is_dir($targetDir) && !mkdir($targetDir, 0775, true) && !is_dir($targetDir)) {
    fwrite(STDERR, "Nao foi possivel criar {$targetDir}\n");
    exit(1);
}

$reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader('Xlsx');
$reader->setReadDataOnly(true);
$sheet = $reader->load($source)->getActiveSheet();
$rows = $sheet->toArray(null, true, true, false);
if ($rows === []) {
    fwrite(STDERR, "Planilha vazia.\n");
    exit(1);
}

$header = array_map(
    static fn ($value): string => mb_strtolower(trim((string) $value)),
    $rows[0]
);

$indexOf = static function (array $header, array $aliases): ?int {
    foreach ($aliases as $alias) {
        $pos = array_search($alias, $header, true);
        if ($pos !== false) {
            return (int) $pos;
        }
    }

    return null;
};

$colTable = $indexOf($header, ['table', 'tabela']);
$colName = $indexOf($header, ['table name', 'nome', 'name']);
$colDescription = $indexOf($header, ['table description', 'description', 'descricao', 'descrição']);
$colPrimaryKey = $indexOf($header, ['primary key', 'pk', 'chave']);
$colObjectType = $indexOf($header, ['object type', 'objtype', 'object_type', 'otype']);

if ($colTable === null || $colObjectType === null) {
    fwrite(STDERR, "Cabecalho invalido. Esperado Table + Object type.\n");
    exit(1);
}

$entries = [];
$seen = [];
for ($i = 1, $n = count($rows); $i < $n; $i++) {
    $row = $rows[$i];
    $table = strtoupper(trim((string) ($row[$colTable] ?? '')));
    if ($table === '' || !preg_match('/^[A-Z][A-Z0-9_]{1,60}$/', $table)) {
        continue;
    }
    if (isset($seen[$table])) {
        continue;
    }
    $seen[$table] = true;

    $label = trim((string) ($row[$colName] ?? ''));
    $description = trim((string) ($row[$colDescription] ?? ''));
    $primaryKey = trim((string) ($row[$colPrimaryKey] ?? ''));
    $objectType = trim((string) ($row[$colObjectType] ?? ''));
    if ($objectType !== '' && !preg_match('/^\d{1,6}$/', $objectType)) {
        // Algumas linhas podem vir trocadas; tenta recuperar digitos.
        if (preg_match('/\d{1,6}/', $objectType, $m)) {
            $objectType = $m[0];
        } else {
            $objectType = '';
        }
    }

    $synonyms = array_values(array_unique(array_filter([
        mb_strtolower($table),
        mb_strtolower($label),
        mb_strtolower($description),
        $objectType !== '' ? 'object type ' . $objectType : '',
        $objectType !== '' ? 'objtype ' . $objectType : '',
        $objectType !== '' ? 'otype ' . $objectType : '',
    ], static fn (string $value): bool => $value !== '')));

    $keyFields = [];
    if ($primaryKey !== '' && !preg_match('/^object\s*type$/i', $primaryKey)) {
        foreach (preg_split('/\s*,\s*/', $primaryKey) ?: [] as $field) {
            $field = trim($field);
            if ($field !== '') {
                $keyFields[] = $field;
            }
        }
    }

    $summaryParts = array_values(array_filter([
        $description !== '' ? $description : null,
        $label !== '' && $label !== $description ? 'Nome funcional: ' . $label . '.' : null,
        $objectType !== '' ? 'Object Type (ObjType): ' . $objectType . '.' : null,
        $keyFields !== [] ? 'Chave: ' . implode(', ', $keyFields) . '.' : null,
    ]));

    $entries[] = [
        'id' => mb_strtolower($table),
        'title' => $label !== '' ? $label : $table,
        'object_type' => 'table',
        'table_name' => $table,
        'module' => 'SAP B1 9.3',
        'summary' => implode(' ', $summaryParts),
        'sap_object_type' => $objectType,
        'primary_key' => $primaryKey,
        'key_fields' => $keyFields,
        'related' => [],
        'synonyms' => $synonyms,
        'source' => 'tables_sap_b1_9_3_xlsx',
    ];
}

usort($entries, static fn (array $a, array $b): int => strcmp($a['table_name'], $b['table_name']));

$payload = [
    'version' => 'sap-b1-9.3',
    'generated_at' => gmdate('c'),
    'source_file' => basename($source),
    'references' => [
        'https://community.sap.com/t5/enterprise-resource-planning-blog-posts-by-members/sap-business-one-tables/ba-p/13280709',
        'https://help.sap.com/docs/SAP_BUSINESS_ONE/83196e2e80d941d798668e7c34568832/5a96195057e845719865ac24e46b9a95.html?locale=pt-BR&version=9.3',
    ],
    'count' => count($entries),
    'entries' => $entries,
];

$json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
if ($json === false || file_put_contents($target, $json . PHP_EOL) === false) {
    fwrite(STDERR, "Falha ao gravar {$target}\n");
    exit(1);
}

echo 'Importadas ' . count($entries) . ' tabelas em ' . $target . PHP_EOL;
