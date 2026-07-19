<?php

declare(strict_types=1);

namespace App\adms\Models\Services;

/**
 * Projeção de rh_candidatos.status_processo a partir dos vínculos ativos.
 *
 * Fonte de transições: rh_candidaturas_historico → rh_candidatos_vagas.status (projeção por vaga)
 * → status_processo (projeção agregada do candidato).
 *
 * Estados protegidos (não sobrescritos pelo pipeline): contratado, anonimizado.
 */
final class RhCandidatoStatusProcessoProjector
{
    public const PROTEGIDOS = ['contratado', 'anonimizado'];

    /**
     * @param list<string> $statusVinculos status de rh_candidatos_vagas em vagas abertas/pausadas
     */
    public static function fromVinculos(?string $statusAtual, array $statusVinculos): string
    {
        $statusAtual = self::normalizar((string) ($statusAtual ?? ''));

        if (in_array($statusAtual, self::PROTEGIDOS, true)) {
            return $statusAtual;
        }

        $statusVinculos = array_values(array_filter(array_map(
            static fn ($s): string => self::normalizar((string) $s),
            $statusVinculos
        )));

        if ($statusVinculos === []) {
            return 'candidatado';
        }

        if (in_array('aprovado', $statusVinculos, true)) {
            return 'aprovado';
        }
        if (in_array('em_entrevista', $statusVinculos, true)) {
            return 'em_entrevista';
        }
        if (in_array('candidatado', $statusVinculos, true)) {
            return 'candidatado';
        }
        if (in_array('reprovado', $statusVinculos, true) || in_array('desistiu', $statusVinculos, true)) {
            return 'reprovado';
        }

        return 'candidatado';
    }

    /**
     * Resolve status em edição manual do cadastro (não via pipeline).
     *
     * @param list<string> $statusVinculos
     */
    public static function resolveForManualEdit(
        string $statusAtual,
        array $statusVinculos,
        bool $marcarContratado = false
    ): string {
        if ($marcarContratado) {
            return 'contratado';
        }

        $statusAtual = self::normalizar($statusAtual);
        if (in_array($statusAtual, self::PROTEGIDOS, true)) {
            return $statusAtual;
        }

        return self::fromVinculos($statusAtual, $statusVinculos);
    }

    public static function normalizar(string $status): string
    {
        $status = trim($status);
        $legado = [
            'recebido' => 'candidatado',
            'em_analise' => 'em_entrevista',
            'banco_talentos' => 'aprovado',
        ];

        return $legado[$status] ?? $status;
    }

    public static function isProtegido(string $status): bool
    {
        return in_array(self::normalizar($status), self::PROTEGIDOS, true);
    }
}
