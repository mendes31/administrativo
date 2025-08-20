<?php

namespace App\adms\Controllers\lgpd;

use App\adms\Models\Repository\LgpdTiaRepository;
use Exception;

/**
 * Controller responsável pela exclusão de Testes de Impacto às Atividades (TIA).
 *
 * @package App\adms\Controllers\lgpd
 */
class LgpdTiaDelete
{
    /** @var LgpdTiaRepository $tiaRepo */
    private LgpdTiaRepository $tiaRepo;

    public function __construct()
    {
        $this->tiaRepo = new LgpdTiaRepository();
    }

    /**
     * Método para excluir um teste TIA específico.
     *
     * @param int $id ID do teste TIA
     * @return void
     */
    public function index(int $id): void
    {
        try {
            // Verificar se o teste TIA existe
            $tia = $this->tiaRepo->getTiaById($id);
            
            if (!$tia) {
                $_SESSION['error'] = "Teste TIA não encontrado!";
                header("Location: " . $_ENV['URL_ADM'] . "lgpd-tia");
                exit;
            }
            
            // Excluir o teste TIA
            $result = $this->tiaRepo->delete($id);
            
            if ($result) {
                $_SESSION['success'] = "Teste TIA excluído com sucesso!";
            } else {
                $_SESSION['error'] = "Erro ao excluir teste TIA!";
            }
            
        } catch (Exception $e) {
            error_log("Erro no controller LgpdTiaDelete: " . $e->getMessage());
            $_SESSION['error'] = "Erro ao excluir teste TIA!";
        }
        
        // Redirecionar para a listagem
        header("Location: " . $_ENV['URL_ADM'] . "lgpd-tia");
        exit;
    }
}
