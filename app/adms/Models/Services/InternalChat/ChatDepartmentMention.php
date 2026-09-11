<?php

namespace App\adms\Models\Services\InternalChat;

/**
 * Localiza o departamento citado no texto livre (cadastro + sinônimos).
 * Combina com idade/outros filtros no PHP — a IA não precisa “adivinhar” o nome canônico.
 */
final class ChatDepartmentMention
{
    public static function fold(string $value): string
    {
        $value = mb_strtolower(trim($value));
        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;
        $ascii = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
        if (is_string($ascii) && trim($ascii) !== '') {
            $value = mb_strtolower(trim($ascii));
        }

        return $value;
    }

    /**
     * @param list<string> $canonicalNames nomes em adms_departments
     * @param array<string, string> $aliases sinônimo → nome canônico
     */
    public static function match(string $message, array $canonicalNames, array $aliases): ?string
    {
        $hay = ' ' . self::fold($message) . ' ';
        $needles = [];

        foreach ($canonicalNames as $name) {
            $name = trim($name);
            if ($name === '') {
                continue;
            }
            $needles[self::fold($name)] = $name;
        }

        foreach ($aliases as $alias => $canonical) {
            $alias = trim((string) $alias);
            $canonical = trim((string) $canonical);
            if ($alias === '' || $canonical === '') {
                continue;
            }
            $key = self::fold($alias);
            $resolved = $canonical;
            foreach ($canonicalNames as $name) {
                if (self::fold($name) === self::fold($canonical)) {
                    $resolved = $name;
                    break;
                }
            }
            if (!isset($needles[$key]) || mb_strlen($key) >= mb_strlen(self::fold((string) $needles[$key]))) {
                $needles[$key] = $resolved;
            }
        }

        uksort($needles, static fn (string $a, string $b): int => mb_strlen($b) <=> mb_strlen($a));

        foreach ($needles as $folded => $canonical) {
            $len = mb_strlen($folded);
            if ($len < 2) {
                continue;
            }
            $quoted = preg_quote($folded, '/');
            $ok = $len <= 3
                ? (bool) preg_match('/(?<![a-z0-9])' . $quoted . '(?![a-z0-9])/', $hay)
                : str_contains($hay, $folded);
            if ($ok) {
                return $canonical;
            }
        }

        return null;
    }
}
