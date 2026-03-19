<?php

namespace App\adms\Helpers;

class FormatHelper
{
    /**
     * Formata o período de reciclagem com singular/plural
     */
    public static function formatReciclagemPeriodo(?int $periodo): string
    {
        if (empty($periodo) || $periodo <= 0) {
            return 'N/A';
        }
        
        $texto = $periodo === 1 ? 'mês' : 'meses';
        return $periodo . ' ' . $texto;
    }
    
    /**
     * Formata o período de reciclagem para exibição em tabelas
     */
    public static function formatReciclagemPeriodoTable(?int $periodo): string
    {
        if (empty($periodo) || $periodo <= 0) {
            return 'N/A';
        }
        
        $texto = $periodo === 1 ? 'mês' : 'meses';
        return $periodo . ' - ' . $texto;
    }

    public static function formatDate(?string $date, $format = 'd/m/Y'): string
    {
        if (empty($date) || $date === '0000-00-00' || $date === '0000-00-00 00:00:00') {
            return '-';
        }
        try {
            $dt = new \DateTime($date);
            return $dt->format($format);
        } catch (\Throwable $e) {
            // Se não for uma data válida, retorna o valor original para facilitar diagnóstico
            return (string) $date;
        }
    }

    public static function formatDateTime(?string $dateTime, string $format = 'd/m/Y H:i:s'): string
    {
        if (empty($dateTime) || $dateTime === '0000-00-00' || $dateTime === '0000-00-00 00:00:00') {
            return '-';
        }
        try {
            $dt = new \DateTime($dateTime);
            return $dt->format($format);
        } catch (\Throwable $e) {
            return (string) $dateTime;
        }
    }

    /**
     * Retorna o HTML de um ícone de arquivo baseado na extensão.
     */
    public static function renderFileIcon(?string $path, string $extraClasses = ''): string
    {
        if (!$path) {
            return '';
        }

        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $icon = 'fa-file';
        $color = 'text-muted';

        if ($ext === 'pdf') {
            $icon = 'fa-file-pdf';
            $color = 'text-danger';
        } elseif (in_array($ext, ['doc', 'docx'], true)) {
            $icon = 'fa-file-word';
            $color = 'text-primary';
        } elseif (in_array($ext, ['xls', 'xlsx', 'csv'], true)) {
            $icon = 'fa-file-excel';
            $color = 'text-success';
        } elseif ($ext === 'txt') {
            $icon = 'fa-file-lines';
            $color = 'text-secondary';
        } elseif (in_array($ext, ['zip', 'rar'], true)) {
            $icon = 'fa-file-archive';
            $color = 'text-warning';
        }

        $classes = trim("fas {$icon} {$color} {$extraClasses}");
        return '<i class="' . $classes . '"></i>';
    }
} 