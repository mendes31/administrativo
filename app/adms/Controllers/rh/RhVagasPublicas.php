<?php

declare(strict_types=1);

namespace App\adms\Controllers\rh;

use App\adms\Models\Repository\RhVagasRepository;

/**
 * Portal público de vagas — somente leitura (Expand Fase 3).
 * Sem login; só vagas publicada=1 + status aberta + dentro do prazo.
 */
final class RhVagasPublicas
{
    public function index(int|string $id = 0): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET' && $_SERVER['REQUEST_METHOD'] !== 'HEAD') {
            http_response_code(405);
            header('Allow: GET, HEAD');
            echo 'Método não permitido.';
            return;
        }

        $idInt = (int) $id;
        if ($idInt > 0) {
            $this->show($idInt);
            return;
        }

        $this->list();
    }

    private function list(): void
    {
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $titulo = trim((string) ($_GET['q'] ?? ''));
        $repo = new RhVagasRepository();
        $result = $repo->listPublicadas(
            $titulo !== '' ? ['titulo' => $titulo] : [],
            $page,
            12
        );

        $this->render('list', [
            'title' => 'Vagas abertas',
            'vagas' => $result['data'],
            'total' => $result['total'],
            'page' => $page,
            'per_page' => 12,
            'q' => $titulo,
        ]);
    }

    private function show(int $id): void
    {
        $vaga = (new RhVagasRepository())->getPublicadaById($id);
        if ($vaga === null) {
            http_response_code(404);
            $this->render('not_found', [
                'title' => 'Vaga não encontrada',
            ]);
            return;
        }

        $this->render('view', [
            'title' => (string) ($vaga['titulo'] ?? 'Vaga'),
            'vaga' => $vaga,
        ]);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function render(string $view, array $data): void
    {
        $base = rtrim((string) ($_ENV['URL_ADM'] ?? '/'), '/') . '/';
        $data['base_url'] = $base . 'vagas-abertas';
        $data['url_adm'] = $base;
        $data['view'] = $view;

        extract($data, EXTR_SKIP);
        require dirname(__DIR__, 2) . '/Views/rh/public/layout.php';
    }
}
