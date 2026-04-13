<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Cadastro de turnos de trabalho (jornada única, sem dias da semana).
 * Registra páginas do CRUD; matriz de permissões inicia com permission = 0 em todos os níveis (páginas privadas).
 */
final class CreateAdmsWorkShiftsAndPages extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('adms_work_shifts')) {
            $this->table('adms_work_shifts')
                ->addColumn('description', 'string', ['limit' => 255, 'null' => false])
                ->addColumn('entry_1', 'time', ['null' => true])
                ->addColumn('exit_1', 'time', ['null' => true])
                ->addColumn('entry_2', 'time', ['null' => true])
                ->addColumn('exit_2', 'time', ['null' => true])
                ->addColumn('entry_3', 'time', ['null' => true])
                ->addColumn('exit_3', 'time', ['null' => true])
                ->addColumn('overtime_tolerance_minutes', 'smallinteger', [
                    'signed' => false,
                    'default' => 0,
                    'null' => false,
                    'comment' => 'Tolerância para extras (minutos)',
                ])
                ->addColumn('absence_tolerance_minutes', 'smallinteger', [
                    'signed' => false,
                    'default' => 0,
                    'null' => false,
                    'comment' => 'Tolerância para faltas (minutos)',
                ])
                ->addColumn('total_minutes', 'smallinteger', [
                    'signed' => false,
                    'default' => 0,
                    'null' => false,
                    'comment' => 'Carga horária líquida calculada (minutos)',
                ])
                ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
                ->addColumn('updated_at', 'timestamp', [
                    'default' => 'CURRENT_TIMESTAMP',
                    'update' => 'CURRENT_TIMESTAMP',
                ])
                ->create();
        }

        if (!$this->hasTable('adms_pages')) {
            return;
        }

        $g = $this->fetchRow("SELECT id FROM adms_groups_pages WHERE name = 'Gestão de Pessoas' LIMIT 1");
        if (!$g) {
            $g = $this->fetchRow("SELECT id FROM adms_groups_pages WHERE name = 'Departamento' LIMIT 1");
        }
        if (!$g) {
            return;
        }
        $gid = (int) $g['id'];
        $now = date('Y-m-d H:i:s');

        $pages = [
            ['Listar Turnos de Trabalho', 'ListWorkShifts', 'list-work-shifts', 'workShifts', 'Listagem de turnos de trabalho.'],
            ['Cadastrar Turno de Trabalho', 'CreateWorkShift', 'create-work-shift', 'workShifts', 'Cadastro de turno de trabalho.'],
            ['Visualizar Turno de Trabalho', 'ViewWorkShift', 'view-work-shift', 'workShifts', 'Visualização de turno de trabalho.'],
            ['Editar Turno de Trabalho', 'UpdateWorkShift', 'update-work-shift', 'workShifts', 'Edição de turno de trabalho.'],
            ['Apagar Turno de Trabalho', 'DeleteWorkShift', 'delete-work-shift', 'workShifts', 'Exclusão de turno de trabalho.'],
        ];

        $newIds = [];
        foreach ($pages as [$name, $controller, $url, $dir, $obs]) {
            $ex = $this->fetchRow('SELECT id FROM adms_pages WHERE controller = ' . $this->q($controller) . ' LIMIT 1');
            if ($ex) {
                $newIds[] = (int) $ex['id'];
                continue;
            }
            $this->table('adms_pages')->insert([
                'name' => $name,
                'controller' => $controller,
                'controller_url' => $url,
                'directory' => $dir,
                'obs' => $obs,
                'public_page' => 0,
                'default_page' => 0,
                'page_status' => 1,
                'adms_packages_page_id' => 1,
                'adms_groups_page_id' => $gid,
                'created_at' => $now,
                'updated_at' => $now,
            ])->save();
            $row = $this->fetchRow('SELECT LAST_INSERT_ID() AS id');
            $newIds[] = (int) ($row['id'] ?? 0);
        }

        if (!$this->hasTable('adms_access_levels_pages')) {
            return;
        }

        foreach ($newIds as $pid) {
            if ($pid <= 0) {
                continue;
            }
            $this->execute(
                "INSERT IGNORE INTO adms_access_levels_pages (permission, adms_access_level_id, adms_page_id, created_at, updated_at)
                 SELECT 0, al.id, {$pid}, '{$now}', '{$now}'
                 FROM adms_access_levels al"
            );
        }
    }

    public function down(): void
    {
        if ($this->hasTable('adms_pages')) {
            $controllers = ['ListWorkShifts', 'CreateWorkShift', 'ViewWorkShift', 'UpdateWorkShift', 'DeleteWorkShift'];
            foreach ($controllers as $c) {
                $row = $this->fetchRow('SELECT id FROM adms_pages WHERE controller = ' . $this->q($c) . ' LIMIT 1');
                if (!$row) {
                    continue;
                }
                $pid = (int) $row['id'];
                if ($this->hasTable('adms_access_levels_pages')) {
                    $this->execute("DELETE FROM adms_access_levels_pages WHERE adms_page_id = {$pid}");
                }
                $this->execute("DELETE FROM adms_pages WHERE id = {$pid} LIMIT 1");
            }
        }
        if ($this->hasTable('adms_work_shifts')) {
            $this->table('adms_work_shifts')->drop()->save();
        }
    }

    private function q(string $s): string
    {
        return "'" . str_replace("'", "''", $s) . "'";
    }
}
