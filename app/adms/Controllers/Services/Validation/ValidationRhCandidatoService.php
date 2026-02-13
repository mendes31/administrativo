<?php

namespace App\adms\Controllers\Services\Validation;

use Rakit\Validation\Validator;

/**
 * Validação de formulário de Candidatos (Recrutamento).
 */
class ValidationRhCandidatoService
{
    /**
     * Valida os dados de criação/edição de candidato.
     *
     * @param array $data
     * @return array Array de erros (vazio se não houver).
     */
    public function validate(array $data): array
    {
        $errors = [];

        $validator = new Validator();

        $validation = $validator->make($data, [
            'nome'                    => 'required|min:3|max:255',
            'email'                   => 'nullable|email|max:255',
            'telefone'                => 'nullable|max:30',
            'cidade'                  => 'nullable|max:120',
            'estado'                  => 'nullable|max:2',
            'origem'                  => 'required|in:email,whatsapp,form_trabalhe_conosco,manual,outro',
            'status_processo'         => 'required|in:candidatado,em_entrevista,aprovado,reprovado,desistiu,contratado,anonimizado,recebido,em_analise,banco_talentos',
            'area_interesse'          => 'required|max:255',
            'graduacao'               => 'required|min:10',
            'ultima_experiencia'      => 'required|min:10',
            'score'                   => 'nullable|integer|min:0|max:100',
            'classificacao'           => 'nullable|in:Excelente,Muito Bom,Bom,Regular,Baixo',
            'classificacao_observacoes' => 'nullable|max:1000',
            'observacoes'             => 'nullable|max:2000',
        ]);

        $validation->setMessages([
            'nome:required'                   => 'Nome do candidato é obrigatório.',
            'nome:min'                        => 'Nome do candidato deve ter pelo menos 3 caracteres.',
            'nome:max'                        => 'Nome do candidato deve ter no máximo 255 caracteres.',
            'email:email'                     => 'Informe um e-mail válido.',
            'email:max'                       => 'E-mail deve ter no máximo 255 caracteres.',
            'telefone:max'                    => 'Telefone deve ter no máximo 30 caracteres.',
            'cidade:max'                      => 'Cidade deve ter no máximo 120 caracteres.',
            'estado:max'                      => 'UF deve ter no máximo 2 caracteres.',
            'origem:required'                 => 'Origem do candidato é obrigatória.',
            'origem:in'                       => 'Origem do candidato inválida.',
            'status_processo:required'        => 'Status do processo é obrigatório.',
            'status_processo:in'              => 'Status do processo inválido.',
            'area_interesse:required'         => 'Área de interesse é obrigatória.',
            'area_interesse:max'              => 'Área de interesse deve ter no máximo 255 caracteres.',
            'graduacao:required'              => 'Graduação/Formação é obrigatória.',
            'graduacao:min'                   => 'Graduação/Formação deve ter pelo menos 10 caracteres.',
            'ultima_experiencia:required'     => 'Última experiência profissional é obrigatória.',
            'ultima_experiencia:min'          => 'Última experiência profissional deve ter pelo menos 10 caracteres.',
            'score:integer'                   => 'Score deve ser um número inteiro.',
            'score:min'                       => 'Score não pode ser menor que 0.',
            'score:max'                       => 'Score não pode ser maior que 100.',
            'classificacao:in'                => 'Classificação inválida.',
            'classificacao_observacoes:max'   => 'Observações da classificação devem ter no máximo 1000 caracteres.',
            'observacoes:max'                 => 'Observações devem ter no máximo 2000 caracteres.',
        ]);

        $validation->validate();

        if ($validation->fails()) {
            $arrayErrors = $validation->errors();
            foreach ($arrayErrors->firstOfAll() as $key => $message) {
                $errors[$key] = $message;
            }
        }

        return $errors;
    }
}

