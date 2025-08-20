<?php

namespace App\adms\Helpers;

/**
 * Converter a o nome da imagem.
 *
 * Esta classe é responsável por converter um nome de imagem, que segue o formato de "slug".
 *
 * @package App\adms\Helpers
 * @author Rafael Mendes <raffaell_mendez@hotmail.com>
 */
class SlugImg
{
    /** @var string $text Recebe o texto que dever converter para o SLUG */
    private string $text;

    /** @var array $format Recebe o array de caracteres especiais que devem ser substituido */
    private array $format;

    /**
     * Metodo faz a conversão da informação em SLUG
     *
     * @param string $text
     * @return string|null
     */
    public function slug(string $text): string|null
    {
        $this->text = $text;

        $this->format['a'] = 'ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜüÝÞßàáâãäåæçèéêëìíîïðñòóôõöøùúûýýþÿRr"!@#$%&*()_-+={[}]?;:,\\\'<>°ºª';
        $this->format['b'] = 'aaaaaaaceeeeiiiidnoooooouuuuuybsaaaaaaaceeeeiiiidnoooooouuuyybyRr-----------------------------------------------------------------------------------------------';
        $this->text = strtr(mb_convert_encoding($this->text, 'ISO-8859-1', 'UTF-8'), mb_convert_encoding($this->format['a'], 'ISO-8859-1', 'UTF-8'), mb_convert_encoding($this->format['b'], 'ISO-8859-1', 'UTF-8'));
        $this->text = str_replace(" ", "-", $this->text);
        $this->text = str_replace(array('-----', '----', '---', '--'), '-', $this->text);
        $this->text = strtolower($this->text);

        return $this->text;
    }
}
