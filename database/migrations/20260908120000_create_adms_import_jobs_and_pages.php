<?php

declare(strict_types=1);

use App\adms\Models\Repository\MenuPermissionUserRepository;
use Phinx\Db\Adapter\MysqlAdapter;
use Phinx\Migration\AbstractMigration;

/**
 * Central de Importações: jobs persistentes + páginas ACL (permission=0).
 */
final class CreateAdmsImportJobsAndPages extends AbstractMigration
{
    /** @var list<array{name: string, controller: string, controller_url: string, obs: string}> */
    private const PAGES = [
        [
            'name' => 'Central de Importações',
            'controller' => 'ImportCenter',
            'controller_url' => 'import-center',
            'obs' => 'Hub de importações por perfil declarado (usuários, departamentos, cargos).',
        ],
        [
            'name' => 'Enviar importação (Central)',
            'controller' => 'ImportCenterCreate',
            'controller_url' => 'import-center-create',
            'obs' => 'Upload da planilha e definição da operação (inserir / atualizar / upsert / simulação).',
        ],
        [
            'name' => 'Mapear colunas da importação',
            'controller' => 'ImportCenterMap',
            'controller_url' => 'import-center-map',
            'obs' => 'Associação campo do sistema × coluna do arquivo e execução do job.',
        ],
        [
            'name' => 'Resultado da importação',
            'controller' => 'ImportCenterView',
            'controller_url' => 'import-center-view',
            'obs' => 'Resumo e relatório por linha do job de importação.',
        ],
        [
            'name' => 'Modelo CSV da importação',
            'controller' => 'ImportCenterTemplate',
            'controller_url' => 'import-center-template',
            'obs' => 'Download do CSV modelo do perfil selecionado.',
        ],
        [
            'name' => 'Importar usuários (Central)',
            'controller' => 'ImportCenterUsers',
            'controller_url' => 'import-center-users',
            'obs' => 'Permissão do tipo Usuários / colaboradores na Central de Importações.',
        ],
        [
            'name' => 'Importar departamentos (Central)',
            'controller' => 'ImportCenterDepartments',
            'controller_url' => 'import-center-departments',
            'obs' => 'Permissão do tipo Departamentos / setores na Central de Importações.',
        ],
        [
            'name' => 'Importar cargos (Central)',
            'controller' => 'ImportCenterPositions',
            'controller_url' => 'import-center-positions',
            'obs' => 'Permissão do tipo Cargos na Central de Importações.',
        ],
    ];

    public function up(): void
    {
        if (!$this->hasTable('adms_import_jobs')) {
            $this->table('adms_import_jobs')
                ->addColumn('profile_key', 'string', ['limit' => 50, 'null' => false])
                ->addColumn('operation', 'string', ['limit' => 20, 'null' => false, 'default' => 'upsert'])
                ->addColumn('empty_policy', 'string', ['limit' => 10, 'null' => false, 'default' => 'skip'])
                ->addColumn('dry_run', 'boolean', ['null' => false, 'default' => false])
                ->addColumn('status', 'string', ['limit' => 20, 'null' => false, 'default' => 'uploaded'])
                ->addColumn('original_filename', 'string', ['limit' => 255, 'null' => true])
                ->addColumn('stored_path', 'string', ['limit' => 500, 'null' => true])
                ->addColumn('delimiter', 'string', ['limit' => 8, 'null' => false, 'default' => ';'])
                ->addColumn('headers_json', 'text', ['null' => true])
                ->addColumn('mapping_json', 'text', ['null' => true])
                ->addColumn('key_field', 'string', ['limit' => 50, 'null' => true])
                ->addColumn('stats_json', 'text', ['null' => true])
                ->addColumn('report_json', 'text', ['limit' => MysqlAdapter::TEXT_MEDIUM, 'null' => true])
                ->addColumn('error_message', 'text', ['null' => true])
                ->addColumn('created_by', 'integer', ['null' => true, 'signed' => true])
                ->addColumn('started_at', 'timestamp', ['null' => true])
                ->addColumn('finished_at', 'timestamp', ['null' => true])
                ->addColumn('created_at', 'timestamp', ['null' => true])
                ->addColumn('updated_at', 'timestamp', ['null' => true])
                ->addIndex(['profile_key'])
                ->addIndex(['status'])
                ->addIndex(['created_by'])
                ->create();
        }

        if (!$this->hasTable('adms_pages') || !$this->hasTable('adms_groups_pages')) {
            return;
        }

        $now = date('Y-m-d H:i:s');
        $existsGroup = $this->fetchRow("SELECT id FROM adms_groups_pages WHERE name = 'Administração - Importações' LIMIT 1");
        if (!$existsGroup) {
            $this->execute(
                "INSERT INTO adms_groups_pages (name, obs, created_at)
                 VALUES ('Administração - Importações', 'Central de importações por perfil (não substitui ImportUsers)', '{$now}')"
            );
        }
        $groupRow = $this->fetchRow("SELECT id FROM adms_groups_pages WHERE name = 'Administração - Importações' LIMIT 1");
        if (!$groupRow) {
            return;
        }
        $gid = (int) $groupRow['id'];

        foreach (self::PAGES as $p) {
            $exists = $this->fetchRow("SELECT id FROM adms_pages WHERE controller = '{$p['controller']}' LIMIT 1");
            if ($exists) {
                continue;
            }
            $this->table('adms_pages')->insert([
                'name' => $p['name'],
                'controller' => $p['controller'],
                'controller_url' => $p['controller_url'],
                'directory' => 'imports',
                'obs' => $p['obs'],
                'public_page' => 0,
                'default_page' => 0,
                'page_status' => 1,
                'adms_packages_page_id' => 1,
                'adms_groups_page_id' => $gid,
                'created_at' => $now,
                'updated_at' => $now,
            ])->save();
        }

        if ($this->hasTable('adms_access_levels_pages')) {
            foreach (self::PAGES as $p) {
                $row = $this->fetchRow("SELECT id FROM adms_pages WHERE controller = '{$p['controller']}' LIMIT 1");
                if (!$row) {
                    continue;
                }
                $pid = (int) $row['id'];
                $this->execute(
                    "INSERT IGNORE INTO adms_access_levels_pages (permission, adms_access_level_id, adms_page_id, created_at, updated_at)
                     SELECT 0, al.id, {$pid}, '{$now}', '{$now}'
                     FROM adms_access_levels al"
                );
            }
        }

        MenuPermissionUserRepository::bumpGlobalPermissionCacheVersion();
    }

    public function down(): void
    {
        if ($this->hasTable('adms_pages')) {
            foreach (self::PAGES as $p) {
                $row = $this->fetchRow("SELECT id FROM adms_pages WHERE controller = '{$p['controller']}' LIMIT 1");
                if (!$row) {
                    continue;
                }
                $pid = (int) $row['id'];
                if ($this->hasTable('adms_access_levels_pages')) {
                    $this->execute("DELETE FROM adms_access_levels_pages WHERE adms_page_id = {$pid}");
                }
                $this->execute("DELETE FROM adms_pages WHERE id = {$pid}");
            }
        }

        if ($this->hasTable('adms_groups_pages')) {
            $this->execute("DELETE FROM adms_groups_pages WHERE name = 'Administração - Importações'");
        }

        if ($this->hasTable('adms_import_jobs')) {
            $this->table('adms_import_jobs')->drop()->save();
        }

        MenuPermissionUserRepository::bumpGlobalPermissionCacheVersion();
    }
}
