<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Garante que a coluna id da tabela adms_access_levels_pages
 * seja AUTO_INCREMENT, evitando o erro "Duplicate entry '0' for key 'PRIMARY'"
 * ao salvar permissões de nível de acesso.
 */
final class FixAdmsAccessLevelsPagesIdAutoIncrement extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('adms_access_levels_pages')) {
            return;
        }

        $conn = $this->getAdapter()->getConnection();

        // Verificar se existe coluna id
        $columns = $this->fetchAll("SHOW COLUMNS FROM adms_access_levels_pages WHERE Field = 'id'");
        if (empty($columns)) {
            return;
        }

        $extra = strtolower($columns[0]['Extra'] ?? '');
        if (strpos($extra, 'auto_increment') !== false) {
            // Já é AUTO_INCREMENT, nada a fazer
            return;
        }

        // Se existir linha com id = 0, atualizar para o próximo valor disponível
        // (evita conflito ao definir AUTO_INCREMENT)
        $countZero = $this->fetchRow("SELECT COUNT(*) AS c FROM adms_access_levels_pages WHERE id = 0");
        if (isset($countZero['c']) && (int) $countZero['c'] > 0) {
            $max = $this->fetchRow("SELECT COALESCE(MAX(id), 0) + 1 AS next_id FROM adms_access_levels_pages");
            $nextId = (int) ($max['next_id'] ?? 1);
            $this->execute(sprintf(
                "UPDATE adms_access_levels_pages SET id = %d WHERE id = 0 LIMIT 1",
                $nextId
            ));
        }

        $this->execute(
            "ALTER TABLE adms_access_levels_pages MODIFY COLUMN id INT UNSIGNED NOT NULL AUTO_INCREMENT"
        );
    }

    public function down(): void
    {
        if (!$this->hasTable('adms_access_levels_pages')) {
            return;
        }

        $this->execute(
            "ALTER TABLE adms_access_levels_pages MODIFY COLUMN id INT UNSIGNED NOT NULL"
        );
    }
}
