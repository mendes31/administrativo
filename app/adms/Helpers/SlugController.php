<?php

namespace App\adms\Helpers;

/**
 * Converter a controller enviada na URL para o formato da classe.
 *
 * Esta classe é responsável por converter uma string de URL, que segue o formato de "slug", para o formato de
 * uma classe PHP. A conversão inclui transformar a string em uma representação de nome de classe, como converter
 * "sobre-empresa" para "SobreEmpresa".
 *
 * @package App\adms\Helpers
 * @author Rafael Mendes <raffaell_mendez@hotmail.com>
 */
class SlugController
{
    /**
     * Converter o slug de URL para o formato de nome de classe.
     *
     * Este método estático converte uma string que representa um slug de URL (por exemplo, "sobre-empresa") para
     * um nome de classe no formato PascalCase (por exemplo, "SobreEmpresa"). A conversão inclui transformar a string
     * em minúsculas, substituir traços por espaços, capitalizar a primeira letra de cada palavra e remover espaços
     * em branco.
     *
     * @param string $slugController Nome do slug da URL a ser convertido.
     * @return string Nome da classe correspondente ao slug da URL, no formato PascalCase.
     */
    public static function slugController(string $slugController) : string
    {
        // Converter para minusculo
        $slugController = strtolower($slugController);

        // Converter o traço para espaço em branço
        $slugController = str_replace("-"," ", $slugController);

        // COnverter a primeira letra de cada palavra para maiusculo
        $slugController = ucwords($slugController);

        // Retirar espaço em braco
        $slugController = str_replace(" ", "",  $slugController);

        // Retorna a controller convertida

        return  $slugController;
    }
}