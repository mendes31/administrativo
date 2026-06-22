<?php

namespace App\adms\Controllers\trainings;

/**
 * Rota legada: sincronização manual foi descontinuada.
 * Redireciona para a visão da matriz com aviso.
 */
class UpdateTrainingMatrix
{
    public function index(): void
    {
        $_SESSION['success'] = 'A matriz de treinamentos é atualizada automaticamente a cada operação no módulo. Não é necessário sincronizar manualmente.';
        header('Location: ' . $_ENV['URL_ADM'] . 'training-matrix-manager');
        exit;
    }
}
