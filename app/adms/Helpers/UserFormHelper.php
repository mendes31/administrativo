<?php

declare(strict_types=1);

namespace App\adms\Helpers;

/**
 * Normalização e rótulos para campos demográficos do cadastro de utilizador.
 */
final class UserFormHelper
{
    /** @var list<string> */
    public const ESTADO_CIVIL_SLUGS = [
        'solteiro',
        'casado',
        'uniao_estavel',
        'divorciado',
        'viuvo',
        'separado',
        'outro',
    ];

    /** @var list<string> */
    public const ESCOLARIDADE_SLUGS = [
        'fundamental_incompleto',
        'fundamental_completo',
        'medio_incompleto',
        'medio_completo',
        'tecnico',
        'superior_incompleto',
        'superior_completo',
        'pos_graduacao',
        'mestrado',
        'doutorado',
    ];

    /** Categorias de cor/raça (padrão IBGE / eSocial). @var list<string> */
    public const RACA_SLUGS = [
        'branca',
        'preta',
        'parda',
        'amarela',
        'indigena',
        'nao_informado',
    ];

    /** @var list<string> */
    public const EMPRESA_CONTRATANTE_SLUGS = [
        'tiaraju_farma',
        'lab_tiaraju_matriz',
        'lab_tiaraju_filial',
    ];

    public static function normalizeSexo(mixed $value): ?string
    {
        $v = strtoupper(trim((string) $value));

        return in_array($v, ['M', 'F', 'O'], true) ? $v : null;
    }

    public static function normalizeFilhos(mixed $value): ?string
    {
        $v = strtoupper(trim((string) $value));

        return in_array($v, ['S', 'N'], true) ? $v : null;
    }

    public static function sexoLabel(?string $code): string
    {
        return match ($code) {
            'M' => 'Masculino',
            'F' => 'Feminino',
            'O' => 'Outros',
            default => 'Não informado',
        };
    }

    public static function filhosLabel(?string $code): string
    {
        return match ($code) {
            'S' => 'Sim',
            'N' => 'Não',
            default => 'Não informado',
        };
    }

    /** @return 'regrettable'|'non_regrettable'|'nao_classificado'|null */
    public static function normalizeTipoImpactoDesligamento(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        $v = strtolower(trim((string) $value));

        return in_array($v, ['regrettable', 'non_regrettable', 'nao_classificado'], true) ? $v : null;
    }

    public static function tipoImpactoDesligamentoLabel(?string $code): string
    {
        return match ($code) {
            'regrettable' => 'Regrettable (desejável reter)',
            'non_regrettable' => 'Non-regrettable',
            'nao_classificado' => 'Não classificado',
            default => 'Não informado',
        };
    }

    public static function normalizeEstadoCivil(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        $v = strtolower(trim((string) $value));

        return in_array($v, self::ESTADO_CIVIL_SLUGS, true) ? $v : null;
    }

    public static function estadoCivilLabel(?string $code): string
    {
        return match ($code) {
            'solteiro' => 'Solteiro(a)',
            'casado' => 'Casado(a)',
            'uniao_estavel' => 'União estável',
            'divorciado' => 'Divorciado(a)',
            'viuvo' => 'Viúvo(a)',
            'separado' => 'Separado(a)',
            'outro' => 'Outro',
            default => 'Estado civil não informado',
        };
    }

    /** @return array<string, string> slug => rótulo para selects e gráficos */
    public static function estadoCivilOptions(): array
    {
        $out = [];
        foreach (self::ESTADO_CIVIL_SLUGS as $slug) {
            $out[$slug] = self::estadoCivilLabel($slug);
        }

        return $out;
    }

    public static function normalizeEscolaridade(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        $v = strtolower(trim((string) $value));

        return in_array($v, self::ESCOLARIDADE_SLUGS, true) ? $v : null;
    }

    public static function escolaridadeLabel(?string $code): string
    {
        return match ($code) {
            'fundamental_incompleto' => 'Ensino fundamental incompleto',
            'fundamental_completo' => 'Ensino fundamental completo',
            'medio_incompleto' => 'Ensino médio incompleto',
            'medio_completo' => 'Ensino médio completo',
            'tecnico' => 'Curso técnico',
            'superior_incompleto' => 'Ensino superior incompleto',
            'superior_completo' => 'Ensino superior completo',
            'pos_graduacao' => 'Pós-graduação',
            'mestrado' => 'Mestrado',
            'doutorado' => 'Doutorado',
            default => 'Escolaridade não informada',
        };
    }

    /** @return array<string, string> slug => rótulo */
    public static function escolaridadeOptions(): array
    {
        $out = [];
        foreach (self::ESCOLARIDADE_SLUGS as $slug) {
            $out[$slug] = self::escolaridadeLabel($slug);
        }

        return $out;
    }

    public static function normalizePaisResidenciaIso(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        $v = strtoupper(trim((string) $value));
        if (strlen($v) !== 2 || !ctype_alpha($v)) {
            return null;
        }
        $countries = CountryHelper::getCountries();

        return isset($countries[$v]) ? $v : null;
    }

    public static function paisResidenciaLabel(?string $iso): string
    {
        if ($iso === null || $iso === '') {
            return 'País não informado';
        }
        $countries = CountryHelper::getCountries();
        if (isset($countries[$iso]['name'])) {
            return (string) $countries[$iso]['name'];
        }

        return 'País (' . $iso . ')';
    }

    public static function normalizeRaca(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        $v = strtolower(trim((string) $value));

        return in_array($v, self::RACA_SLUGS, true) ? $v : null;
    }

    public static function racaLabel(?string $code): string
    {
        return match ($code) {
            'branca' => 'Branca',
            'preta' => 'Preta',
            'parda' => 'Parda',
            'amarela' => 'Amarela',
            'indigena' => 'Indígena',
            'nao_informado' => 'Não informado',
            default => 'Raça/cor não informada',
        };
    }

    /** @return array<string, string> */
    public static function racaOptions(): array
    {
        $out = [];
        foreach (self::RACA_SLUGS as $slug) {
            $out[$slug] = self::racaLabel($slug);
        }

        return $out;
    }

    public static function normalizeEmpresaContratante(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        $v = strtolower(trim((string) $value));

        return in_array($v, self::EMPRESA_CONTRATANTE_SLUGS, true) ? $v : null;
    }

    public static function empresaContratanteLabel(?string $code): string
    {
        return match ($code) {
            'tiaraju_farma' => 'Tiaraju Farma',
            'lab_tiaraju_matriz' => 'Lab. Tiaraju Matriz',
            'lab_tiaraju_filial' => 'Lab. Tiaraju Filial',
            default => 'Empresa contratante não informada',
        };
    }

    /** @return array<string, string> */
    public static function empresaContratanteOptions(): array
    {
        $out = [];
        foreach (self::EMPRESA_CONTRATANTE_SLUGS as $slug) {
            $out[$slug] = self::empresaContratanteLabel($slug);
        }

        return $out;
    }
}
