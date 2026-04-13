<?php

namespace App\adms\Controllers\Services\Validation;

use App\adms\Models\Repository\WorkShiftsRepository;
use Rakit\Validation\Validator;

class ValidationWorkShiftService
{
    public function validate(array $data): array
    {
        $errors = [];
        $validator = new Validator();
        $rules = [
            'description' => 'required|max:255',
            'overtime_tolerance_minutes' => 'required|numeric|min:0|max:999',
            'absence_tolerance_minutes' => 'required|numeric|min:0|max:999',
        ];
        if (isset($data['id'])) {
            $rules['id'] = 'required|integer';
        }
        $messages = [
            'description:required' => 'A descrição é obrigatória.',
            'description:max' => 'A descrição deve ter no máximo 255 caracteres.',
            'overtime_tolerance_minutes:required' => 'Informe a tolerância de extras (minutos).',
            'overtime_tolerance_minutes:numeric' => 'Tolerância de extras inválida.',
            'overtime_tolerance_minutes:min' => 'Tolerância de extras não pode ser negativa.',
            'overtime_tolerance_minutes:max' => 'Tolerância de extras máxima: 999 minutos.',
            'absence_tolerance_minutes:required' => 'Informe a tolerância de faltas (minutos).',
            'absence_tolerance_minutes:numeric' => 'Tolerância de faltas inválida.',
            'absence_tolerance_minutes:min' => 'Tolerância de faltas não pode ser negativa.',
            'absence_tolerance_minutes:max' => 'Tolerância de faltas máxima: 999 minutos.',
            'id:required' => 'Registro inválido.',
            'id:integer' => 'Registro inválido.',
        ];
        $validation = $validator->make($data, $rules);
        $validation->setMessages($messages);
        $validation->validate();
        if ($validation->fails()) {
            foreach ($validation->errors()->firstOfAll() as $key => $message) {
                $errors[$key] = $message;
            }
        }

        $pairErrors = $this->validateIntervals($data);
        foreach ($pairErrors as $idx => $msg) {
            $errors['interval_' . $idx] = $msg;
        }

        return $errors;
    }

    private function validateIntervals(array $data): array
    {
        $errs = [];
        foreach ([1, 2, 3] as $i) {
            $en = trim((string) ($data['entry_' . $i] ?? ''));
            $ex = trim((string) ($data['exit_' . $i] ?? ''));
            if ($en === '' && $ex === '') {
                continue;
            }
            if ($en === '' || $ex === '') {
                $errs[] = "Intervalo {$i}: informe entrada e saída, ou deixe os dois vazios.";

                continue;
            }
            $test = [
                'entry_1' => '', 'exit_1' => '',
                'entry_2' => '', 'exit_2' => '',
                'entry_3' => '', 'exit_3' => '',
            ];
            $test['entry_' . $i] = $en;
            $test['exit_' . $i] = $ex;
            if (WorkShiftsRepository::computeTotalMinutes($test) < 1) {
                $errs[] = "Intervalo {$i}: horário de saída deve ser posterior à entrada (mesmo dia).";
            }
        }

        $total = WorkShiftsRepository::computeTotalMinutes($data);
        if ($total <= 0) {
            $errs[] = 'Defina ao menos um intervalo de trabalho válido (entrada e saída).';
        }

        return $errs;
    }
}
