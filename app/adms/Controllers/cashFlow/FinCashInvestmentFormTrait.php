<?php

declare(strict_types=1);

namespace App\adms\Controllers\cashFlow;

trait FinCashInvestmentFormTrait
{
    /**
     * @param array<string, mixed> $form
     * @return array{ok:bool,errors:list<string>,data:array<string,mixed>}
     */
    private function validateInvestmentForm(array $form): array
    {
        $errors = [];
        $types = ['APPLICATION', 'REDEMPTION', 'YIELD'];
        $cats = ['STANDARD', 'GUARANTEE'];
        $type = strtoupper((string) ($form['movement_type'] ?? ''));
        $cat = strtoupper((string) ($form['category'] ?? 'STANDARD'));
        $bank = trim((string) ($form['bank_label'] ?? ''));
        $date = trim((string) ($form['movement_date'] ?? ''));
        $amount = $this->parseMoney((string) ($form['amount'] ?? ''));

        if (!in_array($type, $types, true)) {
            $errors[] = 'Informe o tipo do lançamento (aplicação, resgate ou rendimento).';
        }
        if (!in_array($cat, $cats, true)) {
            $cat = 'STANDARD';
        }
        if ($bank === '') {
            $errors[] = 'Informe o banco / conta da aplicação.';
        }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $errors[] = 'Informe a data do lançamento.';
        }
        if ($amount <= 0) {
            $errors[] = 'Informe um valor maior que zero.';
        }

        return [
            'ok' => $errors === [],
            'errors' => $errors,
            'data' => [
                'movement_type' => $type,
                'category' => $cat,
                'bank_label' => $bank,
                'account_id' => (int) ($form['account_id'] ?? 0) ?: null,
                'movement_date' => $date,
                'amount' => $amount,
                'description' => trim((string) ($form['description'] ?? '')),
                'status' => 'ACTIVE',
            ],
        ];
    }

    private function parseMoney(string $raw): float
    {
        $raw = trim(str_replace(['R$', ' '], '', $raw));
        if ($raw === '') {
            return 0.0;
        }
        if (str_contains($raw, ',') && str_contains($raw, '.')) {
            $raw = str_replace('.', '', $raw);
            $raw = str_replace(',', '.', $raw);
        } elseif (str_contains($raw, ',')) {
            $raw = str_replace(',', '.', $raw);
        }
        return (float) $raw;
    }
}
