<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreatePasswordPolicyTable extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('adms_password_policy')) {
            $table = $this->table('adms_password_policy');
            $table
                ->addColumn('vencimento_dias', 'integer', ['default' => 90, 'null' => false])
                ->addColumn('comprimento_minimo', 'integer', ['default' => 8, 'null' => false])
                ->addColumn('min_maiusculas', 'integer', ['default' => 0, 'null' => false])
                ->addColumn('min_minusculas', 'integer', ['default' => 1, 'null' => false])
                ->addColumn('min_digitos', 'integer', ['default' => 1, 'null' => false])
                ->addColumn('min_nao_alfanumericos', 'integer', ['default' => 1, 'null' => false])
                ->addColumn('historico_senhas', 'integer', ['default' => 5, 'null' => false])
                ->addColumn('tentativas_bloqueio', 'integer', ['default' => 5, 'null' => false])
                ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
                ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
                ->create();
        }
    }

    public function down(): void
    {
        if ($this->hasTable('adms_password_policy')) {
            $this->table('adms_password_policy')->drop()->save();
        }
    }
} 