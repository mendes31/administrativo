<?php

declare(strict_types=1);

namespace App\adms\Helpers;

final class UserEducationHelper
{
    /** @var array<string, string> */
    private const TYPES = [
        'curso_livre' => 'Curso livre',
        'profissionalizante' => 'Curso profissionalizante',
        'tecnico' => 'Curso técnico',
        'graduacao' => 'Graduação',
        'pos_graduacao' => 'Pós-graduação',
        'mba' => 'MBA',
        'mestrado' => 'Mestrado',
        'doutorado' => 'Doutorado',
        'pos_doutorado' => 'Pós-doutorado',
        'outro' => 'Outro',
    ];

    /** @var array<string, string> */
    private const STATUSES = [
        'cursando' => 'Cursando',
        'concluido' => 'Concluído',
        'trancado' => 'Trancado',
        'interrompido' => 'Interrompido',
    ];

    /** @return array<string, string> */
    public static function typeOptions(): array
    {
        return self::TYPES;
    }

    /** @return array<string, string> */
    public static function statusOptions(): array
    {
        return self::STATUSES;
    }

    public static function normalizeType(mixed $value): ?string
    {
        return self::resolveOption($value, self::TYPES);
    }

    public static function normalizeStatus(mixed $value): ?string
    {
        return self::resolveOption($value, self::STATUSES);
    }

    public static function typeLabel(?string $value): string
    {
        return self::TYPES[$value ?? ''] ?? 'Não informado';
    }

    public static function statusLabel(?string $value): string
    {
        return self::STATUSES[$value ?? ''] ?? 'Não informada';
    }

    public static function normalizeDate(mixed $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) === 1) {
            $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value);
            $expected = $value;
        } elseif (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $value, $parts) === 1) {
            $expected = sprintf('%04d-%02d-%02d', (int) $parts[3], (int) $parts[2], (int) $parts[1]);
            $date = \DateTimeImmutable::createFromFormat(
                '!Y-m-d',
                $expected
            );
        } else {
            return null;
        }
        $errors = \DateTimeImmutable::getLastErrors();
        if ($date === false || ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0))) {
            return null;
        }

        return $date->format('Y-m-d') === $expected ? $expected : null;
    }

    /** @param array<string, string> $options */
    private static function resolveOption(mixed $value, array $options): ?string
    {
        $needle = mb_strtolower(trim((string) $value));
        if ($needle === '') {
            return null;
        }
        if (isset($options[$needle])) {
            return $needle;
        }
        foreach ($options as $slug => $label) {
            if ($needle === mb_strtolower($label)) {
                return $slug;
            }
        }

        return null;
    }
}
