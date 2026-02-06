<?php

namespace App\adms\Controllers\lgpd;

use App\adms\Models\Repository\LgpdConsentimentosRepository;
use App\adms\Models\Services\SensitiveActionService;

/**
 * Controller responsável por revogar Consentimentos LGPD.
 *
 * Segue o mesmo padrão das controllers *Delete* dos módulos LGPD,
 * recebendo o ID na URL (ex.: lgpd-consentimentos-revogar/8).
 */
class LgpdConsentimentosRevogar
{
    /** @var LgpdConsentimentosRepository */
    private LgpdConsentimentosRepository $consentimentosRepo;

    public function __construct()
    {
        $this->consentimentosRepo = new LgpdConsentimentosRepository();
    }

    /**
     * Revogar um consentimento (status => Revogado).
     *
     * @param int $id
     * @return void
     */
    public function index(int $id): void
    {
        // Se for requisição AJAX (modal), responder em JSON e exigir senha + motivo
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            header('Content-Type: application/json');

            $consentimento = $this->consentimentosRepo->getConsentimentoById($id);
            if (!$consentimento) {
                echo json_encode(['success' => false, 'message' => 'Consentimento não encontrado']);
                exit;
            }

            $motivo = trim($_POST['motivo'] ?? '');
            $password = $_POST['password'] ?? '';

            $validacao = SensitiveActionService::validarConfirmacao($password, $motivo, true);
            if (!$validacao['success']) {
                echo json_encode(['success' => false, 'message' => $validacao['message']]);
                exit;
            }

            $userId = (int)($_SESSION['user_id'] ?? 0);
            $result = $this->consentimentosRepo->revogarConsentimento($id, $userId, $motivo);

            if ($result) {
                echo json_encode(['success' => true, 'message' => 'Consentimento revogado com sucesso.']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Erro ao revogar consentimento.']);
            }
            exit;
        }

        // Fluxo padrão (fallback) via GET com redirect simples
        $consentimento = $this->consentimentosRepo->getConsentimentoById($id);

        if (!$consentimento) {
            $_SESSION['error'] = "Consentimento não encontrado!";
            header("Location: " . $_ENV['URL_ADM'] . "lgpd-consentimentos");
            exit;
        }

        $userId = $_SESSION['user_id'] ?? null;
        $motivo = $_GET['motivo'] ?? null;

        $result = $this->consentimentosRepo->revogarConsentimento($id, $userId ? (int)$userId : null, $motivo);

        if ($result) {
            $_SESSION['success'] = "Consentimento revogado com sucesso!";
        } else {
            $_SESSION['error'] = "Erro ao revogar consentimento!";
        }

        header("Location: " . $_ENV['URL_ADM'] . "lgpd-consentimentos");
        exit;
    }
}


