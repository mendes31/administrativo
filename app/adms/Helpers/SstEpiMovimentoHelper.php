<?php

declare(strict_types=1);

namespace App\adms\Helpers;

/** Validação de movimentações de estoque EPI (CA, custo médio, DOCNUM, justificativa). */
final class SstEpiMovimentoHelper
{
    /** Tipos em que o CA do lote é obrigatório. */
    private const TIPOS_COM_CA = ['Entrada', 'Saída', 'Entrega', 'Devolução'];

    /** Tipos que registram/consomem custo unitário. */
    private const TIPOS_COM_VALOR = ['Entrada', 'Saída', 'Entrega', 'Devolução'];

    /** Tipos que exigem justificativa textual. */
    private const TIPOS_COM_JUSTIFICATIVA = ['Saída', 'Ajuste'];

    /** @var array<string, string> */
    public const SERIES = [
        'Entrada' => 'EM',
        'Saída' => 'SM',
        'Entrega' => 'ET',
        'Devolução' => 'DS',
        'Ajuste' => 'AS',
    ];

    /** @return list<string> */
    public static function motivosSaida(): array
    {
        return [
            'Vencimento',
            'Descarte',
            'Danificado',
            'Doação',
            'Transferência',
            'Perda',
            'Outro',
        ];
    }

    /** @return list<string> */
    public static function motivosAjuste(): array
    {
        return [
            'Contagem física',
            'Quebra',
            'Sobra',
            'Correção de erro',
            'Extravio',
            'Outro',
        ];
    }

    public static function seriePorTipo(string $tipo): ?string
    {
        return self::SERIES[$tipo] ?? null;
    }

    public static function formatDocCodigo(string $serie, int $numero): string
    {
        return strtoupper($serie) . ' ' . str_pad((string) $numero, 6, '0', STR_PAD_LEFT);
    }

    public static function exigeJustificativa(string $tipo): bool
    {
        return in_array($tipo, self::TIPOS_COM_JUSTIFICATIVA, true);
    }

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

        if (self::exigeJustificativa($tipo)) {
            $just = trim((string) ($data['justificativa'] ?? ''));
            if ($just === '') {
                return 'Informe a justificativa para ' . $tipo . '.';
            }
            if (mb_strlen($just) < 5) {
                return 'A justificativa deve ter ao menos 5 caracteres.';
            }
            $motivo = trim((string) ($data['motivo'] ?? ''));
            if ($motivo === '') {
                return 'Selecione o motivo de ' . $tipo . '.';
            }
            $allowed = $tipo === 'Saída' ? self::motivosSaida() : self::motivosAjuste();
            if (!in_array($motivo, $allowed, true)) {
                return 'Motivo inválido para ' . $tipo . '.';
            }
            if ($motivo === 'Outro' && mb_strlen($just) < 10) {
                return 'Para motivo "Outro", detalhe melhor na justificativa (mín. 10 caracteres).';
            }
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

        if ($tipo === 'Entrada') {
            $unit = self::parseMoney($data['valor_unitario'] ?? null);
            if ($unit === null || $unit < 0) {
                return 'Informe o valor unitário (R$) da entrada.';
            }
        }

        return null;
    }

    public static function normalizeCa(string $ca): string
    {
        return strtoupper(preg_replace('/\s+/', '', trim($ca)) ?? '');
    }

    /**
     * Aceita 12,50 | 12.50 | 1.234,56
     */
    public static function parseMoney(mixed $raw): ?float
    {
        if ($raw === null || $raw === '') {
            return null;
        }
        if (is_int($raw) || is_float($raw)) {
            return round((float) $raw, 2);
        }

        $s = trim((string) $raw);
        $s = preg_replace('/[^\d,.\-]/', '', $s) ?? '';
        if ($s === '' || $s === '-' || $s === '.' || $s === ',') {
            return null;
        }

        if (str_contains($s, ',') && str_contains($s, '.')) {
            $s = str_replace('.', '', $s);
            $s = str_replace(',', '.', $s);
        } elseif (str_contains($s, ',')) {
            $s = str_replace(',', '.', $s);
        }

        if (!is_numeric($s)) {
            return null;
        }

        return round((float) $s, 2);
    }

    public static function formatMoney(?float $value): string
    {
        if ($value === null) {
            return '—';
        }

        return 'R$ ' . number_format($value, 2, ',', '.');
    }

    public static function tipoUsaValor(string $tipo): bool
    {
        return in_array($tipo, self::TIPOS_COM_VALOR, true);
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

        $data['motivo'] = trim((string) ($data['motivo'] ?? '')) ?: null;
        $data['justificativa'] = trim((string) ($data['justificativa'] ?? '')) ?: null;

        $tipo = (string) ($data['tipo_movimento'] ?? '');
        if (!self::tipoUsaValor($tipo) && $tipo !== 'Ajuste') {
            $data['valor_unitario'] = null;
            $data['valor_total'] = null;

            return $data;
        }

        if ($tipo === 'Ajuste') {
            return $data;
        }

        $unit = self::parseMoney($data['valor_unitario'] ?? null);
        $data['valor_unitario'] = $unit;
        $qty = abs((int) ($data['quantidade'] ?? 0));
        if ($unit !== null && $qty > 0) {
            $data['valor_total'] = round($unit * $qty, 2);
        } else {
            $data['valor_total'] = self::parseMoney($data['valor_total'] ?? null);
        }

        return $data;
    }
}
