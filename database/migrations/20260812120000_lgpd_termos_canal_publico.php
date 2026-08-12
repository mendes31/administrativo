<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Canal público LGPD: só termos marcados como públicos (com slug) aparecem em /lgpd/{slug}.
 * Termos de Uso do sistema deixam de ser página pública (só portal autenticado).
 */
final class LgpdTermosCanalPublico extends AbstractMigration
{
    public function up(): void
    {
        if ($this->hasTable('lgpd_termos')) {
            $table = $this->table('lgpd_termos');
            if (!$table->hasColumn('publico_canal')) {
                $table->addColumn('publico_canal', 'boolean', [
                    'default' => 0,
                    'null' => false,
                    'after' => 'status',
                    'comment' => '1 = exibir no canal público /lgpd/{slug}',
                ]);
            }
            if (!$table->hasColumn('slug_publico')) {
                $table->addColumn('slug_publico', 'string', [
                    'limit' => 80,
                    'null' => true,
                    'default' => null,
                    'after' => 'publico_canal',
                    'comment' => 'Slug da URL pública (ex.: politica, termos)',
                ]);
            }
            $table->update();

            if (!$table->hasIndexByName('uniq_lgpd_termos_slug_publico')) {
                $table->addIndex(['slug_publico'], [
                    'unique' => true,
                    'name' => 'uniq_lgpd_termos_slug_publico',
                ])->update();
            }

            // Política de Privacidade ativa tipo site → publica por padrão (se ainda sem flag)
            $this->execute(
                "UPDATE lgpd_termos
                 SET publico_canal = 1,
                     slug_publico = 'politica'
                 WHERE TRIM(tipo) = 'site'
                   AND status = 'Ativo'
                   AND (publico_canal = 0 OR publico_canal IS NULL)
                   AND LOWER(titulo) LIKE '%pol%tica%privacidade%'
                   AND (slug_publico IS NULL OR slug_publico = '')
                 LIMIT 1"
            );

            // Termos de Uso do sistema: nunca públicos neste passo
            $this->execute(
                "UPDATE lgpd_termos
                 SET publico_canal = 0,
                     slug_publico = NULL
                 WHERE TRIM(tipo) = 'site'
                   AND LOWER(titulo) LIKE '%termos%uso%'"
            );
        }

        // Páginas legais do rodapé: Termos de Uso deixa de ser público
        if ($this->hasTable('adms_pages')) {
            $this->execute(
                "UPDATE adms_pages
                 SET public_page = 0, default_page = 0, updated_at = NOW()
                 WHERE controller = 'TermosDeUso'
                   AND controller_url = 'termos-de-uso'"
            );
            // Política antiga: mantém acessível autenticada; canal público usa /lgpd/politica
            $this->execute(
                "UPDATE adms_pages
                 SET public_page = 0, default_page = 0, updated_at = NOW()
                 WHERE controller = 'PoliticaPrivacidade'
                   AND controller_url = 'politica-privacidade'"
            );
        }
    }

    public function down(): void
    {
        if ($this->hasTable('adms_pages')) {
            $this->execute(
                "UPDATE adms_pages
                 SET public_page = 1, default_page = 1, updated_at = NOW()
                 WHERE controller IN ('TermosDeUso', 'PoliticaPrivacidade')"
            );
        }

        if ($this->hasTable('lgpd_termos')) {
            $table = $this->table('lgpd_termos');
            if ($table->hasIndexByName('uniq_lgpd_termos_slug_publico')) {
                $table->removeIndexByName('uniq_lgpd_termos_slug_publico')->update();
            }
            if ($table->hasColumn('slug_publico')) {
                $table->removeColumn('slug_publico')->update();
            }
            if ($table->hasColumn('publico_canal')) {
                $table->removeColumn('publico_canal')->update();
            }
        }
    }
}
