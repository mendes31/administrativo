<?php

namespace App\adms\Models\Services;

use App\adms\Models\Repository\LogAlteracoesRepository;

/**
 * Serviço utilitário para obter resumo de alterações de um registro.
 *
 * Permite reutilizar a mesma lógica em qualquer tela de visualização
 * (tabela + ID do objeto) para exibir botão de "Log de Alterações".
 */
class LogResumoService
{
    /**
     * Retorna um pequeno resumo de alterações para uma tabela/objeto.
     *
     * @param string $tabela   Nome da tabela no log (ex.: 'lgpd_bases_legais')
     * @param int    $objetoId ID do registro na tabela
     * @return array{has_logs:bool,count:int,list_url:string}
     */
    public static function getResumo(string $tabela, int $objetoId): array
    {
        $repo = new LogAlteracoesRepository();

        $count = $repo->countAll([
            'tabela'   => $tabela,
            'objeto_id'=> (string)$objetoId,
        ]);

        $hasLogs = $count > 0;

        $listUrl = $_ENV['URL_ADM'] . 'list-log-alteracoes'
            . '?tabela=' . urlencode($tabela)
            . '&objeto_id=' . urlencode((string)$objetoId);

        return [
            'has_logs' => $hasLogs,
            'count'    => (int)$count,
            'list_url' => $listUrl,
        ];
    }
}
*** End Patch```} ***!

