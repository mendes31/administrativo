<?php

namespace App\adms\Helpers;

class ScreenResolutionHelper
{
    /**
     * Detecta a resolução da tela via JavaScript e retorna via AJAX
     */
    public static function getScreenResolution(): array
    {
        // Valores padrão para fallback
        $defaultResolution = [
            'width' => 1920,
            'height' => 1080,
            'category' => 'large'
        ];

        // Se não há dados de resolução, retorna padrão
        if (!isset($_SESSION['screen_resolution'])) {
            return $defaultResolution;
        }

        return $_SESSION['screen_resolution'];
    }

    /**
     * Categoriza a resolução da tela
     */
    public static function categorizeResolution(int $width, int $height): string
    {
        if ($width >= 1920) {
            return 'large'; // Full HD e superior
        } elseif ($width >= 1440) {
            return 'medium'; // HD+ e similar
        } elseif ($width >= 1366) {
            return 'small'; // HD (1366x768) - notebooks comuns
        } elseif ($width >= 1024) {
            return 'tablet'; // Tablets
        } else {
            return 'mobile'; // Mobile
        }
    }

    /**
     * Retorna classes CSS baseadas na resolução
     */
    public static function getResponsiveClasses(string $category): array
    {
        $classes = [
            'large' => [
                'container' => 'container-fluid px-4',
                'table' => 'table-responsive',
                'cards' => 'row g-4',
                'card_cols' => 'col-md-3 col-lg-2',
                'filters' => 'row g-3',
                'filter_cols' => 'col-md-3 col-lg-2'
            ],
            'medium' => [
                'container' => 'container-fluid px-3',
                'table' => 'table-responsive',
                'cards' => 'row g-3',
                'card_cols' => 'col-md-4 col-lg-3',
                'filters' => 'row g-2',
                'filter_cols' => 'col-md-4 col-lg-3'
            ],
            'small' => [
                'container' => 'container-fluid px-1',
                'table' => 'table-responsive',
                'cards' => 'row g-1',
                'card_cols' => 'col-md-6 col-lg-4',
                'filters' => 'row g-1',
                'filter_cols' => 'col-md-4 col-lg-3'
            ],
            'tablet' => [
                'container' => 'container-fluid px-2',
                'table' => 'table-responsive',
                'cards' => 'row g-2',
                'card_cols' => 'col-md-6 col-lg-4',
                'filters' => 'row g-2',
                'filter_cols' => 'col-md-6 col-lg-4'
            ],
            'mobile' => [
                'container' => 'container-fluid px-2',
                'table' => 'd-none d-md-block',
                'cards' => 'row g-2',
                'card_cols' => 'col-12',
                'filters' => 'row g-2',
                'filter_cols' => 'col-12'
            ]
        ];

        return $classes[$category] ?? $classes['medium'];
    }

    /**
     * Retorna configurações de paginação baseadas na resolução
     */
    public static function getPaginationSettings(string $category): array
    {
        $settings = [
            'large' => [
                'per_page' => 50,
                'options' => [10, 25, 50, 100]
            ],
            'medium' => [
                'per_page' => 25,
                'options' => [10, 20, 25, 50]
            ],
            'small' => [
                'per_page' => 10,
                'options' => [5, 10, 15, 20]
            ],
            'tablet' => [
                'per_page' => 15,
                'options' => [5, 10, 15, 25]
            ],
            'mobile' => [
                'per_page' => 10,
                'options' => [5, 10, 15]
            ]
        ];

        return $settings[$category] ?? $settings['medium'];
    }
} 