<?php

namespace App\adms\Models\Services;

class ValidationMovBetweenAccountsService
{
    public function validate(array $data): array
    {
        $errors = [];

        if (empty($data['source_account_id'])) {
            $errors[] = "Selecione a conta de origem!";
        }

        if (empty($data['destination_account_id'])) {
            $errors[] = "Selecione a conta de destino!";
        }

        if ($data['source_account_id'] === $data['destination_account_id']) {
            $errors[] = "Não é possível transferir para a mesma conta!";
        }

        if (empty($data['value']) || $data['value'] <= 0) {
            $errors[] = "Informe um valor válido para a transferência!";
        }

        return $errors;
    }
} 