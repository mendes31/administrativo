<?php

namespace App\adms\Controllers\evaluations;

/**
 * Redirect de URL antiga para nova
 * Redireciona /minhas-avaliacoes para /my-evaluations
 * 
 * @package App\adms\Controllers\evaluations
 */
class MinhasAvaliacoes
{
    public function index(): void
    {
        header('Location: ' . $_ENV['URL_ADM'] . 'my-evaluations', true, 301);
        exit;
    }
}

