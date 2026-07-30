<?php

declare(strict_types=1);

namespace App\adms\Models\Services;

/**
 * Catálogo estável de motivos de movimentação de candidatura (Fase 1).
 * Códigos persistem em rh_candidaturas_historico.motivo_codigo.
 */
final class RhCandidaturaMotivoCatalog
{
    public const OUTRO = 'OUTRO';

    /**
     * @return array<string, array<string, string>> status_destino => [codigo => rotulo]
     */
    public static function allGrouped(): array
    {
        return [
            'candidatado' => [
                'RETORNO_ETAPA' => 'Retorno à etapa inicial',
                'REABERTURA' => 'Reabertura do processo',
                self::OUTRO => 'Outro',
            ],
            'em_entrevista' => [
                'TRIAGEM_OK' => 'Triagem aprovada',
                'AGENDAR_ENTREVISTA' => 'Agendar entrevista',
                self::OUTRO => 'Outro',
            ],
            'aprovado' => [
                'PERFIL_ADEQUADO' => 'Perfil adequado à vaga',
                'APROVADO_ENTREVISTA' => 'Aprovado na entrevista',
                self::OUTRO => 'Outro',
            ],
            'banco_talentos' => [
                'RESERVA_FINALISTA' => 'Finalista reserva (não contratado nesta vaga)',
                'PERFIL_FUTURO' => 'Perfil interessante para outras vagas/áreas',
                'OFERTA_RECUSADA_BANCO' => 'Oferta recusada — manter no banco',
                self::OUTRO => 'Outro',
            ],
            'reprovado' => [
                'PERFIL_INADEQUADO' => 'Perfil inadequado',
                'REPROVADO_ENTREVISTA' => 'Reprovado na entrevista',
                'SALARIO_INCOMPATIVEL' => 'Expectativa salarial incompatível',
                'EXPERIENCIA_INSUFICIENTE' => 'Experiência insuficiente',
                self::OUTRO => 'Outro',
            ],
            'desistiu' => [
                'DESISTENCIA_CANDIDATO' => 'Desistência do candidato',
                'ACEITOU_OUTRA_OFERTA' => 'Aceitou outra oferta',
                self::OUTRO => 'Outro',
            ],
        ];
    }

    /**
     * @return array<string, string> codigo => rotulo
     */
    public static function labelsFlat(): array
    {
        $flat = [];
        foreach (self::allGrouped() as $motivos) {
            foreach ($motivos as $codigo => $rotulo) {
                $flat[$codigo] = $rotulo;
            }
        }

        return $flat;
    }

    /**
     * @return array<string, string>
     */
    public static function forStatus(string $status): array
    {
        $status = $status === 'em_analise' ? 'em_entrevista' : $status;
        $grouped = self::allGrouped();

        return $grouped[$status] ?? [];
    }

    public static function isValidForStatus(string $status, string $codigo): bool
    {
        $codigo = trim($codigo);
        if ($codigo === '') {
            return false;
        }

        return array_key_exists($codigo, self::forStatus($status));
    }

    public static function requiresObservacao(string $codigo): bool
    {
        return trim($codigo) === self::OUTRO;
    }

    public static function label(?string $codigo): string
    {
        if ($codigo === null || trim($codigo) === '') {
            return '-';
        }

        $flat = self::labelsFlat();

        return $flat[$codigo] ?? $codigo;
    }

    public static function forEntrevistaResultado(string $resultado): ?string
    {
        return match ($resultado) {
            'aprovado' => 'APROVADO_ENTREVISTA',
            'reprovado' => 'REPROVADO_ENTREVISTA',
            default => null,
        };
    }
}
