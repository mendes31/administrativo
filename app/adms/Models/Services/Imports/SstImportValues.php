<?php

declare(strict_types=1);

namespace App\adms\Models\Services\Imports;

final class SstImportValues
{
    public static function v(array $mapped, string $field): string
    {
        return trim((string) ($mapped[$field] ?? ''));
    }

    public static function has(array $mapped, string $field): bool
    {
        return array_key_exists($field, $mapped);
    }

    public static function status(string $raw): ?string
    {
        $n = mb_strtolower(trim($raw));
        if ($n === '') {
            return null;
        }
        if (in_array($n, ['ativo', 'a', '1', 'sim', 's', 'true'], true)) {
            return 'Ativo';
        }
        if (in_array($n, ['inativo', 'i', '0', 'nao', 'não', 'n', 'false'], true)) {
            return 'Inativo';
        }

        return in_array($raw, ['Ativo', 'Inativo'], true) ? $raw : null;
    }

    public static function bool01(string $raw): ?int
    {
        $n = mb_strtolower(trim($raw));
        if ($n === '') {
            return null;
        }
        if (in_array($n, ['1', 'sim', 's', 'true', 'obrigatorio', 'obrigatório', 'yes'], true)) {
            return 1;
        }
        if (in_array($n, ['0', 'nao', 'não', 'n', 'false', 'no'], true)) {
            return 0;
        }

        return null;
    }

    /**
     * @param array<string, mixed> $payload
     * @param array<string, mixed> $existing
     * @param list<string> $fields
     * @return array<string, mixed>
     */
    public static function mergeSkipEmpty(array $payload, array $existing, string $emptyPolicy, array $fields): array
    {
        foreach ($fields as $field) {
            if (!array_key_exists($field, $payload)) {
                if (array_key_exists($field, $existing)) {
                    $payload[$field] = $existing[$field];
                }
                continue;
            }
            $val = $payload[$field];
            if (($val === null || $val === '') && $emptyPolicy === 'skip' && array_key_exists($field, $existing)) {
                $payload[$field] = $existing[$field];
            }
        }

        return $payload;
    }

    /** @return list<string> */
    public static function parseMomentos(string $raw): array
    {
        if (trim($raw) === '') {
            return [];
        }
        $aliases = [
            'admissao' => 'admissional',
            'admissão' => 'admissional',
            'admissional' => 'admissional',
            'reciclagem' => 'reciclagem',
            'periodico' => 'periodico',
            'periódico' => 'periodico',
            'mudanca de funcao' => 'mudanca_funcao',
            'mudança de função' => 'mudanca_funcao',
            'mudanca_funcao' => 'mudanca_funcao',
            'retorno ao trabalho' => 'retorno_trabalho',
            'retorno_trabalho' => 'retorno_trabalho',
            'demissional' => 'demissional',
        ];
        $out = [];
        foreach (preg_split('/[,;|]+/', $raw) ?: [] as $part) {
            $k = mb_strtolower(trim($part));
            if ($k === '') {
                continue;
            }
            if (isset($aliases[$k])) {
                $out[] = $aliases[$k];
            }
        }

        return array_values(array_unique($out));
    }
}
