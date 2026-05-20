<?php

declare(strict_types=1);

use App\adms\Database\BaseMigration;

/**
 * Configuração VAPID (singleton) e inscrições Web Push por usuário/dispositivo.
 */
final class CreateAdmsPushConfigAndSubscriptions extends BaseMigration
{
    public function up(): void
    {
        if (!$this->hasTable('adms_push_config')) {
            $table = $this->table('adms_push_config');
            $table->addColumn('vapid_public_key', 'text', ['null' => true])
                ->addColumn('vapid_private_key', 'text', ['null' => true])
                ->addColumn('vapid_subject', 'string', ['null' => true, 'limit' => 255])
                ->addColumn('is_enabled', 'boolean', ['null' => false, 'default' => 0])
                ->addColumn('created_at', 'timestamp', ['null' => false, 'default' => 'CURRENT_TIMESTAMP'])
                ->addColumn('updated_at', 'timestamp', [
                    'null' => false,
                    'default' => 'CURRENT_TIMESTAMP',
                    'update' => 'CURRENT_TIMESTAMP',
                ])
                ->create();
        }

        if (!$this->hasTable('adms_push_subscriptions')) {
            $table = $this->table('adms_push_subscriptions');
            $table->addColumn('user_id', 'integer', ['null' => false, 'signed' => false])
                ->addColumn('endpoint_hash', 'string', ['null' => false, 'limit' => 64])
                ->addColumn('endpoint', 'text', ['null' => false])
                ->addColumn('public_key', 'string', ['null' => false, 'limit' => 255])
                ->addColumn('auth_token', 'string', ['null' => false, 'limit' => 255])
                ->addColumn('content_encoding', 'string', ['null' => false, 'limit' => 32, 'default' => 'aesgcm'])
                ->addColumn('user_agent', 'string', ['null' => true, 'limit' => 512])
                ->addColumn('created_at', 'timestamp', ['null' => false, 'default' => 'CURRENT_TIMESTAMP'])
                ->addColumn('updated_at', 'timestamp', [
                    'null' => false,
                    'default' => 'CURRENT_TIMESTAMP',
                    'update' => 'CURRENT_TIMESTAMP',
                ])
                ->addIndex(['user_id'])
                ->addIndex(['endpoint_hash'], ['unique' => true])
                ->create();
        }
    }

    public function down(): void
    {
        if ($this->hasTable('adms_push_subscriptions')) {
            $this->table('adms_push_subscriptions')->drop()->save();
        }
        if ($this->hasTable('adms_push_config')) {
            $this->table('adms_push_config')->drop()->save();
        }
    }
}
