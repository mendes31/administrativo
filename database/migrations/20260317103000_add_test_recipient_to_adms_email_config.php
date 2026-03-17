<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddTestRecipientToAdmsEmailConfig extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('adms_email_config')) {
            return;
        }

        $table = $this->table('adms_email_config');

        if (!$table->hasColumn('test_recipient')) {
            $table
                ->addColumn('test_recipient', 'string', [
                    'null'    => true,
                    'limit'   => 255,
                    'after'   => 'from_name',
                    'comment' => 'E-mail destinatário para teste SMTP',
                ])
                ->update();
        }
    }

    public function down(): void
    {
        if (!$this->hasTable('adms_email_config')) {
            return;
        }

        $table = $this->table('adms_email_config');

        if ($table->hasColumn('test_recipient')) {
            $table
                ->removeColumn('test_recipient')
                ->update();
        }
    }
}

