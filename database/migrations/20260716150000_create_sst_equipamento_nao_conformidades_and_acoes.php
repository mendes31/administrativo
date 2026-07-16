<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateSstEquipamentoNaoConformidadesAndAcoes extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('adms_sst_equipamento_nao_conformidades')) {
            $this->table('adms_sst_equipamento_nao_conformidades')
                ->addColumn('codigo', 'string', ['limit' => 20, 'null' => false])
                ->addColumn('adms_sst_equipamento_vistoria_id', 'integer', ['null' => false, 'signed' => false])
                ->addColumn('adms_sst_equipamento_id', 'integer', ['null' => false, 'signed' => false])
                ->addColumn('adms_sst_equipamento_vistoria_resposta_id', 'integer', ['null' => true, 'signed' => false])
                ->addColumn('descricao', 'string', ['limit' => 255, 'null' => false])
                ->addColumn('observacao', 'text', ['null' => true])
                ->addColumn('status', 'enum', [
                    'values' => ['Aberta', 'Em tratamento', 'Encerrada', 'Cancelada'],
                    'default' => 'Aberta',
                ])
                ->addColumn('encerrada_em', 'datetime', ['null' => true])
                ->addColumn('encerrada_por_adms_user_id', 'integer', ['null' => true, 'signed' => false])
                ->addColumn('encerrada_por_acao_id', 'integer', ['null' => true, 'signed' => false])
                ->addColumn('created_by', 'integer', ['null' => true, 'signed' => false])
                ->addColumn('updated_by', 'integer', ['null' => true, 'signed' => false])
                ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
                ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP'])
                ->addIndex(['codigo'], ['unique' => true])
                ->addIndex(['adms_sst_equipamento_vistoria_id'])
                ->addIndex(['adms_sst_equipamento_id'])
                ->addIndex(['status'])
                ->addForeignKey('adms_sst_equipamento_vistoria_id', 'adms_sst_equipamento_vistorias', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
                ->addForeignKey('adms_sst_equipamento_id', 'adms_sst_equipamentos', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
                ->addForeignKey('adms_sst_equipamento_vistoria_resposta_id', 'adms_sst_equipamento_vistoria_respostas', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
                ->addForeignKey('encerrada_por_adms_user_id', 'adms_users', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
                ->addForeignKey('created_by', 'adms_users', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
                ->addForeignKey('updated_by', 'adms_users', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
                ->create();
        }

        if (!$this->hasTable('adms_sst_equipamento_acoes_corretivas')) {
            $this->table('adms_sst_equipamento_acoes_corretivas')
                ->addColumn('codigo', 'string', ['limit' => 20, 'null' => false])
                ->addColumn('adms_sst_equipamento_nao_conformidade_id', 'integer', ['null' => false, 'signed' => false])
                ->addColumn('titulo', 'string', ['limit' => 255, 'null' => false])
                ->addColumn('descricao', 'text', ['null' => true])
                ->addColumn('responsavel_adms_user_id', 'integer', ['null' => true, 'signed' => false])
                ->addColumn('prazo', 'date', ['null' => true])
                ->addColumn('data_conclusao', 'date', ['null' => true])
                ->addColumn('status', 'enum', [
                    'values' => ['Pendente', 'Em andamento', 'Concluído', 'Cancelado'],
                    'default' => 'Pendente',
                ])
                ->addColumn('observacoes', 'text', ['null' => true])
                ->addColumn('created_by', 'integer', ['null' => true, 'signed' => false])
                ->addColumn('updated_by', 'integer', ['null' => true, 'signed' => false])
                ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
                ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP'])
                ->addIndex(['codigo'], ['unique' => true])
                ->addIndex(['adms_sst_equipamento_nao_conformidade_id'])
                ->addIndex(['status'])
                ->addIndex(['prazo'])
                ->addForeignKey('adms_sst_equipamento_nao_conformidade_id', 'adms_sst_equipamento_nao_conformidades', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
                ->addForeignKey('responsavel_adms_user_id', 'adms_users', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
                ->addForeignKey('created_by', 'adms_users', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
                ->addForeignKey('updated_by', 'adms_users', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
                ->create();
        }

        // FK adiada: encerrada_por_acao_id → ações (tabela criada depois)
        if ($this->hasTable('adms_sst_equipamento_nao_conformidades')
            && $this->hasTable('adms_sst_equipamento_acoes_corretivas')
        ) {
            $fkExists = $this->fetchRow(
                "SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE
                 WHERE TABLE_SCHEMA = DATABASE()
                   AND TABLE_NAME = 'adms_sst_equipamento_nao_conformidades'
                   AND COLUMN_NAME = 'encerrada_por_acao_id'
                   AND REFERENCED_TABLE_NAME = 'adms_sst_equipamento_acoes_corretivas'
                 LIMIT 1"
            );
            if (!$fkExists) {
                $this->execute(
                    'ALTER TABLE adms_sst_equipamento_nao_conformidades
                     ADD CONSTRAINT adms_sst_equipamento_nao_conformidades_encerrada_por_acao_id
                     FOREIGN KEY (encerrada_por_acao_id)
                     REFERENCES adms_sst_equipamento_acoes_corretivas (id)
                     ON DELETE SET NULL ON UPDATE CASCADE'
                );
            }
        }

        // Status Bloqueado no equipamento
        if ($this->hasTable('adms_sst_equipamentos')) {
            $this->execute(
                "ALTER TABLE adms_sst_equipamentos
                 MODIFY COLUMN status ENUM('Ativo','Inativo','Baixado','Bloqueado') NOT NULL DEFAULT 'Ativo'"
            );
        }

        $this->registerPages();
    }

    public function down(): void
    {
        if ($this->hasTable('adms_sst_equipamento_nao_conformidades')) {
            $this->table('adms_sst_equipamento_nao_conformidades')->dropForeignKey('encerrada_por_acao_id')->update();
        }
        if ($this->hasTable('adms_sst_equipamento_acoes_corretivas')) {
            $this->table('adms_sst_equipamento_acoes_corretivas')->drop()->save();
        }
        if ($this->hasTable('adms_sst_equipamento_nao_conformidades')) {
            $this->table('adms_sst_equipamento_nao_conformidades')->drop()->save();
        }
        if ($this->hasTable('adms_sst_equipamentos')) {
            $this->execute(
                "UPDATE adms_sst_equipamentos SET status = 'Inativo' WHERE status = 'Bloqueado'"
            );
            $this->execute(
                "ALTER TABLE adms_sst_equipamentos
                 MODIFY COLUMN status ENUM('Ativo','Inativo','Baixado') NOT NULL DEFAULT 'Ativo'"
            );
        }
        if ($this->hasTable('adms_pages')) {
            $this->execute(
                "DELETE FROM adms_pages WHERE controller IN (
                    'SstListEquipamentoNaoConformidades',
                    'SstViewEquipamentoNaoConformidade',
                    'SstCreateEquipamentoAcaoCorretiva',
                    'SstUpdateEquipamentoAcaoCorretiva',
                    'SstEncerrarEquipamentoNaoConformidade'
                )"
            );
        }
        $this->bumpMenuPermissionCache();
    }

    private function registerPages(): void
    {
        if (!$this->hasTable('adms_pages')) {
            return;
        }

        $group = $this->fetchRow(
            "SELECT id FROM adms_groups_pages WHERE name = 'Segurança e Medicina' LIMIT 1"
        );
        $groupId = $group ? (int) $group['id'] : 42;

        $pages = [
            [
                'name' => 'Listar NCs equipamentos SST',
                'controller' => 'SstListEquipamentoNaoConformidades',
                'controller_url' => 'sst-list-equipamento-nao-conformidades',
                'obs' => 'Lista não conformidades geradas por vistorias de equipamentos.',
                'ref' => 'SstMinhasEquipamentoVistorias',
            ],
            [
                'name' => 'Ver NC equipamento SST',
                'controller' => 'SstViewEquipamentoNaoConformidade',
                'controller_url' => 'sst-view-equipamento-nao-conformidade',
                'obs' => 'Detalhe da NC com ações corretivas.',
                'ref' => 'SstExecuteEquipamentoVistoria',
            ],
            [
                'name' => 'Criar ação corretiva equipamento SST',
                'controller' => 'SstCreateEquipamentoAcaoCorretiva',
                'controller_url' => 'sst-create-equipamento-acao-corretiva',
                'obs' => 'Cadastra ação corretiva vinculada à NC.',
                'ref' => 'SstExecuteEquipamentoVistoria',
            ],
            [
                'name' => 'Editar ação corretiva equipamento SST',
                'controller' => 'SstUpdateEquipamentoAcaoCorretiva',
                'controller_url' => 'sst-update-equipamento-acao-corretiva',
                'obs' => 'Atualiza ação corretiva da NC.',
                'ref' => 'SstExecuteEquipamentoVistoria',
            ],
            [
                'name' => 'Encerrar NC equipamento SST',
                'controller' => 'SstEncerrarEquipamentoNaoConformidade',
                'controller_url' => 'sst-encerrar-equipamento-nao-conformidade',
                'obs' => 'Encerra NC mediante ação corretiva concluída (vistoria permanece Não conforme).',
                'ref' => 'SstExecuteEquipamentoVistoria',
            ],
        ];

        $now = date('Y-m-d H:i:s');
        foreach ($pages as $pageDef) {
            $exists = $this->fetchRow(
                "SELECT id FROM adms_pages WHERE controller = '" . addslashes($pageDef['controller']) . "' LIMIT 1"
            );
            if ($exists) {
                continue;
            }

            $this->table('adms_pages')->insert([
                'name' => $pageDef['name'],
                'controller' => $pageDef['controller'],
                'controller_url' => $pageDef['controller_url'],
                'directory' => 'sst',
                'obs' => $pageDef['obs'],
                'public_page' => 0,
                'default_page' => 0,
                'page_status' => 1,
                'adms_packages_page_id' => 1,
                'adms_groups_page_id' => $groupId,
                'created_at' => $now,
                'updated_at' => $now,
            ])->save();

            if (!$this->hasTable('adms_access_levels_pages')) {
                continue;
            }

            $page = $this->fetchRow(
                "SELECT id FROM adms_pages WHERE controller = '" . addslashes($pageDef['controller']) . "' LIMIT 1"
            );
            $ref = $this->fetchRow(
                "SELECT id FROM adms_pages WHERE controller = '" . addslashes($pageDef['ref']) . "' LIMIT 1"
            );
            if (!$page || !$ref) {
                continue;
            }

            $pageId = (int) $page['id'];
            $refPageId = (int) $ref['id'];

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

        $this->bumpMenuPermissionCache();
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
