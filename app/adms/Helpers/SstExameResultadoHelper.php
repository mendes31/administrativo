<?php

declare(strict_types=1);

namespace App\adms\Helpers;

/**
 * Resultados do exame complementar ao lançar no ASO (achados clínicos/laboratoriais).
 * A conclusão ocupacional (Apto / Inapto / Apto com restrição) fica no campo resultado do ASO.
 */
final class SstExameResultadoHelper
{
    public const NORMAL = 'Normal';
    public const ALTERADO = 'Alterado';

    /** @deprecated Legado — não usar no cadastro de exames; mantido só para leitura de dados antigos. */
    public const APTO = 'Apto';
    /** @deprecated Legado */
    public const INAPTO = 'Inapto';
    /** @deprecated Legado */
    public const APTO_RESTRICAO = 'Apto com Restrição';

    /** Opções exibidas no cadastro do exame complementar. */
    public static function catalogOptions(): array
    {
        return [
            self::NORMAL,
            self::ALTERADO,
        ];
    }

    public static function isCatalogOption(?string $value): bool
    {
        return $value !== null && $value !== '' && in_array($value, self::catalogOptions(), true);
    }

    /**
     * @param list<string> $values
     * @return list<string>
     */
    public static function filterCatalogOptions(array $values): array
    {
        $out = [];
        foreach ($values as $v) {
            if (!is_string($v) || $v === '') {
                continue;
            }
            if (self::isCatalogOption($v) && !in_array($v, $out, true)) {
                $out[] = $v;
            }
        }

        return $out;
    }

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return array_values(array_unique(array_merge(
            self::catalogOptions(),
            [self::APTO, self::INAPTO, self::APTO_RESTRICAO]
        )));
    }

    public static function isValid(?string $value): bool
    {
        return $value !== null && $value !== '' && in_array($value, self::all(), true);
    }

    /**
     * @param list<string> $values
     * @return list<string>
     */
    public static function filterValid(array $values): array
    {
        $out = [];
        foreach ($values as $v) {
            if (!is_string($v) || $v === '') {
                continue;
            }
            if (self::isValid($v) && !in_array($v, $out, true)) {
                $out[] = $v;
            }
        }

        return $out;
    }

    /**
     * @param list<string> $values
     */
    public static function encodeCatalog(array $values): ?string
    {
        $filtered = self::filterCatalogOptions($values);
        if ($filtered === []) {
            return null;
        }

        return json_encode($filtered, JSON_UNESCAPED_UNICODE);
    }

    /**
     * @param list<string> $values
     */
    public static function encode(array $values): ?string
    {
        return self::encodeCatalog($values);
    }

    /**
     * @return list<string>
     */
    public static function decode(?string $json): array
    {
        if ($json === null || $json === '') {
            return [];
        }

        $decoded = json_decode($json, true);
        if (!is_array($decoded)) {
            return [];
        }

        return self::filterValid(array_map('strval', $decoded));
    }

    /** @return list<string> */
    public static function decodeCatalog(?string $json): array
    {
        return self::filterCatalogOptions(self::decode($json));
    }

    /**
     * Opções do select de resultado na linha do exame complementar do ASO.
     *
     * @return list<string>
     */
    public static function optionsForComplementaryLaunch(bool $exigeResultado, ?string $tipoExame = null): array
    {
        if (!$exigeResultado) {
            return [];
        }

        return SstExameTipoHelper::defaultResultadoOptions($tipoExame);
    }

    /**
     * @deprecated Use optionsForComplementaryLaunch()
     *
     * @return list<string>
     */
    public static function optionsForAsoLine(?string $json): array
    {
        return self::decodeCatalog($json) !== [] ? self::decodeCatalog($json) : self::catalogOptions();
    }

    public static function labelList(?string $json): string
    {
        $items = self::decodeCatalog($json);

        return $items === [] ? '-' : implode(', ', $items);
    }
}
