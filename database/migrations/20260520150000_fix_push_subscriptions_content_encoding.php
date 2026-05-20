<?php

declare(strict_types=1);

use App\adms\Database\BaseMigration;

/**
 * Chrome/Edge (FCM) exigem aes128gcm; registros antigos com aesgcm falham no desktop.
 */
final class FixPushSubscriptionsContentEncoding extends BaseMigration
{
    public function up(): void
    {
        if (!$this->hasTable('adms_push_subscriptions')) {
            return;
        }

        $this->execute("
            UPDATE adms_push_subscriptions
            SET content_encoding = 'aes128gcm', updated_at = NOW()
            WHERE content_encoding IS NULL
               OR content_encoding = ''
               OR content_encoding = 'aesgcm'
        ");
    }

    public function down(): void
    {
        // Sem rollback — aes128gcm é o padrão correto para navegadores atuais.
    }
}
