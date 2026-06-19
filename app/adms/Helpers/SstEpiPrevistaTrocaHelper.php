<?php

declare(strict_types=1);

namespace App\adms\Helpers;

use App\adms\Models\Repository\SstEpiMovimentosRepository;
use App\adms\Models\Repository\SstEpisRepository;

/** Calcula previsão de substituição de EPI (vida útil × validade do CA). */
final class SstEpiPrevistaTrocaHelper
{
    /**
     * Menor data entre (entrega + vida útil do EPI) e validade do CA do lote.
     * A troca não pode ser prevista após o fim da homologação do CA.
     */
    public static function calcular(string $dataEntrega, int $epiId, ?string $caNumero = null): ?string
    {
        $dataEntrega = trim($dataEntrega);
        if ($dataEntrega === '' || $epiId <= 0) {
            return null;
        }

        $candidatas = [];

        $epi = (new SstEpisRepository())->getById($epiId);
        $dias = (int) ($epi['periodicidade_troca_dias'] ?? 0);
        if ($dias > 0) {
            $ts = strtotime($dataEntrega . ' +' . $dias . ' days');
            if ($ts !== false) {
                $candidatas[] = date('Y-m-d', $ts);
            }
        }

        $ca = SstEpiMovimentoHelper::normalizeCa((string) ($caNumero ?? ''));
        if ($ca !== '') {
            $validadeCa = (new SstEpiMovimentosRepository())->getValidadeCaLote($epiId, $ca);
            if ($validadeCa !== null && $validadeCa !== '') {
                $candidatas[] = $validadeCa;
            }
        }

        if ($candidatas === []) {
            return null;
        }

        sort($candidatas);

        return $candidatas[0];
    }

    /** Aplica data informada, limitada ao teto calculado (não permite data após validade/vida útil). */
    public static function resolver(?string $informada, string $dataEntrega, int $epiId, ?string $caNumero = null): ?string
    {
        $calculada = self::calcular($dataEntrega, $epiId, $caNumero);
        $informada = trim((string) ($informada ?? ''));

        if ($calculada === null) {
            return $informada !== '' ? $informada : null;
        }
        if ($informada === '') {
            return $calculada;
        }

        return min($informada, $calculada);
    }
}
