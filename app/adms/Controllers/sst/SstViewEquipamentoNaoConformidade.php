<?php

declare(strict_types=1);

namespace App\adms\Controllers\sst;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\SstAnexosRepository;
use App\adms\Models\Repository\SstEquipamentoAcoesCorretivasRepository;
use App\adms\Models\Repository\SstEquipamentoNaoConformidadesRepository;
use App\adms\Views\Services\LoadViewService;

class SstViewEquipamentoNaoConformidade
{
    private const ENTITY_AC = 'equipamento_acoes_corretivas';

    private array $data = [];

    public function index(string|int $id = 0): void
    {
        $id = (int) $id;
        $nc = (new SstEquipamentoNaoConformidadesRepository())->getById($id);
        if (!$nc) {
            $_SESSION['msg'] = 'Não conformidade não encontrada.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-list-equipamento-nao-conformidades');
            exit;
        }

        $acoes = (new SstEquipamentoAcoesCorretivasRepository())->getByNaoConformidadeId($id);
        $anexosPorAcao = [];
        $anexoRepo = new SstAnexosRepository();
        foreach ($acoes as $a) {
            $aid = (int) ($a['id'] ?? 0);
            if ($aid > 0) {
                $anexosPorAcao[$aid] = $anexoRepo->getByEntity(self::ENTITY_AC, $aid);
            }
        }

        $this->data['nc'] = $nc;
        $this->data['acoes'] = $acoes;
        $this->data['anexos_por_acao'] = $anexosPorAcao;
        $pageElements = [
            'title_head' => 'NC ' . ($nc['codigo'] ?? '') . ' - SST',
            'menu' => 'sst-list-equipamento-nao-conformidades',
            'buttonPermission' => [
                'SstViewEquipamentoNaoConformidade',
                'SstCreateEquipamentoAcaoCorretiva',
                'SstUpdateEquipamentoAcaoCorretiva',
                'SstEncerrarEquipamentoNaoConformidade',
                'SstExecuteEquipamentoVistoria',
                'SstViewAnexo',
            ],
        ];
        $this->data = array_merge($this->data, (new PageLayoutService())->configurePageElements($pageElements));
        (new LoadViewService('adms/Views/sst/equipamentos/nao_conformidade_view', $this->data))->loadView();
    }
}
