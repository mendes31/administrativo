<?php

namespace App\adms\Controllers\evaluations;

/**
 * Redirect de URL antiga para nova
 * Redireciona /resultado-avaliacao/{id} para /view-evaluation-result/{id}
 * 
 * @package App\adms\Controllers\evaluations
 */
class ResultadoAvaliacao
{
    public function index($id = null): void
    {
        if ($id) {
            header('Location: ' . $_ENV['URL_ADM'] . 'view-evaluation-result/' . $id, true, 301);
        } else {
            header('Location: ' . $_ENV['URL_ADM'] . 'my-evaluations', true, 301);
        }
        exit;
    }
}

