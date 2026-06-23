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
            // TEXT em vez de JSON: compatível com MySQL/MariaDB antigos em produção.
            $table->addColumn('aplicacao_momentos', 'text', [
                'null' => true,
                'comment' => 'JSON: momentos de exigencia (admissional, reciclagem, etc.)',
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
