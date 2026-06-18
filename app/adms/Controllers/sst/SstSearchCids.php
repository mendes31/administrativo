<?php

declare(strict_types=1);

namespace App\adms\Controllers\sst;

use App\adms\Models\Repository\SstCidsRepository;

/** JSON para autocomplete de CID (Select2). */
class SstSearchCids
{
    public function index(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        $term = trim((string) ($_GET['q'] ?? $_GET['term'] ?? ''));
        $id = (int) ($_GET['id'] ?? 0);
        $frequentes = isset($_GET['frequentes']) && (string) $_GET['frequentes'] === '1';
        $repo = new SstCidsRepository();

        if ($id > 0) {
            $row = $repo->getById($id);
            if ($row) {
                echo json_encode([
                    'results' => [[
                        'id' => (int) $row['id'],
                        'text' => ($row['codigo'] ?? '') . ' — ' . ($row['descricao'] ?? ''),
                    ]],
                ]);

                return;
            }
        }

        $results = $repo->searchForSelect($term, 30, $frequentes);
        echo json_encode(['results' => $results]);
    }
}
