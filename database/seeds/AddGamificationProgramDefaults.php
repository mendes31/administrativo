<?php

declare(strict_types=1);

use Phinx\Seed\AbstractSeed;

class AddGamificationProgramDefaults extends AbstractSeed
{
    private function quote(string $value): string
    {
        return "'" . str_replace("'", "''", $value) . "'";
    }

    public function run(): void
    {
        if ($this->hasTable('adms_gamification_levels')) {
            $levels = [
                ['name' => 'Iniciante', 'min_points' => 0, 'badge_color' => 'secondary', 'sort_order' => 1, 'is_active' => 1],
                ['name' => 'Participante', 'min_points' => 100, 'badge_color' => 'info', 'sort_order' => 2, 'is_active' => 1],
                ['name' => 'Colaborador', 'min_points' => 300, 'badge_color' => 'primary', 'sort_order' => 3, 'is_active' => 1],
                ['name' => 'Influente', 'min_points' => 700, 'badge_color' => 'warning', 'sort_order' => 4, 'is_active' => 1],
                ['name' => 'Embaixador', 'min_points' => 1500, 'badge_color' => 'success', 'sort_order' => 5, 'is_active' => 1],
            ];
            foreach ($levels as $level) {
                $name = (string)($level['name'] ?? '');
                if ($name === '') {
                    continue;
                }
                $exists = $this->fetchRow(
                    'SELECT id FROM adms_gamification_levels WHERE name = ' . $this->quote($name) . ' LIMIT 1'
                );
                if ($exists) {
                    continue;
                }
                $this->insert('adms_gamification_levels', [array_merge($level, [
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ])]);
            }
        }

        if ($this->hasTable('adms_gamification_badges')) {
            $badges = [
                ['name' => 'Comunicador do Mês', 'slug' => 'comunicador-do-mes', 'description' => 'Atingiu pelo menos 80 pontos no mês.', 'criteria_key' => 'monthly_points', 'criteria_value_json' => json_encode(['min_points' => 80]), 'icon' => 'fa-bullhorn', 'is_active' => 1],
                ['name' => 'Ideia do Mês', 'slug' => 'ideia-do-mes', 'description' => 'Conquistou reconhecimento por contribuição de ideias.', 'criteria_key' => 'monthly_posts', 'criteria_value_json' => json_encode(['min_posts' => 4]), 'icon' => 'fa-lightbulb', 'is_active' => 1],
                ['name' => 'Colaborador Destaque', 'slug' => 'colaborador-destaque', 'description' => 'Acumulou pontuação total de destaque.', 'criteria_key' => 'total_points', 'criteria_value_json' => json_encode(['min_points' => 300]), 'icon' => 'fa-users', 'is_active' => 1],
                ['name' => 'Engajamento Máximo', 'slug' => 'engajamento-maximo', 'description' => 'Manteve alto nível de interações válidas.', 'criteria_key' => 'total_points', 'criteria_value_json' => json_encode(['min_points' => 700]), 'icon' => 'fa-fire', 'is_active' => 1],
                ['name' => 'Presença Constante', 'slug' => 'presenca-constante', 'description' => 'Completou ao menos 4 missões mensais.', 'criteria_key' => 'monthly_missions_completed', 'criteria_value_json' => json_encode(['min_missions' => 4]), 'icon' => 'fa-calendar-check', 'is_active' => 1],
            ];
            foreach ($badges as $badge) {
                $slug = (string)($badge['slug'] ?? '');
                if ($slug === '') {
                    continue;
                }
                $exists = $this->fetchRow(
                    'SELECT id FROM adms_gamification_badges WHERE slug = ' . $this->quote($slug) . ' LIMIT 1'
                );
                if ($exists) {
                    continue;
                }
                $this->insert('adms_gamification_badges', [array_merge($badge, [
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ])]);
            }
        }

        if ($this->hasTable('adms_gamification_weekly_missions')) {
            $missions = [
                ['title' => 'Fazer 12 comentários relevantes (mês)', 'description' => 'Comente de forma construtiva 12 vezes no mês civil.', 'event_key' => 'timeline_comment_created', 'target_value' => 12, 'reward_points' => 10, 'sort_order' => 1, 'is_active' => 1],
                ['title' => 'Criar 4 publicações úteis (mês)', 'description' => 'Publique ao menos quatro conteúdos relevantes no mês.', 'event_key' => 'timeline_post_created', 'target_value' => 4, 'reward_points' => 15, 'sort_order' => 2, 'is_active' => 1],
                ['title' => 'Participar de 8 enquetes (mês)', 'description' => 'Vote em oito enquetes durante o mês.', 'event_key' => 'timeline_poll_vote', 'target_value' => 8, 'reward_points' => 5, 'sort_order' => 3, 'is_active' => 1],
                ['title' => 'Interagir em 20 ações válidas (mês)', 'description' => 'Some 20 reações qualificadas no mês.', 'event_key' => 'timeline_reaction_created', 'target_value' => 20, 'reward_points' => 12, 'sort_order' => 4, 'is_active' => 1],
            ];
            foreach ($missions as $mission) {
                $title = (string)($mission['title'] ?? '');
                if ($title === '') {
                    continue;
                }
                $exists = $this->fetchRow(
                    'SELECT id FROM adms_gamification_weekly_missions WHERE title = ' . $this->quote($title) . ' LIMIT 1'
                );
                if ($exists) {
                    continue;
                }
                $this->insert('adms_gamification_weekly_missions', [array_merge($mission, [
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ])]);
            }
        }

        if ($this->hasTable('adms_gamification_settings')) {
            $settings = [
                ['setting_key' => 'anti_fraud_min_comment_chars', 'setting_value' => '15', 'description' => 'Tamanho mínimo do comentário para pontuar.'],
                ['setting_key' => 'anti_fraud_max_same_event_per_minute', 'setting_value' => '3', 'description' => 'Máximo de ações iguais por minuto antes do bloqueio.'],
                ['setting_key' => 'anti_fraud_block_self_reaction', 'setting_value' => '1', 'description' => 'Bloquear pontuação para reação no próprio post.'],
            ];
            foreach ($settings as $setting) {
                $key = (string)($setting['setting_key'] ?? '');
                if ($key === '') {
                    continue;
                }
                $exists = $this->fetchRow(
                    'SELECT id FROM adms_gamification_settings WHERE setting_key = ' . $this->quote($key) . ' LIMIT 1'
                );
                if ($exists) {
                    continue;
                }
                $this->insert('adms_gamification_settings', [$setting]);
            }
        }
    }
}
