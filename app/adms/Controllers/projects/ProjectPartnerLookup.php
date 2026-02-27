<?php

namespace App\adms\Controllers\projects;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Controllers\Services\PaginationService;
use App\adms\Models\Repository\CustomerRepository;
use App\adms\Models\Repository\SupplierRepository;
use App\adms\Views\Services\LoadViewService;

class ProjectPartnerLookup
{
    private array|string|null $data = null;
    private int $limitResult = 10;

    public function index(string|int $page = 1): void
    {
        if (isset($_GET['page']) && is_numeric($_GET['page'])) {
            $page = (int)$_GET['page'];
        }
        if (isset($_GET['per_page']) && in_array((int)$_GET['per_page'], [10, 20, 50, 100], true)) {
            $this->limitResult = (int)$_GET['per_page'];
        }

        $rawSearch = trim($_GET['q'] ?? '');
        // Comportamento tipo ERP:
        // - "*" sozinho => lista todos (sem filtro)
        // - "texto*" ou "*texto" => ignora o "*" e usa "texto" como contains
        $normalized = $rawSearch;
        if ($rawSearch === '*') {
            $normalized = '';
        } elseif (str_contains($rawSearch, '*')) {
            $normalized = str_replace('*', '', $rawSearch);
        }

        // Vamos buscar em Clientes e Fornecedores (módulo Parceiros de Negócio)
        $customersRepo = new CustomerRepository();
        $suppliersRepo = new SupplierRepository();

        $criteria = [
            'search' => $normalized,
            'card_code' => $normalized,
            'card_name' => $normalized,
        ];

        // Clientes
        $customers = $customersRepo->getAllCustomers(1, 1000); // sem paginação detalhada nesta V1
        if ($normalized !== '') {
            $customers = array_filter($customers, function ($c) use ($normalized) {
                $needle = mb_strtolower($normalized);
                return str_contains(mb_strtolower($c['card_code'] ?? ''), $needle)
                    || str_contains(mb_strtolower($c['card_name'] ?? ''), $needle)
                    || str_contains(mb_strtolower($c['doc'] ?? ''), $needle);
            });
        }

        // Fornecedores
        $suppliers = $suppliersRepo->getAllSuppliers($criteria, 1, 1000);

        // Unificar em uma lista de "Parceiros de Negócio"
        $partners = [];
        foreach ($customers as $c) {
            $partners[] = [
                'origin' => 'Cliente',
                'code' => $c['card_code'] ?? '',
                'name' => $c['card_name'] ?? '',
                'document' => $c['doc'] ?? '',
                'person_type' => $c['type_person'] ?? '',
                'phone' => $c['phone'] ?? '',
                'email' => $c['email'] ?? '',
                'address' => $c['address'] ?? '',
                'description' => $c['description'] ?? '',
                'date_birth' => $c['date_birth'] ?? null,
                'active' => ($c['active'] ?? 1) ? 1 : 0,
            ];
        }
        foreach ($suppliers as $s) {
            $partners[] = [
                'origin' => 'Fornecedor',
                'code' => $s['card_code'] ?? '',
                'name' => $s['card_name'] ?? '',
                'document' => $s['doc'] ?? '',
                'person_type' => $s['type_person'] ?? '',
                'phone' => $s['phone'] ?? '',
                'email' => $s['email'] ?? '',
                'address' => $s['address'] ?? '',
                'description' => $s['description'] ?? '',
                'date_birth' => $s['date_birth'] ?? null,
                'active' => ($s['active'] ?? 1) ? 1 : 0,
            ];
        }

        // Paginação simples em memória
        $total = count($partners);
        $partners = array_slice($partners, max(0, ((int)$page - 1) * $this->limitResult), $this->limitResult);
        $this->data['partners'] = $partners;

        // Filtros usados apenas para manter parâmetros na paginação
        $filters = [
            'q' => $rawSearch,
        ];

        $pagination = PaginationService::generatePagination(
            (int)$total,
            $this->limitResult,
            (int)$page,
            'project-partner-lookup',
            array_merge($filters, ['per_page' => $this->limitResult])
        );
        $this->data['pagination'] = $pagination;
        $this->data['per_page'] = $this->limitResult;
        $this->data['search'] = $rawSearch;

        // Layout simples, sem marcar menu lateral (é usado como popup)
        $pageElements = [
            'title_head' => 'Selecionar Parceiro de Negócio',
            'menu' => '', // não destaca nenhum menu
            'buttonPermission' => ['ListProjects'],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data ?? [], $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService('adms/Views/projects/partner_lookup', $this->data);
        $loadView->loadView();
    }
}

