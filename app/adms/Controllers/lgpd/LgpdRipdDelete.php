<?php

namespace App\adms\Controllers\lgpd;

use App\adms\Models\Repository\LgpdRipdRepository;
use App\adms\Helpers\CSRFHelper;
use Exception;

/**
 * Controller responsável pela exclusão de Relatórios de Impacto à Proteção de Dados (RIPD).
 *
 * @package App\adms\Controllers\lgpd
 */
class LgpdRipdDelete
{
    /** @var LgpdRipdRepository $ripdRepo */
    private LgpdRipdRepository $ripdRepo;

    public function __construct()
    {
        $this->ripdRepo = new LgpdRipdRepository();
    }

    /**
     * Método para excluir um relatório RIPD.
     *
     * @param int $id ID do RIPD
     * @return void
     */
    public function index(int $id): void
    {
        try {
            // Verificar CSRF
            if (!CSRFHelper::validateCSRFToken('ripd_delete', $_POST['csrf_token'] ?? '')) {
                $_SESSION['error'] = "Token de segurança inválido!";
                header("Location: " . $_ENV['URL_ADM'] . "lgpd-ripd");
                exit;
            }

            // Verificar se o RIPD existe
            $existingRipd = $this->ripdRepo->getRipdById($id);
            if (!$existingRipd) {
                $_SESSION['error'] = "RIPD não encontrado!";
                header("Location: " . $_ENV['URL_ADM'] . "lgpd-ripd");
                exit;
            }

            // Verificar se pode ser excluído (não pode excluir RIPDs aprovados)
            if ($existingRipd['status'] === 'Aprovado') {
                $_SESSION['error'] = "Não é possível excluir um RIPD aprovado!";
                header("Location: " . $_ENV['URL_ADM'] . "lgpd-ripd");
                exit;
            }

            // Excluir o RIPD
            if ($this->ripdRepo->delete($id)) {
                $_SESSION['success'] = "RIPD excluído com sucesso!";
            } else {
                $_SESSION['error'] = "Erro ao excluir RIPD!";
            }

            header("Location: " . $_ENV['URL_ADM'] . "lgpd-ripd");
            exit;
        } catch (Exception $e) {
            error_log("Erro ao excluir RIPD: " . $e->getMessage());
            $_SESSION['error'] = "Erro interno ao excluir RIPD!";
            header("Location: " . $_ENV['URL_ADM'] . "lgpd-ripd");
            exit;
        }
    }
}
