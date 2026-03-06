<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddNotifyToAdmsInformativos extends AbstractMigration
{
    public function up(): void
    {
        $table = $this->table('adms_informativos');

        if (!$table->hasColumn('notificar')) {
            $table->addColumn('notificar', 'boolean', [
                'default' => false,
                'null' => false,
                'after' => 'urgente',
                'comment' => 'Indica se o informativo deve disparar notificações (WhatsApp, etc.)',
            ])->save();
        }

        // Tabela de relação opcional informativo x departamentos (alvo das notificações)
        if (!$this->hasTable('adms_informativos_notify_departments')) {
            // Deixar o Phinx criar a coluna "id" padrão (auto-increment)
            $notifyTable = $this->table('adms_informativos_notify_departments');
            $notifyTable
                ->addColumn('informativo_id', 'integer', [
                    'signed' => false,
                    'null' => false,
                ])
                ->addColumn('department_id', 'integer', [
                    'signed' => false,
                    'null' => false,
                ])
                ->addColumn('created_at', 'datetime', [
                    'default' => 'CURRENT_TIMESTAMP',
                ])
                ->addIndex(['informativo_id'])
                ->addIndex(['department_id'])
                ->addForeignKey('informativo_id', 'adms_informativos', 'id', [
                    'delete' => 'CASCADE',
                    'update' => 'CASCADE',
                ])
                ->addForeignKey('department_id', 'adms_departments', 'id', [
                    'delete' => 'CASCADE',
                    'update' => 'CASCADE',
                ])
                ->create();
        }
    }

    public function down(): void
    {
        if ($this->hasTable('adms_informativos_notify_departments')) {
            $this->table('adms_informativos_notify_departments')->drop()->save();
        }

        $table = $this->table('adms_informativos');
        if ($table->hasColumn('notificar')) {
            $table->removeColumn('notificar')->save();
        }
    }
}

