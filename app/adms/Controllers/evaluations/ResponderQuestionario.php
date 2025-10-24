<?php

namespace App\adms\Controllers\evaluations;

/**
 * Redirect de URL antiga para nova
 * Redireciona /responder-questionario/{id} para /answer-evaluation/{id}
 * 
 * @package App\adms\Controllers\evaluations
 */
class ResponderQuestionario
{
    public function index($id = null): void
    {
        if ($id) {
            header('Location: ' . $_ENV['URL_ADM'] . 'answer-evaluation/' . $id, true, 301);
        } else {
            header('Location: ' . $_ENV['URL_ADM'] . 'my-evaluations', true, 301);
        }
        exit;
    }
}

