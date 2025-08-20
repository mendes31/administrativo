<?php

namespace App\adms\Controllers\informativos;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\InformativosRepository;
use App\adms\Views\Services\LoadViewService;

class UpdateInformativo
{
    private array|string|null $data = null;

    public function index(string|int $id = null)
    {
        if (!$id) {
            $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">ID do informativo não informado!</div>';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-informativos');
            exit;
        }

        $repo = new InformativosRepository();
        $informativo = $repo->getInformativoById((int)$id);

        if (!$informativo) {
            $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">Informativo não encontrado!</div>';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-informativos');
            exit;
        }

        $this->data['informativo'] = $informativo;
        $this->data['categorias'] = $repo->getCategorias();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->update((int)$id);
        }

        $pageElements = [
            'title_head' => 'Editar Informativo',
            'menu' => 'update-informativo',
            'buttonPermission' => ['UpdateInformativo'],
        ];

        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService('adms/Views/informativos/update', $this->data);
        $loadView->loadView();
    }

    private function update(int $id): void
    {
        if (!CSRFHelper::validateCSRFToken('update_informativo', $_POST['csrf_token'] ?? '')) {
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

        // Upload de nova imagem
        $imagem = $this->data['informativo']['imagem'];
        if (isset($_FILES['imagem']) && $_FILES['imagem']['error'] === UPLOAD_ERR_OK) {
            $novaImagem = $this->uploadFile($_FILES['imagem'], 'imagens', $imagem);
            if ($novaImagem) {
                $imagem = $novaImagem;
            }
        }

        // Upload de novo anexo
        $anexo = $this->data['informativo']['anexo'];
        if (isset($_FILES['anexo']) && $_FILES['anexo']['error'] === UPLOAD_ERR_OK) {
            $novoAnexo = $this->uploadFile($_FILES['anexo'], 'anexos', $anexo);
            if ($novoAnexo) {
                $anexo = $novoAnexo;
            }
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
        ];

        $repo = new InformativosRepository();
        
        try {
            $success = $repo->updateInformativo($id, $data);
            
            if ($success) {
                $_SESSION['msg'] = '<div class="alert alert-success" role="alert">Informativo atualizado com sucesso!</div>';
                header('Location: ' . $_ENV['URL_ADM'] . 'list-informativos');
                exit;
            } else {
                $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">Erro ao atualizar informativo!</div>';
            }
        } catch (\Exception $e) {
            $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">Erro ao atualizar informativo: ' . $e->getMessage() . '</div>';
        }
    }

    private function uploadFile(array $file, string $folder, ?string $oldFile = null): ?string
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

        // Caminho correto para uploads (subindo 4 níveis)
        $basePath = dirname(__DIR__, 4);
        $uploadDir = $basePath . '/public/adms/uploads/' . $folder . '/';
        error_log('UPLOAD DIR (corrigido): ' . $uploadDir);
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
            // Remover arquivo antigo se existir
            if ($oldFile) {
                $oldPath = $basePath . '/public/adms/uploads/' . $oldFile;
                if (file_exists($oldPath)) {
                    unlink($oldPath);
                }
            }
            return $folder . '/' . $filename;
        } else {
            error_log('Erro ao mover arquivo: ' . $file['tmp_name'] . ' para ' . $filepath);
            error_log('Erro do PHP: ' . (error_get_last()['message'] ?? 'Desconhecido'));
            return null;
        }
    }

    // Remover métodos removerImagem e removerAnexo para isolar o problema do upload/atualização.
} 