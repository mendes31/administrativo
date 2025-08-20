<?php

namespace App\adms\Controllers\lgpd;

use App\adms\Models\Repository\LgpdRipdRepository;
use Exception;

/**
 * Controller responsável pela visualização de relatórios RIPD em PDF.
 */
class LgpdRipdExportPdfView
{
    private LgpdRipdRepository $ripdRepo;

    public function __construct()
    {
        $this->ripdRepo = new LgpdRipdRepository();
    }

    /**
     * Método padrão - redireciona para lista de RIPDs.
     */
    public function index(): void
    {
        header("Location: " . $_ENV['URL_ADM'] . "lgpd-ripd");
        exit;
    }

    /**
     * Exibe página de visualização de PDF específico.
     */
    public function view(int $id): void
    {
        try {
            $ripd = $this->ripdRepo->getRipdById($id);
            
            if (!$ripd) {
                $_SESSION['error'] = "Relatório RIPD não encontrado";
                header("Location: " . $_ENV['URL_ADM'] . "lgpd-ripd");
                exit;
            }

            // Redirecionar para exportação direta
            header("Location: " . $_ENV['URL_ADM'] . "lgpd-ripd-export-pdf/" . $id);
            exit;

        } catch (Exception $e) {
            error_log("Erro em LgpdRipdExportPdfView::view: " . $e->getMessage());
            $_SESSION['error'] = "Erro ao carregar visualização PDF: " . $e->getMessage();
            header("Location: " . $_ENV['URL_ADM'] . "lgpd-ripd");
            exit;
        }
    }
}
