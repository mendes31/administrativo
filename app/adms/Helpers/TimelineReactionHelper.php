<?php

declare(strict_types=1);

namespace App\adms\Helpers;

/**
 * Reações estilo Facebook (um tipo por usuário por post).
 * Ordem exibida no seletor: Curtir, Amei, Cuidar, Risada, Uau, Triste, Raiva.
 */
class TimelineReactionHelper
{
    /** @var list<string> */
    public const TYPES = ['like', 'love', 'care', 'haha', 'wow', 'sad', 'angry'];

    public static function normalize(?string $type): string
    {
        $t = strtolower(trim((string)$type));
        $legacy = [
            'heart' => 'love',
            'celebrate' => 'haha',
            'clap' => 'care',
            'insight' => 'wow',
        ];
        if (isset($legacy[$t])) {
            $t = $legacy[$t];
        }
        return in_array($t, self::TYPES, true) ? $t : 'like';
    }

    public static function iconClass(string $type): string
    {
        $t = self::normalize($type);
        return match ($t) {
            'like' => 'fas fa-thumbs-up',
            'love' => 'fas fa-heart',
            'care' => 'fas fa-hand-holding-heart',
            'haha' => 'fas fa-laugh-beam',
            'wow' => 'fas fa-grin-stars',
            'sad' => 'fas fa-sad-tear',
            'angry' => 'fas fa-angry',
            default => 'fas fa-thumbs-up',
        };
    }

    /** Ícone “contorno” quando o usuário não reagiu (estilo FB). */
    public static function outlineIconClass(string $type): string
    {
        $t = self::normalize($type);
        return match ($t) {
            'like' => 'far fa-thumbs-up',
            'love' => 'far fa-heart',
            'care' => 'far fa-hand-holding-heart',
            'haha' => 'far fa-laugh-beam',
            'wow' => 'far fa-grin-stars',
            'sad' => 'far fa-sad-tear',
            'angry' => 'far fa-angry',
            default => 'far fa-thumbs-up',
        };
    }

    public static function label(string $type): string
    {
        $t = self::normalize($type);
        return match ($t) {
            'like' => 'Curtir',
            'love' => 'Amei',
            'care' => 'Cuidar',
            'haha' => 'Risada',
            'wow' => 'Uau',
            'sad' => 'Triste',
            'angry' => 'Raiva',
            default => 'Curtir',
        };
    }

    public static function colorClass(string $type): string
    {
        $t = self::normalize($type);
        return match ($t) {
            'like' => 'text-fb-like',
            'love' => 'text-fb-love',
            'care' => 'text-fb-care',
            'haha' => 'text-fb-haha',
            'wow' => 'text-fb-wow',
            'sad' => 'text-fb-sad',
            'angry' => 'text-fb-angry',
            default => 'text-fb-like',
        };
    }

    /**
     * Até 3 tipos com reação, na ordem do seletor (para ícones empilhados estilo Facebook).
     *
     * @param array<string, int> $summary
     * @return list<string>
     */
    public static function stackTypesFromSummary(array $summary): array
    {
        $out = [];
        foreach (self::TYPES as $t) {
            $n = (int)($summary[$t] ?? 0);
            if ($n > 0) {
                $out[] = $t;
            }
            if (count($out) >= 3) {
                break;
            }
        }
        return $out;
    }
}
