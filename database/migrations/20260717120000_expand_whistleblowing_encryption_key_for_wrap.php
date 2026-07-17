<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Amplia encryption_key para caber blob envelopado (wbk1: + AES-GCM).
 */
final class ExpandWhistleblowingEncryptionKeyForWrap extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('adms_whistleblowing_config')) {
            return;
        }

        $table = $this->table('adms_whistleblowing_config');
        if (!$table->hasColumn('encryption_key')) {
            return;
        }

        $table->changeColumn('encryption_key', 'text', [
            'null' => true,
            'comment' => 'DEK envelopada (wbk1:...) com WHISTLEBLOWING_KEY_WRAP_SECRET; legado pode estar em claro até migrar',
        ])->update();
    }

    public function down(): void
    {
        if (!$this->hasTable('adms_whistleblowing_config')) {
            return;
        }

        $table = $this->table('adms_whistleblowing_config');
        if (!$table->hasColumn('encryption_key')) {
            return;
        }

        $table->changeColumn('encryption_key', 'string', [
            'limit' => 255,
            'null' => true,
        ])->update();
    }
}
