<?php

declare(strict_types=1);

use App\adms\Helpers\SstEpiCategoriaHelper;
use Phinx\Migration\AbstractMigration;

/** Alinha categorias gravadas (ex.: "Proteção de Tronco") ao valor do combo. */
final class SstEpiCategoriaCanonicalize extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('adms_sst_epis') || !$this->table('adms_sst_epis')->hasColumn('categoria')) {
            return;
        }

        $rows = $this->fetchAll('SELECT id, categoria FROM adms_sst_epis');
        $pdo = $this->getAdapter()->getConnection();
        foreach ($rows as $row) {
            $id = (int) ($row['id'] ?? 0);
            $atual = (string) ($row['categoria'] ?? '');
            $canon = SstEpiCategoriaHelper::canonicalize($atual);
            if ($id <= 0 || $canon === null || $canon === $atual) {
                continue;
            }
            $stmt = $pdo->prepare('UPDATE adms_sst_epis SET categoria = :c WHERE id = :id');
            $stmt->execute([':c' => $canon, ':id' => $id]);
        }
    }

    public function down(): void
    {
    }
}
