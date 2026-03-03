<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateCalendarHolidays extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('calendar_settings')) {
            $this->table('calendar_settings')
                ->addColumn('week_start_day', 'integer', [
                    'null' => false,
                    'default' => 1,
                    'comment' => 'Dia em que a semana começa (1=segunda ... 7=domingo)',
                ])
                ->addColumn('weekend_start_day', 'integer', [
                    'null' => false,
                    'default' => 6,
                    'comment' => 'Primeiro dia considerado fim de semana (1=segunda ... 7=domingo)',
                ])
                ->addColumn('weekend_end_day', 'integer', [
                    'null' => false,
                    'default' => 7,
                    'comment' => 'Último dia considerado fim de semana (1=segunda ... 7=domingo)',
                ])
                ->addColumn('valid_for_one_year', 'boolean', [
                    'null' => false,
                    'default' => 1,
                    'comment' => 'Se as configurações são válidas apenas para um ano',
                ])
                ->addColumn('created_at', 'datetime', [
                    'null' => false,
                    'default' => 'CURRENT_TIMESTAMP',
                ])
                ->addColumn('updated_at', 'datetime', [
                    'null' => true,
                    'default' => null,
                ])
                ->create();
        }

        if (!$this->hasTable('calendar_holidays')) {
            $this->table('calendar_holidays')
                ->addColumn('name', 'string', [
                    'limit' => 150,
                    'null' => false,
                    'comment' => 'Nome do feriado (ex.: Carnaval, Natal)',
                ])
                ->addColumn('start_date', 'date', [
                    'null' => false,
                    'comment' => 'Data de início do feriado',
                ])
                ->addColumn('end_date', 'date', [
                    'null' => true,
                    'default' => null,
                    'comment' => 'Data de término (opcional, para feriados que duram mais de um dia)',
                ])
                ->addColumn('observations', 'string', [
                    'limit' => 255,
                    'null' => true,
                    'default' => null,
                ])
                ->addColumn('year', 'integer', [
                    'null' => false,
                    'comment' => 'Ano de referência do feriado (para filtro e configuração anual)',
                ])
                ->addColumn('created_at', 'datetime', [
                    'null' => false,
                    'default' => 'CURRENT_TIMESTAMP',
                ])
                ->addColumn('updated_at', 'datetime', [
                    'null' => true,
                    'default' => null,
                ])
                ->addIndex(['year', 'start_date'])
                ->create();
        }
    }

    public function down(): void
    {
        if ($this->hasTable('calendar_holidays')) {
            $this->table('calendar_holidays')->drop()->save();
        }
        if ($this->hasTable('calendar_settings')) {
            $this->table('calendar_settings')->drop()->save();
        }
    }
}

