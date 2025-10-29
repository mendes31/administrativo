<?php

namespace App\adms\Controllers\crm;

use App\adms\Models\Repository\CrmDocumentsRepository;

/**
 * Controller para Upload de Documento CRM
 *
 * @package App\adms\Controllers\crm
 * @author Rafael Mendes
 */
class CrmUploadDocument
{
    private string $uploadDir = 'public/adms/uploads/crm/documents/';

    public function index(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $_SESSION['msg'] = "Método não permitido.";
            $_SESSION['msg_type'] = "danger";
            header("Location: " . $_ENV['URL_ADM'] . "crm-kanban-pipeline");
            exit;
        }

        $opportunityId = $_POST['opportunity_id'] ?? null;
        $partnerId = $_POST['partner_id'] ?? null;
        $description = $_POST['description'] ?? '';

        // Validações
        if (empty($opportunityId) && empty($partnerId)) {
            $_SESSION['msg'] = "O documento deve estar vinculado a uma oportunidade ou parceiro.";
            $_SESSION['msg_type'] = "danger";
            $this->redirectBack($opportunityId, $partnerId);
            exit;
        }

        if (empty($_FILES['document']) || $_FILES['document']['error'] !== UPLOAD_ERR_OK) {
            $_SESSION['msg'] = "Nenhum arquivo foi enviado ou ocorreu um erro no upload.";
            $_SESSION['msg_type'] = "danger";
            $this->redirectBack($opportunityId, $partnerId);
            exit;
        }

        $file = $_FILES['document'];
        
        // Validar tamanho (max 10MB)
        $maxSize = 10 * 1024 * 1024; // 10MB
        if ($file['size'] > $maxSize) {
            $_SESSION['msg'] = "O arquivo excede o tamanho máximo permitido de 10MB.";
            $_SESSION['msg_type'] = "danger";
            $this->redirectBack($opportunityId, $partnerId);
            exit;
        }

        // Validar tipo de arquivo
        $allowedTypes = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'jpg', 'jpeg', 'png', 'gif', 'zip', 'rar'];
        $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (!in_array($fileExtension, $allowedTypes)) {
            $_SESSION['msg'] = "Tipo de arquivo não permitido. Permitidos: " . implode(', ', $allowedTypes);
            $_SESSION['msg_type'] = "danger";
            $this->redirectBack($opportunityId, $partnerId);
            exit;
        }

        // Criar diretório se não existir
        if (!is_dir($this->uploadDir)) {
            mkdir($this->uploadDir, 0755, true);
        }

        // Gerar nome único para o arquivo
        $uniqueName = uniqid() . '_' . time() . '.' . $fileExtension;
        $filePath = $this->uploadDir . $uniqueName;

        // Mover arquivo
        if (!move_uploaded_file($file['tmp_name'], $filePath)) {
            $_SESSION['msg'] = "Erro ao salvar o arquivo no servidor.";
            $_SESSION['msg_type'] = "danger";
            $this->redirectBack($opportunityId, $partnerId);
            exit;
        }

        // Salvar no banco de dados
        $data = [
            'partner_id' => $partnerId,
            'opportunity_id' => $opportunityId,
            'file_name' => $file['name'],
            'file_path' => $filePath,
            'file_type' => $file['type'],
            'file_size' => $file['size'],
            'description' => $description,
        ];

        $documentsRepo = new CrmDocumentsRepository();
        $result = $documentsRepo->uploadDocument($data);

        if ($result) {
            $_SESSION['msg'] = "Documento enviado com sucesso!";
            $_SESSION['msg_type'] = "success";
        } else {
            // Remover arquivo se falhou ao salvar no BD
            if (file_exists($filePath)) {
                unlink($filePath);
            }
            $_SESSION['msg'] = "Erro ao registrar documento no banco de dados.";
            $_SESSION['msg_type'] = "danger";
        }

        $this->redirectBack($opportunityId, $partnerId);
    }

    private function redirectBack($opportunityId, $partnerId): void
    {
        if (!empty($opportunityId)) {
            header("Location: " . $_ENV['URL_ADM'] . "crm-view-opportunity/" . $opportunityId);
        } elseif (!empty($partnerId)) {
            header("Location: " . $_ENV['URL_ADM'] . "crm-view-partner/" . $partnerId);
        } else {
            header("Location: " . $_ENV['URL_ADM'] . "crm-kanban-pipeline");
        }
        exit;
    }
}

