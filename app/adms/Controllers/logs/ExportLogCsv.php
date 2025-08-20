<?php

namespace App\adms\Controllers\logs;

use App\adms\Models\Repository\LogAlteracoesRepository;

class ExportLogCsv
{
    public function index(): void
    {
        $filtros = $this->getFiltros();
        $repo = new LogAlteracoesRepository();
        $logs = $repo->getAll(1, 10000, $filtros); // Buscar todos os registros filtrados
        
        // Configurar headers para download
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="logs_alteracoes_' . date('Y-m-d_H-i-s') . '.csv"');
        
        // Criar arquivo CSV
        $output = fopen('php://output', 'w');
        
        // BOM para UTF-8
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Cabeçalhos
        fputcsv($output, [
            'ID',
            'Tabela',
            'ID Objeto',
            'Identificador',
            'Usuário',
            'Data/Hora',
            'Tipo',
            'IP',
            'User Agent'
        ], ';');
        
        // Dados
        foreach ($logs as $log) {
            fputcsv($output, [
                $log['id'],
                $log['tabela'],
                $log['objeto_id'],
                $log['identificador'],
                $log['usuario_nome'] ?: $log['usuario_id'],
                date('d/m/Y H:i:s', strtotime($log['data_alteracao'])),
                $log['tipo_operacao'],
                $log['ip'],
                $log['user_agent']
            ], ';');
        }
        
        fclose($output);
        exit;
    }
    
    private function getFiltros(): array
    {
        return [
            'tabela' => $_GET['tabela'] ?? '',
            'objeto_id' => $_GET['objeto_id'] ?? '',
            'identificador' => $_GET['identificador'] ?? '',
            'usuario_nome' => $_GET['usuario_nome'] ?? '',
            'tipo' => $_GET['tipo'] ?? '',
            'data_inicio' => $_GET['data_inicio'] ?? '',
            'data_fim' => $_GET['data_fim'] ?? '',
        ];
    }
} 