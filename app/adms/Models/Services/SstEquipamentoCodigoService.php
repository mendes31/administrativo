<?php

declare(strict_types=1);

namespace App\adms\Models\Services;

use App\adms\Helpers\SstEquipamentoCodigoHelper;
use App\adms\Helpers\SstEquipamentoSiteHelper;
use App\adms\Models\Repository\SstEquipamentoTiposRepository;
use PDO;
use RuntimeException;
use Throwable;

/**
 * Aloca o próximo código de equipamento por tipo e site (prefixo + sequência de 5 dígitos).
 */
class SstEquipamentoCodigoService extends DbConnection
{
    /**
     * Reserva e devolve o próximo código (ex.: EXT00001) neste site.
     * Deve ser chamado dentro de uma transação quando possível.
     *
     * @throws RuntimeException
     */
    public function allocateNextCodigo(int $tipoId, string $siteSlug, ?PDO $pdo = null): string
    {
        $pdo = $pdo ?? $this->getConnection();
        $siteSlug = $this->requireSiteSlug($siteSlug);
        $tipo = (new SstEquipamentoTiposRepository())->getById($tipoId);
        if (!$tipo) {
            throw new RuntimeException('Tipo de equipamento não encontrado.');
        }

        $prefixo = SstEquipamentoCodigoHelper::normalizePrefixo((string) ($tipo['prefixo'] ?? ''));
        if (!SstEquipamentoCodigoHelper::isValidPrefixo($prefixo)) {
            throw new RuntimeException('Tipo sem prefixo válido (3 caracteres). Cadastre o prefixo no tipo.');
        }

        $ownsTransaction = !$pdo->inTransaction();
        if ($ownsTransaction) {
            $pdo->beginTransaction();
        }

        try {
            $numero = $this->nextNumero($pdo, $tipoId, $siteSlug, $prefixo);
            $codigo = SstEquipamentoCodigoHelper::format($prefixo, $numero);

            if ($ownsTransaction) {
                $pdo->commit();
            }

            return $codigo;
        } catch (Throwable $e) {
            if ($ownsTransaction && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Pré-visualização do próximo código neste site sem consumir a sequência (pode divergir sob concorrência).
     */
    public function peekNextCodigo(int $tipoId, string $siteSlug): ?string
    {
        $siteSlug = SstEquipamentoSiteHelper::normalize($siteSlug);
        if ($siteSlug === null) {
            return null;
        }
        $tipo = (new SstEquipamentoTiposRepository())->getById($tipoId);
        if (!$tipo) {
            return null;
        }
        $prefixo = SstEquipamentoCodigoHelper::normalizePrefixo((string) ($tipo['prefixo'] ?? ''));
        if (!SstEquipamentoCodigoHelper::isValidPrefixo($prefixo)) {
            return null;
        }

        $pdo = $this->getConnection();
        $stmt = $pdo->prepare(
            'SELECT ultimo_numero FROM adms_sst_equipamento_codigo_seq
             WHERE adms_sst_equipamento_tipo_id = :id AND site_slug = :site LIMIT 1'
        );
        $stmt->bindValue(':id', $tipoId, PDO::PARAM_INT);
        $stmt->bindValue(':site', $siteSlug);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $ultimo = (int) ($row['ultimo_numero'] ?? 0);

        if ($ultimo === 0) {
            $ultimo = $this->maxNumeroFromEquipamentos($pdo, $tipoId, $siteSlug, $prefixo);
        }

        return SstEquipamentoCodigoHelper::format($prefixo, $ultimo + 1);
    }

    private function requireSiteSlug(string $siteSlug): string
    {
        $norm = SstEquipamentoSiteHelper::normalize($siteSlug);
        if ($norm === null) {
            throw new RuntimeException('Selecione a empresa (site). O código é numerado por site.');
        }

        return $norm;
    }

    private function nextNumero(PDO $pdo, int $tipoId, string $siteSlug, string $prefixo): int
    {
        $stmt = $pdo->prepare(
            'SELECT ultimo_numero FROM adms_sst_equipamento_codigo_seq
             WHERE adms_sst_equipamento_tipo_id = :id AND site_slug = :site FOR UPDATE'
        );
        $stmt->bindValue(':id', $tipoId, PDO::PARAM_INT);
        $stmt->bindValue(':site', $siteSlug);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            $seed = $this->maxNumeroFromEquipamentos($pdo, $tipoId, $siteSlug, $prefixo);
            try {
                $insert = $pdo->prepare(
                    'INSERT INTO adms_sst_equipamento_codigo_seq
                        (adms_sst_equipamento_tipo_id, site_slug, ultimo_numero, updated_at)
                     VALUES (:id, :site, :n, NOW())'
                );
                $insert->bindValue(':id', $tipoId, PDO::PARAM_INT);
                $insert->bindValue(':site', $siteSlug);
                $insert->bindValue(':n', $seed, PDO::PARAM_INT);
                $insert->execute();
            } catch (Throwable $e) {
                // Concorrência: outra requisição criou a linha.
            }

            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$row) {
                throw new RuntimeException('Não foi possível alocar sequência de código.');
            }
        }

        $proximo = (int) ($row['ultimo_numero'] ?? 0) + 1;
        $update = $pdo->prepare(
            'UPDATE adms_sst_equipamento_codigo_seq
             SET ultimo_numero = :n, updated_at = NOW()
             WHERE adms_sst_equipamento_tipo_id = :id AND site_slug = :site'
        );
        $update->bindValue(':n', $proximo, PDO::PARAM_INT);
        $update->bindValue(':id', $tipoId, PDO::PARAM_INT);
        $update->bindValue(':site', $siteSlug);
        $update->execute();

        return $proximo;
    }

    private function maxNumeroFromEquipamentos(PDO $pdo, int $tipoId, string $siteSlug, string $prefixo): int
    {
        $slugs = SstEquipamentoSiteHelper::slugsForFilter($siteSlug);
        if ($slugs === []) {
            return 0;
        }

        $placeholders = [];
        foreach ($slugs as $i => $slug) {
            $placeholders[] = ':s' . $i;
        }

        $stmt = $pdo->prepare(
            'SELECT codigo FROM adms_sst_equipamentos
             WHERE adms_sst_equipamento_tipo_id = :id
               AND empresa_contratante IN (' . implode(', ', $placeholders) . ')
               AND codigo LIKE :like
             ORDER BY codigo DESC LIMIT 50'
        );
        $stmt->bindValue(':id', $tipoId, PDO::PARAM_INT);
        foreach ($slugs as $i => $slug) {
            $stmt->bindValue(':s' . $i, $slug);
        }
        $stmt->bindValue(':like', $prefixo . '%');
        $stmt->execute();
        $max = 0;
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $n = SstEquipamentoCodigoHelper::extractNumero((string) ($row['codigo'] ?? ''), $prefixo);
            if ($n !== null && $n > $max) {
                $max = $n;
            }
        }

        return $max;
    }
}
