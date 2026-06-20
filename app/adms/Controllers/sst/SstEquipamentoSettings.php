<?php

declare(strict_types=1);

namespace App\adms\Controllers\sst;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\SstEquipamentoPeriodicidadeHelper;
use App\adms\Models\Repository\SstEquipamentoSettingsRepository;
use App\adms\Views\Services\LoadViewService;

class SstEquipamentoSettings
{
    private array $data = [];

    public function index(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->save();
            return;
        }

        $settings = (new SstEquipamentoSettingsRepository())->get();
        $this->data['settings'] = $settings;
        $this->data['periodicidades'] = SstEquipamentoPeriodicidadeHelper::options();
        $this->data['dias'] = SstEquipamentoPeriodicidadeHelper::dayOptions();
        $this->data['csrf_token'] = CSRFHelper::generateCSRFToken('sst_equipamento_settings');

        $pageElements = [
            'title_head' => 'Configurações de vistorias - Equipamentos SST',
            'menu' => 'sst-equipamento-settings',
            'buttonPermission' => ['SstEquipamentoSettings'],
        ];
        $this->data = array_merge($this->data, (new PageLayoutService())->configurePageElements($pageElements));
        (new LoadViewService('adms/Views/sst/equipamentos/settings', $this->data))->loadView();
    }

    private function save(): void
    {
        if (!CSRFHelper::validateCSRFToken('sst_equipamento_settings', $_POST['csrf_token'] ?? '')) {
            $_SESSION['msg'] = 'Token inválido.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-equipamento-settings');
            exit;
        }

        $diaPrevisto = SstEquipamentoPeriodicidadeHelper::clampDay((int) ($_POST['dia_previsto_padrao'] ?? 1));
        $periodicidade = (int) ($_POST['periodicidade_meses_padrao'] ?? 1);
        if (!SstEquipamentoPeriodicidadeHelper::isValid($periodicidade)) {
            $periodicidade = 1;
        }

        $ok = (new SstEquipamentoSettingsRepository())->save([
            'dia_geracao_vistorias' => $diaPrevisto,
            'dia_previsto_padrao' => $diaPrevisto,
            'periodicidade_meses_padrao' => $periodicidade,
            'gerar_vistoria_na_criacao' => !empty($_POST['gerar_vistoria_na_criacao']),
            'dias_tolerancia_vencimento' => max(0, (int) ($_POST['dias_tolerancia_vencimento'] ?? 0)),
        ]);

        $_SESSION['msg'] = $ok ? 'Configurações salvas.' : 'Erro ao salvar configurações.';
        $_SESSION['msg_type'] = $ok ? 'success' : 'danger';
        header('Location: ' . $_ENV['URL_ADM'] . 'sst-equipamento-settings');
        exit;
    }
}
