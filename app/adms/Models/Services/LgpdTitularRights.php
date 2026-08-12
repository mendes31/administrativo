<?php

declare(strict_types=1);

namespace App\adms\Models\Services;

/**
 * Direitos do titular (Art. 18 e Art. 20 da LGPD) usados no formulário público.
 */
final class LgpdTitularRights
{
    /**
     * @return array<string, array{artigo:string, titulo:string, ajuda:string}>
     */
    public static function catalog(): array
    {
        $empresa = LgpdPublicConfig::companyName();

        return [
            'confirmacao_tratamento' => [
                'artigo' => 'Art. 18, I',
                'titulo' => 'Confirmação da existência de tratamento',
                'ajuda' => "Permite saber se a {$empresa} realizou qualquer tipo de tratamento de seus dados pessoais, ainda que não se tenha certeza se você foi atendido ou se teve algum tipo de relação anterior.",
            ],
            'acesso' => [
                'artigo' => 'Art. 18, II',
                'titulo' => 'Acesso aos dados',
                'ajuda' => "Permite saber sobre o tratamento de dados pessoais realizado pela {$empresa}. Assinale apenas se você já foi atendido ou teve algum tipo de relação anterior.",
            ],
            'correcao' => [
                'artigo' => 'Art. 18, III',
                'titulo' => 'Correção de dados incompletos, inexatos ou desatualizados',
                'ajuda' => "Permite a retificação, em geral, dos dados pessoais do requerente que tenham sido tratados pela {$empresa}.",
            ],
            'anonimizacao' => [
                'artigo' => 'Art. 18, IV',
                'titulo' => 'Anonimização, bloqueio ou eliminação de dados desnecessários, excessivos ou tratados em desconformidade',
                'ajuda' => 'Permite a anonimização, bloqueio ou eliminação de dados desnecessários, excessivos ou tratados em desconformidade com a LGPD.',
            ],
            'portabilidade' => [
                'artigo' => 'Art. 18, V',
                'titulo' => 'Portabilidade dos dados a outro fornecedor de serviço ou produto',
                'ajuda' => 'Permite a obtenção de dados pessoais estruturados, de modo a permitir a sua transmissão a outro controlador.',
            ],
            'eliminacao_consentimento' => [
                'artigo' => 'Art. 18, VI',
                'titulo' => 'Eliminação dos dados pessoais tratados com o consentimento do titular',
                'ajuda' => 'Permite a eliminação dos dados pessoais tratados com o consentimento do titular, após a sua revogação.',
            ],
            'compartilhamento' => [
                'artigo' => 'Art. 18, VII',
                'titulo' => 'Informação das entidades públicas e privadas com as quais o controlador realizou uso compartilhado de dados',
                'ajuda' => "Permite a obtenção de informações acerca do compartilhamento de seus dados pessoais com terceiros pela {$empresa}.",
            ],
            'info_consentimento' => [
                'artigo' => 'Art. 18, VIII',
                'titulo' => 'Informação sobre a possibilidade de não fornecer consentimento e sobre as consequências da negativa',
                'ajuda' => 'Permite a obtenção de informações mais precisas acerca da possibilidade de não fornecer o consentimento e as respectivas consequências da negativa.',
            ],
            'revogacao' => [
                'artigo' => 'Art. 18, IX',
                'titulo' => 'Revogação do consentimento',
                'ajuda' => "Permite a revogação do consentimento dado em momento prévio à {$empresa} em relação ao tratamento de dados pessoais.",
            ],
            'revisao_automatizada' => [
                'artigo' => 'Art. 20',
                'titulo' => 'Revisão de decisões tomadas unicamente com base em tratamento automatizado',
                'ajuda' => 'Permite a contestação dos critérios utilizados para a tomada de decisões tomadas unicamente com base em tratamento automatizado de dados pessoais.',
            ],
        ];
    }

    public static function categorias(): array
    {
        return ['Cliente', 'Colaborador', 'Fornecedor', 'Cooperado', 'Outro'];
    }
}
