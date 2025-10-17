<?php
use Phinx\Migration\AbstractMigration;

class CreateInvMovementSequences extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('inv_movement_sequences')) {
            $this->table('inv_movement_sequences', ['id' => false, 'primary_key' => ['type']])
                ->addColumn('type', 'string', ['limit' => 20, 'null' => false])
                ->addColumn('current_number', 'integer', ['null' => false, 'default' => 0])
                ->create();

            // Tipos padrão
            $this->execute("INSERT INTO inv_movement_sequences (type, current_number) VALUES
                ('entry', 0), ('exit', 0), ('transfer', 0), ('adjust', 0)");
        }
        // Adiciona coluna doc_number em inv_movements se não existir
        if ($this->hasTable('inv_movements')) {
            $table = $this->table('inv_movements');
            if (!$table->hasColumn('doc_number')) {
                $table->addColumn('doc_number', 'integer', ['null' => true, 'after' => 'id'])->update();
                $this->execute('UPDATE inv_movements SET doc_number = id WHERE doc_number IS NULL');
            }
        }
    }

    public function down(): void
    {
        if ($this->hasTable('inv_movement_sequences')) {
            $this->table('inv_movement_sequences')->drop()->save();
        }
        if ($this->hasTable('inv_movements')) {
            $table = $this->table('inv_movements');
            if ($table->hasColumn('doc_number')) {
                $table->removeColumn('doc_number')->update();
            }
        }
    }
}








