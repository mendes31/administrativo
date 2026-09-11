<?php

declare(strict_types=1);

namespace App\adms\Helpers;

/** Grade de numeração/tamanho do EPI (cadastro único; saldo por tamanho na movimentação). */
final class SstEpiTamanhoHelper
{
    public const PRESET_NENHUM = '';
    public const PRESET_CALCADO = 'calcado';
    public const PRESET_VESTUARIO = 'vestuario';
    public const PRESET_PERSONALIZADA = 'personalizada';

    /** @return list<string> */
    public static function calcado(): array
    {
        $out = [];
        for ($n = 33; $n <= 46; $n++) {
            $out[] = (string) $n;
        }

        return $out;
    }

    /** @return list<string> */
    public static function vestuario(): array
    {
        return ['PP', 'P', 'M', 'G', 'GG', 'XG', 'XXG'];
    }

    /**
     * @return array<string, string>
     */
    public static function presetLabels(): array
    {
        return [
            self::PRESET_NENHUM => 'Não controla tamanho',
            self::PRESET_CALCADO => 'Calçado (nº 33 a 46)',
            self::PRESET_VESTUARIO => 'Vestuário (PP a XXG)',
            self::PRESET_PERSONALIZADA => 'Grade personalizada',
        ];
    }

    public static function normalize(string $raw): string
    {
        $s = trim($raw);
        if ($s === '') {
            return '';
        }
        $s = preg_replace('/^(n[ºo°.\s]+|tam(?:anho)?[.\s:]*)/iu', '', $s) ?? $s;
        $s = strtoupper(preg_replace('/\s+/', '', trim($s)) ?? '');

        return $s;
    }

    /**
     * @return list<string>
     */
    public static function parseGrade(?string $raw): array
    {
        $raw = trim((string) $raw);
        if ($raw === '') {
            return [];
        }
        $key = strtolower($raw);
        if ($key === self::PRESET_CALCADO) {
            return self::calcado();
        }
        if ($key === self::PRESET_VESTUARIO) {
            return self::vestuario();
        }
        $parts = preg_split('/[,;|\n\r\/]+/', $raw) ?: [];
        $out = [];
        foreach ($parts as $part) {
            $n = self::normalize((string) $part);
            if ($n !== '' && !in_array($n, $out, true)) {
                $out[] = $n;
            }
        }

        return $out;
    }

    /**
     * @param list<string> $grade
     */
    public static function serializeGrade(array $grade): string
    {
        $clean = [];
        foreach ($grade as $item) {
            $n = self::normalize((string) $item);
            if ($n !== '' && !in_array($n, $clean, true)) {
                $clean[] = $n;
            }
        }

        return implode(',', $clean);
    }

    /**
     * @param list<string> $grade
     */
    public static function detectPreset(array $grade): string
    {
        if ($grade === []) {
            return self::PRESET_NENHUM;
        }
        if ($grade === self::calcado()) {
            return self::PRESET_CALCADO;
        }
        if ($grade === self::vestuario()) {
            return self::PRESET_VESTUARIO;
        }

        return self::PRESET_PERSONALIZADA;
    }

    /**
     * @param list<string> $grade
     */
    public static function isAllowed(string $tamanho, array $grade): bool
    {
        $n = self::normalize($tamanho);
        if ($n === '' || $grade === []) {
            return false;
        }

        return in_array($n, $grade, true);
    }

    public static function compare(string $a, string $b): int
    {
        return strnatcasecmp(self::normalize($a), self::normalize($b));
    }

    public static function label(?string $tamanho): string
    {
        $n = self::normalize((string) $tamanho);

        return $n !== '' ? $n : '—';
    }

    /**
     * @return array{controla_tamanho: int, grade_tamanhos: string|null}
     */
    public static function fromForm(array $post): array
    {
        $preset = (string) ($post['grade_preset'] ?? self::PRESET_NENHUM);
        if ($preset === self::PRESET_CALCADO) {
            return ['controla_tamanho' => 1, 'grade_tamanhos' => self::serializeGrade(self::calcado())];
        }
        if ($preset === self::PRESET_VESTUARIO) {
            return ['controla_tamanho' => 1, 'grade_tamanhos' => self::serializeGrade(self::vestuario())];
        }
        if ($preset === self::PRESET_PERSONALIZADA) {
            $grade = self::parseGrade((string) ($post['grade_tamanhos'] ?? ''));

            return [
                'controla_tamanho' => $grade !== [] ? 1 : 0,
                'grade_tamanhos' => $grade !== [] ? self::serializeGrade($grade) : null,
            ];
        }

        return ['controla_tamanho' => 0, 'grade_tamanhos' => null];
    }

    /**
     * Mínimo efetivo da numeração: override do tamanho ou o padrão da grade.
     *
     * @param array<string, int> $overrides
     */
    public static function minimoEfetivo(string $tamanho, int $padrao, array $overrides): int
    {
        $tam = self::normalize($tamanho);
        if ($tam === '') {
            return max(0, $padrao);
        }
        if (isset($overrides[$tam]) && (int) $overrides[$tam] > 0) {
            return (int) $overrides[$tam];
        }

        return max(0, $padrao);
    }

    /**
     * @param list<string> $grade
     * @return array<string, int> tamanho => mínimo (>0)
     */
    public static function parseMinimosPost(array $post, array $grade): array
    {
        $raw = $post['min_tamanho'] ?? [];
        if (!is_array($raw)) {
            return [];
        }
        $out = [];
        foreach ($grade as $tam) {
            $key = self::normalize((string) $tam);
            if ($key === '') {
                continue;
            }
            $val = $raw[$key] ?? $raw[$tam] ?? null;
            if ($val === null || trim((string) $val) === '') {
                continue;
            }
            $n = (int) $val;
            if ($n > 0) {
                $out[$key] = $n;
            }
        }

        return $out;
    }

    /**
     * Lista de exceções: "38=8, 42=5" ou "GG:12".
     *
     * @param list<string> $grade se informado, ignora tamanhos fora da grade
     * @return array<string, int>
     */
    public static function parseMinimosLista(string $raw, array $grade = []): array
    {
        $raw = trim($raw);
        if ($raw === '') {
            return [];
        }
        $gradeNorm = [];
        foreach ($grade as $g) {
            $t = self::normalize((string) $g);
            if ($t !== '') {
                $gradeNorm[] = $t;
            }
        }
        $out = [];
        foreach (preg_split('/[,;|\n\r]+/', $raw) ?: [] as $part) {
            $part = trim((string) $part);
            if ($part === '' || !preg_match('/^(.+?)\s*[=:]\s*(\d+)\s*$/u', $part, $m)) {
                continue;
            }
            $tam = self::normalize($m[1]);
            $n = (int) $m[2];
            if ($tam === '' || $n <= 0) {
                continue;
            }
            if ($gradeNorm !== [] && !in_array($tam, $gradeNorm, true)) {
                continue;
            }
            $out[$tam] = $n;
        }

        return $out;
    }

    /**
     * Une a grade com os saldos e aplica mínimo padrão/overrides.
     *
     * @param list<string> $grade
     * @param list<array<string, mixed>> $saldos
     * @param array<string, int> $overrides
     * @return list<array{tamanho: string, saldo: int, cas: list<array<string, mixed>>, minimo: int, estoque_baixo: bool}>
     */
    public static function linhasEstoquePorTamanho(array $grade, array $saldos, int $padrao, array $overrides): array
    {
        $porTam = [];
        foreach ($saldos as $st) {
            $tam = self::normalize((string) ($st['tamanho'] ?? ''));
            $porTam[$tam] = [
                'tamanho' => $tam,
                'saldo' => (int) ($st['saldo'] ?? 0),
                'cas' => is_array($st['cas'] ?? null) ? $st['cas'] : [],
            ];
        }

        $ordem = [];
        foreach ($grade as $g) {
            $t = self::normalize((string) $g);
            if ($t !== '' && !in_array($t, $ordem, true)) {
                $ordem[] = $t;
            }
        }
        foreach (array_keys($porTam) as $t) {
            $t = self::normalize((string) $t);
            if ($t !== '' && !in_array($t, $ordem, true)) {
                $ordem[] = $t;
            }
        }
        if (isset($porTam['']) && (int) $porTam['']['saldo'] !== 0) {
            $ordem[] = '';
        }

        $out = [];
        foreach ($ordem as $tam) {
            $tam = self::normalize((string) $tam);
            $saldo = (int) ($porTam[$tam]['saldo'] ?? 0);
            $cas = $porTam[$tam]['cas'] ?? [];
            $min = $tam === '' ? max(0, $padrao) : self::minimoEfetivo($tam, $padrao, $overrides);
            $out[] = [
                'tamanho' => $tam,
                'saldo' => $saldo,
                'cas' => $cas,
                'minimo' => $min,
                'estoque_baixo' => self::abaixoDoMinimo($saldo, $min),
                'estoque_ok' => $min > 0 && $saldo >= $min,
            ];
        }

        return $out;
    }

    public static function abaixoDoMinimo(int $saldo, int $minimo): bool
    {
        return $minimo > 0 && $saldo < $minimo;
    }

    public static function classeLinhaEstoque(int $saldo, int $minimo): string
    {
        if ($minimo <= 0) {
            return '';
        }

        return $saldo < $minimo ? 'table-warning' : 'sst-epi-estoque-ok';
    }
}
