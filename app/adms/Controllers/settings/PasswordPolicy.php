<?php

namespace App\adms\Controllers\settings;

use App\adms\Views\Services\LoadViewService;
use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\AdmsPasswordPolicyRepository;

class PasswordPolicy
{
    public function index(): void
    {
        $repo = new AdmsPasswordPolicyRepository();
        $form = $repo->getPolicy();
        // Normalizar o valor de bloqueio_temporario para 'Sim' ou 'Não'
        if ($form) {
            if (isset($form->bloqueio_temporario)) {
                $form->bloqueio_temporario = ($form->bloqueio_temporario === 'Sim' || $form->bloqueio_temporario === 1 || $form->bloqueio_temporario === true) ? 'Sim' : 'Não';
            } else {
                $form->bloqueio_temporario = 'Não';
            }
        }
        $data = [
            'title_head' => 'Administração de Senhas',
            'menu' => 'password-policy',
            'buttonPermission' => ['PasswordPolicy'],
            'form' => $form,
        ];
        $pageLayout = new PageLayoutService();
        $data = array_merge($data, $pageLayout->configurePageElements($data));
        $loadView = new LoadViewService('adms/Views/settings/passwordPolicy', $data);
        $loadView->loadView();
    }
} 