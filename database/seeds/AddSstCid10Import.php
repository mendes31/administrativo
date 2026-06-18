<?php

declare(strict_types=1);

use App\adms\Models\Services\SstCid10Importer;
use Phinx\Seed\AbstractSeed;

/**
 * Importa tabela completa CID-10 (DATASUS) para adms_sst_cids.
 *
 * Pré-requisito: extrair CID10CSV.zip em database/seeds/data/cid10_raw/
 * Download: http://www2.datasus.gov.br/cid10/V2008/downloads/CID10CSV.zip
 */
class AddSstCid10Import extends AbstractSeed
{
    public function run(): void
    {
        $importer = new SstCid10Importer();
        $dir = $importer->defaultDataDir();

        if (!is_dir($dir)) {
            echo "⚠️  Diretório não encontrado: {$dir}\n";
            echo "   Baixe CID10CSV.zip do DATASUS e extraia em database/seeds/data/cid10_raw/\n";

            return;
        }

        echo "Importando CID-10 de {$dir}...\n";

        try {
            $result = $importer->import($dir);
            echo sprintf(
                "✓ CID-10: %d códigos processados (%d novos, %d atualizados, %d ignorados)\n",
                $result['total'],
                $result['inserted'],
                $result['updated'],
                $result['skipped']
            );
        } catch (\Throwable $e) {
            echo '✗ Erro na importação CID-10: ' . $e->getMessage() . "\n";
        }
    }
}
