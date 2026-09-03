<?php

declare(strict_types=1);

namespace App\adms\Models\Services;

/**
 * Política do rascunho eSocial/PPP no SST.
 *
 * A fila JSON não transmite ao governo. S-2240 não nasce de entrega de EPI;
 * S-2245 não entra no desenho oficial atual (S-2210, S-2220, S-2221, S-2240).
 */
final class SstEsocialPolicy
{
    public const EVENTO_ACIDENTE = 'S-2210';
    public const EVENTO_ASO = 'S-2220';
    public const EVENTO_TOXICOLOGICO = 'S-2221';
    public const EVENTO_CONDICOES_AMBIENTAIS = 'S-2240';
    public const EVENTO_TREINAMENTO_LEGADO = 'S-2245';

    public const LAYOUT_ATUAL_RASCUNHO = 'S-1.2';
    public const LAYOUT_ALVO = 'S-1.3';

    /** @var list<string> */
    public const EVENTOS_RASCUNHO = [self::EVENTO_ACIDENTE, self::EVENTO_ASO];

    /** @var list<string> */
    public const EVENTOS_BLOQUEADOS = [self::EVENTO_CONDICOES_AMBIENTAIS, self::EVENTO_TREINAMENTO_LEGADO];

    public static function isGeracaoBloqueada(string $tipoEvento): bool
    {
        return in_array($tipoEvento, self::EVENTOS_BLOQUEADOS, true);
    }

    public static function isRascunhoPermitido(string $tipoEvento): bool
    {
        return in_array($tipoEvento, self::EVENTOS_RASCUNHO, true);
    }

    public static function assertGeracaoPermitida(string $tipoEvento): void
    {
        if (self::isGeracaoBloqueada($tipoEvento)) {
            throw new SstEsocialGeracaoBloqueadaException(self::mensagemBloqueio($tipoEvento));
        }
    }

    public static function mensagemBloqueio(string $tipoEvento): string
    {
        return match ($tipoEvento) {
            self::EVENTO_CONDICOES_AMBIENTAIS => 'S-2240 não é gerado a partir de entrega de EPI. '
                . 'O evento oficial descreve condições ambientais e agentes nocivos (Tabela 24). '
                . 'Esta fila não transmite ao governo.',
            self::EVENTO_TREINAMENTO_LEGADO => 'S-2245 não faz parte do desenho oficial atual do SST '
                . '(S-2210, S-2220, S-2221 e S-2240). A geração está bloqueada.',
            default => 'A geração deste evento eSocial está bloqueada. A fila é rascunho interno, não transmissão oficial.',
        };
    }

    public static function avisoFila(): string
    {
        return 'Rascunho interno — não oficial. Não transmite ao eSocial. '
            . 'S-2210 e S-2220 podem ser gerados só para conferência (JSON simplificado, layout '
            . self::LAYOUT_ATUAL_RASCUNHO . '). S-2240 a partir de EPI e S-2245 estão bloqueados. '
            . 'Layout alvo futuro: ' . self::LAYOUT_ALVO . ', com S-2240 de condições ambientais.';
    }

    public static function avisoPpp(): string
    {
        return 'PPP não oficial. Rascunho interno a partir dos registros SST. '
            . 'Não substitui o PPP previdenciário nem serve para INSS, eSocial ou perícia.';
    }

    public static function observacaoPayload(): string
    {
        return 'Rascunho interno. Não é XML/XSD eSocial e não transmite ao governo. '
            . 'Layout ' . self::LAYOUT_ATUAL_RASCUNHO . ' simplificado; alvo futuro '
            . self::LAYOUT_ALVO . '.';
    }

    public static function observacaoPpp(): string
    {
        return self::avisoPpp()
            . ' Campos como eficácia de EPI/EPC e técnica utilizada são placeholders, não avaliação legal.';
    }
}
