<?php

declare(strict_types=1);

namespace App\adms\Controllers\sst;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Controllers\Services\PaginationService;
use App\adms\Helpers\SstAsoPrevisaoHelper;
use App\adms\Models\Repository\DepartmentsRepository;
use App\adms\Models\Repository\SstAsosRepository;
use App\adms\Models\Repository\UsersRepository;
use App\adms\Models\Services\SstAsoPrevisaoService;
use App\adms\Views\Services\LoadViewService;

class SstListAsos
{
    private array|string|null $data = null;
    private int $limitResult = 10;

    public function index(string|int $page = 1): void
    {
        $onlyUserId = !empty($_GET['adms_user_id']) ? (int) $_GET['adms_user_id'] : null;
        $sync = (new \App\adms\Models\Services\SstAsoSolicitacaoService())->sincronizarSolicitacoesPendentes($onlyUserId);
        if ($sync['criados'] > 0 && empty($_SESSION['msg'])) {
            $_SESSION['msg'] = $sync['criados'] . ' solicitação(ões) de ASO gerada(s) automaticamente a partir dos vínculos.';
            $_SESSION['msg_type'] = 'info';
        }

        $visao = ($_GET['visao'] ?? '') === 'previsao' ? 'previsao' : 'fila';
        $this->data['visao'] = $visao;
        $repo = new SstAsosRepository();
        $this->data['aguardando_count'] = $repo->countAguardando();

        if ($visao === 'previsao') {
            if (isset($_GET['page']) && is_numeric($_GET['page'])) {
                $page = (int) $_GET['page'];
            }
            if (isset($_GET['per_page']) && in_array((int) $_GET['per_page'], [10, 20, 50, 100], true)) {
                $this->limitResult = (int) $_GET['per_page'];
            }
            $mes = SstAsoPrevisaoHelper::mesFiltro((string) ($_GET['mes'] ?? ''));
            $filters = [
                'search' => $_GET['search'] ?? '',
                'adms_user_id' => $_GET['adms_user_id'] ?? '',
                'adms_department_id' => $_GET['adms_department_id'] ?? '',
                'mes' => $mes,
                'visao' => 'previsao',
            ];
            $previsao = (new SstAsoPrevisaoService())->listarPorMes($mes, $filters);
            if (($_GET['export'] ?? '') === 'csv') {
                $this->exportPrevisaoCsv($previsao);
                return;
            }
            $total = count($previsao['itens']);
            $paginationFilters = array_merge(['per_page' => $this->limitResult], $filters);
            $pagination = PaginationService::generatePagination(
                $total,
                $this->limitResult,
                (int) $page,
                'sst-list-asos',
                $paginationFilters
            );
            $pageAtual = (int) ($pagination['current_page'] ?? 1);
            $offset = max(0, ($pageAtual - 1) * $this->limitResult);
            $previsao['itens'] = array_slice($previsao['itens'], $offset, $this->limitResult);
            $this->data['previsao'] = $previsao;
            $this->data['previsao_total'] = $total;
            $this->data['filters'] = $filters;
            $this->data['meses_opcoes'] = array_merge(
                [['value' => '', 'label' => 'Todos']],
                SstAsoPrevisaoHelper::mesesOpcoes()
            );
            $this->data['departments'] = (new DepartmentsRepository())->getAllDepartmentsSelect();
            $this->data['items'] = [];
            $this->data['pagination'] = $pagination;
            $this->data['per_page'] = $this->limitResult;
            $this->data['paginationSettings'] = ['per_page' => $this->limitResult, 'options' => [10, 20, 50, 100]];
        } else {
        $semFiltrosNaUrl = !array_key_exists('search', $_GET)
            && !array_key_exists('adms_user_id', $_GET)
            && !array_key_exists('status', $_GET)
            && !array_key_exists('page', $_GET)
            && !array_key_exists('per_page', $_GET)
            && !array_key_exists('visao', $_GET);

        $filters = [
            'search' => $_GET['search'] ?? '',
            'adms_user_id' => $_GET['adms_user_id'] ?? '',
            'status' => $_GET['status'] ?? ($semFiltrosNaUrl ? 'Aguardando exames' : ''),
        ];
        if (isset($_GET['page']) && is_numeric($_GET['page'])) {
            $page = (int) $_GET['page'];
        }
        if (isset($_GET['per_page']) && in_array((int) $_GET['per_page'], [10, 20, 50, 100], true)) {
            $this->limitResult = (int) $_GET['per_page'];
        }
        $total = $repo->getTotal($filters);
        $this->data['items'] = $repo->getAll((int) $page, $this->limitResult, $filters);
        $this->data['pagination'] = PaginationService::generatePagination(
            $total,
            $this->limitResult,
            (int) $page,
            'sst-list-asos',
            array_merge(['per_page' => $this->limitResult], $filters)
        );
        $this->data['per_page'] = $this->limitResult;
        $this->data['filters'] = $filters;
        }
        $this->data['entity'] = array (
  'table' => 'adms_sst_asos',
  'singular' => 'ASO',
  'plural' => 'ASOs',
  'prefix' => 'Aso',
  'url' => 'aso',
  'menu' => 'sst-list-asos',
  'icon' => 'fa-file-medical',
  'type' => 'employee',
  'has_anexos' => true,
  'fields' => 
  array (
    'adms_user_id' => 
    array (
      'label' => 'Colaborador',
      'type' => 'user',
      'required' => true,
    ),
    'adms_sst_exame_id' => 
    array (
      'label' => 'Exame',
      'type' => 'fk_exame',
    ),
    'adms_sst_medico_id' => 
    array (
      'label' => 'Médico',
      'type' => 'fk_medico',
    ),
    'tipo' => 
    array (
      'label' => 'Tipo',
      'type' => 'select',
      'options' => 
      array (
        0 => 'Admissional',
        1 => 'Periódico',
        2 => 'Mudança de função',
        3 => 'Retorno ao trabalho',
        4 => 'Demissional',
      ),
      'required' => true,
    ),
    'data_realizacao' => 
    array (
      'label' => 'Data realização',
      'type' => 'date',
      'required' => true,
    ),
    'data_validade' => 
    array (
      'label' => 'Validade',
      'type' => 'date',
    ),
    'resultado' => 
    array (
      'label' => 'Resultado',
      'type' => 'select',
      'options' => 
      array (
        0 => 'Apto',
        1 => 'Inapto',
        2 => 'Apto com restrição',
      ),
    ),
    'restricoes' => 
    array (
      'label' => 'Restrições',
      'type' => 'textarea',
    ),
    'clinica' => 
    array (
      'label' => 'Clínica',
      'type' => 'text',
    ),
    'observacoes' => 
    array (
      'label' => 'Observações',
      'type' => 'textarea',
    ),
  ),
  'list_cols' => 
  array (
    0 => 'id',
    1 => 'colaborador_nome',
    2 => 'tipo',
    3 => 'data_realizacao',
    4 => 'data_validade',
    5 => 'resultado',
  ),
);
        if ('employee' === 'employee') {
            $this->data['users'] = (new UsersRepository())->getAllUsersForSelect();
        }
        $pageElements = [
            'title_head' => ($this->data['visao'] ?? '') === 'previsao' ? 'ASOs previstos - SST' : 'ASOs - SST',
            'menu' => 'sst-list-asos',
            'buttonPermission' => [
                'SstViewAso', 'SstCreateAso', 'SstUpdateAso', 'SstDeleteAso', 'SstRegistrarResultadosAso',
                'SstEncaminhamentoAso', 'SstExportEncaminhamentoAsoPdf', 'SstAbrirAsoPendencia',
            ],
        ];
        $this->data = array_merge($this->data ?? [], (new PageLayoutService())->configurePageElements($pageElements));
        (new LoadViewService('adms/Views/sst/asos/list', $this->data))->loadView();
    }

    /**
     * @param array<string, mixed> $previsao
     */
    private function exportPrevisaoCsv(array $previsao): void
    {
        $mes = (string) ($previsao['mes'] ?? '');
        $filename = 'asos-previstos-' . ($mes !== '' ? $mes : 'completo') . '.csv';
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        $out = fopen('php://output', 'w');
        if ($out === false) {
            return;
        }
        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, ['Departamento', 'Colaborador', 'Cargo', 'Última realização', 'Validade / previsto', 'Situação'], ';');
        foreach ($previsao['itens'] ?? [] as $row) {
            if (!is_array($row)) {
                continue;
            }
            fputcsv($out, [
                (string) ($row['departamento_nome'] ?: 'Sem departamento'),
                (string) ($row['colaborador_nome'] ?? ''),
                (string) ($row['cargo_nome'] ?? ''),
                $this->csvDate($row['data_realizacao'] ?? null),
                $this->csvDate($row['previsto_em'] ?? $row['data_validade'] ?? null),
                (string) ($row['situacao_label'] ?? ''),
            ], ';');
        }
        fclose($out);
    }

    private function csvDate(mixed $value): string
    {
        $raw = substr(trim((string) $value), 0, 10);
        if ($raw === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $raw)) {
            return '';
        }
        $dt = \DateTimeImmutable::createFromFormat('Y-m-d', $raw);

        return $dt ? $dt->format('d/m/Y') : '';
    }
}