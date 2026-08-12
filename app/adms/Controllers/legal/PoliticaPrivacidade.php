<?php

namespace App\adms\Controllers\legal;

use App\adms\Controllers\lgpd\LgpdPublico;
use App\adms\Models\Services\SiteLegalTermService;

class PoliticaPrivacidade
{
    public function index(): void
    {
        $termo = (new SiteLegalTermService())->getPoliticaPrivacidade();
        $tituloBusca = trim((string) ($_ENV['LGPD_POLITICA_PRIVACIDADE_TITULO'] ?? 'Política de Privacidade')) ?: 'Política de Privacidade';
        $emptyHint = 'Cadastre um termo LGPD com tipo <strong>Site/Portal</strong> e título contendo '
            . htmlspecialchars($tituloBusca, ENT_QUOTES, 'UTF-8')
            . ' em LGPD → Termos LGPD.';

        LgpdPublico::renderTermoPublico(
            'Política de Privacidade',
            is_array($termo) ? $termo : null,
            $emptyHint
        );
    }
}
