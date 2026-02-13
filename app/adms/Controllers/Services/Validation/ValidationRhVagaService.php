<?php

namespace App\adms\Controllers\Services\Validation;

use Rakit\Validation\Validator;

/**
 * Validação de formulário de Vagas (Recrutamento).
 */
class ValidationRhVagaService
{
    /**
     * Valida os dados de criação/edição de vaga.
     *
     * @param array $data
     * @return array Array de erros (vazio se não houver).
     */
    public function validate(array $data): array
    {
        $errors = [];

        $validator = new Validator();

        $validation = $validator->make($data, [
            'titulo'                => 'required|min:3|max:255',
            'area_id'               => 'nullable|integer',
            'cargo_id'              => 'nullable|integer',
            'tipo_contrato'         => 'required|in:CLT,PJ,Estágio,Temporário,Freelancer',
            'salario_min'           => 'nullable|numeric|min:0',
            'salario_max'           => 'nullable|numeric|min:0',
            'quantidade_vagas'      => 'nullable|integer|min:1',
            'data_limite_inscricao' => 'nullable|date',
            'status'                => 'required|in:aberta,pausada,fechada,cancelada',
            'responsavel_id'        => 'nullable|integer',
        ]);

        $validation->setMessages([
            'titulo:required'                => 'Título da vaga é obrigatório.',
            'titulo:min'                     => 'Título da vaga deve ter pelo menos 3 caracteres.',
            'titulo:max'                     => 'Título da vaga deve ter no máximo 255 caracteres.',
            'tipo_contrato:required'         => 'Tipo de contrato é obrigatório.',
            'tipo_contrato:in'               => 'Tipo de contrato inválido.',
            'salario_min:numeric'            => 'Salário mínimo deve ser numérico.',
            'salario_min:min'                => 'Salário mínimo não pode ser negativo.',
            'salario_max:numeric'            => 'Salário máximo deve ser numérico.',
            'salario_max:min'                => 'Salário máximo não pode ser negativo.',
            'quantidade_vagas:integer'       => 'Quantidade de vagas deve ser um número inteiro.',
            'quantidade_vagas:min'           => 'Quantidade de vagas deve ser pelo menos 1.',
            'data_limite_inscricao:date'     => 'Data limite de inscrição inválida.',
            'status:required'                => 'Status da vaga é obrigatório.',
            'status:in'                      => 'Status da vaga inválido.',
        ]);

        $validation->validate();

        if ($validation->fails()) {
            $arrayErrors = $validation->errors();
            foreach ($arrayErrors->firstOfAll() as $key => $message) {
                $errors[$key] = $message;
            }
        }

        // Regra extra: se ambos salários informados, max >= min
        if (
            isset($data['salario_min'], $data['salario_max']) &&
            $data['salario_min'] !== '' &&
            $data['salario_max'] !== '' &&
            is_numeric($data['salario_min']) &&
            is_numeric($data['salario_max']) &&
            (float)$data['salario_max'] < (float)$data['salario_min']
        ) {
            $errors['salario_max'] = 'Salário máximo não pode ser menor que o salário mínimo.';
        }

        return $errors;
    }
}

