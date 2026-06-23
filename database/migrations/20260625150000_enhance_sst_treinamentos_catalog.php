<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class EnhanceSstTreinamentosCatalog extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('adms_sst_treinamentos')) {
            return;
        }

        $table = $this->table('adms_sst_treinamentos');
        if (!$table->hasColumn('aplicacao_momentos')) {
            $table->addColumn('aplicacao_momentos', 'json', [
                'null' => true,
                'comment' => 'Momentos de exigência: admissional, reciclagem, periodico, etc.',
            ])->update();
        }

        $rows = $this->fetchAll('SELECT id, tipo, aplicacao_momentos FROM adms_sst_treinamentos');
        foreach ($rows as $row) {
            if (!empty($row['aplicacao_momentos'])) {
                continue;
            }
            $tipo = (string) ($row['tipo'] ?? 'Ambos');
            $momentos = match ($tipo) {
                'Inicial' => '["admissional"]',
                'Reciclagem' => '["reciclagem","periodico"]',
                default => '["admissional","reciclagem","periodico"]',
            };
            $id = (int) $row['id'];
            $this->execute("UPDATE adms_sst_treinamentos SET aplicacao_momentos = '{$momentos}' WHERE id = {$id}");
        }
    }

    public function down(): void
    {
        if (!$this->hasTable('adms_sst_treinamentos')) {
            return;
        }
        $table = $this->table('adms_sst_treinamentos');
        if ($table->hasColumn('aplicacao_momentos')) {
            $table->removeColumn('aplicacao_momentos')->update();
        }
    }
}
