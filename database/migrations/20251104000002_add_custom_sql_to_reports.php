<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddCustomSqlToReports extends AbstractMigration
{
    public function up(): void
    {
        $table = $this->table('adms_dynamic_reports');
        
        if (!$table->hasColumn('custom_sql')) {
            $table->addColumn('custom_sql', 'text', ['null' => true, 'comment' => 'SQL personalizado', 'after' => 'data_source'])
                  ->save();
        }
        
        if (!$table->hasColumn('query_mode')) {
            $table->addColumn('query_mode', 'enum', ['values' => ['builder', 'custom_sql'], 'default' => 'builder', 'after' => 'custom_sql'])
                  ->save();
        }
        
        // Alterar data_source para permitir NULL (quando usar SQL customizado)
        if ($table->hasColumn('data_source')) {
            $table->changeColumn('data_source', 'string', ['limit' => 100, 'null' => true])
                  ->save();
        }
        
        // Alterar fields para permitir NULL (quando usar SQL customizado)
        if ($table->hasColumn('fields')) {
            $table->changeColumn('fields', 'text', ['null' => true, 'comment' => 'JSON'])
                  ->save();
        }
    }

    public function down(): void
    {
        $table = $this->table('adms_dynamic_reports');
        
        if ($table->hasColumn('query_mode')) {
            $table->removeColumn('query_mode')->save();
        }
        
        if ($table->hasColumn('custom_sql')) {
            $table->removeColumn('custom_sql')->save();
        }
    }
}

