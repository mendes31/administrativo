<?php

namespace App\adms\Controllers\Services\Validation;

use App\adms\Models\Repository\CustomerRepository;
use App\adms\Models\Repository\PaymentsRepository;
use App\adms\Models\Repository\ReceiptsRepository;
use App\adms\Models\Repository\SupplierRepository;
use Rakit\Validation\Validator;

/**
 * Classe ValidationReceiptsService
 * 
 * Esta classe é responsável por validar os dados de um formulário de contas a pagar, aplicando regras de validação para criação e edição de Contas à pagar.
 * Ela utiliza o pacote `Rakit\Validation` para realizar as validações e inclui uma regra personalizada de unicidade em múltiplas colunas.
 * 
 * @package App\adms\Controllers\Services\Validation
 * @author Rafael Mendes 
 */
class ValidationReceiptsService
{
    /**
     * Validar os dados do formulário.
     * 
     * Este método valida os dados fornecidos no formulário de contas a pagar, aplicando diferentes regras dependendo se é uma criação ou edição de Contas à pagar.
     * 
     * @param array $data Dados do formulário.
     * @return array Lista de erros. Se não houver erros, o array será vazio.
     */
    public function validate(array $data): array
    {
        // Criar o array para receber as mensagens de erro
        $errors = [];

        // Instanciar a classe Validator para validar o formulário
        $validator = new Validator();

        // Instanciar o repositório para verificar unicidade no banco
        $receiptsRepo = new ReceiptsRepository();

        // Definir regras de validação
        $rules = [
            'num_doc'    => 'required'
        ];

        // Só exige cliente se NÃO houver pagamento (parcial ou integral)
        $amount_paid = isset($data['amount_paid']) ? (float)$data['amount_paid'] : 0;
        if ($amount_paid <= 0) {
            $rules['partner_id'] = 'required|integer';
        }

        // Verificar se num_doc já existe para o mesmo parceiro
        if (!isset($data['id'])) {
            if (isset($data['partner_id']) && $receiptsRepo->existsNumDocForPartner($data['num_doc'], $data['partner_id'])) {
                $errors['num_doc'] = 'O número do documento já existe para este Cliente.';
            }
        }

        // Criar a validação
        $validation = $validator->make($data, $rules);

        // Definir mensagens personalizadas
        $messages = [
            'num_doc:required' => 'O número do documento é obrigatório.'
        ];
        if (isset($rules['partner_id'])) {
            $messages['partner_id:required'] = 'O Cliente é obrigatório.';
            $messages['partner_id:integer'] = 'O Cliente deve ser um número válido.';
        }
        $validation->setMessages($messages);

        // Executar a validação
        $validation->validate();

        if ($validation->fails()) {
            $arrayErrors = $validation->errors();

            foreach ($arrayErrors->firstOfAll() as $key => $message) {
                $errors[$key] = $message;
            }
        }

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
                $errors['description'] = 'Descrição obrigatória e parceiro não encontrado.';
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
