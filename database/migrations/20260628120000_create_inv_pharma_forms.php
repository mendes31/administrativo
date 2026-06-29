<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateInvPharmaForms extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('inv_pharma_forms')) {
            $this->table('inv_pharma_forms')
                ->addColumn('name', 'string', [
                    'limit' => 100,
                    'null' => false,
                    'comment' => 'Descrição da forma farmacêutica (SAP U_FormaFarma)',
                ])
                ->addColumn('created_at', 'timestamp', ['null' => true])
                ->addColumn('updated_at', 'timestamp', ['null' => true])
                ->addIndex(['name'], ['unique' => true, 'name' => 'uniq_inv_pharma_forms_name'])
                ->create();
        }

        if (!$this->hasTable('inv_items')) {
            return;
        }

        $table = $this->table('inv_items');
        if (!$table->hasColumn('inv_pharma_form_id')) {
            $table->addColumn('inv_pharma_form_id', 'integer', [
                'signed' => false,
                'null' => true,
                'after' => 'production_line',
                'comment' => 'Forma farmacêutica (lista SAP)',
            ])->update();
        }

        if ($this->hasTable('inv_pharma_forms') && $table->hasColumn('pharma_form')) {
            $rows = $this->fetchAll(
                "SELECT id, pharma_form FROM inv_items WHERE pharma_form IS NOT NULL AND TRIM(pharma_form) <> ''"
            );
            foreach ($rows as $row) {
                $name = trim((string)($row['pharma_form'] ?? ''));
                if ($name === '') {
                    continue;
                }
                $escaped = str_replace("'", "''", $name);
                $existing = $this->fetchRow("SELECT id FROM inv_pharma_forms WHERE name = '{$escaped}' LIMIT 1");
                if ($existing) {
                    $formId = (int)$existing['id'];
                } else {
                    $this->execute(
                        "INSERT INTO inv_pharma_forms (name, created_at, updated_at) VALUES ('{$escaped}', NOW(), NOW())"
                    );
                    $formId = (int)$this->getAdapter()->getConnection()->lastInsertId();
                }
                $itemId = (int)$row['id'];
                $this->execute("UPDATE inv_items SET inv_pharma_form_id = {$formId} WHERE id = {$itemId}");
            }
        }

        if ($table->hasColumn('pharma_form')) {
            $table->removeColumn('pharma_form')->update();
        }

        if ($this->hasTable('inv_pharma_forms') && $table->hasColumn('inv_pharma_form_id')) {
            $this->table('inv_items')
                ->addForeignKey(
                    'inv_pharma_form_id',
                    'inv_pharma_forms',
                    'id',
                    ['delete' => 'SET_NULL', 'update' => 'CASCADE']
                )
                ->update();
        }
    }

    public function down(): void
    {
        if ($this->hasTable('inv_items') && $this->table('inv_items')->hasColumn('inv_pharma_form_id')) {
            $table = $this->table('inv_items');
            if ($this->hasTable('inv_pharma_forms')) {
                $table->dropForeignKey('inv_pharma_form_id')->save();
            }
            if (!$table->hasColumn('pharma_form')) {
                $table->addColumn('pharma_form', 'string', [
                    'limit' => 100,
                    'null' => true,
                    'after' => 'production_line',
                ])->update();
            }
            $rows = $this->fetchAll(
                'SELECT i.id, pf.name AS pharma_form
                 FROM inv_items i
                 INNER JOIN inv_pharma_forms pf ON pf.id = i.inv_pharma_form_id'
            );
            foreach ($rows as $row) {
                $name = str_replace("'", "''", (string)($row['pharma_form'] ?? ''));
                $itemId = (int)$row['id'];
                $this->execute("UPDATE inv_items SET pharma_form = '{$name}' WHERE id = {$itemId}");
            }
            $table->removeColumn('inv_pharma_form_id')->update();
        }

        if ($this->hasTable('inv_pharma_forms')) {
            $this->table('inv_pharma_forms')->drop()->save();
        }
    }
}
