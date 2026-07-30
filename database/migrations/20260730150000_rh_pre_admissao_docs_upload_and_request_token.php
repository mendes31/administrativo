<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Pré-admissão: token de solicitação de documentos ao candidato + metadados de arquivo.
 * Upload manual pelo RH e envio pelo candidato via link público (sem login).
 */
final class RhPreAdmissaoDocsUploadAndRequestToken extends AbstractMigration
{
    public function up(): void
    {
        if ($this->hasTable('rh_ofertas')) {
            $t = $this->table('rh_ofertas');
            if (!$t->hasColumn('docs_request_token')) {
                $t->addColumn('docs_request_token', 'string', [
                    'limit' => 64,
                    'null' => true,
                ])
                    ->addColumn('docs_requested_at', 'datetime', ['null' => true])
                    ->addColumn('docs_request_expires_at', 'datetime', ['null' => true])
                    ->addIndex(['docs_request_token'], [
                        'unique' => true,
                        'name' => 'uq_rh_ofertas_docs_request_token',
                    ])
                    ->update();
            }
        }

        if ($this->hasTable('rh_pre_admissao_documentos')) {
            $d = $this->table('rh_pre_admissao_documentos');
            if (!$d->hasColumn('arquivo_caminho')) {
                $d->addColumn('arquivo_caminho', 'string', ['limit' => 255, 'null' => true, 'after' => 'observacoes'])
                    ->addColumn('arquivo_nome_original', 'string', ['limit' => 255, 'null' => true, 'after' => 'arquivo_caminho'])
                    ->addColumn('arquivo_mime', 'string', ['limit' => 120, 'null' => true, 'after' => 'arquivo_nome_original'])
                    ->addColumn('arquivo_tamanho', 'integer', ['signed' => false, 'null' => true, 'after' => 'arquivo_mime'])
                    ->addColumn('uploaded_by', 'string', ['limit' => 20, 'null' => true, 'after' => 'arquivo_tamanho'])
                    ->addColumn('uploaded_at', 'datetime', ['null' => true, 'after' => 'uploaded_by'])
                    ->addColumn('uploaded_by_user_id', 'integer', ['signed' => false, 'null' => true, 'after' => 'uploaded_at'])
                    ->update();
            }
        }

        $this->registerPublicPage();

        if (class_exists(\App\adms\Models\Repository\MenuPermissionUserRepository::class)) {
            \App\adms\Models\Repository\MenuPermissionUserRepository::bumpGlobalPermissionCacheVersion();
        }
    }

    public function down(): void
    {
        if ($this->hasTable('adms_pages')) {
            $row = $this->fetchRow("SELECT id FROM adms_pages WHERE controller = 'RhPreAdmissaoDocsPublic' LIMIT 1");
            if ($row) {
                $pid = (int) $row['id'];
                if ($this->hasTable('adms_access_levels_pages')) {
                    $this->execute("DELETE FROM adms_access_levels_pages WHERE adms_page_id = {$pid}");
                }
                $this->execute("DELETE FROM adms_pages WHERE id = {$pid} LIMIT 1");
            }
            $dl = $this->fetchRow("SELECT id FROM adms_pages WHERE controller = 'RhPreAdmissaoDownloadDoc' LIMIT 1");
            if ($dl) {
                $pid = (int) $dl['id'];
                if ($this->hasTable('adms_access_levels_pages')) {
                    $this->execute("DELETE FROM adms_access_levels_pages WHERE adms_page_id = {$pid}");
                }
                $this->execute("DELETE FROM adms_pages WHERE id = {$pid} LIMIT 1");
            }
        }

        if ($this->hasTable('rh_pre_admissao_documentos')) {
            $d = $this->table('rh_pre_admissao_documentos');
            foreach ([
                'uploaded_by_user_id', 'uploaded_at', 'uploaded_by',
                'arquivo_tamanho', 'arquivo_mime', 'arquivo_nome_original', 'arquivo_caminho',
            ] as $col) {
                if ($d->hasColumn($col)) {
                    $d->removeColumn($col);
                }
            }
            $d->update();
        }

        if ($this->hasTable('rh_ofertas')) {
            $t = $this->table('rh_ofertas');
            if ($t->hasIndexByName('uq_rh_ofertas_docs_request_token')) {
                $t->removeIndexByName('uq_rh_ofertas_docs_request_token');
            }
            foreach (['docs_request_expires_at', 'docs_requested_at', 'docs_request_token'] as $col) {
                if ($t->hasColumn($col)) {
                    $t->removeColumn($col);
                }
            }
            $t->update();
        }
    }

    private function registerPublicPage(): void
    {
        if (!$this->hasTable('adms_pages')) {
            return;
        }

        $conn = $this->getAdapter()->getConnection();
        $now = date('Y-m-d H:i:s');

        $group = $this->fetchRow(
            "SELECT adms_groups_page_id FROM adms_pages WHERE controller = 'RhOfertasView' LIMIT 1"
        );
        $gid = (int) ($group['adms_groups_page_id'] ?? 0);
        if ($gid <= 0) {
            $group = $this->fetchRow(
                "SELECT adms_groups_page_id FROM adms_pages WHERE controller = 'RhVagas' LIMIT 1"
            );
            $gid = (int) ($group['adms_groups_page_id'] ?? 46);
        }

        $pages = [
            [
                'name' => 'Pré-admissão — envio de documentos (público)',
                'controller' => 'RhPreAdmissaoDocsPublic',
                'url' => 'pre-admissao-documentos',
                'obs' => 'Formulário público com token para o candidato anexar documentos de pré-admissão.',
                'public' => 1,
            ],
            [
                'name' => 'Download documento pré-admissão',
                'controller' => 'RhPreAdmissaoDownloadDoc',
                'url' => 'rh-pre-admissao-download-doc',
                'obs' => 'Download autenticado de arquivo de documento de pré-admissão.',
                'public' => 0,
            ],
        ];

        foreach ($pages as $p) {
            $exists = $this->fetchRow(
                'SELECT id FROM adms_pages WHERE controller = ' . $conn->quote($p['controller']) . ' LIMIT 1'
            );
            if ($exists) {
                continue;
            }
            $this->execute(
                'INSERT INTO adms_pages
                    (name, controller, controller_url, directory, obs, public_page, default_page, page_status,
                     adms_packages_page_id, adms_groups_page_id, created_at, updated_at)
                 VALUES ('
                . $conn->quote($p['name']) . ', '
                . $conn->quote($p['controller']) . ', '
                . $conn->quote($p['url']) . ', '
                . $conn->quote('rh') . ', '
                . $conn->quote($p['obs']) . ', '
                . (int) $p['public'] . ', 0, 1, 1, '
                . $gid . ', '
                . $conn->quote($now) . ', '
                . $conn->quote($now) . ')'
            );

            if ((int) $p['public'] === 1) {
                continue;
            }

            $new = $this->fetchRow(
                'SELECT id FROM adms_pages WHERE controller = ' . $conn->quote($p['controller']) . ' LIMIT 1'
            );
            $ref = $this->fetchRow("SELECT id FROM adms_pages WHERE controller = 'RhOfertasView' LIMIT 1");
            if (!$new || !$ref || !$this->hasTable('adms_access_levels_pages')) {
                continue;
            }
            $newId = (int) $new['id'];
            $refId = (int) $ref['id'];
            $this->execute(
                "INSERT INTO adms_access_levels_pages (permission, adms_access_level_id, adms_page_id, created_at, updated_at)
                 SELECT permission, adms_access_level_id, {$newId}, '{$now}', '{$now}'
                 FROM adms_access_levels_pages
                 WHERE adms_page_id = {$refId}"
            );
        }
    }
}
