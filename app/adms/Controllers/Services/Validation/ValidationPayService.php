<?php

namespace App\adms\Controllers\Services\Validation;

use App\adms\Models\Repository\PaymentsRepository;
use App\adms\Models\Repository\SupplierRepository;
use Rakit\Validation\Validator;
use App\adms\Models\Repository\PartialValuesRepository;

/**
 * Classe ValidationPayService
 * 
 * Valida os dados do formulário de contas a pagar.
 * 
 * @package App\adms\Controllers\Services\Validation
 * @author Rafael Mendes
 */
class ValidationPayService
{
    /**
     * Validar os dados do formulário.
     * 
     * @param array $data Dados do formulário.
     * @return array Lista de erros.
     */

    public function validate(array $dataBD, array $data): array
    {
        // var_dump($data);
        // var_dump($dataBD);

        // Criar o array para receber mensagens de erro
        $errors = [];

        // Instanciar Validator
        $validator = new Validator();

        // Instanciar o repositório de pagamentos
        $paymentsRepo = new PaymentsRepository();

        // Definir regras de validação
        $rules = [
            'id_pay'    => 'required',
            'value'     => 'required|numeric|min:0.01', // Garante que o valor seja maior que 0
            'pay_method_id' => 'required',
            'bank_id' => 'required',
        ];

        // Criar a validação
        $validation = $validator->make($data, $rules);

        // Definir mensagens personalizadas
        $validation->setMessages([
            'id_pay:required'    => 'O ID da conta é obrigatório.',
            'value:required'     => 'O valor a ser baixado é obrigatório.',
            'value:numeric'      => 'O valor a ser baixado deve ser um número válido.',
            'value:min'          => 'O valor a ser baixado deve ser maior que zero.',
            'pay_method_id:required' => 'Selecione uma Forma de Pagamento.',
            'bank_id:required' => 'Selecione um Banco Saída.',
        ]);

        // Executar a validação
        $validation->validate();

        // Verifica erros do Validator
        if ($validation->fails()) {
            $arrayErrors = $validation->errors();

            foreach ($arrayErrors->firstOfAll() as $key => $message) {
                $errors[$key] = $message;
            }
        }

        // var_dump($data);


        // Verificar se o valor a ser baixado não é maior que o saldo a pagar
        if (!empty($data['value']) && !empty($data['id_pay'])) {
            $conta = $paymentsRepo->getPay($data['id_pay']);

            if ($conta) {
                // Buscar movimentos
                $movementsRepo = new PartialValuesRepository();
                $movements = $movementsRepo->getMovementValues($data['id_pay']);

                $totalPago = 0;
                $totalDesconto = 0;
                if (!empty($movements)) {
                    foreach ($movements as $mov) {
                        $totalPago += $mov['movement_value'];
                        $totalDesconto += $mov['discount_value'] ?? 0;
                    }
                }
                if ($totalDesconto > 0) {
                    $saldoPagar = $conta['original_value'] - ($totalPago + $totalDesconto);
                } else {
                    $saldoPagar = $conta['original_value'] - $totalPago;
                }
                if ($saldoPagar < 0) {
                    $saldoPagar = 0;
                }

                if ((float) $data['value'] > $saldoPagar) {
                    $errors['value'] = "O valor a ser baixado não pode ser maior que o saldo da conta ({$saldoPagar}).";
                }
            } else {
                $errors['id_pay'] = 'Conta não encontrada.';
            }
        }

        if ($conta) {
            $valorTotal = (float) $conta['value']; // Total da conta
            $valorBaixado = (float) $data['value'];

            if ($valorBaixado < $valorTotal) {
                // Verifica se algum dos campos foi preenchido
                $temMultaOuDesconto =
                    (!empty($data['discount_value']) && (float)$data['discount_value'] > 0) ||
                    (!empty($data['fine_value']) && (float)$data['fine_value'] > 0) ||
                    (!empty($data['interest']) && (float)$data['interest'] > 0);

                if ($temMultaOuDesconto) {
                    $errors['value'] = "Se o valor a ser baixado for menor que o valor original ({$valorTotal}), não pode ser adicionado Desconto, Multa ou Juros.";
                }
            }
        } else {
            $errors['id_pay'] = 'Conta não encontrada.';
        }
        return $errors;
    }


    public function validateOriginalValue(array $dataBD, array $data): bool
    {
        var_dump($data);
        var_dump($dataBD);

        // Instanciar o repositório de pagamentos
        $paymentsRepo = new PaymentsRepository();

        // var_dump($data);

        // Verificar se os valores necessários estão definidos
        if (empty($data['value']) || empty($data['id_pay'])) {
            return false;
        }

        // Buscar os dados do pagamento
        $conta = $paymentsRepo->getPay($data['id_pay']);


        var_dump($conta);

        // Verificar se a conta foi encontrada e se tem um valor original válido
        if (!empty($conta) && isset($conta['value'])) {
            return ((float) $data['value'] === (float) $conta['value']);
        }

        return false;
    }


    public function getSupplierName(int $partner_id): string
    {
        $supplierRepo = new SupplierRepository();
        return $supplierRepo->getSupplierName($partner_id) ?? '';
    }

    public function validateFile(array $data): bool
    {
        return !empty($data['file']);
    }
}
