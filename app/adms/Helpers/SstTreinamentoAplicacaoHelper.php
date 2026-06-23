<?php

declare(strict_types=1);

namespace App\adms\Helpers;

/** Momentos em que o treinamento SST pode ser exigido. */
final class SstTreinamentoAplicacaoHelper
{
    /** @return array<string, string> chave interna => rótulo */
    public static function all(): array
    {
        return [
            'admissional' => 'Admissão',
            'reciclagem' => 'Reciclagem',
            'periodico' => 'Periódico',
            'mudanca_funcao' => 'Mudança de função',
            'retorno_trabalho' => 'Retorno ao trabalho',
            'demissional' => 'Demissional',
        ];
    }

    /** @param list<string>|null $momentos */
    public static function labelList(?array $momentos): string
    {
        if ($momentos === null || $momentos === []) {
            return '-';
        }
        $map = self::all();
        $labels = [];
        foreach ($momentos as $m) {
            if (isset($map[$m])) {
                $labels[] = $map[$m];
            }
        }

        return $labels === [] ? '-' : implode(', ', $labels);
    }

    /** @param list<string> $postValues */
    public static function normalizeFromPost(array $postValues): array
    {
        $valid = array_keys(self::all());
        $selected = [];
        foreach ($postValues as $v) {
            $v = trim((string) $v);
            if ($v !== '' && in_array($v, $valid, true)) {
                $selected[] = $v;
            }
        }

        return array_values(array_unique($selected));
    }

    /** Converte legado `tipo` para momentos (migrations / leitura antiga). */
    public static function fromLegacyTipo(?string $tipo): array
    {
        return match ($tipo) {
            'Inicial' => ['admissional'],
            'Reciclagem' => ['reciclagem', 'periodico'],
            default => ['admissional', 'reciclagem', 'periodico'],
        };
    }

    /** Mantém coluna legada `tipo` para integrações (eSocial etc.). */
    public static function toLegacyTipo(array $momentos): string
    {
        $hasAdmissao = in_array('admissional', $momentos, true);
        $hasReciclagem = in_array('reciclagem', $momentos, true)
            || in_array('periodico', $momentos, true);

        if ($hasAdmissao && !$hasReciclagem) {
            return 'Inicial';
        }
        if ($hasReciclagem && !$hasAdmissao) {
            return 'Reciclagem';
        }

        return 'Ambos';
    }

    /** @param mixed $json */
    public static function decodeFromDb(mixed $json): array
    {
        if ($json === null || $json === '') {
            return [];
        }
        if (is_array($json)) {
            return self::normalizeFromPost($json);
        }
        if (is_string($json)) {
            $decoded = json_decode($json, true);

            return is_array($decoded) ? self::normalizeFromPost($decoded) : [];
        }

        return [];
    }

    /** @param list<string> $momentos */
    public static function encodeForDb(array $momentos): ?string
    {
        $momentos = self::normalizeFromPost($momentos);

        return $momentos === [] ? null : json_encode($momentos, JSON_UNESCAPED_UNICODE);
    }
}
