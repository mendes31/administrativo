<?php

declare(strict_types=1);

namespace App\adms\Controllers\databaseSchema;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\DatabaseSchemaRepository;
use App\adms\Views\Services\LoadViewService;

class ViewDatabaseTable
{
    private array|string|null $data = null;

    /**
     * Converte segmento da URL para nome real da tabela MySQL.
     * ClearUrl remove '_' da rota; usamos '-' no link e restauramos aqui.
     */
    private function resolveTableName(string $raw): string
    {
        $raw = trim(rawurldecode($raw));
        if ($raw === '' && isset($_GET['table'])) {
            $raw = trim((string) $_GET['table']);
        }
        if ($raw === '') {
            return '';
        }

        if (str_contains($raw, '-')) {
            return str_replace('-', '_', $raw);
        }

        return $raw;
    }

    public function index(string $table = ''): void
    {
        $table = $this->resolveTableName($table);

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['refresh_table_detail'])) {
            $this->handleRefreshTableDetail($table);

            return;
        }

        $repo = new DatabaseSchemaRepository();

        if (!$repo->isValidTableIdentifier($table) || !$repo->tableExists($table)) {
            $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">Tabela não encontrada no catálogo. Atualize o catálogo na biblioteca.</div>';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-database-tables');
            exit;
        }

        $hasDetail = $repo->hasTableDetailCache($table);
        $catalogRow = $this->findCatalogRow($repo, $table);
        $this->data['table'] = $repo->getTableSummary($table)
            ?? $catalogRow
            ?? ['table_name' => $table, 'module' => $repo->resolveModule($table)];
        $this->data['columns'] = $hasDetail ? $repo->getTableColumnsDetailed($table) : [];
        $this->data['indexes'] = $hasDetail ? $repo->getTableIndexes($table) : [];
        $this->data['foreign_keys'] = $hasDetail ? $repo->getTableForeignKeys($table) : [];
        $this->data['create_ddl'] = $hasDetail ? (string) ($repo->getCreateTableDdl($table) ?? '') : '';
        $this->data['database_name'] = $repo->getDatabaseName();
        $this->data['table_name'] = $table;
        $this->data['column_count'] = $hasDetail
            ? count($this->data['columns'])
            : (int) ($catalogRow['column_count'] ?? 0);
        $this->data['index_count'] = $hasDetail
            ? count($this->data['indexes'])
            : (int) ($catalogRow['index_count'] ?? 0);
        $this->data['detail_updated_at'] = $repo->getTableDetailUpdatedAt($table);
        $this->data['detail_empty'] = !$hasDetail;
        $this->data['catalog_updated_at'] = $repo->getCatalogUpdatedAt();

        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements([
            'title_head' => 'Tabela — ' . $table,
            'menu' => 'list-database-tables',
            'buttonPermission' => ['ListDatabaseTables', 'ViewDatabaseTable'],
        ]));

        (new LoadViewService('adms/Views/databaseSchema/view', $this->data))->loadView();
    }

    private function handleRefreshTableDetail(string $table): void
    {
        $pageLayoutService = new PageLayoutService();
        $layout = $pageLayoutService->configurePageElements([
            'title_head' => 'Tabela — ' . $table,
            'menu' => 'list-database-tables',
            'buttonPermission' => ['ViewDatabaseTable'],
        ]);
        if (!in_array('ViewDatabaseTable', $layout['menuPermission'] ?? [], true)) {
            $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">Sem permissão para atualizar esta tabela.</div>';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-database-tables');
            exit;
        }

        set_time_limit(60);
        $redirect = $_ENV['URL_ADM'] . 'view-database-table/' . rawurlencode(str_replace('_', '-', $table));

        try {
            (new DatabaseSchemaRepository())->refreshTableDetailCache($table);
            $_SESSION['msg'] = '<div class="alert alert-success" role="alert">Detalhes da tabela <code>'
                . htmlspecialchars($table) . '</code> atualizados com sucesso.</div>';
        } catch (\Throwable $e) {
            $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">Falha ao atualizar os detalhes da tabela.</div>';
        }

        header('Location: ' . $redirect);
        exit;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function findCatalogRow(DatabaseSchemaRepository $repo, string $table): ?array
    {
        foreach ($repo->listTables() as $row) {
            if ((string) ($row['table_name'] ?? '') === $table) {
                return $row;
            }
        }

        return null;
    }
}
