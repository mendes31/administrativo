<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddTrainingUsersGuards extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('adms_training_users')) {
            return;
        }

        $indexes = $this->fetchAll("SHOW INDEX FROM adms_training_users WHERE Key_name = 'idx_tu_user_training_tipo_status'");
        if (empty($indexes)) {
            $this->table('adms_training_users')
                ->addIndex(
                    ['adms_user_id', 'adms_training_id', 'tipo_vinculo', 'status', 'id'],
                    ['name' => 'idx_tu_user_training_tipo_status']
                )
                ->update();
        }

        $this->execute('DROP TRIGGER IF EXISTS trg_tu_prevent_duplicate_active_insert');
        $this->execute(
            "CREATE TRIGGER trg_tu_prevent_duplicate_active_insert
             BEFORE INSERT ON adms_training_users
             FOR EACH ROW
             BEGIN
                 IF NEW.status <> 'concluido' AND EXISTS (
                     SELECT 1
                     FROM adms_training_users tu
                     WHERE tu.adms_user_id = NEW.adms_user_id
                       AND tu.adms_training_id = NEW.adms_training_id
                       AND tu.tipo_vinculo = NEW.tipo_vinculo
                       AND tu.status <> 'concluido'
                     LIMIT 1
                 ) THEN
                     SIGNAL SQLSTATE '45000'
                         SET MESSAGE_TEXT = 'Vínculo ativo duplicado para usuário+treinamento+tipo.';
                 END IF;
             END"
        );

        $this->execute('DROP TRIGGER IF EXISTS trg_tu_cargo_overrides_individual_insert');
        $this->execute(
            "CREATE TRIGGER trg_tu_cargo_overrides_individual_insert
             AFTER INSERT ON adms_training_users
             FOR EACH ROW
             BEGIN
                 IF NEW.tipo_vinculo = 'cargo' AND NEW.status <> 'concluido' THEN
                     DELETE FROM adms_training_users
                     WHERE adms_user_id = NEW.adms_user_id
                       AND adms_training_id = NEW.adms_training_id
                       AND tipo_vinculo = 'individual'
                       AND status <> 'concluido'
                       AND id <> NEW.id;
                 END IF;
             END"
        );
    }

    public function down(): void
    {
        if ($this->hasTable('adms_training_users')) {
            $this->execute('DROP TRIGGER IF EXISTS trg_tu_prevent_duplicate_active_insert');
            $this->execute('DROP TRIGGER IF EXISTS trg_tu_cargo_overrides_individual_insert');

            $indexes = $this->fetchAll("SHOW INDEX FROM adms_training_users WHERE Key_name = 'idx_tu_user_training_tipo_status'");
            if (!empty($indexes)) {
                $this->table('adms_training_users')
                    ->removeIndexByName('idx_tu_user_training_tipo_status')
                    ->update();
            }
        }
    }
}

