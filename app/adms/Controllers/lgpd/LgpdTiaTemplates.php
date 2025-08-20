<?php

namespace App\adms\Controllers\lgpd;

class LgpdTiaTemplates
{
    public function index(): void
    {
        $controller = new LgpdTiaTemplate();
        $controller->index();
    }
}
