<?php

declare(strict_types=1);

use App\adms\Database\BaseMigration;

final class AddMediaColumnsToCompanyEvents extends BaseMigration
{
    public function up(): void
    {
        $table = $this->table('adms_company_events');
        if (!$table->hasColumn('anexo')) {
            $table->addColumn('anexo', 'string', [
                'limit' => 255,
                'null' => true,
                'after' => 'description',
                'comment' => 'Caminho relativo do anexo do evento (uploads)',
            ]);
        }
        $table->update();
    }

    public function down(): void
    {
        $table = $this->table('adms_company_events');
        if ($table->hasColumn('anexo')) {
            $table->removeColumn('anexo');
        }
        $table->update();
    }
}
