<?php

declare(strict_types=1);

namespace App\adms\Controllers\sst;

/**
 * Tela legada — estoque consultado no cadastro de EPIs (sst-list-epis).
 */
class SstListEpiEstoque
{
    public function index(): void
    {
        $query = http_build_query(array_filter([
            'search' => trim((string) ($_GET['search'] ?? '')) ?: null,
            'estoque_baixo' => !empty($_GET['estoque_baixo']) ? '1' : null,
            'status' => trim((string) ($_GET['status'] ?? '')) ?: null,
        ]));
        header('Location: ' . $_ENV['URL_ADM'] . 'sst-list-epis' . ($query !== '' ? '?' . $query : ''));
        exit;
    }
}
