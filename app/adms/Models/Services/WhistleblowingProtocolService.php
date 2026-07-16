<?php

declare(strict_types=1);

namespace App\adms\Models\Services;

/**
 * Geração de protocolo e senha de acompanhamento anônimo.
 */
final class WhistleblowingProtocolService
{
    /** @var list<string> */
    public const CATEGORIES = [
        'Assédio Moral',
        'Assédio Sexual',
        'Fraude',
        'Corrupção',
        'Favorecimento',
        'Furto',
        'Discriminação',
        'Segurança',
        'Qualidade',
        'Meio Ambiente',
        'Conflito de Interesse',
        'Outros',
    ];

    /** @var list<string> */
    public const RISK_LEVELS = ['Baixo', 'Médio', 'Alto', 'Crítico'];

    /** @var list<string> */
    public const STATUSES = [
        'Recebida',
        'Em triagem',
        'Em análise',
        'Comitê',
        'Investigação',
        'Providências',
        'Encerrada',
    ];

    /** @var list<string> */
    public const CLOSURE_OUTCOMES = [
        'Procedente',
        'Improcedente',
        'Parcialmente procedente',
        'Arquivado',
    ];

    public static function generateUuid(): string
    {
        $bytes = random_bytes(16);
        $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
        $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($bytes), 4));
    }

    public static function generateProtocol(): string
    {
        $year = date('Y');
        $part1 = self::randomSegment(4);
        $part2 = self::randomSegment(4);

        return "CD-{$year}-{$part1}-{$part2}";
    }

    public static function generateAccessPassword(): string
    {
        $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $len = strlen($chars);
        $password = '';

        for ($i = 0; $i < 6; $i++) {
            $password .= $chars[random_int(0, $len - 1)];
        }

        return $password;
    }

    public static function hashAccessPassword(string $password): string
    {
        return password_hash(strtoupper(trim($password)), PASSWORD_DEFAULT);
    }

    public static function verifyAccessPassword(string $password, string $hash): bool
    {
        return password_verify(strtoupper(trim($password)), $hash);
    }

    private static function randomSegment(int $length): string
    {
        $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $len = strlen($chars);
        $out = '';

        for ($i = 0; $i < $length; $i++) {
            $out .= $chars[random_int(0, $len - 1)];
        }

        return $out;
    }
}
