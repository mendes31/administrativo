<?php

namespace App\adms\Controllers\Services\Validation;

use App\adms\Helpers\BranchFormHelper;
use App\adms\Models\Repository\BranchesRepository;

/**
 * Service de validação para Filiais / estabelecimentos (matriz e filial).
 */
class ValidationBranchService
{
    /**
     * Valida os dados do formulário de filial.
     *
     * @param array|null $data Dados do formulário (já normalizados preferencialmente)
     * @return array Lista de erros encontrados
     */
    public function validate(?array $data): array
    {
        $errors = [];
        $data = $data ?? [];

        if (empty($data['name']) && empty($data['nome_fantasia'])) {
            $errors[] = 'Informe o nome fantasia (ou o nome interno da unidade).';
        }
        if (empty($data['code'])) {
            $errors[] = 'O código da unidade é obrigatório.';
        }

        $type = BranchFormHelper::normalizeType($data['establishment_type'] ?? null);
        if ($type === null) {
            $errors[] = 'Selecione se o estabelecimento é Matriz ou Filial.';
        }

        $cnpjRaw = trim((string) ($data['cnpj'] ?? ''));
        if ($cnpjRaw !== '') {
            $digits = BranchFormHelper::cnpjDigits($cnpjRaw);
            if (strlen($digits) !== 14) {
                $errors[] = 'CNPJ deve ter 14 dígitos.';
            } else {
                $excludeId = isset($data['id']) && $data['id'] !== '' ? (int) $data['id'] : null;
                $repo = new BranchesRepository();
                if ($repo->cnpjExists($digits, $excludeId)) {
                    $errors[] = 'Já existe um estabelecimento cadastrado com este CNPJ.';
                }
            }
        }

        $cepRaw = trim((string) ($data['cep'] ?? ''));
        if ($cepRaw !== '') {
            $cepDigits = BranchFormHelper::cepDigits($cepRaw);
            if (strlen($cepDigits) !== 8) {
                $errors[] = 'CEP deve ter 8 dígitos.';
            }
        }

        $uf = strtoupper(trim((string) ($data['uf'] ?? '')));
        if ($uf !== '' && !isset(BranchFormHelper::ufOptions()[$uf])) {
            $errors[] = 'UF inválida.';
        }

        if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'E-mail (endereço eletrônico) inválido.';
        }
        if (!empty($data['phone']) && !preg_match('/^[0-9\-\(\)\s]+$/', $data['phone'])) {
            $errors[] = 'Telefone inválido.';
        }

        return $errors;
    }
}
