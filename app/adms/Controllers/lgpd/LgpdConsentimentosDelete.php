<?php

namespace App\adms\Controllers\lgpd;

use App\adms\Models\Repository\LgpdConsentimentosRepository;
use App\adms\Models\Services\SensitiveActionService;

/**
 * Controller responsável pela exclusão de Consentimentos LGPD.
 *
 * @package App\adms\Controllers\lgpd
 */
class LgpdConsentimentosDelete
{
    /** @var LgpdConsentimentosRepository $consentimentosRepo */
    private LgpdConsentimentosRepository $consentimentosRepo;

    public function __construct()
    {
        $this->consentimentosRepo = new LgpdConsentimentosRepository();
    }

    /**
     * Método para excluir consentimento.
     *
     * @param int $id ID do consentimento
     * @return void
     */
    public function index(int $id): void
    {
        $consentimento = $this->consentimentosRepo->getConsentimentoById($id);
        
        if (!$consentimento) {
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Consentimento não encontrado.']);
                exit;
            }

            $_SESSION['error'] = "Consentimento não encontrado!";
            header("Location: " . $_ENV['URL_ADM'] . "lgpd-consentimentos");
            exit;
        }

        // Fluxo via AJAX (modal) com senha + justificativa
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            header('Content-Type: application/json');

            $motivo = trim($_POST['motivo'] ?? '');
            $password = $_POST['password'] ?? '';

            $validacao = SensitiveActionService::validarConfirmacao($password, $motivo, true);
            if (!$validacao['success']) {
                echo json_encode(['success' => false, 'message' => $validacao['message']]);
                exit;
            }

            $userId = (int)($_SESSION['user_id'] ?? 0);
            $result = $this->consentimentosRepo->delete($id, $userId, $motivo);

            if ($result) {
                echo json_encode(['success' => true, 'message' => 'Consentimento excluído com sucesso!']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Erro ao excluir consentimento!']);
            }
            exit;
        }

        // Fluxo GET antigo (fallback, sem senha/justificativa)
        $result = $this->consentimentosRepo->delete($id, null, null);
        
        if ($result) {
            $_SESSION['success'] = "Consentimento excluído com sucesso!";
        } else {
            $_SESSION['error'] = "Erro ao excluir consentimento!";
        }

        header("Location: " . $_ENV['URL_ADM'] . "lgpd-consentimentos");
        exit;
    }
}
