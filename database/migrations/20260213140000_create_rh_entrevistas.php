<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Cria a tabela rh_entrevistas para registro de entrevistas do processo seletivo.
 * Permite vincular a um candidato e opcionalmente a uma vaga; armazena tipo, data/hora,
 * entrevistador, local, resultado e feedback para indicadores no Dashboard.
 */
final class CreateRhEntrevistas extends AbstractMigration
{
    public function up(): void
    {
        if ($this->hasTable('rh_entrevistas')) {
            return;
        }

        $this->table('rh_entrevistas')
            ->addColumn('rh_candidato_id', 'integer', [
                'null'   => false,
                'signed' => false,
            ])
            ->addColumn('rh_vaga_id', 'integer', [
                'null'   => true,
                'signed' => false,
                'comment' => 'Opcional: vaga relacionada à entrevista',
            ])
            ->addColumn('tipo', 'string', [
                'limit'   => 30,
                'null'    => false,
                'default' => 'presencial',
                'comment' => 'presencial, online, telefone',
            ])
            ->addColumn('entrevistador_id', 'integer', [
                'null'   => true,
                'signed' => false,
                'comment' => 'adms_users.id',
            ])
            ->addColumn('data_hora', 'datetime', [
                'null' => false,
            ])
            ->addColumn('local', 'string', [
                'limit' => 255,
                'null' => true,
            ])
            ->addColumn('observacoes', 'text', [
                'null' => true,
            ])
            ->addColumn('resultado', 'string', [
                'limit'   => 30,
                'null'    => true,
                'default' => null,
                'comment' => 'aprovado, reprovado, pendente',
            ])
            ->addColumn('feedback', 'text', [
                'null' => true,
            ])
            ->addColumn('created_at', 'datetime', [
                'null'    => false,
                'default' => 'CURRENT_TIMESTAMP',
            ])
            ->addColumn('updated_at', 'datetime', [
                'null'    => true,
                'default' => null,
            ])
            ->addIndex(['rh_candidato_id'])
            ->addIndex(['rh_vaga_id'])
            ->addIndex(['entrevistador_id'])
            ->addIndex(['data_hora'])
            ->addIndex(['resultado'])
            ->addForeignKey('rh_candidato_id', 'rh_candidatos', 'id', [
                'delete' => 'CASCADE',
                'update' => 'CASCADE',
            ])
            ->addForeignKey('rh_vaga_id', 'rh_vagas', 'id', [
                'delete' => 'SET_NULL',
                'update' => 'CASCADE',
            ])
            ->addForeignKey('entrevistador_id', 'adms_users', 'id', [
                'delete' => 'SET_NULL',
                'update' => 'CASCADE',
            ])
            ->create();
    }

    public function down(): void
    {
        if ($this->hasTable('rh_entrevistas')) {
            $this->table('rh_entrevistas')->drop()->save();
        }
    }
}
