<?php

declare(strict_types=1);

namespace App\adms\Models\Services;

use App\adms\Helpers\SstCidCapituloHelper;
use PDO;

/**
 * Importa CID-10 brasileiro (DATASUS) para adms_sst_cids.
 * Arquivos esperados em database/seeds/data/cid10_raw/ (extraídos de CID10CSV.zip).
 */
class SstCid10Importer extends DbConnection
{
    /** @var list<string> */
    private const FREQUENT_EXACT = [
        'M54.5', 'M54.4', 'M75.1', 'M75.4', 'M77.1', 'M65.4', 'M79.6', 'M25.5', 'M51.1', 'G56.0',
        'F32.0', 'F32.1', 'F32.2', 'F32.3', 'F33.0', 'F33.1', 'F41.0', 'F41.1', 'F43.1', 'F43.2',
        'F48.0', 'Z73.0', 'T14.9', 'T15.0', 'T20.0', 'T63.0',
        'S52.0', 'S62.0', 'S72.0', 'S82.0', 'S92.0',
        'J00', 'J06.9', 'J18.9', 'J45', 'J44', 'J30',
        'B34.2', 'U07.1', 'A09', 'B27', 'J10',
        'H83.3', 'J68', 'L23',
    ];

    /** @var list<string> */
    private const FREQUENT_PREFIX = [
        'F32', 'F33', 'F41', 'F43', 'S60', 'S61', 'S62', 'S63', 'S80', 'S81', 'S82', 'S90', 'S91', 'S92',
        'S40', 'S41', 'S50', 'S51', 'T20', 'T21', 'T22', 'T23', 'T24', 'T25', 'T26', 'T27', 'T28', 'T29', 'T30', 'T31', 'T32',
    ];

  /** @var array<string, array{num: int, nome: string}> */
    private array $capitulosByRange = [];

    public function __construct(?string $dataDir = null)
    {
        $this->loadCapitulosFromCsv($dataDir);
    }

    /**
     * @return array{inserted: int, updated: int, skipped: int, total: int}
     */
    public function import(?string $dataDir = null): array
    {
        $dir = $dataDir ?? $this->defaultDataDir();
        $subFile = $dir . DIRECTORY_SEPARATOR . 'CID-10-SUBCATEGORIAS.CSV';
        $catFile = $dir . DIRECTORY_SEPARATOR . 'CID-10-CATEGORIAS.CSV';

        if (!is_readable($subFile)) {
            throw new \RuntimeException(
                'Arquivo CID-10 não encontrado. Extraia CID10CSV.zip em database/seeds/data/cid10_raw/'
            );
        }

        $rows = [];
        $this->parseSubcategorias($subFile, $rows);
        if (is_readable($catFile)) {
            $this->parseCategorias($catFile, $rows);
        }

        return $this->persistRows($rows);
    }

    public function defaultDataDir(): string
    {
        return dirname(__DIR__, 4) . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'seeds'
            . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'cid10_raw';
    }

    /**
     * @param array<string, array{codigo: string, descricao: string, capitulo_num: ?int, capitulo_nome: ?string, categoria: ?string, frequente: bool}> $rows
     * @return array{inserted: int, updated: int, skipped: int, total: int}
     */
    private function persistRows(array $rows): array
    {
        $conn = $this->getConnection();
        $inserted = 0;
        $updated = 0;
        $skipped = 0;
        $batch = [];
        $batchSize = 300;

        $sql = 'INSERT INTO adms_sst_cids (codigo, descricao, capitulo_num, capitulo_nome, categoria, frequente, status, created_at, updated_at)
                VALUES (:codigo, :descricao, :capitulo_num, :capitulo_nome, :categoria, :frequente, :status, NOW(), NOW())
                ON DUPLICATE KEY UPDATE
                    descricao = VALUES(descricao),
                    capitulo_num = VALUES(capitulo_num),
                    capitulo_nome = VALUES(capitulo_nome),
                    categoria = VALUES(categoria),
                    frequente = GREATEST(frequente, VALUES(frequente)),
                    updated_at = NOW()';

        $stmt = $conn->prepare($sql);

        foreach ($rows as $row) {
            $batch[] = $row;
            if (count($batch) >= $batchSize) {
                [$i, $u, $s] = $this->flushBatch($stmt, $batch);
                $inserted += $i;
                $updated += $u;
                $skipped += $s;
                $batch = [];
            }
        }

        if ($batch !== []) {
            [$i, $u, $s] = $this->flushBatch($stmt, $batch);
            $inserted += $i;
            $updated += $u;
            $skipped += $s;
        }

        return [
            'inserted' => $inserted,
            'updated' => $updated,
            'skipped' => $skipped,
            'total' => count($rows),
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $batch
     * @return array{0: int, 1: int, 2: int}
     */
    private function flushBatch(\PDOStatement $stmt, array $batch): array
    {
        $inserted = 0;
        $updated = 0;
        $skipped = 0;

        foreach ($batch as $row) {
            try {
                $stmt->bindValue(':codigo', $row['codigo']);
                $stmt->bindValue(':descricao', $row['descricao']);
                $stmt->bindValue(':capitulo_num', $row['capitulo_num'], $row['capitulo_num'] === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
                $stmt->bindValue(':capitulo_nome', $row['capitulo_nome']);
                $stmt->bindValue(':categoria', $row['categoria']);
                $stmt->bindValue(':frequente', $row['frequente'] ? 1 : 0, PDO::PARAM_INT);
                $stmt->bindValue(':status', 'Ativo');
                $stmt->execute();
                $affected = $stmt->rowCount();
                if ($affected === 1) {
                    $inserted++;
                } elseif ($affected === 2) {
                    $updated++;
                } else {
                    $skipped++;
                }
            } catch (\Throwable) {
                $skipped++;
            }
        }

        return [$inserted, $updated, $skipped];
    }

    /**
     * @param array<string, array{codigo: string, descricao: string, capitulo_num: ?int, capitulo_nome: ?string, categoria: ?string, frequente: bool}> $rows
     */
    private function parseSubcategorias(string $file, array &$rows): void
    {
        $handle = fopen($file, 'rb');
        if ($handle === false) {
            return;
        }

        $header = true;
        while (($line = fgets($handle)) !== false) {
            if ($header) {
                $header = false;
                continue;
            }
            $line = $this->toUtf8($line);
            $parts = str_getcsv(trim($line), ';');
            if (count($parts) < 5) {
                continue;
            }
            $subcat = trim($parts[0] ?? '');
            $descricao = trim($parts[4] ?? '');
            if ($subcat === '' || $descricao === '') {
                continue;
            }
            $codigo = SstCidCapituloHelper::formatCodigoFromSubcat($subcat);
            $this->addRow($rows, $codigo, $descricao);
        }
        fclose($handle);
    }

    /**
     * @param array<string, array{codigo: string, descricao: string, capitulo_num: ?int, capitulo_nome: ?string, categoria: ?string, frequente: bool}> $rows
     */
    private function parseCategorias(string $file, array &$rows): void
    {
        $handle = fopen($file, 'rb');
        if ($handle === false) {
            return;
        }

        $header = true;
        while (($line = fgets($handle)) !== false) {
            if ($header) {
                $header = false;
                continue;
            }
            $line = $this->toUtf8($line);
            $parts = str_getcsv(trim($line), ';');
            if (count($parts) < 3) {
                continue;
            }
            $codigo = strtoupper(trim($parts[0] ?? ''));
            $descricao = trim($parts[2] ?? '');
            if ($codigo === '' || $descricao === '') {
                continue;
            }
            $this->addRow($rows, $codigo, $descricao);
        }
        fclose($handle);
    }

    /**
     * @param array<string, array{codigo: string, descricao: string, capitulo_num: ?int, capitulo_nome: ?string, categoria: ?string, frequente: bool}> $rows
     */
    private function addRow(array &$rows, string $codigo, string $descricao): void
    {
        if ($codigo === '' || isset($rows[$codigo])) {
            return;
        }

        $cap = SstCidCapituloHelper::resolveFromCodigo($codigo);
        $rows[$codigo] = [
            'codigo' => $codigo,
            'descricao' => $descricao,
            'capitulo_num' => $cap['num'] ?? null,
            'capitulo_nome' => $cap['nome'] ?? null,
            'categoria' => SstCidCapituloHelper::categoriaFromCodigo($codigo),
            'frequente' => $this->isFrequente($codigo),
        ];
    }

    private function isFrequente(string $codigo): bool
    {
        if (in_array($codigo, self::FREQUENT_EXACT, true)) {
            return true;
        }
        $base = strtoupper(str_replace('.', '', $codigo));
        foreach (self::FREQUENT_PREFIX as $prefix) {
            $p = str_replace('.', '', $prefix);
            if (str_starts_with($base, $p)) {
                return true;
            }
        }

        return false;
    }

    private function loadCapitulosFromCsv(?string $dataDir): void
    {
        $dir = $dataDir ?? $this->defaultDataDir();
        $file = $dir . DIRECTORY_SEPARATOR . 'CID-10-CAPITULOS.CSV';
        if (!is_readable($file)) {
            return;
        }

        $handle = fopen($file, 'rb');
        if ($handle === false) {
            return;
        }

        $header = true;
        while (($line = fgets($handle)) !== false) {
            if ($header) {
                $header = false;
                continue;
            }
            $line = $this->toUtf8($line);
            $parts = str_getcsv(trim($line), ';');
            if (count($parts) < 4) {
                continue;
            }
            $num = (int) ($parts[0] ?? 0);
            $nome = trim($parts[3] ?? '');
            if ($num > 0 && $nome !== '') {
                $nome = preg_replace('/^Capítulo\s+[IVXLC\d]+\s*-\s*/i', '', $nome) ?? $nome;
                $this->capitulosByRange[$parts[1] . '-' . $parts[2]] = ['num' => $num, 'nome' => $nome];
            }
        }
        fclose($handle);
    }

    private function toUtf8(string $line): string
    {
        if (!mb_check_encoding($line, 'UTF-8')) {
            return mb_convert_encoding($line, 'UTF-8', 'ISO-8859-1');
        }

        return $line;
    }
}
