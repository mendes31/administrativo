<?php

declare(strict_types=1);

namespace App\adms\Controllers\sac;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\SacClientsRepository;
use App\adms\Views\Services\LoadViewService;

class SacCreateClient
{
    private array $data = [];

    public function index(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->create();
            return;
        }

        $repo = new SacClientsRepository();
        $this->data['next_code'] = $repo->getNextCode();

        $this->data['type_persons'] = ['PF' => 'Pessoa Física', 'PJ' => 'Pessoa Jurídica'];
        $this->data['statuses'] = ['Ativo', 'Inativo', 'Bloqueado'];

        $pageElements = [
            'title_head' => 'Novo Cliente - SAC',
            'menu' => 'sac-list-clients',
            'buttonPermission' => ['SacCreateClient'],
        ];

        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService("adms/Views/sac/clients/form", $this->data);
        $loadView->loadView();
    }

    private function create(): void
    {
        if (!CSRFHelper::validateCSRFToken('sac_client_form', $_POST['csrf_token'] ?? '')) {
            $_SESSION['msg'] = "Token CSRF inválido. Recarregue a página e tente novamente.";
            $_SESSION['msg_type'] = "danger";
            header("Location: " . $_ENV['URL_ADM'] . "sac-create-client");
            exit;
        }

        $data = [
            'razao_social' => $_POST['razao_social'] ?? '',
            'nome_fantasia' => $_POST['nome_fantasia'] ?? null,
            'type_person' => $_POST['type_person'] ?? 'PJ',
            'document' => $_POST['document'] ?? null,
            'contact_name' => $_POST['contact_name'] ?? null,
            'phone' => $_POST['phone'] ?? null,
            'mobile' => $_POST['mobile'] ?? null,
            'email' => $_POST['email'] ?? null,
            'zip_code' => $_POST['zip_code'] ?? null,
            'address' => $_POST['address'] ?? null,
            'number' => $_POST['number'] ?? null,
            'complement' => $_POST['complement'] ?? null,
            'neighborhood' => $_POST['neighborhood'] ?? null,
            'city' => $_POST['city'] ?? null,
            'state' => $_POST['state'] ?? null,
            'segment' => $_POST['segment'] ?? null,
            'notes' => $_POST['notes'] ?? null,
            'status' => $_POST['status'] ?? 'Ativo',
        ];

        if (empty($data['razao_social'])) {
            $_SESSION['msg'] = "A Razão Social é obrigatória.";
            $_SESSION['msg_type'] = "danger";
            header("Location: " . $_ENV['URL_ADM'] . "sac-create-client");
            exit;
        }

        if (empty($data['type_person'])) {
            $_SESSION['msg'] = "O Tipo de Pessoa é obrigatório.";
            $_SESSION['msg_type'] = "danger";
            header("Location: " . $_ENV['URL_ADM'] . "sac-create-client");
            exit;
        }

        $repo = new SacClientsRepository();

        if (!empty($data['document'])) {
            $existing = $repo->findByDocument($data['document']);
            if ($existing) {
                $_SESSION['msg'] = "Já existe um cliente cadastrado com este documento.";
                $_SESSION['msg_type'] = "danger";
                header("Location: " . $_ENV['URL_ADM'] . "sac-create-client");
                exit;
            }
        }

        $data['code'] = $repo->getNextCode();

        $clientId = $repo->createClient($data);

        if ($clientId) {
            $_SESSION['msg'] = "Cliente cadastrado com sucesso!";
            $_SESSION['msg_type'] = "success";
            header("Location: " . $_ENV['URL_ADM'] . "sac-view-client/" . $clientId);
        } else {
            $_SESSION['msg'] = "Erro ao cadastrar cliente.";
            $_SESSION['msg_type'] = "danger";
            header("Location: " . $_ENV['URL_ADM'] . "sac-create-client");
        }
        exit;
    }
}
