<?php

declare(strict_types=1);

namespace App\adms\Models\Services;

/**
 * Monta URLs de drill do People Analytics → list-users.
 *
 * Envia um snapshot completo de filtros (incluindo vazios) + from=people-analytics
 * para não herdar filtros antigos da sessão de list-users (ex.: desligado=1).
 *
 * Departamento/cargo só entram quando há exatamente um id (list-users é single-select).
 * Estado civil e país não são enviados (list-users ainda não filtra esses campos).
 */
final class PeopleAnalyticsListUsersUrlBuilder
{
    public const INTENT_UNIVERSE = 'universe';
    public const INTENT_ACTIVE = 'active';
    public const INTENT_ADMISSIONS = 'admissions';
    public const INTENT_TERMINATIONS = 'terminations';

    public const FROM_PARAM = 'from';
    public const FROM_VALUE = 'people-analytics';

    /**
     * @param array{
     *     period_start?: string,
     *     period_end?: string,
     *     departamento_ids?: int[],
     *     cargo_ids?: int[],
     *     sexo?: string|null,
     *     filhos?: string|null
     * } $filters
     * @return array<string, string|int>
     */
    public static function queryParams(array $filters, string $intent): array
    {
        // Snapshot limpo: evita mesclar com sessão (desligado/status/período anteriores).
        $q = [
            self::FROM_PARAM => self::FROM_VALUE,
            'nome' => '',
            'usuario' => '',
            'departamento_id' => '',
            'cargo_id' => '',
            'turno_id' => '',
            'status' => '',
            'bloqueado' => '',
            'desligado' => '',
            'sexo' => '',
            'filhos' => '',
            'periodo_tipo' => '',
            'data_de' => '',
            'data_ate' => '',
        ];

        $sexo = $filters['sexo'] ?? null;
        if (is_string($sexo) && $sexo !== '') {
            $q['sexo'] = $sexo;
        }

        $filhos = $filters['filhos'] ?? null;
        if (is_string($filhos) && $filhos !== '') {
            $q['filhos'] = $filhos;
        }

        $deps = array_values(array_filter(
            array_map('intval', $filters['departamento_ids'] ?? []),
            static fn (int $id): bool => $id > 0
        ));
        if (count($deps) === 1) {
            $q['departamento_id'] = $deps[0];
        }

        $pos = array_values(array_filter(
            array_map('intval', $filters['cargo_ids'] ?? []),
            static fn (int $id): bool => $id > 0
        ));
        if (count($pos) === 1) {
            $q['cargo_id'] = $pos[0];
        }

        $start = (string) ($filters['period_start'] ?? '');
        $end = (string) ($filters['period_end'] ?? '');

        switch ($intent) {
            case self::INTENT_ACTIVE:
                $q['desligado'] = '0';
                break;
            case self::INTENT_ADMISSIONS:
                // Admissões no período: qualquer status de vínculo; só a data de admissão importa.
                $q['periodo_tipo'] = 'admissao';
                $q['desligado'] = '';
                if ($start !== '') {
                    $q['data_de'] = $start;
                }
                if ($end !== '') {
                    $q['data_ate'] = $end;
                }
                break;
            case self::INTENT_TERMINATIONS:
                $q['periodo_tipo'] = 'desligamento';
                $q['desligado'] = '1';
                if ($start !== '') {
                    $q['data_de'] = $start;
                }
                if ($end !== '') {
                    $q['data_ate'] = $end;
                }
                break;
            case self::INTENT_UNIVERSE:
            default:
                break;
        }

        return $q;
    }

    /**
     * @param array{
     *     period_start?: string,
     *     period_end?: string,
     *     departamento_ids?: int[],
     *     cargo_ids?: int[],
     *     sexo?: string|null,
     *     filhos?: string|null
     * } $filters
     */
    public static function url(string $baseUrl, array $filters, string $intent): string
    {
        $base = rtrim($baseUrl, '/') . '/list-users';
        $q = self::queryParams($filters, $intent);

        return $base . '?' . http_build_query($q);
    }
}
