<?php

namespace App\adms\Services;

/**
 * Serviço para correção automática de questões de avaliação
 * 
 * @package App\adms\Services
 */
class EvaluationGradingService
{
    /**
     * Corrigir uma questão comparando resposta com gabarito
     * 
     * @param string $tipo Tipo da questão (multipla_escolha, verdadeiro_falso, texto, numerica)
     * @param string|null $respostaUsuario Resposta fornecida pelo usuário
     * @param string|null $gabarito Resposta correta
     * @param float $pontos Pontuação da questão
     * @return array ['correta' => bool, 'pontos_obtidos' => float]
     */
    public static function corrigirQuestao(
        string $tipo,
        ?string $respostaUsuario,
        ?string $gabarito,
        float $pontos
    ): array {
        // Se não tem gabarito ou não tem resposta, não corrige
        if ($gabarito === null || $respostaUsuario === null || trim($respostaUsuario) === '') {
            return [
                'correta' => false,
                'pontos_obtidos' => 0,
                'avaliavel' => $gabarito !== null
            ];
        }

        $correta = false;

        switch ($tipo) {
            case 'multipla_escolha':
            case 'verdadeiro_falso':
                // Comparação case-insensitive exata
                $correta = strcasecmp(trim($gabarito), trim($respostaUsuario)) === 0;
                break;

            case 'numerica':
                // Comparação numérica com margem de erro
                $gabaritoNum = (float)str_replace(',', '.', $gabarito);
                $respostaNum = (float)str_replace(',', '.', $respostaUsuario);
                $margem = 0.01; // Margem de erro de 0.01
                $correta = abs($gabaritoNum - $respostaNum) <= $margem;
                break;

            case 'texto':
                // Para texto, pode implementar similaridade ou deixar correção manual
                // Por enquanto, considera correto apenas se idêntico (case-insensitive)
                $correta = strcasecmp(trim($gabarito), trim($respostaUsuario)) === 0;
                break;

            default:
                $correta = false;
        }

        return [
            'correta' => $correta,
            'pontos_obtidos' => $correta ? $pontos : 0,
            'avaliavel' => true
        ];
    }

    /**
     * Calcular nota final baseada nas respostas
     * 
     * @param array $questoes Array de questões com gabarito e pontos
     * @param array $respostasUsuario Array com respostas do usuário
     * @return array Estatísticas da avaliação
     */
    public static function calcularNota(array $questoes, array $respostasUsuario): array
    {
        $totalPontosPossiveis = 0;
        $pontosPossiveisAvaliados = 0;
        $pontosObtidos = 0;
        $questoesCorretas = 0;
        $questoesErradas = 0;
        $questoesSemGabarito = 0;
        $resultadosDetalhados = [];

        foreach ($questoes as $questao) {
            $questaoId = $questao['id'];
            $pontosQuestao = (float)($questao['pontos'] ?? 1.00);
            $respostaUsuario = $respostasUsuario[$questaoId] ?? null;
            $gabarito = $questao['resposta_correta'] ?? null;
            $tipo = $questao['tipo'] ?? 'texto';

            $totalPontosPossiveis += $pontosQuestao;

            // Corrigir questão
            $resultado = self::corrigirQuestao($tipo, $respostaUsuario, $gabarito, $pontosQuestao);

            if ($resultado['avaliavel']) {
                $pontosPossiveisAvaliados += $pontosQuestao;
                
                if ($resultado['correta']) {
                    $pontosObtidos += $resultado['pontos_obtidos'];
                    $questoesCorretas++;
                } else {
                    $questoesErradas++;
                }
            } else {
                $questoesSemGabarito++;
            }

            $resultadosDetalhados[] = [
                'questao_id' => $questaoId,
                'resposta' => $respostaUsuario,
                'correta' => $resultado['correta'],
                'pontos_questao' => $pontosQuestao,
                'pontos_obtidos' => $resultado['pontos_obtidos'],
                'avaliavel' => $resultado['avaliavel'],
                'gabarito' => $gabarito,
                'explicacao' => $questao['explicacao'] ?? null
            ];
        }

        // Calcular nota final (0 a 10)
        $notaFinal = $pontosPossiveisAvaliados > 0 
            ? ($pontosObtidos / $pontosPossiveisAvaliados) * 10 
            : 0;

        $percentual = $pontosPossiveisAvaliados > 0 
            ? ($pontosObtidos / $pontosPossiveisAvaliados) * 100 
            : 0;

        return [
            'nota_final' => round($notaFinal, 2),
            'percentual' => round($percentual, 2),
            'total_questoes' => count($questoes),
            'questoes_corretas' => $questoesCorretas,
            'questoes_erradas' => $questoesErradas,
            'questoes_sem_gabarito' => $questoesSemGabarito,
            'total_pontos_possiveis' => $totalPontosPossiveis,
            'pontos_possiveis_avaliados' => $pontosPossiveisAvaliados,
            'pontos_obtidos' => $pontosObtidos,
            'resultados_detalhados' => $resultadosDetalhados
        ];
    }

    /**
     * Verificar se o usuário foi aprovado
     * 
     * @param float $notaObtida Nota obtida pelo usuário
     * @param float $notaMinima Nota mínima para aprovação
     * @return bool
     */
    public static function verificarAprovacao(float $notaObtida, float $notaMinima): bool
    {
        return $notaObtida >= $notaMinima;
    }

    /**
     * Gerar feedback baseado na nota
     * 
     * @param float $nota Nota obtida (0 a 10)
     * @param float $notaMinima Nota mínima para aprovação
     * @return string Mensagem de feedback
     */
    public static function gerarFeedback(float $nota, float $notaMinima): string
    {
        if ($nota >= $notaMinima) {
            if ($nota >= 9.0) {
                return "Excelente! Você demonstrou domínio completo do conteúdo.";
            } elseif ($nota >= 8.0) {
                return "Muito bom! Você teve um ótimo desempenho.";
            } else {
                return "Parabéns! Você foi aprovado.";
            }
        } else {
            $diferenca = $notaMinima - $nota;
            if ($diferenca <= 1.0) {
                return "Você ficou próximo da aprovação. Revise o conteúdo e tente novamente.";
            } else {
                return "É necessário mais estudo. Revise o material do treinamento e refaça a avaliação.";
            }
        }
    }

    /**
     * Calcular estatísticas de desempenho por tipo de questão
     * 
     * @param array $resultadosDetalhados Array com resultados de cada questão
     * @param array $questoes Array com informações das questões
     * @return array Estatísticas por tipo
     */
    public static function calcularEstatisticasPorTipo(array $resultadosDetalhados, array $questoes): array
    {
        $estatisticas = [];

        foreach ($resultadosDetalhados as $resultado) {
            $questaoId = $resultado['questao_id'];
            $questao = array_filter($questoes, fn($q) => $q['id'] == $questaoId);
            $questao = reset($questao);
            
            if (!$questao) continue;

            $tipo = $questao['tipo'];

            if (!isset($estatisticas[$tipo])) {
                $estatisticas[$tipo] = [
                    'total' => 0,
                    'corretas' => 0,
                    'erradas' => 0,
                    'taxa_acerto' => 0
                ];
            }

            $estatisticas[$tipo]['total']++;
            
            if ($resultado['avaliavel']) {
                if ($resultado['correta']) {
                    $estatisticas[$tipo]['corretas']++;
                } else {
                    $estatisticas[$tipo]['erradas']++;
                }
            }
        }

        // Calcular taxa de acerto
        foreach ($estatisticas as $tipo => &$dados) {
            if ($dados['total'] > 0) {
                $dados['taxa_acerto'] = round(($dados['corretas'] / $dados['total']) * 100, 2);
            }
        }

        return $estatisticas;
    }
}

