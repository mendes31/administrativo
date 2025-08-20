<?php

namespace App\adms\Controllers\financialReports;

use App\adms\Models\Repository\FinancialMovementsRepository;
use App\adms\Views\Services\LoadViewService;
use Mpdf\Mpdf;

class CashFlow
{
    private array|string|null $data = null;

    public function index(): void
    {
        $mes = $_GET['mes'] ?? date('m');
        $ano = $_GET['ano'] ?? date('Y');
        $repo = new FinancialMovementsRepository();
        $this->data['efetivo'] = $repo->getCashFlow($mes, $ano, 'efetivo');
        $this->data['previsto'] = $repo->getCashFlow($mes, $ano, 'previsto');
        $this->data['mes'] = $mes;
        $this->data['ano'] = $ano;
        // Buscar saldo inicial do mês anterior
        $this->data['acumuladoInicialEfetivo'] = $repo->getSaldoFinal($mes, $ano, 'efetivo');
        $this->data['acumuladoInicialPrevisto'] = $repo->getSaldoFinal($mes, $ano, 'previsto');
        $pageElements = [
            'title_head' => 'Rel Fluxo de Caixa',
            'menu' => 'cash-flow',
            'buttonPermission' => [],
        ];
        $pageLayoutService = new \App\adms\Controllers\Services\PageLayoutService();
        $this->data = array_merge($this->data ?? [], $pageLayoutService->configurePageElements($pageElements));
        $loadView = new LoadViewService('adms/Views/financialReports/cashFlow', $this->data);
        $loadView->loadView();
    }

    public function exportarPdf(): void
    {
        $mes = $_GET['mes'] ?? date('m');
        $ano = $_GET['ano'] ?? date('Y');
        $repo = new FinancialMovementsRepository();
        $efetivo = $repo->getCashFlow($mes, $ano, 'efetivo');
        $previsto = $repo->getCashFlow($mes, $ano, 'previsto');

        $mpdf = new Mpdf();

        // Página 1: Efetivo
        $htmlEfetivo = '<h2>Relatório Fluxo de Caixa - Valores Efetivos</h2>';
        $htmlEfetivo .= '<table border="1" width="100%" style="border-collapse:collapse;">';
        $htmlEfetivo .= '<tr><th>Dia</th><th>Receita</th><th>Despesa</th><th>Saldo</th><th>Acumulado</th></tr>';
        foreach ($efetivo as $linha) {
            $htmlEfetivo .= '<tr>';
            $htmlEfetivo .= '<td>' . $linha['dia'] . '</td>';
            $htmlEfetivo .= '<td>R$ ' . number_format($linha['receita'], 2, ',', '.') . '</td>';
            $htmlEfetivo .= '<td>R$ ' . number_format($linha['despesa'], 2, ',', '.') . '</td>';
            $htmlEfetivo .= '<td>R$ ' . number_format(($linha['receita'] - $linha['despesa']), 2, ',', '.') . '</td>';
            $htmlEfetivo .= '<td>R$ ' . number_format(($linha['acumulado'] ?? 0), 2, ',', '.') . '</td>';
            $htmlEfetivo .= '</tr>';
        }
        $htmlEfetivo .= '</table>';
        $mpdf->WriteHTML($htmlEfetivo);

        // Página 2: Previsto
        $mpdf->AddPage();
        $htmlPrevisto = '<h2>Relatório Fluxo de Caixa - Valores Previstos</h2>';
        $htmlPrevisto .= '<table border="1" width="100%" style="border-collapse:collapse;">';
        $htmlPrevisto .= '<tr><th>Dia</th><th>Receita</th><th>Despesa</th><th>Saldo</th><th>Acumulado</th></tr>';
        foreach ($previsto as $linha) {
            $htmlPrevisto .= '<tr>';
            $htmlPrevisto .= '<td>' . $linha['dia'] . '</td>';
            $htmlPrevisto .= '<td>R$ ' . number_format($linha['receita'], 2, ',', '.') . '</td>';
            $htmlPrevisto .= '<td>R$ ' . number_format($linha['despesa'], 2, ',', '.') . '</td>';
            $htmlPrevisto .= '<td>R$ ' . number_format(($linha['receita'] - $linha['despesa']), 2, ',', '.') . '</td>';
            $htmlPrevisto .= '<td>R$ ' . number_format(($linha['acumulado'] ?? 0), 2, ',', '.') . '</td>';
            $htmlPrevisto .= '</tr>';
        }
        $htmlPrevisto .= '</table>';
        $mpdf->WriteHTML($htmlPrevisto);

        $mpdf->Output('relatorio_fluxo_caixa.pdf', 'I');
    }

    public function exportarPdfCashFlow(): void
    {
        ini_set('display_errors', 1);
        ini_set('display_startup_errors', 1);
        error_reporting(E_ALL);

        echo "Iniciando método exportarPdfCashFlow...<br>";
        flush();
        sleep(1);

        try {
            $mpdf = new \Mpdf\Mpdf();
            $mpdf->WriteHTML('<h1>PDF Teste</h1><p>Se você está vendo isso, o mPDF está funcionando!</p>');
            $mpdf->Output('teste.pdf', 'I');
            exit;
        } catch (\Throwable $e) {
            echo '<pre>Erro ao gerar PDF: ' . $e->getMessage() . "\n" . $e->getTraceAsString() . '</pre>';
            exit;
        }
    }
} 