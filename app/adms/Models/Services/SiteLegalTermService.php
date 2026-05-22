<?php

namespace App\adms\Models\Services;

use App\adms\Models\Repository\LgpdTermosRepository;

/**
 * Resolve termos LGPD tipo "site" para exibição informativa no rodapé do portal.
 */
class SiteLegalTermService
{
    private LgpdTermosRepository $termosRepo;

    public function __construct(?LgpdTermosRepository $termosRepo = null)
    {
        $this->termosRepo = $termosRepo ?? new LgpdTermosRepository();
    }

    public function getTermosDeUso(): ?array
    {
        return $this->resolveByEnvKey('LGPD_TERMO_USO_TITULO', 'Termos de Uso');
    }

    public function getPoliticaPrivacidade(): ?array
    {
        return $this->resolveByEnvKey('LGPD_POLITICA_PRIVACIDADE_TITULO', 'Política de Privacidade');
    }

    private function resolveByEnvKey(string $envKey, string $defaultTitulo): ?array
    {
        $tituloBusca = trim((string) ($_ENV[$envKey] ?? $defaultTitulo));
        if ($tituloBusca === '') {
            $tituloBusca = $defaultTitulo;
        }

        return $this->termosRepo->getTermoAtivoSitePorTituloContem($tituloBusca);
    }
}
