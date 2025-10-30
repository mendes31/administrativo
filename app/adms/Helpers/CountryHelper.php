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
            // América do Sul
            'BR' => ['name' => 'Brasil', 'ddi' => '55', 'flag' => '🇧🇷'],
            'AR' => ['name' => 'Argentina', 'ddi' => '54', 'flag' => '🇦🇷'],
            'CL' => ['name' => 'Chile', 'ddi' => '56', 'flag' => '🇨🇱'],
            'CO' => ['name' => 'Colômbia', 'ddi' => '57', 'flag' => '🇨🇴'],
            'UY' => ['name' => 'Uruguai', 'ddi' => '598', 'flag' => '🇺🇾'],
            'PY' => ['name' => 'Paraguai', 'ddi' => '595', 'flag' => '🇵🇾'],
            'PE' => ['name' => 'Peru', 'ddi' => '51', 'flag' => '🇵🇪'],
            'BO' => ['name' => 'Bolívia', 'ddi' => '591', 'flag' => '🇧🇴'],
            'EC' => ['name' => 'Equador', 'ddi' => '593', 'flag' => '🇪🇨'],
            'VE' => ['name' => 'Venezuela', 'ddi' => '58', 'flag' => '🇻🇪'],
            
            // América Central e Caribe
            'MX' => ['name' => 'México', 'ddi' => '52', 'flag' => '🇲🇽'],
            'CR' => ['name' => 'Costa Rica', 'ddi' => '506', 'flag' => '🇨🇷'],
            'PA' => ['name' => 'Panamá', 'ddi' => '507', 'flag' => '🇵🇦'],
            'DO' => ['name' => 'República Dominicana', 'ddi' => '1', 'flag' => '🇩🇴'],
            
            // América do Norte
            'US' => ['name' => 'Estados Unidos', 'ddi' => '1', 'flag' => '🇺🇸'],
            'CA' => ['name' => 'Canadá', 'ddi' => '1', 'flag' => '🇨🇦'],
            
            // Europa Ocidental
            'PT' => ['name' => 'Portugal', 'ddi' => '351', 'flag' => '🇵🇹'],
            'ES' => ['name' => 'Espanha', 'ddi' => '34', 'flag' => '🇪🇸'],
            'FR' => ['name' => 'França', 'ddi' => '33', 'flag' => '🇫🇷'],
            'DE' => ['name' => 'Alemanha', 'ddi' => '49', 'flag' => '🇩🇪'],
            'IT' => ['name' => 'Itália', 'ddi' => '39', 'flag' => '🇮🇹'],
            'GB' => ['name' => 'Reino Unido', 'ddi' => '44', 'flag' => '🇬🇧'],
            'IE' => ['name' => 'Irlanda', 'ddi' => '353', 'flag' => '🇮🇪'],
            'NL' => ['name' => 'Holanda', 'ddi' => '31', 'flag' => '🇳🇱'],
            'BE' => ['name' => 'Bélgica', 'ddi' => '32', 'flag' => '🇧🇪'],
            'CH' => ['name' => 'Suíça', 'ddi' => '41', 'flag' => '🇨🇭'],
            'AT' => ['name' => 'Áustria', 'ddi' => '43', 'flag' => '🇦🇹'],
            
            // Europa do Leste
            'PL' => ['name' => 'Polônia', 'ddi' => '48', 'flag' => '🇵🇱'],
            'RU' => ['name' => 'Rússia', 'ddi' => '7', 'flag' => '🇷🇺'],
            
            // Ásia
            'CN' => ['name' => 'China', 'ddi' => '86', 'flag' => '🇨🇳'],
            'JP' => ['name' => 'Japão', 'ddi' => '81', 'flag' => '🇯🇵'],
            'KR' => ['name' => 'Coreia do Sul', 'ddi' => '82', 'flag' => '🇰🇷'],
            'IN' => ['name' => 'Índia', 'ddi' => '91', 'flag' => '🇮🇳'],
            'SG' => ['name' => 'Singapura', 'ddi' => '65', 'flag' => '🇸🇬'],
            'TH' => ['name' => 'Tailândia', 'ddi' => '66', 'flag' => '🇹🇭'],
            'IL' => ['name' => 'Israel', 'ddi' => '972', 'flag' => '🇮🇱'],
            'AE' => ['name' => 'Emirados Árabes', 'ddi' => '971', 'flag' => '🇦🇪'],
            
            // Oceania
            'AU' => ['name' => 'Austrália', 'ddi' => '61', 'flag' => '🇦🇺'],
            'NZ' => ['name' => 'Nova Zelândia', 'ddi' => '64', 'flag' => '🇳🇿'],
            
            // África
            'ZA' => ['name' => 'África do Sul', 'ddi' => '27', 'flag' => '🇿🇦'],
            'EG' => ['name' => 'Egito', 'ddi' => '20', 'flag' => '🇪🇬'],
        ];
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

