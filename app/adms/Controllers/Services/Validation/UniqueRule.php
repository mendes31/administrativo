<?php

namespace App\adms\Controllers\Services\Validation;

use App\adms\Helpers\GenerateLog;
use App\adms\Models\Repository\UniqueValueRepository;
use Exception;
use Rakit\Validation\Rule;

/**
 * Classe UniqueRule
 * 
 * @author Rafael Mendes
 * 
 * Esta classe define uma regra dse validação personalizada para verificar a unicidade de um valor em uma tabela do banco de dados.
 * Ela estende a classse `Rule`do pacote `Rakit\Validatio`.
 * 
 * @package App\adms\Controllers\Services\Validation
 */
class UniqueRule extends Rule
{
    // Mensagem de erro genéria (value = valor dentro do campo)
    protected $message = ":value já está em uso";

    // Parâmetros dinâmicos
    protected $fillableParams = ['table', 'column', 'except'];

    /**
     * Verifica se o valor é único em uma coluna específica de uma tabela.
     *      *
     * @param mixed $value O valor a ser validado.
     * 
     * @return boolean Retorna true se o valor for único ou igual ao valor de esceção; caso contrário, false.
     * 
     * @throws Exception Caso ocorra um erro ao verificar a unicidade do valor, uma exceção será lançada e registrada no log.
     */
    public function check($value): bool
    {
        
        try{// Usar o try catch para gerenciar exceção/erro

        // Verificar se os parâmetros necessários existem
        $this->requireParameters(['table', 'column']);

        // Recuperar os parâmetros
        $table = $this->parameter('table');
        $column = $this->parameter('column');
        $except = $this->parameter('except');

        if ($except and $except == $value) {
            return true;
        }
       
        // Instanciar o Repository para verificar se existe registro com o valor fornecido
        $validateUniqueValue = new UniqueValueRepository();
        return $validateUniqueValue->getRecord($table, $column, $value);
    }catch (Exception $e) { // Acessa o catch quando houver erro no try

        // Chamar o método para salvar o log
        GenerateLog::generateLog("error", "Usuário não cadastrado.", ['error' => $e->getMessage()]);

        return false;

    }
    }
}
