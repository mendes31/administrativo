<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateAdmsNotifications extends AbstractMigration
{
    public function up(): void
    {
        if ($this->hasTable('adms_notifications')) {
            return;
        }

        $table = $this->table('adms_notifications');
        $table
            ->addColumn('user_id', 'integer', [
                'signed' => false,
                'null' => false,
                'comment' => 'Usuário que recebe a notificação',
            ])
            ->addColumn('type', 'string', [
                'limit' => 64,
                'null' => false,
                'default' => 'info',
                'comment' => 'projeto_etapa, avaliacao, treinamento, comentario_mencao, etc.',
            ])
            ->addColumn('title', 'string', [
                'limit' => 255,
                'null' => false,
            ])
            ->addColumn('message', 'text', [
                'null' => true,
            ])
            ->addColumn('link_url', 'string', [
                'limit' => 500,
                'null' => true,
            ])
            ->addColumn('entity_type', 'string', [
                'limit' => 64,
                'null' => true,
            ])
            ->addColumn('entity_id', 'integer', [
                'signed' => false,
                'null' => true,
            ])
            ->addColumn('read_at', 'datetime', [
                'null' => true,
                'comment' => 'NULL = não lida',
            ])
            ->addColumn('created_at', 'datetime', [
                'default' => 'CURRENT_TIMESTAMP',
            ])
            ->addIndex(['user_id'])
            ->addIndex(['read_at'])
            ->addIndex(['created_at'])
            ->addForeignKey('user_id', 'adms_users', 'id', [
                'delete' => 'CASCADE',
                'update' => 'CASCADE',
            ])
            ->create();
    }

    public function down(): void
    {
        if ($this->hasTable('adms_notifications')) {
            $this->table('adms_notifications')->drop()->save();
        }
    }
}
