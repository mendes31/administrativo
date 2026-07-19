<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Fase 0 — currículos ATS:
 * - FK CASCADE em rh_candidatos_anexos (após limpeza de órfãos);
 * - termo LGPD tipo curriculo_candidato;
 * - página de download autorizado;
 * - exclusão deploy-safe do storage privado (estrutura via .gitkeep no repo).
 */
final class SecureRhCandidatosAnexosAndLgpd extends AbstractMigration
{
    public function up(): void
    {
        $this->cleanupOrphanAnexos();
        $this->addAnexosForeignKey();
        $this->seedLgpdTermoCurriculo();
        $this->registerDownloadPage();
    }

    public function down(): void
    {
        if ($this->hasTable('rh_candidatos_anexos')) {
            $table = $this->table('rh_candidatos_anexos');
            if ($table->hasForeignKey('rh_candidato_id')) {
                $table->dropForeignKey('rh_candidato_id')->update();
            }
        }

        if ($this->hasTable('adms_pages')) {
            $this->execute(
                "DELETE FROM adms_access_levels_pages
                 WHERE adms_page_id IN (
                    SELECT id FROM (
                        SELECT id FROM adms_pages WHERE controller = 'RhCandidatosDownloadAnexo'
                    ) t
                 )"
            );
            $this->execute("DELETE FROM adms_pages WHERE controller = 'RhCandidatosDownloadAnexo'");
        }

        // Termo LGPD e arquivos físicos não são removidos no down (dados de produção).
    }

    private function cleanupOrphanAnexos(): void
    {
        if (!$this->hasTable('rh_candidatos_anexos') || !$this->hasTable('rh_candidatos')) {
            return;
        }

        $this->execute(
            'DELETE a FROM rh_candidatos_anexos a
             LEFT JOIN rh_candidatos c ON c.id = a.rh_candidato_id
             WHERE c.id IS NULL'
        );
    }

    private function addAnexosForeignKey(): void
    {
        if (!$this->hasTable('rh_candidatos_anexos') || !$this->hasTable('rh_candidatos')) {
            return;
        }

        $table = $this->table('rh_candidatos_anexos');
        if ($table->hasForeignKey('rh_candidato_id')) {
            return;
        }

        $table
            ->addForeignKey(
                'rh_candidato_id',
                'rh_candidatos',
                'id',
                [
                    'delete' => 'CASCADE',
                    'update' => 'NO_ACTION',
                    'constraint' => 'fk_rh_candidatos_anexos_candidato',
                ]
            )
            ->update();
    }

    private function seedLgpdTermoCurriculo(): void
    {
        if (!$this->hasTable('lgpd_termos')) {
            return;
        }

        $conn = $this->getAdapter()->getConnection();
        $existing = $this->fetchRow(
            "SELECT id FROM lgpd_termos
             WHERE TRIM(tipo) = 'curriculo_candidato' AND status = 'Ativo'
             LIMIT 1"
        );
        if ($existing) {
            return;
        }

        $now = date('Y-m-d H:i:s');
        $titulo = 'Consentimento para tratamento de currículo e dados de candidato';
        $conteudo = "Ao consentir, o titular autoriza a Tiaraju a tratar os dados pessoais "
            . "e o currículo informados no cadastro de candidatos, para finalidades de "
            . "recrutamento, seleção, contato sobre oportunidades e cumprimento de "
            . "obrigações legais. Os prazos de retenção seguem a política LGPD vigente "
            . "(currículos não aproveitados e banco de talentos).";

        $sql = 'INSERT INTO lgpd_termos
                    (versao, titulo, tipo, conteudo, data_inicio_vigencia, data_fim_vigencia, status, created_at, updated_at)
                VALUES (
                    ' . $conn->quote('1.0') . ',
                    ' . $conn->quote($titulo) . ',
                    ' . $conn->quote('curriculo_candidato') . ',
                    ' . $conn->quote($conteudo) . ',
                    ' . $conn->quote($now) . ',
                    NULL,
                    ' . $conn->quote('Ativo') . ',
                    ' . $conn->quote($now) . ',
                    ' . $conn->quote($now) . '
                )';
        $this->execute($sql);
    }

    private function registerDownloadPage(): void
    {
        if (!$this->hasTable('adms_pages')) {
            return;
        }

        $conn = $this->getAdapter()->getConnection();
        $existing = $this->fetchRow(
            "SELECT id FROM adms_pages WHERE controller = 'RhCandidatosDownloadAnexo' LIMIT 1"
        );
        if ($existing) {
            $this->grantDownloadPageToRhLevels((int) $existing['id']);
            return;
        }

        $groupId = 30;
        $group = $this->fetchRow(
            "SELECT adms_groups_page_id FROM adms_pages WHERE controller = 'RhCandidatosView' LIMIT 1"
        );
        if ($group && !empty($group['adms_groups_page_id'])) {
            $groupId = (int) $group['adms_groups_page_id'];
        }

        $now = date('Y-m-d H:i:s');
        $this->execute(
            'INSERT INTO adms_pages
                (name, controller, controller_url, directory, obs, public_page, default_page, page_status,
                 adms_packages_page_id, adms_groups_page_id, created_at, updated_at)
             VALUES ('
            . $conn->quote('Download de Currículo / Anexo') . ', '
            . $conn->quote('RhCandidatosDownloadAnexo') . ', '
            . $conn->quote('rh-candidatos-download-anexo') . ', '
            . $conn->quote('rh') . ', '
            . $conn->quote('Download autorizado de currículo/anexo de candidato (storage privado).') . ', '
            . '0, 0, 1, 1, '
            . $groupId . ', '
            . $conn->quote($now) . ', '
            . $conn->quote($now)
            . ')'
        );

        $page = $this->fetchRow(
            "SELECT id FROM adms_pages WHERE controller = 'RhCandidatosDownloadAnexo' LIMIT 1"
        );
        if ($page) {
            $this->grantDownloadPageToRhLevels((int) $page['id']);
        }
    }

    private function grantDownloadPageToRhLevels(int $pageId): void
    {
        if (!$this->hasTable('adms_access_levels_pages') || !$this->hasTable('adms_pages')) {
            return;
        }

        $viewPage = $this->fetchRow(
            "SELECT id FROM adms_pages WHERE controller = 'RhCandidatosView' LIMIT 1"
        );
        if (!$viewPage) {
            return;
        }

        $viewPageId = (int) $viewPage['id'];
        $now = date('Y-m-d H:i:s');

        // Replica permissão aos níveis que já acessam a visualização do candidato.
        $this->execute(
            "INSERT INTO adms_access_levels_pages (permission, adms_access_level_id, adms_page_id, created_at, updated_at)
             SELECT 1, alp.adms_access_level_id, {$pageId}, '{$now}', '{$now}'
             FROM adms_access_levels_pages alp
             WHERE alp.adms_page_id = {$viewPageId}
               AND alp.permission = 1
             ON DUPLICATE KEY UPDATE permission = 1, updated_at = '{$now}'"
        );
    }
}
