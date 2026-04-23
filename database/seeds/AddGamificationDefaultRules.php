<?php

declare(strict_types=1);

use Phinx\Seed\AbstractSeed;

/**
 * Regras iniciais de gamificação (timeline). Valores conservadores; administrador ajusta nas telas.
 */
class AddGamificationDefaultRules extends AbstractSeed
{
    public function run(): void
    {
        if (!$this->getAdapter()->hasTable('adms_gamification_timeline_rules')) {
            return;
        }

        $rows = [
            [
                'event_key' => 'timeline_post_created',
                'title' => 'Nova publicação na timeline',
                'description' => 'Pontos ao criar um post original (não repost).',
                'points' => 2,
                'max_awards_per_user_per_day' => 10,
                'max_awards_per_user_total' => null,
                'is_active' => 1,
            ],
            [
                'event_key' => 'timeline_share_created',
                'title' => 'Repost / compartilhamento',
                'description' => 'Pontos ao republicar uma publicação.',
                'points' => 1,
                'max_awards_per_user_per_day' => 20,
                'max_awards_per_user_total' => null,
                'is_active' => 1,
            ],
            [
                'event_key' => 'timeline_comment_created',
                'title' => 'Comentário em publicação',
                'description' => 'Pontos ao comentar (exceto voto em enquete).',
                'points' => 1,
                'max_awards_per_user_per_day' => 40,
                'max_awards_per_user_total' => null,
                'is_active' => 1,
            ],
            [
                'event_key' => 'timeline_reaction_created',
                'title' => 'Reação em publicação',
                'description' => 'Pontos ao reagir/curtir uma publicação (uma vez por post).',
                'points' => 1,
                'max_awards_per_user_per_day' => 50,
                'max_awards_per_user_total' => null,
                'is_active' => 1,
            ],
            [
                'event_key' => 'timeline_poll_vote',
                'title' => 'Voto em enquete',
                'description' => 'Pontos ao participar de enquete na timeline (uma vez por post).',
                'points' => 1,
                'max_awards_per_user_per_day' => 30,
                'max_awards_per_user_total' => null,
                'is_active' => 1,
            ],
        ];

        foreach ($rows as $r) {
            $ek = $r['event_key'];
            if (!is_string($ek) || !preg_match('/^[a-z0-9_]+$/', $ek)) {
                continue;
            }
            $exists = $this->fetchRow(
                "SELECT id FROM adms_gamification_timeline_rules WHERE event_key = '{$ek}' LIMIT 1"
            );
            if ($exists) {
                continue;
            }
            $this->table('adms_gamification_timeline_rules')->insert(array_merge($r, [
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]))->save();
        }
    }
}
