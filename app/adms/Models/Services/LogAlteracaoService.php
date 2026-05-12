<?php

namespace App\adms\Models\Services;

use App\adms\Models\Repository\LogAlteracoesRepository;
use App\adms\Models\Repository\LogAlteracoesDetalhesRepository;
use App\adms\Controllers\Services\RequestHelper;
use App\adms\Helpers\EnvLoader;

class LogAlteracaoService
{
    /** Tabelas que não devem ser registadas aqui (recursão, infra sensível ou volume excessivo). */
    private const TABLES_BLOCKED_FROM_ALTERACAO_LOG = [
        'adms_log_alteracoes',
        'adms_log_alteracoes_detalhes',
        'adms_log_justificativas',
        'adms_log_acessos',
        'adms_sessions',
        'adms_slow_request_profiles',
    ];

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
        // Garantir que o timezone está configurado corretamente
        EnvLoader::loadWithTimezone();

        $tabelaNorm = strtolower(trim($tabela));
        if ($tabelaNorm !== '' && in_array($tabelaNorm, self::TABLES_BLOCKED_FROM_ALTERACAO_LOG, true)) {
            return;
        }
        
        $logRepo = new LogAlteracoesRepository();
        $detalheRepo = new LogAlteracoesDetalhesRepository();

        // Antes de salvar, garantir que o tipo_operacao está em maiúsculo
        $tipoOperacao = strtoupper($tipoOperacao);

        // Capturar informações do cliente
        $ip = RequestHelper::getClientIp();
        $hostname = RequestHelper::getClientHostname();
        $userAgent = RequestHelper::getUserAgent();

        // Cria a instância do log
        $logId = $logRepo->insert([
            'tabela' => $tabela,
            'objeto_id' => $objetoId,
            'usuario_id' => $usuarioId,
            'data_alteracao' => date('Y-m-d H:i:s'),
            'tipo_operacao' => $tipoOperacao,
            'ip' => $ip,
            'hostname' => $hostname,
            'user_agent' => $userAgent,
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