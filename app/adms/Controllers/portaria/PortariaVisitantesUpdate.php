<?php

declare(strict_types=1);

namespace App\adms\Controllers\portaria;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\PortariaVisitantesRepository;
use App\adms\Views\Services\LoadViewService;

final class PortariaVisitantesUpdate
{
    private array $data = [];

    public function index(int|string $id): void
    {
        $visitanteId = (int) $id;
        $repo = new PortariaVisitantesRepository();
        $visitante = $repo->getById($visitanteId);
        if ($visitante === null) {
            $_SESSION['msg'] = 'Visitante não encontrado.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . ($_ENV['URL_ADM'] ?? '') . 'portaria-visitantes');
            return;
        }
        $post = filter_input_array(INPUT_POST, FILTER_DEFAULT);
        $this->data['form'] = is_array($post) ? $post : $visitante;
        if (is_array($post) && isset($post['csrf_token'])
            && CSRFHelper::validateCSRFToken('form_portaria_visitante_' . $visitanteId, (string) $post['csrf_token'])) {
            $this->data['form']['ativo'] = !empty($post['ativo']) ? 1 : 0;
            if (trim((string) ($post['nome'] ?? '')) === '') {
                $this->data['errors'] = ['Informe o nome do visitante.'];
            } elseif ($repo->update($visitanteId, $this->data['form'])) {
                $_SESSION['msg'] = 'Visitante atualizado com sucesso.';
                $_SESSION['msg_type'] = 'success';
                header('Location: ' . ($_ENV['URL_ADM'] ?? '') . 'portaria-visitantes-view/' . $visitanteId);
                return;
            } else {
                $this->data['errors'] = ['Não foi possível atualizar o visitante.'];
            }
        }
        $this->data['visitante_id'] = $visitanteId;
        $this->data = array_merge($this->data, (new PageLayoutService())->configurePageElements([
            'title_head' => 'Editar Visitante',
            'menu' => 'portaria-visitantes',
            'buttonPermission' => ['PortariaVisitantes', 'PortariaVisitantesView'],
        ]));
        (new LoadViewService('adms/Views/portaria/visitantes/edit', $this->data))->loadView();
    }
}
