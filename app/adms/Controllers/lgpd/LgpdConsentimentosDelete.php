<?php

namespace App\adms\Controllers\lgpd;

use App\adms\Models\Repository\LgpdConsentimentosRepository;

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
            $_SESSION['error'] = "Consentimento não encontrado!";
            header("Location: " . $_ENV['URL_ADM'] . "lgpd-consentimentos");
            exit;
        }

        $result = $this->consentimentosRepo->delete($id);
        
        if ($result) {
            $_SESSION['success'] = "Consentimento excluído com sucesso!";
        } else {
            $_SESSION['error'] = "Erro ao excluir consentimento!";
        }

        header("Location: " . $_ENV['URL_ADM'] . "lgpd-consentimentos");
        exit;
    }
}
