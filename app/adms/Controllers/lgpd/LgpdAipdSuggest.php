<?php

namespace App\adms\Controllers\lgpd;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\LgpdRopaRepository;
use App\adms\Views\Services\LoadViewService;

/**
 * Controller responsável por sugerir AIPDs automaticamente baseado nos ROPAs.
 *
 * @package App\adms\Controllers\lgpd
 */
class LgpdAipdSuggest
{
    private array|string|null $data = null;

    public function index(): void
    {
        $ropaRepo = new LgpdRopaRepository();
        
        // Buscar ROPAs que podem necessitar de AIPD
        $this->data['sugestoes'] = $this->analisarRopasParaAipd($ropaRepo);
        
        $pageElements = [
            'title_head' => 'Sugestões de AIPD',
            'menu' => 'lgpd-aipd-suggest',
            'buttonPermission' => ['SuggestLgpdAipd'],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService("adms/Views/lgpd/aipd/suggest", $this->data);
        $loadView->loadView();
    }

    /**
     * Analisa ROPAs para identificar quais necessitam de AIPD
     */
    private function analisarRopasParaAipd(LgpdRopaRepository $ropaRepo): array
    {
        $ropas = $ropaRepo->getAll();
        $sugestoes = [];

        foreach ($ropas as $ropa) {
            $pontuacao = 0;
            $motivos = [];

            // Critérios para AIPD obrigatória
            if ($this->temDadosSensiveis($ropa)) {
                $pontuacao += 30;
                $motivos[] = "Dados sensíveis identificados";
            }

            if ($this->temDadosCriancas($ropa)) {
                $pontuacao += 25;
                $motivos[] = "Dados de crianças/adolescentes";
            }

            if ($this->temDadosBiometricos($ropa)) {
                $pontuacao += 20;
                $motivos[] = "Dados biométricos";
            }

            if ($this->temDadosSaude($ropa)) {
                $pontuacao += 20;
                $motivos[] = "Dados de saúde";
            }

            if ($this->temMonitoramento($ropa)) {
                $pontuacao += 15;
                $motivos[] = "Monitoramento sistemático";
            }

            if ($this->temLargaEscala($ropa)) {
                $pontuacao += 10;
                $motivos[] = "Tratamento em larga escala";
            }

            // Se pontuação >= 30, sugerir AIPD
            if ($pontuacao >= 30) {
                $sugestoes[] = [
                    'ropa' => $ropa,
                    'pontuacao' => $pontuacao,
                    'motivos' => $motivos,
                    'nivel_risco' => $this->calcularNivelRisco($pontuacao),
                    'prioridade' => $this->calcularPrioridade($pontuacao)
                ];
            }
        }

        // Ordenar por pontuação (maior primeiro)
        usort($sugestoes, function($a, $b) {
            return $b['pontuacao'] - $a['pontuacao'];
        });

        return $sugestoes;
    }

    private function temDadosSensiveis($ropa): bool
    {
        // Verificar se há dados sensíveis baseado nos grupos de dados
        return strpos(strtolower($ropa['descricao'] ?? ''), 'sensível') !== false ||
               strpos(strtolower($ropa['descricao'] ?? ''), 'racial') !== false ||
               strpos(strtolower($ropa['descricao'] ?? ''), 'religioso') !== false ||
               strpos(strtolower($ropa['descricao'] ?? ''), 'político') !== false;
    }

    private function temDadosCriancas($ropa): bool
    {
        return strpos(strtolower($ropa['descricao'] ?? ''), 'criança') !== false ||
               strpos(strtolower($ropa['descricao'] ?? ''), 'adolescente') !== false ||
               strpos(strtolower($ropa['descricao'] ?? ''), 'menor') !== false;
    }

    private function temDadosBiometricos($ropa): bool
    {
        return strpos(strtolower($ropa['descricao'] ?? ''), 'biométrico') !== false ||
               strpos(strtolower($ropa['descricao'] ?? ''), 'impressão') !== false ||
               strpos(strtolower($ropa['descricao'] ?? ''), 'facial') !== false;
    }

    private function temDadosSaude($ropa): bool
    {
        return strpos(strtolower($ropa['descricao'] ?? ''), 'saúde') !== false ||
               strpos(strtolower($ropa['descricao'] ?? ''), 'médico') !== false ||
               strpos(strtolower($ropa['descricao'] ?? ''), 'hospital') !== false;
    }

    private function temMonitoramento($ropa): bool
    {
        return strpos(strtolower($ropa['descricao'] ?? ''), 'monitoramento') !== false ||
               strpos(strtolower($ropa['descricao'] ?? ''), 'rastreamento') !== false ||
               strpos(strtolower($ropa['descricao'] ?? ''), 'vigilância') !== false;
    }

    private function temLargaEscala($ropa): bool
    {
        // Verificar volume de dados (exemplo simplificado)
        return strpos(strtolower($ropa['descricao'] ?? ''), 'massa') !== false ||
               strpos(strtolower($ropa['descricao'] ?? ''), 'grande volume') !== false;
    }

    private function calcularNivelRisco(int $pontuacao): string
    {
        if ($pontuacao >= 80) return 'Crítico';
        if ($pontuacao >= 60) return 'Alto';
        if ($pontuacao >= 40) return 'Médio';
        return 'Baixo';
    }

    private function calcularPrioridade(int $pontuacao): string
    {
        if ($pontuacao >= 80) return 'Alta';
        if ($pontuacao >= 60) return 'Média';
        return 'Baixa';
    }
}
