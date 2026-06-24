<?php

declare(strict_types=1);

use Phinx\Seed\AbstractSeed;

class SyncAccessLevelsPages extends AbstractSeed
{
    /**
     * Sincroniza automaticamente as permissões de páginas com os níveis de acesso.
     *
     * Insere pares (nível, página) em falta com INSERT IGNORE, sempre com permission = 0.
     * Páginas **privadas** (sem público nem padrão na matriz) permanecem 0 em todos os níveis até liberação manual.
     *
     * Em seguida, alinha com a migration {@see SyncPublicDefaultPagesPermissionsAllLevels}:
     * páginas ativas com `public_page = 1` ou `default_page = 1` recebem permission = 1 em **todos** os níveis.
     *
     * Nota: em bases já populadas com a regra antiga (super admin = 1 em todas as páginas), linhas existentes
     * não são alteradas por este seed; use migrações pontuais ou ajuste manual na matriz de permissões.
     *
     * @return void
     */
    public function run(): void
    {
        if (
            !$this->hasTable('adms_access_levels_pages')
            || !$this->hasTable('adms_pages')
            || !$this->hasTable('adms_access_levels')
        ) {
            return;
        }

        // Uma única operação em lote — evita milhares de INSERTs e lock prolongado em produção.
        $this->execute(
            "INSERT IGNORE INTO adms_access_levels_pages
                (permission, adms_access_level_id, adms_page_id, created_at, updated_at)
             SELECT 0, al.id, p.id, NOW(), NOW()
             FROM adms_access_levels al
             CROSS JOIN adms_pages p
             WHERE p.page_status = 1"
        );

        $this->applyPublicAndDefaultPagePermissionsToAllLevels();

        echo "✅ Sincronização automática concluída (INSERT IGNORE com 0 + públicas/padrão = 1 em todos os níveis).\n";
    }

    /**
     * Garante permission = 1 para todas as páginas ativas públicas ou marcadas como padrão na matriz,
     * em todos os níveis (inclui id 1). Idempotente com UNIQUE (nível, página).
     */
    private function applyPublicAndDefaultPagePermissionsToAllLevels(): void
    {
        if (
            !$this->hasTable('adms_access_levels_pages')
            || !$this->hasTable('adms_pages')
            || !$this->hasTable('adms_access_levels')
        ) {
            return;
        }

        $hasDefaultPage = $this->table('adms_pages')->hasColumn('default_page');
        $pageCond = $hasDefaultPage
            ? '(p.public_page = 1 OR p.default_page = 1)'
            : '(p.public_page = 1)';

        $sql = <<<SQL
INSERT INTO adms_access_levels_pages (permission, adms_access_level_id, adms_page_id, created_at, updated_at)
SELECT 1, al.id, p.id, NOW(), NOW()
FROM adms_access_levels al
CROSS JOIN adms_pages p
WHERE p.page_status = 1
  AND {$pageCond}
ON DUPLICATE KEY UPDATE
  permission = 1,
  updated_at = NOW()
SQL;

        $this->execute($sql);
    }
} 