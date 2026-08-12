<?php

declare(strict_types=1);

namespace App\adms\Controllers\portaria;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\PortariaAutorizacoesRepository;
use App\adms\Models\Repository\PortariaTermoAceitesRepository;
use App\adms\Models\Repository\PortariaVisitantesRepository;
use App\adms\Views\Services\LoadViewService;

final class PortariaVisitantesView
{
    private array $data = [];

    public function index(int|string $id): void
    {
        $visitanteId = (int) $id;
        $visitante = (new PortariaVisitantesRepository())->getById($visitanteId);
        if ($visitante === null) {
            $_SESSION['msg'] = 'Visitante não encontrado.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . ($_ENV['URL_ADM'] ?? '') . 'portaria-visitantes');
            return;
        }
        $this->data['visitante'] = $visitante;
        $this->data['aceites'] = (new PortariaTermoAceitesRepository())->listByVisitante($visitanteId);
        $this->data['autorizacoes'] = array_values(array_filter(
            (new PortariaAutorizacoesRepository())->getAll(),
            static fn (array $row): bool => (int) $row['visitante_id'] === $visitanteId
        ));
        $this->data = array_merge($this->data, (new PageLayoutService())->configurePageElements([
            'title_head' => 'Ficha do Visitante',
            'menu' => 'portaria-visitantes',
            'buttonPermission' => ['PortariaVisitantes', 'PortariaVisitantesUpdate', 'PortariaAutorizacoesCreate'],
        ]));
        (new LoadViewService('adms/Views/portaria/visitantes/view', $this->data))->loadView();
    }
}
