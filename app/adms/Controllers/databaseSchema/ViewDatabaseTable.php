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
        $repo = new DatabaseSchemaRepository();

        if (!$repo->isValidTableIdentifier($table) || !$repo->tableExists($table)) {
            $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">Tabela não encontrada na base de dados do sistema.</div>';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-database-tables');
            exit;
        }

        $this->data['table'] = $repo->getTableSummary($table);
        $this->data['columns'] = $repo->getTableColumnsDetailed($table);
        $this->data['indexes'] = $repo->getTableIndexes($table);
        $this->data['foreign_keys'] = $repo->getTableForeignKeys($table);
        $this->data['create_ddl'] = $repo->getCreateTableDdl($table);
        $this->data['database_name'] = $repo->getDatabaseName();
        $this->data['table_name'] = $table;
        $this->data['column_count'] = count($this->data['columns']);
        $this->data['index_count'] = count($this->data['indexes']);

        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements([
            'title_head' => 'Tabela — ' . $table,
            'menu' => 'list-database-tables',
            'buttonPermission' => ['ListDatabaseTables', 'ViewDatabaseTable'],
        ]));

        (new LoadViewService('adms/Views/databaseSchema/view', $this->data))->loadView();
    }
}
