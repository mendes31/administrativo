<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * @method bool hasTable(string $tableName)
 * @method \Phinx\Db\Table table(string $tableName)
 */
final class AddOffboardingEmailControlToEmploymentHistory extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('adms_employment_history')) {
            return;
        }

        $table = $this->table('adms_employment_history');

        if (!$table->hasColumn('inactivation_email_sent_at')) {
            $table->addColumn('inactivation_email_sent_at', 'datetime', [
                'null' => true,
                'default' => null,
                'comment' => 'Data/hora de envio do e-mail de solicitação de inativação de acessos',
                'after' => 'tipo_impacto_desligamento',
            ])->update();
        }

        $table = $this->table('adms_employment_history');
        if (!$table->hasColumn('inactivation_email_error')) {
            $table->addColumn('inactivation_email_error', 'text', [
                'null' => true,
                'comment' => 'Último erro de envio do e-mail de inativação',
                'after' => 'inactivation_email_sent_at',
            ])->update();
        }
    }

    public function down(): void
    {
        if (!$this->hasTable('adms_employment_history')) {
            return;
        }

        $table = $this->table('adms_employment_history');
        if ($table->hasColumn('inactivation_email_error')) {
            $table->removeColumn('inactivation_email_error')->update();
        }

        $table = $this->table('adms_employment_history');
        if ($table->hasColumn('inactivation_email_sent_at')) {
            $table->removeColumn('inactivation_email_sent_at')->update();
        }
    }
}

