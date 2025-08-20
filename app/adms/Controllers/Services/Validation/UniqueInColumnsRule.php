<?php

namespace App\adms\Controllers\Services\Validation;

use App\adms\Helpers\GenerateLog;
use App\adms\Models\Repository\UniqueValueRepository;
use Exception;
use Rakit\Validation\Rule;

/**
 * Classe UniqueInColumnsRule
 * 
 * @author Rafael Mendes
 * Esta classe define as regras de validação personalizadas para verificar a unicidade de um valor em multiplas colunas de uma tabela no banco de dados.
 * Ela estende a classe `Rule` do pacote `Rakit\Validation`.
 * 
 * @package App\adms\Controllers\Services\Validation
 */
class UniqueInColumnsRule extends Rule
{
    // Mensagem de erro genéria (value = valor dentro do campo)
    protected $message = ":value já está em uso";

    // Parâmetros dinâmicos
    protected $fillableParams = ['table', 'columns', 'except'];

    /**
     * Verifica se o valor fornecido é único em múltiplas colunas especificas de uma tabela.
     *
     * @param mixed $value o valor a ser validado
     * @return boolean Retorna true se o valor for único em todas as colunas especificadas ou igual ao valor de exceção; caso contrário, false.
     * 
     * @throws Exception Caso ocorra um erro ao verificar a unicidade do valor, uma exceção será lançada e registrada no log.
     */
    public function check($value): bool
    {
        
        try{// Usar o try catch para gerenciar exceção/erro

        // Verificar se os parâmetros necessários existem
        $this->requireParameters(['table', 'columns']);

        // Recuperar os parâmetros
        $table = $this->parameter('table');
        $columns = explode(';', $this->parameter('columns')); // Espera-se que as colunas sejam uma string separda por (;)
        $except = $this->parameter('except');

        if ($except and $except == $value) {
            
            return true;
        }
       
        // Instanciar o Repository para verificar se existe registro com o valor fornecido
        $validateUniqueValue = new UniqueValueRepository();

        // Percorrer o array de colunas 
        foreach($columns as $column){
            // Verificar se existe registro com o valor fornecido
            if(!$validateUniqueValue->getRecord($table, $column, $value, $except)){
                return false;
            }
        }

        return true;
    }catch (Exception $e) { // Acessa o catch quando houver erro no try

        // Chamar o método para salvar o log
        GenerateLog::generateLog("error", "Usuário não cadastrado.", ['error' => $e->getMessage()]);

        return false;

    }
    }
}
