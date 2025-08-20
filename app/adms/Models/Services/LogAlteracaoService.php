<?php

namespace App\adms\Models\Services;

use App\adms\Models\Repository\LogAlteracoesRepository;
use App\adms\Models\Repository\LogAlteracoesDetalhesRepository;

class LogAlteracaoService
{
    /**
     * Registra uma alteração sensível no sistema.
     *
     * @param string $tabela Nome da tabela alterada
     * @param int $objetoId ID do registro alterado
     * @param int $usuarioId ID do usuário que fez a alteração
     * @param string $tipoOperacao Tipo da operação (insert, update, delete)
     * @param array $dadosAntes Array associativo com os valores antes da alteração
     * @param array $dadosDepois Array associativo com os valores depois da alteração
     * @return void
     */
    public static function registrarAlteracao(
        string $tabela,
        int $objetoId,
        int $usuarioId,
        string $tipoOperacao,
        array $dadosAntes,
        array $dadosDepois
    ): void {
        $logRepo = new LogAlteracoesRepository();
        $detalheRepo = new LogAlteracoesDetalhesRepository();

        // Antes de salvar, garantir que o tipo_operacao está em maiúsculo
        $tipoOperacao = strtoupper($tipoOperacao);

        // Cria a instância do log
        $logId = $logRepo->insert([
            'tabela' => $tabela,
            'objeto_id' => $objetoId,
            'usuario_id' => $usuarioId,
            'data_alteracao' => date('Y-m-d H:i:s'),
            'tipo_operacao' => $tipoOperacao,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? '',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'criado_por' => $usuarioId,
        ]);

        if ($logId) {
            // Descobre os campos alterados
            foreach ($dadosDepois as $campo => $valorNovo) {
                $valorAntigo = $dadosAntes[$campo] ?? null;
                if ($valorAntigo != $valorNovo) {
                    $detalheRepo->insert([
                        'log_alteracao_id' => $logId,
                        'campo' => $campo,
                        'valor_anterior' => $valorAntigo,
                        'valor_novo' => $valorNovo,
                    ]);
                }
            }
        }
    }
} 