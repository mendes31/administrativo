<?php

declare(strict_types=1);

namespace App\adms\Controllers\portal;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\SstTreinamentoVinculosRepository;
use App\adms\Models\Services\SstPendenciasService;
use App\adms\Views\Services\LoadViewService;

/**
 * Portal do colaborador: treinamentos SST obrigatórios, status e certificados.
 */
class MySstTreinamentos
{
    private array $data = [];

    public function index(): void
    {
        $uid = (int) ($_SESSION['user_id'] ?? 0);
        if ($uid <= 0) {
            header('Location: ' . $_ENV['URL_ADM'] . 'login');
            exit;
        }

        $repo = new SstTreinamentoVinculosRepository();
        $vinculos = $repo->getByUserId($uid, 200);
        $matriz = (new SstPendenciasService())->getPendenciasTreinamentoPorUsuario($uid, true, true);
        $vinculosPorTreinamento = [];
        foreach ($vinculos as $vinculo) {
            $tid = (int) ($vinculo['adms_sst_treinamento_id'] ?? 0);
            if ($tid > 0) {
                $vinculosPorTreinamento[$tid] = $vinculo;
            }
        }

        $lista = [];
        $vistos = [];
        foreach ($matriz as $row) {
            $tid = (int) ($row['adms_sst_treinamento_id'] ?? 0);
            if ($tid <= 0) {
                continue;
            }
            $vistos[$tid] = true;
            $vinculo = $vinculosPorTreinamento[$tid] ?? [];
            $lista[] = [
                'id' => (int) ($vinculo['id'] ?? 0),
                'adms_sst_treinamento_id' => $tid,
                'treinamento_nome' => $row['treinamento_nome'] ?? $vinculo['treinamento_nome'] ?? '',
                'treinamento_codigo' => $row['treinamento_codigo'] ?? $vinculo['treinamento_codigo'] ?? null,
                'nr_referencia' => $row['nr_referencia'] ?? $vinculo['nr_referencia'] ?? null,
                'status' => $row['status'] ?? $vinculo['status'] ?? 'pendente',
                'data_agendada' => $vinculo['data_agendada'] ?? null,
                'data_realizacao' => $row['data_realizacao'] ?? $vinculo['data_realizacao'] ?? null,
                'data_validade' => $row['data_validade'] ?? $vinculo['data_validade'] ?? null,
                'certificado' => $vinculo['certificado'] ?? null,
                'motivo' => $row['motivo'] ?? null,
            ];
        }
        foreach ($vinculos as $vinculo) {
            $tid = (int) ($vinculo['adms_sst_treinamento_id'] ?? 0);
            if ($tid <= 0 || isset($vistos[$tid])) {
                continue;
            }
            $lista[] = $vinculo;
        }

        $this->data['vinculos'] = $lista;
        $this->data['pendentes'] = array_values(array_filter(
            $lista,
            static fn (array $v): bool => in_array($v['status'] ?? '', ['pendente', 'agendado', 'vencido', 'proximo_vencimento'], true)
        ));

        $pageElements = [
            'title_head' => 'Meus treinamentos SST',
            'menu' => 'my-sst-treinamentos',
            'buttonPermission' => ['MySstTreinamentos', 'ViewSstTreinamentoCertificadoPdf'],
        ];
        $this->data = array_merge($this->data, (new PageLayoutService())->configurePageElements($pageElements));
        (new LoadViewService('adms/Views/portal/my_sst_treinamentos', $this->data))->loadView();
    }
}
