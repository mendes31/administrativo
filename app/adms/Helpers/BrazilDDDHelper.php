<?php

namespace App\adms\Helpers;

/**
 * Helper para DDDs (Códigos de Área) do Brasil
 */
class BrazilDDDHelper
{
    /**
     * DDDs por Estado
     * Fonte: Anatel
     */
    public static function getDDDsByState(string $uf): array
    {
        $ddds = [
            'SP' => ['11', '12', '13', '14', '15', '16', '17', '18', '19'],
            'RJ' => ['21', '22', '24'],
            'ES' => ['27', '28'],
            'MG' => ['31', '32', '33', '34', '35', '37', '38'],
            'PR' => ['41', '42', '43', '44', '45', '46'],
            'SC' => ['47', '48', '49'],
            'RS' => ['51', '53', '54', '55'],
            'DF' => ['61'],
            'GO' => ['62', '64'],
            'TO' => ['63'],
            'MT' => ['65', '66'],
            'MS' => ['67'],
            'AC' => ['68'],
            'RO' => ['69'],
            'BA' => ['71', '73', '74', '75', '77'],
            'SE' => ['79'],
            'PE' => ['81', '87'],
            'AL' => ['82'],
            'PB' => ['83'],
            'RN' => ['84'],
            'CE' => ['85', '88'],
            'PI' => ['86', '89'],
            'PA' => ['91', '93', '94'],
            'AP' => ['96'],
            'AM' => ['92', '97'],
            'RR' => ['95'],
            'MA' => ['98', '99'],
        ];
        
        return $ddds[$uf] ?? [];
    }

    /**
     * Obter estado pelo DDD
     */
    public static function getStateByDDD(string $ddd): ?string
    {
        $dddToState = [
            '11' => 'SP', '12' => 'SP', '13' => 'SP', '14' => 'SP', '15' => 'SP',
            '16' => 'SP', '17' => 'SP', '18' => 'SP', '19' => 'SP',
            '21' => 'RJ', '22' => 'RJ', '24' => 'RJ',
            '27' => 'ES', '28' => 'ES',
            '31' => 'MG', '32' => 'MG', '33' => 'MG', '34' => 'MG', '35' => 'MG',
            '37' => 'MG', '38' => 'MG',
            '41' => 'PR', '42' => 'PR', '43' => 'PR', '44' => 'PR', '45' => 'PR', '46' => 'PR',
            '47' => 'SC', '48' => 'SC', '49' => 'SC',
            '51' => 'RS', '53' => 'RS', '54' => 'RS', '55' => 'RS',
            '61' => 'DF',
            '62' => 'GO', '64' => 'GO',
            '63' => 'TO',
            '65' => 'MT', '66' => 'MT',
            '67' => 'MS',
            '68' => 'AC',
            '69' => 'RO',
            '71' => 'BA', '73' => 'BA', '74' => 'BA', '75' => 'BA', '77' => 'BA',
            '79' => 'SE',
            '81' => 'PE', '87' => 'PE',
            '82' => 'AL',
            '83' => 'PB',
            '84' => 'RN',
            '85' => 'CE', '88' => 'CE',
            '86' => 'PI', '89' => 'PI',
            '91' => 'PA', '93' => 'PA', '94' => 'PA',
            '96' => 'AP',
            '92' => 'AM', '97' => 'AM',
            '95' => 'RR',
            '98' => 'MA', '99' => 'MA',
        ];
        
        return $dddToState[$ddd] ?? null;
    }

    /**
     * Validar se DDD pertence ao estado
     */
    public static function validateDDD(string $ddd, string $uf): bool
    {
        $validDDDs = self::getDDDsByState($uf);
        return in_array($ddd, $validDDDs);
    }

    /**
     * Obter DDD principal (primeiro da lista) para um estado
     */
    public static function getMainDDD(string $uf): ?string
    {
        $ddds = self::getDDDsByState($uf);
        return $ddds[0] ?? null;
    }

    /**
     * Formatar DDDs para exibição
     */
    public static function formatDDDsForDisplay(string $uf): string
    {
        $ddds = self::getDDDsByState($uf);
        
        if (empty($ddds)) {
            return '';
        }
        
        if (count($ddds) === 1) {
            return $ddds[0];
        }
        
        return implode(', ', $ddds);
    }
}

