<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddExpiracaoSessaoToAdmsPasswordPolicy extends AbstractMigration
{
    public function up(): void
    {
        if ($this->hasTable('adms_password_policy')) {
            $table = $this->table('adms_password_policy');
            $table->addColumn('expirar_sessao_por_tempo', 'string', [
                'limit' => 5,
                'default' => 'Não',
                'null' => false,
                'after' => 'forcar_logout_troca_senha',
                'comment' => 'Expirar sessão por tempo? Sim/Não',
            ])
            ->addColumn('tempo_expiracao_sessao', 'integer', [
                'default' => 30,
                'null' => false,
                'after' => 'expirar_sessao_por_tempo',
                'comment' => 'Tempo de expiração da sessão em minutos',
            ])
            ->update();
        }
    }

    public function down(): void
    {
        if ($this->hasTable('adms_password_policy')) {
            $table = $this->table('adms_password_policy');
            $table->removeColumn('expirar_sessao_por_tempo')
                  ->removeColumn('tempo_expiracao_sessao')
                  ->update();
        }
    }
} 