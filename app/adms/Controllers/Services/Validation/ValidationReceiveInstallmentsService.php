<?php

namespace App\adms\Controllers\Services\Validation;

use App\adms\Models\Repository\CustomerRepository;
use App\adms\Models\Repository\InstallmentsReceiveRepository;
use Rakit\Validation\Validator;

/**
 * Classe ValidationReceiveInstallmentsService
 * 
 * Esta classe é responsável por validar os dados de um formulário de contas a receber, aplicando regras de validação para criação e edição de Contas à receber.
 * Ela utiliza o pacote `Rakit\Validation` para realizar as validações e inclui uma regra personalizada de unicidade em múltiplas colunas.
 * 
 * @package App\adms\Controllers\Services\Validation
 * @author Rafael Mendes 
 */
class ValidationReceiveInstallmentsService
{
    /**
     * Validar os dados do formulário.
     * 
     * Este método valida os dados fornecidos no formulário de contas a receber, aplicando diferentes regras dependendo se é uma criação ou edição de Contas à receber.
     * 
     * @param array $data Dados do formulário.
     * @return array Lista de erros. Se não houver erros, o array será vazio.
     */
    public function validate(array $data): array
    {
        var_dump($data);
        // Criar o array para receber as mensagens de erro
        $errors = [];
        // Instanciar a classe Validator para validar o formulário
        $validator = new Validator();
        // Instanciar o repositório para verificar unicidade no banco
        $nstallmentsRepo = new InstallmentsReceiveRepository();

        // Garantir que partner_id seja um inteiro (necessário para o validador)
        if (isset($data['partner_id'])) {
            $data['partner_id'] = (int)$data['partner_id'];
        }

        // Definir regras de validação
        $rules = [
            'num_doc'    => 'required',
            'partner_id' => 'required|integer'
        ];

        // Verificar se num_doc já existe para o mesmo parceiro
        if ($nstallmentsRepo->existsNumDocCliPartner($data['num_doc'], $data['partner_id'], $data['id_receive'] ?? null)) {
            $errors['num_doc'] = 'O número do documento já existe para este cliente.';
        }

        // Criar a validação
        $validation = $validator->make($data, $rules);

        // Definir mensagens personalizadas
        $validation->setMessages([
            'num_doc:required' => 'O número do documento é obrigatório.',
            'partner_id:required' => 'O cliente é obrigatório.',
            'partner_id:integer' => 'O cliente deve ser um número válido.',
        ]);

        // Executar a validação
        $validation->validate();

        if ($validation->fails()) {
            $arrayErrors = $validation->errors();

            foreach ($arrayErrors->firstOfAll() as $key => $message) {
                $errors[$key] = $message;
            }
        }

        echo "Iniciando Validation 77";
        var_dump($errors);
        return $errors;
    }


    public function getCustomerName(int $partner_id): string
    {
        // Se a descrição estiver vazia, buscar o nome do cliente
        if (empty($data['description']) && !empty($partner_id)) {

            $customerRepo = new CustomerRepository();
            $customerName = $customerRepo->getCustomer($partner_id);

            // var_dump($supplierName);

            if (!empty($customerName)) { // Verifica se retornou um nome válido
                $data['description'] = $customerName;
            } else {
                $errors['description'] = 'Descrição obrigatória e cliente não encontrado.';
            }
        }

        return $customerName;
    }

    public function validateFile(array $data): bool
    {

        // Verifica se a variável $id está definida e é um número
        if (empty($data['file'])) {
            return false;
        }
        return true;
    }
}
