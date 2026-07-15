<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Complemento de endereço (campo incluído após a migration base já ter sido aplicada).
 */
final class AddComplementoEnderecoToAdmsUsers extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('adms_users')) {
            return;
        }

        $table = $this->table('adms_users');
        if ($table->hasColumn('complemento_endereco')) {
            return;
        }

        $opts = [
            'limit' => 80,
            'null' => true,
            'default' => null,
            'comment' => 'Complemento (apto, bloco, etc.)',
        ];
        if ($table->hasColumn('numero_endereco')) {
            $opts['after'] = 'numero_endereco';
        }

        $table->addColumn('complemento_endereco', 'string', $opts)->update();
    }

    public function down(): void
    {
        if (!$this->hasTable('adms_users')) {
            return;
        }

        $table = $this->table('adms_users');
        if ($table->hasColumn('complemento_endereco')) {
            $table->removeColumn('complemento_endereco')->update();
        }
    }
}
