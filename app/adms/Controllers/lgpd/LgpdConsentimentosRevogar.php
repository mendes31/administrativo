<?php

namespace App\adms\Controllers\lgpd;

use App\adms\Models\Repository\LgpdConsentimentosRepository;

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
        $consentimento = $this->consentimentosRepo->getConsentimentoById($id);

        if (!$consentimento) {
            $_SESSION['error'] = "Consentimento não encontrado!";
            header("Location: " . $_ENV['URL_ADM'] . "lgpd-consentimentos");
            exit;
        }

        $result = $this->consentimentosRepo->revogarConsentimento($id);

        if ($result) {
            $_SESSION['success'] = "Consentimento revogado com sucesso!";
        } else {
            $_SESSION['error'] = "Erro ao revogar consentimento!";
        }

        header("Location: " . $_ENV['URL_ADM'] . "lgpd-consentimentos");
        exit;
    }
}


