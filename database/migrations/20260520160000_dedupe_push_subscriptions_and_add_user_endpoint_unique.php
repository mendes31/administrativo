<?php

declare(strict_types=1);

use App\adms\Database\BaseMigration;

/**
 * Remove duplicatas (mesmo usuário + mesmo user_agent) e garante índice único user_id + endpoint_hash.
 */
final class DedupePushSubscriptionsAndAddUserEndpointUnique extends BaseMigration
{
    public function up(): void
    {
        if (!$this->hasTable('adms_push_subscriptions')) {
            return;
        }

        // Mantém apenas a inscrição mais recente por usuário + user_agent (evita 3x Windows stale).
        $this->execute("
            DELETE s1 FROM adms_push_subscriptions s1
            INNER JOIN adms_push_subscriptions s2
                ON s1.user_id = s2.user_id
               AND s1.user_agent = s2.user_agent
               AND s1.user_agent IS NOT NULL
               AND s1.user_agent <> ''
               AND s1.id < s2.id
        ");

        $table = $this->table('adms_push_subscriptions');
        if (!$table->hasIndex(['user_id', 'endpoint_hash'])) {
            $table->addIndex(['user_id', 'endpoint_hash'], [
                'unique' => true,
                'name' => 'uq_adms_push_subscriptions_user_endpoint',
            ]);
        }
        $table->update();
    }

    public function down(): void
    {
        if (!$this->hasTable('adms_push_subscriptions')) {
            return;
        }

        $table = $this->table('adms_push_subscriptions');
        if ($table->hasIndex(['user_id', 'endpoint_hash'])) {
            $table->removeIndex(['user_id', 'endpoint_hash']);
        }
        $table->update();
    }
}
