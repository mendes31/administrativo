<?php

declare(strict_types=1);

namespace App\adms\Models\Services;

/**
 * Motor dos três métodos de formação de preço (planilha Physician Choice).
 */
final class FormacaoPrecoCalculator
{
    public const METHOD_PB = 'pb';
    public const METHOD_RL = 'rl';
    public const METHOD_MK = 'mk';

    /**
     * Imposto efetivo = ICMS + ((1 - ICMS) × (PIS + COFINS)).
     */
    public static function impostoEfetivo(float $icms, float $pis, float $cofins): float
    {
        return $icms + ((1 - $icms) * ($pis + $cofins));
    }

    public static function fatorPorDentro(float $impostoEfetivo): ?float
    {
        if ($impostoEfetivo >= 1) {
            return null;
        }

        $den = 1 - $impostoEfetivo;
        if ($den == 0.0) {
            return null;
        }

        return 1 / $den;
    }

    public static function tetoPb(float $imposto, float $frete, float $comissao): float
    {
        return 1 - $imposto - $frete - $comissao;
    }

    public static function tetoRl(float $imposto, float $frete, float $comissao): float
    {
        $base = 1 - $imposto;
        if ($base <= 0) {
            return 0.0;
        }

        return 1 - (($frete + $comissao) / $base);
    }

    /**
     * Percentual utilizável (um pouco abaixo do teto teórico, como a planilha).
     */
    public static function tetoUtilizavel(float $tetoTeorico): float
    {
        return max(0.0, $tetoTeorico - 0.0001);
    }

    /**
     * @param array{
     *   icms?: float|null,
     *   pis?: float|null,
     *   cofins?: float|null,
     *   custo_variavel?: float|null,
     *   custo_fixo?: float|null,
     *   frete?: float|null,
     *   comissao?: float|null,
     *   desconto?: float|null,
     *   meta_pb?: float|null,
     *   meta_rl?: float|null,
     *   meta_mk?: float|null,
     *   preco_informado?: float|null,
     *   forecast?: float|null
     * } $in
     * @return array<string, mixed>
     */
    public static function calcularLinha(array $in): array
    {
        $icms = self::f($in['icms'] ?? 0);
        $pis = self::f($in['pis'] ?? 0);
        $cofins = self::f($in['cofins'] ?? 0);
        $cvar = self::f($in['custo_variavel'] ?? 0);
        $cfix = self::f($in['custo_fixo'] ?? 0);
        $frete = self::f($in['frete'] ?? 0);
        $comissao = self::f($in['comissao'] ?? 0);
        $desconto = self::f($in['desconto'] ?? 0);
        $forecast = self::nullable($in['forecast'] ?? null);
        $precoInf = self::nullable($in['preco_informado'] ?? null);

        $custo = $cvar + $cfix;
        $imposto = self::impostoEfetivo($icms, $pis, $cofins);
        $fator = self::fatorPorDentro($imposto);

        $shared = [
            'custo_total' => $custo,
            'imposto_efetivo' => $imposto,
            'fator_por_dentro' => $fator,
            'teto_pb' => self::tetoPb($imposto, $frete, $comissao),
            'teto_rl' => self::tetoRl($imposto, $frete, $comissao),
        ];

        return $shared + [
            'pb' => self::calcularMetodo($in + $shared, self::METHOD_PB, $precoInf, $forecast),
            'rl' => self::calcularMetodo($in + $shared, self::METHOD_RL, $precoInf, $forecast),
            'mk' => self::calcularMetodo($in + $shared, self::METHOD_MK, $precoInf, $forecast),
        ];
    }

    /**
     * @param array<string, mixed> $in
     * @return array<string, mixed>
     */
    public static function calcularMetodo(array $in, string $metodo, ?float $precoInf, ?float $forecast): array
    {
        $custo = self::f($in['custo_total'] ?? 0);
        $imposto = self::f($in['imposto_efetivo'] ?? 0);
        $icms = self::f($in['icms'] ?? 0);
        $pis = self::f($in['pis'] ?? 0);
        $cofins = self::f($in['cofins'] ?? 0);
        $frete = self::f($in['frete'] ?? 0);
        $comissao = self::f($in['comissao'] ?? 0);
        $desconto = self::f($in['desconto'] ?? 0);

        $metaKey = match ($metodo) {
            self::METHOD_PB => 'meta_pb',
            self::METHOD_RL => 'meta_rl',
            default => 'meta_mk',
        };
        $meta = self::nullable($in[$metaKey] ?? null);

        $emptyMeta = $meta === null;
        $emptyPreco = $precoInf === null;
        if (!$emptyMeta && !$emptyPreco) {
            $status = 'VERIFICAR: informe % OU preço';
        } elseif ($emptyMeta && $emptyPreco) {
            $status = 'INFORMAR % OU PREÇO';
        } elseif (!$emptyPreco) {
            $status = 'OK - preço informado';
        } else {
            $status = 'OK - preço calculado';
        }

        $t = null;
        if ($precoInf !== null) {
            $t = $precoInf > 0 ? $precoInf : null;
        } elseif ($meta !== null) {
            $t = self::precoPelaMeta($metodo, $custo, $imposto, $frete, $comissao, $meta);
            if ($t === null) {
                $teto = $metodo === self::METHOD_RL
                    ? self::tetoRl($imposto, $frete, $comissao)
                    : ($metodo === self::METHOD_PB
                        ? self::tetoPb($imposto, $frete, $comissao)
                        : self::tetoPb($imposto, $frete, $comissao));
                if ($metodo === self::METHOD_MK) {
                    $status = 'INVÁLIDO: imposto + frete + comissão ≥ 100%';
                } else {
                    $uso = self::tetoUtilizavel($teto);
                    $status = 'INVÁLIDO: use até ' . self::formatPctLabel($uso) . ' nesta linha';
                }
            }
        }

        $precoTabela = null;
        if ($t !== null) {
            $precoTabela = ($desconto >= 1) ? null : $t / (1 - $desconto);
        }

        $precoBase = null;
        if ($meta !== null) {
            if ($metodo === self::METHOD_MK) {
                $precoBase = $custo * (1 + $meta);
            } elseif ($meta < 1) {
                $precoBase = $custo / (1 - $meta);
            }
        }

        $empty = [
            'status' => $status,
            'preco_base' => $precoBase,
            'preco_vendido' => $t,
            'preco_tabela' => $precoTabela,
            'icms_r' => null,
            'base_pis_cofins' => null,
            'pis_r' => null,
            'cofins_r' => null,
            'impostos_r' => null,
            'receita_liquida' => null,
            'frete_r' => null,
            'comissao_r' => null,
            'lucro_unitario' => null,
            'margem_rl' => null,
            'margem_pb' => null,
            'markup' => null,
            'validacao' => $status === 'VERIFICAR: informe % OU preço' ? 'VERIFICAR entrada' : ($t === null ? 'INVÁLIDO' : ''),
            'lucro_projeto' => null,
            'receita_bruta' => null,
        ];

        if ($t === null) {
            return $empty;
        }

        $icmsR = $t * $icms;
        $basePis = $t - $icmsR;
        $pisR = $basePis * $pis;
        $cofinsR = $basePis * $cofins;
        $impostos = $icmsR + $pisR + $cofinsR;
        $rl = $t - $impostos;
        $freteR = $t * $frete;
        $comissaoR = $t * $comissao;
        $lucro = $rl - $custo - $freteR - $comissaoR;
        $margemRl = $rl > 0 ? $lucro / $rl : null;
        $margemPb = $t > 0 ? $lucro / $t : null;
        $markup = $custo > 0 ? $lucro / $custo : null;

        $validacao = 'OK';
        if (!$emptyMeta && !$emptyPreco) {
            $validacao = 'VERIFICAR entrada';
        } elseif ($precoInf !== null) {
            $validacao = 'PREÇO INFORMADO: margens calculadas';
        } else {
            $real = match ($metodo) {
                self::METHOD_PB => $margemPb,
                self::METHOD_RL => $margemRl,
                default => $markup,
            };
            if ($real === null || abs($real - (float) $meta) >= 0.0001) {
                $validacao = 'VERIFICAR';
            }
        }

        $fc = $forecast ?? 0.0;

        return [
            'status' => $status,
            'preco_base' => $precoBase,
            'preco_vendido' => $t,
            'preco_tabela' => $precoTabela,
            'icms_r' => $icmsR,
            'base_pis_cofins' => $basePis,
            'pis_r' => $pisR,
            'cofins_r' => $cofinsR,
            'impostos_r' => $impostos,
            'receita_liquida' => $rl,
            'frete_r' => $freteR,
            'comissao_r' => $comissaoR,
            'lucro_unitario' => $lucro,
            'margem_rl' => $margemRl,
            'margem_pb' => $margemPb,
            'markup' => $markup,
            'validacao' => $validacao,
            'lucro_projeto' => $fc * $lucro,
            'receita_bruta' => $fc * $t,
        ];
    }

    public static function precoPelaMeta(
        string $metodo,
        float $custo,
        float $imposto,
        float $frete,
        float $comissao,
        float $meta
    ): ?float {
        if ($metodo === self::METHOD_PB) {
            $den = 1 - $imposto - $frete - $comissao - $meta;
            if ($den <= 0) {
                return null;
            }

            return $custo / $den;
        }
        if ($metodo === self::METHOD_RL) {
            $den = ((1 - $imposto) * (1 - $meta)) - $frete - $comissao;
            if ($den <= 0) {
                return null;
            }

            return $custo / $den;
        }
        $den = 1 - $imposto - $frete - $comissao;
        if ($den <= 0) {
            return null;
        }

        return $custo * (1 + $meta) / $den;
    }

    /**
     * Interpreta campo de percentual da tela (12,00 → 0,12).
     */
    public static function parsePercentInput(mixed $raw): ?float
    {
        $n = self::parseDecimalInput($raw);
        if ($n === null) {
            return null;
        }

        return $n / 100;
    }

    public static function parseDecimalInput(mixed $raw): ?float
    {
        if ($raw === null) {
            return null;
        }
        $s = trim((string) $raw);
        if ($s === '') {
            return null;
        }
        $s = str_replace(["\u{00A0}", ' '], '', $s);
        if (str_contains($s, ',') && str_contains($s, '.')) {
            $s = str_replace('.', '', $s);
            $s = str_replace(',', '.', $s);
        } else {
            $s = str_replace(',', '.', $s);
        }
        if (!is_numeric($s)) {
            return null;
        }

        return (float) $s;
    }

    public static function formatMoney(?float $v, int $dec = 2): string
    {
        if ($v === null || !is_finite($v)) {
            return '';
        }

        return number_format($v, $dec, ',', '.');
    }

    public static function formatPercent(?float $fraction, int $dec = 2): string
    {
        if ($fraction === null || !is_finite($fraction)) {
            return '';
        }

        return number_format($fraction * 100, $dec, ',', '.');
    }

    public static function formatPctLabel(float $fraction): string
    {
        return number_format(floor($fraction * 10000) / 100, 2, ',', '.') . '%';
    }

    private static function f(mixed $v): float
    {
        return is_numeric($v) ? (float) $v : 0.0;
    }

    private static function nullable(mixed $v): ?float
    {
        if ($v === null || $v === '') {
            return null;
        }
        if (!is_numeric($v)) {
            return null;
        }

        return (float) $v;
    }
}
