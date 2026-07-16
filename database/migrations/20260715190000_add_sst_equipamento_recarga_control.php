<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Controle de recarga por tipo + histórico de recargas do equipamento.
 */
final class AddSstEquipamentoRecargaControl extends AbstractMigration
{
    public function up(): void
    {
        if ($this->hasTable('adms_sst_equipamento_tipos')) {
            $tipos = $this->table('adms_sst_equipamento_tipos');
            if (!$tipos->hasColumn('controla_recarga')) {
                $tipos->addColumn('controla_recarga', 'boolean', [
                    'default' => false,
                    'null' => false,
                    'after' => 'prefixo',
                    'comment' => '1 = exige controle de recarga/validade de carga',
                ])->update();
            }
            if (!$tipos->hasColumn('validade_recarga_meses')) {
                $tipos->addColumn('validade_recarga_meses', 'integer', [
                    'null' => true,
                    'signed' => false,
                    'default' => 12,
                    'after' => 'controla_recarga',
                    'comment' => 'Meses para calcular próxima recarga (padrão 12)',
                ])->update();
            }

            $this->execute(
                "UPDATE adms_sst_equipamento_tipos
                 SET controla_recarga = 1, validade_recarga_meses = 12
                 WHERE codigo = 'EXTINTOR'"
            );
        }

        if (!$this->hasTable('adms_sst_equipamento_recargas')) {
            $this->table('adms_sst_equipamento_recargas')
                ->addColumn('adms_sst_equipamento_id', 'integer', ['null' => false, 'signed' => false])
                ->addColumn('tipo_evento', 'enum', [
                    'values' => ['Recarga', 'Teste hidrostático', 'Manutenção', 'Outro'],
                    'default' => 'Recarga',
                    'null' => false,
                ])
                ->addColumn('data_recarga', 'date', ['null' => false])
                ->addColumn('data_proxima_recarga', 'date', ['null' => true])
                ->addColumn('empresa', 'string', ['limit' => 150, 'null' => true])
                ->addColumn('numero_documento', 'string', ['limit' => 80, 'null' => true])
                ->addColumn('observacao', 'text', ['null' => true])
                ->addColumn('created_by', 'integer', ['null' => true, 'signed' => false])
                ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
                ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP'])
                ->addIndex(['adms_sst_equipamento_id'])
                ->addIndex(['data_proxima_recarga'])
                ->addForeignKey('adms_sst_equipamento_id', 'adms_sst_equipamentos', 'id', [
                    'delete' => 'CASCADE',
                    'update' => 'CASCADE',
                ])
                ->addForeignKey('created_by', 'adms_users', 'id', [
                    'delete' => 'SET_NULL',
                    'update' => 'CASCADE',
                ])
                ->create();
        }

        // Histórico inicial a partir das datas já cadastradas no equipamento (tipos que controlam recarga)
        if ($this->hasTable('adms_sst_equipamentos') && $this->hasTable('adms_sst_equipamento_recargas')) {
            $rows = $this->fetchAll(
                "SELECT e.id, e.data_recarga, e.data_proxima_recarga
                 FROM adms_sst_equipamentos e
                 INNER JOIN adms_sst_equipamento_tipos t ON t.id = e.adms_sst_equipamento_tipo_id
                 WHERE t.controla_recarga = 1
                   AND e.data_recarga IS NOT NULL
                   AND NOT EXISTS (
                       SELECT 1 FROM adms_sst_equipamento_recargas r
                       WHERE r.adms_sst_equipamento_id = e.id
                   )"
            );
            $now = date('Y-m-d H:i:s');
            foreach ($rows as $row) {
                $this->table('adms_sst_equipamento_recargas')->insert([
                    'adms_sst_equipamento_id' => (int) $row['id'],
                    'tipo_evento' => 'Recarga',
                    'data_recarga' => $row['data_recarga'],
                    'data_proxima_recarga' => $row['data_proxima_recarga'] ?? null,
                    'empresa' => null,
                    'numero_documento' => null,
                    'observacao' => 'Registro inicial a partir do cadastro do equipamento.',
                    'created_at' => $now,
                    'updated_at' => $now,
                ])->save();
            }
        }

        $this->registerPage();
        $this->syncPermissions();
        $this->bumpMenuPermissionCache();
    }

    public function down(): void
    {
        if ($this->hasTable('adms_pages')) {
            $this->execute("DELETE FROM adms_pages WHERE controller = 'SstRegisterEquipamentoRecarga'");
        }
        if ($this->hasTable('adms_sst_equipamento_recargas')) {
            $this->table('adms_sst_equipamento_recargas')->drop()->save();
        }
        if ($this->hasTable('adms_sst_equipamento_tipos')) {
            $t = $this->table('adms_sst_equipamento_tipos');
            if ($t->hasColumn('validade_recarga_meses')) {
                $t->removeColumn('validade_recarga_meses')->update();
            }
            if ($t->hasColumn('controla_recarga')) {
                $t->removeColumn('controla_recarga')->update();
            }
        }
        $this->bumpMenuPermissionCache();
    }

    private function registerPage(): void
    {
        if (!$this->hasTable('adms_pages')) {
            return;
        }
        $exists = $this->fetchRow(
            "SELECT id FROM adms_pages WHERE controller = 'SstRegisterEquipamentoRecarga' LIMIT 1"
        );
        if ($exists) {
            return;
        }
        $now = date('Y-m-d H:i:s');
        $this->table('adms_pages')->insert([
            'name' => 'Registrar recarga equipamento SST',
            'controller' => 'SstRegisterEquipamentoRecarga',
            'controller_url' => 'sst-register-equipamento-recarga',
            'directory' => 'sst',
            'obs' => 'Registra recarga/manutenção e recalcula próxima data.',
            'public_page' => 0,
            'default_page' => 0,
            'page_status' => 1,
            'adms_packages_page_id' => 1,
            'adms_groups_page_id' => 41,
            'created_at' => $now,
            'updated_at' => $now,
        ])->save();
    }

    private function syncPermissions(): void
    {
        if (!$this->hasTable('adms_pages') || !$this->hasTable('adms_access_levels_pages')) {
            return;
        }
        $page = $this->fetchRow(
            "SELECT id FROM adms_pages WHERE controller = 'SstRegisterEquipamentoRecarga' LIMIT 1"
        );
        if (!$page) {
            return;
        }
        $pageId = (int) $page['id'];
        $ref = $this->fetchRow("SELECT id FROM adms_pages WHERE controller = 'SstUpdateEquipamento' LIMIT 1");
        if (!$ref) {
            $ref = $this->fetchRow("SELECT id FROM adms_pages WHERE controller = 'SstListEquipamentos' LIMIT 1");
        }
        if (!$ref) {
            return;
        }
        $refPageId = (int) $ref['id'];
        $now = date('Y-m-d H:i:s');

        $this->execute(
            "INSERT IGNORE INTO adms_access_levels_pages (permission, adms_access_level_id, adms_page_id, created_at, updated_at)
             SELECT 0, al.id, {$pageId}, '{$now}', '{$now}'
             FROM adms_access_levels al"
        );
        $this->execute(
            "INSERT INTO adms_access_levels_pages (permission, adms_access_level_id, adms_page_id, created_at, updated_at)
             SELECT 1, alp.adms_access_level_id, {$pageId}, '{$now}', '{$now}'
             FROM adms_access_levels_pages alp
             WHERE alp.adms_page_id = {$refPageId}
               AND alp.permission = 1
             ON DUPLICATE KEY UPDATE permission = 1, updated_at = '{$now}'"
        );
    }

    private function bumpMenuPermissionCache(): void
    {
        $dir = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'storage'
            . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'system';
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        @file_put_contents($dir . DIRECTORY_SEPARATOR . 'menu_permission_version.txt', (string) time());
    }
}
