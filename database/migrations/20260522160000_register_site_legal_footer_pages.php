<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Páginas informativas do rodapé: Termos de Uso e Política de Privacidade (termos LGPD tipo site).
 */
final class RegisterSiteLegalFooterPages extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('adms_pages')) {
            return;
        }

        $now = date('Y-m-d H:i:s');

        $this->registerPage(
            'TermosDeUso',
            'termos-de-uso',
            'Termos de Uso (rodapé)',
            'Exibe o termo LGPD tipo site cujo título contém o texto configurado em LGPD_TERMO_USO_TITULO.',
            $now
        );

        $this->registerPage(
            'PoliticaPrivacidade',
            'politica-privacidade',
            'Política de Privacidade (rodapé)',
            'Exibe o termo LGPD tipo site cujo título contém LGPD_POLITICA_PRIVACIDADE_TITULO.',
            $now
        );

        if ($this->hasTable('adms_access_levels_pages') && $this->hasTable('adms_access_levels')) {
            $hasDefaultPage = $this->table('adms_pages')->hasColumn('default_page');
            $pageCond = $hasDefaultPage
                ? '(p.public_page = 1 OR p.default_page = 1)'
                : '(p.public_page = 1)';

            $this->execute(<<<SQL
INSERT INTO adms_access_levels_pages (permission, adms_access_level_id, adms_page_id, created_at, updated_at)
SELECT 1, al.id, p.id, NOW(), NOW()
FROM adms_access_levels al
CROSS JOIN adms_pages p
WHERE p.page_status = 1
  AND p.controller IN ('TermosDeUso', 'PoliticaPrivacidade')
  AND {$pageCond}
ON DUPLICATE KEY UPDATE
  permission = 1,
  updated_at = NOW()
SQL);
        }
    }

    public function down(): void
    {
        if (!$this->hasTable('adms_pages')) {
            return;
        }

        foreach (['TermosDeUso', 'PoliticaPrivacidade'] as $controller) {
            $row = $this->fetchRow(
                "SELECT id FROM adms_pages WHERE controller = '" . addslashes($controller) . "' LIMIT 1"
            );
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

    private function registerPage(
        string $controller,
        string $controllerUrl,
        string $name,
        string $obs,
        string $now
    ): void {
        $exists = $this->fetchRow(
            "SELECT id FROM adms_pages WHERE controller = '" . addslashes($controller) . "' LIMIT 1"
        );
        if ($exists) {
            return;
        }

        $row = [
            'name' => $name,
            'controller' => $controller,
            'controller_url' => $controllerUrl,
            'directory' => 'legal',
            'obs' => $obs,
            'public_page' => 0,
            'page_status' => 1,
            'adms_packages_page_id' => 1,
            'adms_groups_page_id' => 31,
            'created_at' => $now,
            'updated_at' => $now,
        ];

        if ($this->table('adms_pages')->hasColumn('default_page')) {
            $row['default_page'] = 1;
        }

        $this->table('adms_pages')->insert($row)->save();
    }
}
