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
        $dt = new \DateTime($date);
        return $dt->format($format);
    }
} 