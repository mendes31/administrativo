<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddLgpdConsentToAdmsUsers extends AbstractMigration
{
    public function up(): void
    {
        if ($this->hasTable('adms_users')) {
            $table = $this->table('adms_users');

            if (!$table->hasColumn('lgpd_consent_given')) {
                $table->addColumn('lgpd_consent_given', 'boolean', [
                    'default' => 0,
                    'null' => false,
                    'after' => 'validate_recover_password'
                ]);
            }

            if (!$table->hasColumn('lgpd_consent_date')) {
                $table->addColumn('lgpd_consent_date', 'datetime', [
                    'null' => true,
                    'after' => 'lgpd_consent_given'
                ]);
            }

            if (!$table->hasColumn('lgpd_consent_version')) {
                $table->addColumn('lgpd_consent_version', 'string', [
                    'null' => true,
                    'limit' => 20,
                    'after' => 'lgpd_consent_date'
                ]);
            }

            $table->update();
        }
    }

    public function down(): void
    {
        if ($this->hasTable('adms_users')) {
            $table = $this->table('adms_users');

            if ($table->hasColumn('lgpd_consent_version')) {
                $table->removeColumn('lgpd_consent_version');
            }
            if ($table->hasColumn('lgpd_consent_date')) {
                $table->removeColumn('lgpd_consent_date');
            }
            if ($table->hasColumn('lgpd_consent_given')) {
                $table->removeColumn('lgpd_consent_given');
            }

            $table->update();
        }
    }
}


