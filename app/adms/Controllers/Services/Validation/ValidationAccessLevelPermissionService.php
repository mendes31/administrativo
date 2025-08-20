<?php

namespace App\adms\Controllers\Services\Validation;

use Rakit\Validation\Validator;

/**
 * Serviço de validação para permissões de níveis de acesso.
 *
 * Esta classe é responsável por validar os dados recebidos para permissões de níveis de acesso,
 * utilizando a biblioteca Rakit Validation. Gera mensagens de erro personalizadas em caso de falha na validação.
 *
 * @package App\adms\Controllers\Services\Validation
 */
class ValidationAccessLevelPermissionService
{

    /**
     * Validar permissões de nível de acesso.
     *
     * Este método valida os dados de permissões de nível de acesso, verificando se o campo `adms_access_level_id`
     * está presente e é um valor inteiro. Em caso de erro, uma lista de mensagens de erro é retornada.
     *
     * @param array $data Dados a serem validados, incluindo `adms_access_level_id`.
     * @return array Retorna um array com as mensagens de erro, ou um array vazio se não houver erros.
     */

    public function validate(array $data): array
    {
        // Log de debug
        error_log('ValidationAccessLevelPermissionService::validate chamado com dados: ' . json_encode($data));
        
        // Criar o array para receber as mensagens de erro
        $errors = [];

        // Instanciar a classe Validator para validar o formulário
        $validator = new Validator();

        // Log de debug - verificar se permissions existe e seu tipo
        if (isset($data['permissions'])) {
            error_log('permissions existe: ' . json_encode($data['permissions']));
            error_log('Tipo de permissions: ' . gettype($data['permissions']));
            error_log('is_array(permissions): ' . (is_array($data['permissions']) ? 'true' : 'false'));
        } else {
            error_log('permissions NÃO existe nos dados');
        }

        // Definir as regras de validação
        $validation = $validator->make($data, [
            'adms_access_level_id' => 'required|integer',
            'permissions' => 'required|array', // Agora espera um array 'permissions'
        ]);

        // Definir mensagens personalizadas
        $validation->setMessages([
            'adms_access_level_id:required' => 'Dados inválidos.',
            'adms_access_level_id:integer' => 'Dados inválidos.',
            'permissions:required' => 'Dados de permissões são obrigatórios.',
            'permissions:array' => 'Formato de permissões inválido.',
        ]);

        // Validar os dados
        $validation->validate();

        // Retornar erros se houver
        if ($validation->fails()) {
            // Recuperar os erros 
            $arrayErrors = $validation->errors();
            
            // Log de debug - verificar erros de validação
            error_log('Validação falhou. Erros: ' . json_encode($arrayErrors->firstOfAll()));

            // Percorrer o array de erros e armazenar a primeira mensagem de erro para cada campo validado
            foreach ($arrayErrors->firstOfAll() as $key => $message) {
                $errors[$key] = $message;
            }
        } else {
            error_log('Validação passou com sucesso');
        }

        // Log de debug - erros finais
        error_log('Erros retornados: ' . json_encode($errors));

        // Retornar o array de erros, se houver
        return $errors;
    }
}
