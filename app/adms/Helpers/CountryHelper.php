<?php

namespace App\adms\Helpers;

/**
 * Helper para países e DDIs
 * 
 * @package App\adms\Helpers
 * @author Rafael Mendes
 */
class CountryHelper
{
    /**
     * Lista de países com DDI (principais para o Brasil)
     */
    public static function getCountries(): array
    {
        return [
            // América do Sul (chart_color: tom sugestivo da bandeira, para gráficos)
            'BR' => ['name' => 'Brasil', 'ddi' => '55', 'flag' => '🇧🇷', 'chart_color' => '#009B3A'],
            'AR' => ['name' => 'Argentina', 'ddi' => '54', 'flag' => '🇦🇷', 'chart_color' => '#75AADB'],
            'CL' => ['name' => 'Chile', 'ddi' => '56', 'flag' => '🇨🇱', 'chart_color' => '#D52B1E'],
            'CO' => ['name' => 'Colômbia', 'ddi' => '57', 'flag' => '🇨🇴', 'chart_color' => '#FCD116'],
            'UY' => ['name' => 'Uruguai', 'ddi' => '598', 'flag' => '🇺🇾', 'chart_color' => '#0038A8'],
            'PY' => ['name' => 'Paraguai', 'ddi' => '595', 'flag' => '🇵🇾', 'chart_color' => '#D52B1E'],
            'PE' => ['name' => 'Peru', 'ddi' => '51', 'flag' => '🇵🇪', 'chart_color' => '#D91023'],
            'BO' => ['name' => 'Bolívia', 'ddi' => '591', 'flag' => '🇧🇴', 'chart_color' => '#007934'],
            'EC' => ['name' => 'Equador', 'ddi' => '593', 'flag' => '🇪🇨', 'chart_color' => '#FCD116'],
            'VE' => ['name' => 'Venezuela', 'ddi' => '58', 'flag' => '🇻🇪', 'chart_color' => '#CF142B'],
            
            // América Central e Caribe
            'MX' => ['name' => 'México', 'ddi' => '52', 'flag' => '🇲🇽', 'chart_color' => '#006847'],
            'CR' => ['name' => 'Costa Rica', 'ddi' => '506', 'flag' => '🇨🇷', 'chart_color' => '#002B7F'],
            'PA' => ['name' => 'Panamá', 'ddi' => '507', 'flag' => '🇵🇦', 'chart_color' => '#005293'],
            'DO' => ['name' => 'República Dominicana', 'ddi' => '1', 'flag' => '🇩🇴', 'chart_color' => '#CE1126'],
            
            // América do Norte
            'US' => ['name' => 'Estados Unidos', 'ddi' => '1', 'flag' => '🇺🇸', 'chart_color' => '#3C3B6E'],
            'CA' => ['name' => 'Canadá', 'ddi' => '1', 'flag' => '🇨🇦', 'chart_color' => '#D80621'],
            
            // Europa Ocidental
            'PT' => ['name' => 'Portugal', 'ddi' => '351', 'flag' => '🇵🇹', 'chart_color' => '#006600'],
            'ES' => ['name' => 'Espanha', 'ddi' => '34', 'flag' => '🇪🇸', 'chart_color' => '#C60B1E'],
            'FR' => ['name' => 'França', 'ddi' => '33', 'flag' => '🇫🇷', 'chart_color' => '#002395'],
            'DE' => ['name' => 'Alemanha', 'ddi' => '49', 'flag' => '🇩🇪', 'chart_color' => '#DD0000'],
            'IT' => ['name' => 'Itália', 'ddi' => '39', 'flag' => '🇮🇹', 'chart_color' => '#009246'],
            'GB' => ['name' => 'Reino Unido', 'ddi' => '44', 'flag' => '🇬🇧', 'chart_color' => '#012169'],
            'IE' => ['name' => 'Irlanda', 'ddi' => '353', 'flag' => '🇮🇪', 'chart_color' => '#169B62'],
            'NL' => ['name' => 'Holanda', 'ddi' => '31', 'flag' => '🇳🇱', 'chart_color' => '#AE1C28'],
            'BE' => ['name' => 'Bélgica', 'ddi' => '32', 'flag' => '🇧🇪', 'chart_color' => '#EF3340'],
            'CH' => ['name' => 'Suíça', 'ddi' => '41', 'flag' => '🇨🇭', 'chart_color' => '#DA291C'],
            'AT' => ['name' => 'Áustria', 'ddi' => '43', 'flag' => '🇦🇹', 'chart_color' => '#ED2939'],
            
            // Europa do Leste
            'PL' => ['name' => 'Polônia', 'ddi' => '48', 'flag' => '🇵🇱', 'chart_color' => '#DC143C'],
            'RU' => ['name' => 'Rússia', 'ddi' => '7', 'flag' => '🇷🇺', 'chart_color' => '#0039A6'],
            
            // Ásia
            'CN' => ['name' => 'China', 'ddi' => '86', 'flag' => '🇨🇳', 'chart_color' => '#DE2910'],
            'JP' => ['name' => 'Japão', 'ddi' => '81', 'flag' => '🇯🇵', 'chart_color' => '#BC002D'],
            'KR' => ['name' => 'Coreia do Sul', 'ddi' => '82', 'flag' => '🇰🇷', 'chart_color' => '#0047A0'],
            'IN' => ['name' => 'Índia', 'ddi' => '91', 'flag' => '🇮🇳', 'chart_color' => '#FF671F'],
            'SG' => ['name' => 'Singapura', 'ddi' => '65', 'flag' => '🇸🇬', 'chart_color' => '#EF3340'],
            'TH' => ['name' => 'Tailândia', 'ddi' => '66', 'flag' => '🇹🇭', 'chart_color' => '#ED1C24'],
            'IL' => ['name' => 'Israel', 'ddi' => '972', 'flag' => '🇮🇱', 'chart_color' => '#0038B8'],
            'AE' => ['name' => 'Emirados Árabes', 'ddi' => '971', 'flag' => '🇦🇪', 'chart_color' => '#00732F'],
            
            // Oceania
            'AU' => ['name' => 'Austrália', 'ddi' => '61', 'flag' => '🇦🇺', 'chart_color' => '#012169'],
            'NZ' => ['name' => 'Nova Zelândia', 'ddi' => '64', 'flag' => '🇳🇿', 'chart_color' => '#00247D'],
            
            // África
            'ZA' => ['name' => 'África do Sul', 'ddi' => '27', 'flag' => '🇿🇦', 'chart_color' => '#007A4D'],
            'EG' => ['name' => 'Egito', 'ddi' => '20', 'flag' => '🇪🇬', 'chart_color' => '#CE1126'],
        ];
    }

    /**
     * Cor hex sugestiva da bandeira (gráficos / dashboards).
     */
    public static function getChartColor(string $countryCode): string
    {
        $countries = self::getCountries();

        return $countries[$countryCode]['chart_color'] ?? '#6c757d';
    }

    /**
     * Obter DDI de um país
     */
    public static function getDDI(string $countryCode): string
    {
        $countries = self::getCountries();
        return $countries[$countryCode]['ddi'] ?? '55'; // Brasil por padrão
    }

    /**
     * Obter nome do país
     */
    public static function getCountryName(string $countryCode): string
    {
        $countries = self::getCountries();
        return $countries[$countryCode]['name'] ?? 'Brasil';
    }

    /**
     * Formatar número de telefone com DDI para salvar no banco
     * 
     * @param string $number Número local (DDD + número) ou já com DDI
     * @param string $countryCode Código do país
     * @return string Apenas dígitos com DDI (ex: 5555999736755)
     */
    public static function formatPhoneWithDDI(string $number, string $countryCode = 'BR'): string
    {
        // Limpar número (apenas dígitos)
        $clean = preg_replace('/\D/', '', $number);
        
        if (empty($clean)) {
            return '';
        }
        
        $ddi = self::getDDI($countryCode);
        
        // Se já começa com DDI, retornar como está
        if (str_starts_with($clean, $ddi)) {
            return $clean;
        }
        
        // Adicionar DDI e retornar apenas dígitos
        return $ddi . $clean;
    }

    /**
     * Remover formatação de número para envio WhatsApp
     */
    public static function cleanPhoneForWhatsApp(string $phone): string
    {
        return preg_replace('/\D/', '', $phone);
    }
}

