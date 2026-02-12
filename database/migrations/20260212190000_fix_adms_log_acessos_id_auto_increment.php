<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Garante que o campo id da tabela adms_log_acessos
 * seja AUTO_INCREMENT em qualquer ambiente.
 *
 * @method \Phinx\Db\Table table(string $tableName, array $options = [])
 */
final class FixAdmsLogAcessosIdAutoIncrement extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('adms_log_acessos')) {
            return;
        }

        // Usar SQL direto para evitar erro "only one auto column" e
        // garantir que o id seja AUTO_INCREMENT + PRIMARY KEY em uma passada.
        // 1) Remover PRIMARY KEY atual (se não for em id).
        // 2) Ajustar coluna id para AUTO_INCREMENT NOT NULL.
        // 3) Definir PRIMARY KEY (id).
        $this->execute("
            ALTER TABLE `adms_log_acessos`
            DROP PRIMARY KEY,
            MODIFY COLUMN `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            ADD PRIMARY KEY (`id`)
        ");
    }

    public function down(): void
    {
        if (!$this->hasTable('adms_log_acessos')) {
            return;
        }

        // Reverter: remove AUTO_INCREMENT de id (mantém NOT NULL e unsigned)
        $this->execute("
            ALTER TABLE `adms_log_acessos`
            MODIFY COLUMN `id` INT UNSIGNED NOT NULL
        ");
    }
}


