<?php

namespace App\adms\Controllers\legal;

use App\adms\Controllers\lgpd\LgpdPublico;
use App\adms\Models\Services\SiteLegalTermService;

class TermosDeUso
{
    public function index(): void
    {
        $termo = (new SiteLegalTermService())->getTermosDeUso();
        $tituloBusca = trim((string) ($_ENV['LGPD_TERMO_USO_TITULO'] ?? 'Termos de Uso')) ?: 'Termos de Uso';
        $emptyHint = 'Cadastre um termo LGPD com tipo <strong>Site/Portal</strong> e título contendo '
            . htmlspecialchars($tituloBusca, ENT_QUOTES, 'UTF-8')
            . ' em LGPD → Termos LGPD.';

        LgpdPublico::renderTermoPublico(
            'Termos de Uso',
            is_array($termo) ? $termo : null,
            $emptyHint
        );
    }
}
