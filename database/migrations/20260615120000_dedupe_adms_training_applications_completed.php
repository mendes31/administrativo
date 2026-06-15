<?php

declare(strict_types=1);

use App\adms\Database\BaseMigration;

/**
 * Remove aplicações concluídas duplicadas (mesmo colaborador + treinamento + data_realizacao).
 * Mantém o registro de maior ID (mais recente). Cópia de segurança em adms_training_applications_dedup_backup.
 */
final class DedupeAdmsTrainingApplicationsCompleted extends BaseMigration
{
    public function up(): void
    {
        if (!$this->hasTable('adms_training_applications')) {
            return;
        }

        if (!$this->hasTable('adms_training_applications_dedup_backup')) {
            $this->execute('CREATE TABLE adms_training_applications_dedup_backup LIKE adms_training_applications');
        }

        $duplicates = $this->fetchAll(
            "SELECT
                d.adms_user_id,
                d.adms_training_id,
                d.data_realizacao,
                d.cnt,
                d.keep_id,
                GROUP_CONCAT(ta.id ORDER BY ta.id) AS all_ids
             FROM adms_training_applications ta
             INNER JOIN (
                 SELECT
                     adms_user_id,
                     adms_training_id,
                     data_realizacao,
                     MAX(id) AS keep_id,
                     COUNT(*) AS cnt
                 FROM adms_training_applications
                 WHERE status = 'concluido'
                   AND data_realizacao IS NOT NULL
                   AND data_realizacao > '0000-00-00'
                 GROUP BY adms_user_id, adms_training_id, data_realizacao
                 HAVING cnt > 1
             ) d ON ta.adms_user_id = d.adms_user_id
                AND ta.adms_training_id = d.adms_training_id
                AND ta.data_realizacao = d.data_realizacao
             WHERE ta.status = 'concluido'
             GROUP BY d.adms_user_id, d.adms_training_id, d.data_realizacao, d.cnt, d.keep_id"
        );

        if ($duplicates === []) {
            echo "  ✓ Nenhuma aplicação concluída duplicada encontrada.\n";

            return;
        }

        $toRemove = (int) ($this->fetchAll(
            "SELECT COUNT(*) AS total
             FROM adms_training_applications ta
             INNER JOIN (
                 SELECT
                     adms_user_id,
                     adms_training_id,
                     data_realizacao,
                     MAX(id) AS keep_id
                 FROM adms_training_applications
                 WHERE status = 'concluido'
                   AND data_realizacao IS NOT NULL
                   AND data_realizacao > '0000-00-00'
                 GROUP BY adms_user_id, adms_training_id, data_realizacao
                 HAVING COUNT(*) > 1
             ) d ON ta.adms_user_id = d.adms_user_id
                AND ta.adms_training_id = d.adms_training_id
                AND ta.data_realizacao = d.data_realizacao
             WHERE ta.id < d.keep_id
               AND ta.status = 'concluido'"
        )[0]['total'] ?? 0);

        echo '  → Grupos duplicados: ' . count($duplicates) . "\n";
        echo '  → Registros a remover: ' . $toRemove . "\n";

        $this->execute(
            "INSERT IGNORE INTO adms_training_applications_dedup_backup
             SELECT ta.*
             FROM adms_training_applications ta
             INNER JOIN (
                 SELECT
                     adms_user_id,
                     adms_training_id,
                     data_realizacao,
                     MAX(id) AS keep_id
                 FROM adms_training_applications
                 WHERE status = 'concluido'
                   AND data_realizacao IS NOT NULL
                   AND data_realizacao > '0000-00-00'
                 GROUP BY adms_user_id, adms_training_id, data_realizacao
                 HAVING COUNT(*) > 1
             ) d ON ta.adms_user_id = d.adms_user_id
                AND ta.adms_training_id = d.adms_training_id
                AND ta.data_realizacao = d.data_realizacao
             WHERE ta.id < d.keep_id
               AND ta.status = 'concluido'"
        );

        $this->execute(
            "DELETE ta FROM adms_training_applications ta
             INNER JOIN (
                 SELECT
                     adms_user_id,
                     adms_training_id,
                     data_realizacao,
                     MAX(id) AS keep_id
                 FROM adms_training_applications
                 WHERE status = 'concluido'
                   AND data_realizacao IS NOT NULL
                   AND data_realizacao > '0000-00-00'
                 GROUP BY adms_user_id, adms_training_id, data_realizacao
                 HAVING COUNT(*) > 1
             ) d ON ta.adms_user_id = d.adms_user_id
                AND ta.adms_training_id = d.adms_training_id
                AND ta.data_realizacao = d.data_realizacao
             WHERE ta.id < d.keep_id
               AND ta.status = 'concluido'"
        );

        echo "  ✓ Duplicatas removidas. Backup em adms_training_applications_dedup_backup.\n";
    }

    public function down(): void
    {
        // Irreversível automaticamente: restaurar manualmente a partir do backup se necessário.
    }
}
