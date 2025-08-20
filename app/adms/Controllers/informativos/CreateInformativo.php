<?php

namespace App\adms\Controllers\informativos;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\InformativosRepository;
use App\adms\Views\Services\LoadViewService;

class CreateInformativo
{
    private array|string|null $data = null;

    public function index()
    {
        $this->data = [];
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->create();
        }
        
        $repo = new InformativosRepository();
        $this->data['categorias'] = $repo->getCategorias();
        
        $pageElements = [
            'title_head' => 'Criar Informativo',
            'menu' => 'create-informativo',
            'buttonPermission' => ['CreateInformativo'],
        ];
        
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));
        
        $loadView = new LoadViewService('adms/Views/informativos/create', $this->data);
        $loadView->loadView();
    }

    private function create(): void
    {
        // Exibir $_FILES na tela para depuração (remover após o teste)
        echo '<pre style="background:#222;color:#fff;z-index:9999;position:relative;">DEBUG $_FILES: ' . print_r($_FILES, true) . '</pre>';
        if (!CSRFHelper::validateCSRFToken('create_informativo', $_POST['csrf_token'] ?? '')) {
            $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">Erro de validação CSRF!</div>';
            return;
        }

        $titulo = trim($_POST['titulo'] ?? '');
        $conteudo = trim($_POST['conteudo'] ?? '');
        $categoria = trim($_POST['categoria'] ?? '');
        $urgente = isset($_POST['urgente']) ? true : false;
        $ativo = isset($_POST['ativo']) ? true : false;

        // Validações
        if (empty($titulo)) {
            $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">O título é obrigatório!</div>';
            return;
        }

        if (empty($conteudo)) {
            $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">O conteúdo é obrigatório!</div>';
            return;
        }

        if (empty($categoria)) {
            $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">A categoria é obrigatória!</div>';
            return;
        }

        // Gerar resumo (primeiras 150 letras)
        $resumo = substr(strip_tags($conteudo), 0, 150);
        if (strlen(strip_tags($conteudo)) > 150) {
            $resumo .= '...';
        }

        // Upload de imagem
        $imagem = null;
        if (isset($_FILES['imagem']) && $_FILES['imagem']['error'] === UPLOAD_ERR_OK) {
            $imagem = $this->uploadFile($_FILES['imagem'], 'imagens');
            if ($imagem === null) {
                $_SESSION['msg'] = '<div class="alert alert-warning" role="alert">Erro ao fazer upload da imagem. Verifique o tipo e tamanho do arquivo.</div>';
                return;
            }
        } elseif (isset($_FILES['imagem']) && $_FILES['imagem']['error'] !== UPLOAD_ERR_NO_FILE) {
            $errorMsg = $this->getUploadErrorMessage($_FILES['imagem']['error']);
            $_SESSION['msg'] = '<div class="alert alert-warning" role="alert">Erro no upload da imagem: ' . $errorMsg . '</div>';
            return;
        }

        // Upload de anexo
        $anexo = null;
        if (isset($_FILES['anexo']) && $_FILES['anexo']['error'] === UPLOAD_ERR_OK) {
            $anexo = $this->uploadFile($_FILES['anexo'], 'anexos');
            if ($anexo === null) {
                $_SESSION['msg'] = '<div class="alert alert-warning" role="alert">Erro ao fazer upload do anexo. Verifique o tipo e tamanho do arquivo.</div>';
                return;
            }
        } elseif (isset($_FILES['anexo']) && $_FILES['anexo']['error'] !== UPLOAD_ERR_NO_FILE) {
            $errorMsg = $this->getUploadErrorMessage($_FILES['anexo']['error']);
            $_SESSION['msg'] = '<div class="alert alert-warning" role="alert">Erro no upload do anexo: ' . $errorMsg . '</div>';
            return;
        }

        $data = [
            'titulo' => $titulo,
            'conteudo' => $conteudo,
            'resumo' => $resumo,
            'categoria' => $categoria,
            'imagem' => $imagem,
            'anexo' => $anexo,
            'urgente' => $urgente,
            'ativo' => $ativo,
            'usuario_id' => $_SESSION['user_id'] ?? 1,
        ];

        $repo = new InformativosRepository();
        
        try {
            $id = $repo->createInformativo($data);
            
            if ($id) {
                $_SESSION['msg'] = '<div class="alert alert-success" role="alert">Informativo criado com sucesso!</div>';
                header('Location: ' . $_ENV['URL_ADM'] . 'list-informativos');
                exit;
            } else {
                $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">Erro ao criar informativo!</div>';
            }
        } catch (\Exception $e) {
            $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">Erro ao criar informativo: ' . $e->getMessage() . '</div>';
        }
    }

    private function uploadFile(array $file, string $folder): ?string
    {
        // Verificar se o arquivo foi enviado corretamente
        if (!isset($file) || !is_array($file) || $file['error'] !== UPLOAD_ERR_OK) {
            error_log('Arquivo não foi enviado corretamente: ' . ($file['error'] ?? 'desconhecido'));
            return null;
        }

        // Verificar se o arquivo temporário existe
        if (!is_uploaded_file($file['tmp_name'])) {
            error_log('Arquivo temporário não existe ou não é válido: ' . $file['tmp_name']);
            return null;
        }

        // Usar caminho relativo para melhor compatibilidade
        $uploadDir = 'public/adms/uploads/' . $folder . '/';
        
        // Criar diretório se não existir
        if (!is_dir($uploadDir)) {
            if (!mkdir($uploadDir, 0755, true)) {
                error_log('Não foi possível criar o diretório: ' . $uploadDir);
                return null;
            }
        }

        // Verificar se o diretório é gravável
        if (!is_writable($uploadDir)) {
            error_log('Diretório não é gravável: ' . $uploadDir);
            return null;
        }

        // Para imagens, verificar extensão
        if ($folder === 'imagens') {
            $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            
            if (!in_array($extension, $allowedExtensions)) {
                error_log('Extensão não permitida para imagem: ' . $extension);
                return null;
            }
        }

        // Validar tamanho (5MB)
        $maxSize = 5 * 1024 * 1024;
        if ($file['size'] > $maxSize) {
            error_log('Arquivo muito grande: ' . $file['size'] . ' bytes');
            return null;
        }

        // Gerar nome único para o arquivo
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        if ($extension) {
            $filename = uniqid() . '_' . time() . '.' . $extension;
        } else {
            $filename = uniqid() . '_' . time();
        }
        $filepath = $uploadDir . $filename;

        // Mover arquivo
        if (move_uploaded_file($file['tmp_name'], $filepath)) {
            return $folder . '/' . $filename;
        } else {
            error_log('Erro ao mover arquivo: ' . $file['tmp_name'] . ' para ' . $filepath);
            error_log('Erro do PHP: ' . error_get_last()['message'] ?? 'Desconhecido');
            return null;
        }
    }

    private function getUploadErrorMessage(int $errorCode): string
    {
        switch ($errorCode) {
            case UPLOAD_ERR_INI_SIZE:
                return 'O arquivo enviado excede o limite definido na diretiva upload_max_filesize do php.ini.';
            case UPLOAD_ERR_FORM_SIZE:
                return 'O arquivo enviado excede o limite definido no formulário HTML.';
            case UPLOAD_ERR_PARTIAL:
                return 'O arquivo enviado foi parcialmente enviado.';
            case UPLOAD_ERR_NO_FILE:
                return 'Nenhum arquivo foi enviado.';
            case UPLOAD_ERR_NO_TMP_DIR:
                return 'Faltou um diretório temporário.';
            case UPLOAD_ERR_CANT_WRITE:
                return 'Falha ao escrever o arquivo no disco.';
            case UPLOAD_ERR_EXTENSION:
                return 'Uma extensão do PHP interrompeu o upload do arquivo.';
            default:
                return 'Erro desconhecido no upload do arquivo.';
        }
    }
} 