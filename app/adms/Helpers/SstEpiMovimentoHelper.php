<?php

declare(strict_types=1);

namespace App\adms\Helpers;

/** Validação de movimentações de estoque EPI (CA por lote). */
final class SstEpiMovimentoHelper
{
    /** Tipos em que o CA do lote é obrigatório. */
    private const TIPOS_COM_CA = ['Entrada', 'Saída', 'Entrega', 'Devolução'];

    /**
     * @param array<string, mixed> $data
     */
    public static function validate(array $data): ?string
    {
        $tipo = (string) ($data['tipo_movimento'] ?? '');
        if ($tipo === '') {
            return 'Informe o tipo de movimentação.';
        }

        if ((int) ($data['adms_sst_epi_id'] ?? 0) <= 0) {
            return 'Selecione o EPI.';
        }

        $dataMov = trim((string) ($data['data_movimento'] ?? ''));
        if ($dataMov === '') {
            return 'Informe a data da movimentação.';
        }

        if ($tipo === 'Ajuste') {
            return null;
        }

        if (!in_array($tipo, self::TIPOS_COM_CA, true)) {
            return null;
        }

        $ca = self::normalizeCa((string) ($data['ca_numero'] ?? ''));
        if ($ca === '') {
            return 'Informe o Nº CA do lote movimentado.';
        }

        return null;
    }

    public static function normalizeCa(string $ca): string
    {
        return strtoupper(preg_replace('/\s+/', '', trim($ca)) ?? '');
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public static function normalize(array $data): array
    {
        $ca = self::normalizeCa((string) ($data['ca_numero'] ?? ''));
        $data['ca_numero'] = $ca !== '' ? $ca : null;

        $validade = trim((string) ($data['ca_validade'] ?? ''));
        $data['ca_validade'] = $validade !== '' ? $validade : null;

        return $data;
    }
}
