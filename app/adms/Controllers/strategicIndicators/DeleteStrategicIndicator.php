<?php

declare(strict_types=1);

namespace App\adms\Controllers\strategicIndicators;

use App\adms\Models\Repository\StrategicIndicatorsRepository;

/**
 * Controller para excluir Indicador Estratégico
 * @package App\adms\Controllers\strategicIndicators
 */
class DeleteStrategicIndicator
{
    /** @var array|string|null $data Dados que devem ser enviados para a VIEW */
    private array $data = [];

    /**
     * Excluir um Indicador Estratégico específico.
     *
     * @param string|int|null $id ID do indicador estratégico
     * @return void
     */
    public function index(string|int|null $id = null): void
    {
        // Converter ID para int se necessário
        $id = $id ? (int)$id : null;
        
        // Verificar se o ID foi fornecido
        if (!$id) {
            $_SESSION['msg'] = "ID do indicador não fornecido!";
            $_SESSION['msg_type'] = "danger";
            header("Location: " . $_ENV['URL_ADM'] . "strategic-indicators-list");
            exit;
        }

        // Instancia o repositório
        $indicatorsRepo = new StrategicIndicatorsRepository();

        // Busca o indicador estratégico pelo ID
        $indicator = $indicatorsRepo->getById($id);

        // Verifica se o indicador existe
        if (!$indicator) {
            $_SESSION['msg'] = "Indicador Estratégico não encontrado!";
            $_SESSION['msg_type'] = "danger";
            header("Location: " . $_ENV['URL_ADM'] . "strategic-indicators-list");
            exit;
        }

        // Tenta excluir o indicador
        if ($indicatorsRepo->delete($id)) {
            $_SESSION['msg'] = "Indicador Estratégico excluído com sucesso!";
            $_SESSION['msg_type'] = "success";
        } else {
            $_SESSION['msg'] = "Erro ao excluir Indicador Estratégico!";
            $_SESSION['msg_type'] = "danger";
        }

        // Redireciona para a listagem
        header("Location: " . $_ENV['URL_ADM'] . "strategic-indicators-list");
        exit;
    }
}
