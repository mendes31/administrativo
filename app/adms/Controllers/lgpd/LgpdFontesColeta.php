<?php

namespace App\adms\Controllers\lgpd;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\LgpdFontesColetaRepository;
use App\adms\Helpers\CSRFHelper;
use App\adms\Views\Services\LoadViewService;

class LgpdFontesColeta
{
    private array|string|null $data = null;
    private LgpdFontesColetaRepository $fontesColetaRepository;

    public function __construct()
    {
        $this->fontesColetaRepository = new LgpdFontesColetaRepository();
    }

    public function index(): void
    {
        $this->data['fontes'] = $this->fontesColetaRepository->listAll();
        
        $pageElements = [
            'title_head' => 'Fontes de Coleta LGPD',
            'menu' => 'ListLgpdFontesColeta',
            'buttonPermission' => ['ListLgpdFontesColeta', 'LgpdFontesColetaCreate', 'LgpdFontesColetaEdit', 'LgpdFontesColetaDelete'],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService("adms/Views/lgpd/fontes-coleta/index", $this->data);
        $loadView->loadView();
    }

    public function create(): void
    {
        $pageElements = [
            'title_head' => 'Criar Fonte de Coleta',
            'menu' => 'ListLgpdFontesColeta',
            'buttonPermission' => ['ListLgpdFontesColeta', 'LgpdFontesColetaCreate'],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService("adms/Views/lgpd/fontes-coleta/create", $this->data);
        $loadView->loadView();
    }

    public function store(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $formData = filter_input_array(INPUT_POST, FILTER_DEFAULT);
            
            if (CSRFHelper::validateCSRFToken($formData['csrf_token'] ?? '', 'form_create_fonte_coleta')) {
                $data = [
                    'nome' => $formData['nome'] ?? '',
                    'descricao' => $formData['descricao'] ?? '',
                    'ativo' => isset($formData['ativo']) ? 1 : 0
                ];

                if ($this->fontesColetaRepository->create($data)) {
                    $_SESSION['msg'] = 'Fonte de coleta criada com sucesso!';
                    $_SESSION['msg_type'] = 'success';
                    header('Location: ' . $_ENV['URL_ADM'] . 'lgpd-fontes-coleta');
                    exit;
                } else {
                    $_SESSION['msg'] = 'Erro ao criar fonte de coleta!';
                    $_SESSION['msg_type'] = 'danger';
                }
            } else {
                $_SESSION['msg'] = 'Token CSRF inválido!';
                $_SESSION['msg_type'] = 'danger';
            }
        }
        
        header('Location: ' . $_ENV['URL_ADM'] . 'lgpd-fontes-coleta-create');
        exit;
    }

    public function edit(int $id): void
    {
        $fonte = $this->fontesColetaRepository->findById($id);
        if (!$fonte) {
            $_SESSION['msg'] = 'Fonte de coleta não encontrada!';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'lgpd-fontes-coleta');
            exit;
        }

        $this->data['fonte'] = $fonte;
        
        $pageElements = [
            'title_head' => 'Editar Fonte de Coleta',
            'menu' => 'ListLgpdFontesColeta',
            'buttonPermission' => ['ListLgpdFontesColeta', 'LgpdFontesColetaEdit'],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService("adms/Views/lgpd/fontes-coleta/edit", $this->data);
        $loadView->loadView();
    }

    public function update(int $id): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $formData = filter_input_array(INPUT_POST, FILTER_DEFAULT);
            
            if (CSRFHelper::validateCSRFToken($formData['csrf_token'] ?? '', 'form_edit_fonte_coleta')) {
                $data = [
                    'nome' => $formData['nome'] ?? '',
                    'descricao' => $formData['descricao'] ?? '',
                    'ativo' => isset($formData['ativo']) ? 1 : 0
                ];

                if ($this->fontesColetaRepository->update($id, $data)) {
                    $_SESSION['msg'] = 'Fonte de coleta atualizada com sucesso!';
                    $_SESSION['msg_type'] = 'success';
                    header('Location: ' . $_ENV['URL_ADM'] . 'lgpd-fontes-coleta');
                    exit;
                } else {
                    $_SESSION['msg'] = 'Erro ao atualizar fonte de coleta!';
                    $_SESSION['msg_type'] = 'danger';
                }
            } else {
                $_SESSION['msg'] = 'Token CSRF inválido!';
                $_SESSION['msg_type'] = 'danger';
            }
        }
        
        header('Location: ' . $_ENV['URL_ADM'] . 'lgpd-fontes-coleta-edit/' . $id);
        exit;
    }

    public function delete(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $formData = filter_input_array(INPUT_POST, FILTER_DEFAULT);
            
            if (CSRFHelper::validateCSRFToken($formData['csrf_token'] ?? '', 'form_delete_fonte_coleta')) {
                $id = (int)($formData['id'] ?? 0);
                
                if ($this->fontesColetaRepository->delete($id)) {
                    $_SESSION['msg'] = 'Fonte de coleta excluída com sucesso!';
                    $_SESSION['msg_type'] = 'success';
                } else {
                    $_SESSION['msg'] = 'Erro ao excluir fonte de coleta!';
                    $_SESSION['msg_type'] = 'danger';
                }
            } else {
                $_SESSION['msg'] = 'Token CSRF inválido!';
                $_SESSION['msg_type'] = 'danger';
            }
        }
        
        header('Location: ' . $_ENV['URL_ADM'] . 'lgpd-fontes-coleta');
        exit;
    }
} 