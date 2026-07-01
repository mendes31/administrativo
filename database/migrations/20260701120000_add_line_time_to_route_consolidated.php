<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddLineTimeToRouteConsolidated extends AbstractMigration
{
    public function up(): void
    {
        if ($this->hasTable('inv_item_route_consolidated_labor')
            && !$this->table('inv_item_route_consolidated_labor')->hasColumn('line_time_minutes')
        ) {
            $this->table('inv_item_route_consolidated_labor')
                ->addColumn('line_time_minutes', 'decimal', [
                    'precision' => 12,
                    'scale' => 6,
                    'null' => true,
                    'after' => 'qty',
                    'comment' => 'Tempo parcial em minutos; NULL = tempo da operação',
                ])
                ->update();
        }

        if ($this->hasTable('inv_item_route_consolidated_resources')
            && !$this->table('inv_item_route_consolidated_resources')->hasColumn('line_time_minutes')
        ) {
            $this->table('inv_item_route_consolidated_resources')
                ->addColumn('line_time_minutes', 'decimal', [
                    'precision' => 12,
                    'scale' => 6,
                    'null' => true,
                    'after' => 'qty',
                    'comment' => 'Tempo parcial em minutos; NULL = tempo da operação',
                ])
                ->update();
        }
    }

    public function down(): void
    {
        if ($this->hasTable('inv_item_route_consolidated_labor')
            && $this->table('inv_item_route_consolidated_labor')->hasColumn('line_time_minutes')
        ) {
            $this->table('inv_item_route_consolidated_labor')->removeColumn('line_time_minutes')->update();
        }

        if ($this->hasTable('inv_item_route_consolidated_resources')
            && $this->table('inv_item_route_consolidated_resources')->hasColumn('line_time_minutes')
        ) {
            $this->table('inv_item_route_consolidated_resources')->removeColumn('line_time_minutes')->update();
        }
    }
}
