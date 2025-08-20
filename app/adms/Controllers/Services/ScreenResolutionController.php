<?php

namespace App\adms\Controllers\Services;

use App\adms\Helpers\ScreenResolutionHelper;

class ScreenResolutionController
{
    /**
     * Recebe a resolução da tela via AJAX e salva na sessão
     */
    public function setScreenResolution(): void
    {
        // Verificar se é uma requisição AJAX
        if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
            http_response_code(400);
            echo json_encode(['error' => 'Requisição inválida']);
            return;
        }

        // Verificar se os dados foram enviados
        if (!isset($_POST['width']) || !isset($_POST['height'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Dados de resolução não fornecidos']);
            return;
        }

        $width = (int)$_POST['width'];
        $height = (int)$_POST['height'];

        // Validar valores
        if ($width < 320 || $width > 7680 || $height < 240 || $height > 4320) {
            http_response_code(400);
            echo json_encode(['error' => 'Valores de resolução inválidos']);
            return;
        }

        // Categorizar a resolução
        $category = ScreenResolutionHelper::categorizeResolution($width, $height);

        // Salvar na sessão
        $_SESSION['screen_resolution'] = [
            'width' => $width,
            'height' => $height,
            'category' => $category
        ];

        // Retornar sucesso
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'resolution' => [
                'width' => $width,
                'height' => $height,
                'category' => $category
            ],
            'classes' => ScreenResolutionHelper::getResponsiveClasses($category),
            'pagination' => ScreenResolutionHelper::getPaginationSettings($category)
        ]);
    }

    /**
     * Retorna a resolução atual da tela
     */
    public function getScreenResolution(): void
    {
        $resolution = ScreenResolutionHelper::getScreenResolution();
        
        header('Content-Type: application/json');
        echo json_encode([
            'resolution' => $resolution,
            'classes' => ScreenResolutionHelper::getResponsiveClasses($resolution['category']),
            'pagination' => ScreenResolutionHelper::getPaginationSettings($resolution['category'])
        ]);
    }
} 