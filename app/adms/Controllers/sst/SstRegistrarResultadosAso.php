<?php

declare(strict_types=1);

namespace App\adms\Controllers\sst;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\SstAsoStatusHelper;
use App\adms\Helpers\SstExameResultadoHelper;
use App\adms\Models\Repository\SstAsoExamesRepository;
use App\adms\Models\Repository\SstAsosRepository;
use App\adms\Models\Repository\SstExamesRepository;
use App\adms\Models\Repository\SstMedicosRepository;
use App\adms\Models\Services\SstAnexosUploadService;
use App\adms\Models\Services\SstPendenciasService;
use App\adms\Views\Services\LoadViewService;

/**
 * Registra resultados de ASO aberto em status "Aguardando exames".
 */
class SstRegistrarResultadosAso
{
    private array $data = [];

    public function index(string|int|null $id = null): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->salvar((int) $id);
            return;
        }

        if (!$id) {
            $_SESSION['msg'] = 'ID não informado.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-list-asos');
            exit;
        }

        $repo = new SstAsosRepository();
        $item = $repo->getById((int) $id);
        if (!$item) {
            $_SESSION['msg'] = 'Registro não encontrado.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-list-asos');
            exit;
        }

        if (!SstAsoStatusHelper::isAguardando($item)) {
            $_SESSION['msg'] = 'Este ASO já foi concluído. Use a edição normal.';
            $_SESSION['msg_type'] = 'info';
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-view-aso/' . (int) $id);
            exit;
        }

        $this->data['item'] = $item;
        $this->data['complementares'] = (new SstAsoExamesRepository())->getByAsoId((int) $id);
        $this->data['medicos'] = (new SstMedicosRepository())->getAll(1, 500);
        $exames = (new SstExamesRepository())->getAll(1, 500);
        $this->data['examesMeta'] = [];
        foreach ($exames as $ex) {
            $eid = (int) ($ex['id'] ?? 0);
            if ($eid <= 0) {
                continue;
            }
            $exige = !isset($ex['exige_resultado']) || !empty($ex['exige_resultado']);
            $this->data['examesMeta'][$eid] = [
                'exige_resultado' => $exige,
                'tipo' => $ex['tipo'] ?? null,
                'resultados' => SstExameResultadoHelper::optionsForComplementaryLaunch($exige, $ex['tipo'] ?? null),
            ];
        }

        $pageElements = [
            'title_head' => 'Registrar resultados ASO - SST',
            'menu' => 'sst-list-asos',
            'buttonPermission' => ['SstRegistrarResultadosAso', 'SstUpdateAso'],
        ];
        $this->data = array_merge($this->data, (new PageLayoutService())->configurePageElements($pageElements));
        (new LoadViewService('adms/Views/sst/asos/registrar_resultados', $this->data))->loadView();
    }

    private function salvar(int $id): void
    {
        if (!CSRFHelper::validateCSRFToken('sst_registrar_resultados_aso', $_POST['csrf_token'] ?? '')) {
            $_SESSION['msg'] = 'Token CSRF inválido.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-list-asos');
            exit;
        }

        $repo = new SstAsosRepository();
        $item = $repo->getById($id);
        if (!$item || !SstAsoStatusHelper::isAguardando($item)) {
            $_SESSION['msg'] = 'ASO não encontrado ou já concluído.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-list-asos');
            exit;
        }

        $dataRealizacao = trim((string) ($_POST['data_realizacao'] ?? ''));
        $resultado = trim((string) ($_POST['resultado'] ?? ''));
        if ($dataRealizacao === '' || $resultado === '') {
            $_SESSION['msg'] = 'Informe data de realização e resultado do ASO.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-registrar-resultados-aso/' . $id);
            exit;
        }

        $complementares = $this->parseComplementaresFromPost();
        $existentes = (new SstAsoExamesRepository())->getByAsoId($id);
        $exigencias = [];
        foreach ($existentes as $row) {
            $exigencias[(int) ($row['adms_sst_exame_id'] ?? 0)] = $row['exigencia'] ?? null;
        }
        foreach ($complementares as &$comp) {
            $eid = (int) ($comp['adms_sst_exame_id'] ?? 0);
            $comp['exigencia'] = $exigencias[$eid] ?? $comp['exigencia'] ?? null;
            if (($comp['exigencia'] ?? '') === 'obrigatorio' && empty($comp['resultado'])) {
                $_SESSION['msg'] = 'Informe o resultado de todos os exames obrigatórios.';
                $_SESSION['msg_type'] = 'danger';
                header('Location: ' . $_ENV['URL_ADM'] . 'sst-registrar-resultados-aso/' . $id);
                exit;
            }
        }
        unset($comp);

        $data = [
            'adms_user_id' => $item['adms_user_id'],
            'adms_sst_exame_id' => $_POST['adms_sst_exame_id'] ?? $item['adms_sst_exame_id'] ?? null,
            'adms_sst_medico_id' => $_POST['adms_sst_medico_id'] ?? null,
            'tipo' => $item['tipo'],
            'status' => SstAsoStatusHelper::CONCLUIDO,
            'data_realizacao' => $dataRealizacao,
            'data_validade' => $_POST['data_validade'] ?? null,
            'resultado' => $resultado,
            'restricoes' => $_POST['restricoes'] ?? null,
            'clinica' => $_POST['clinica'] ?? null,
            'observacoes' => $_POST['observacoes'] ?? null,
        ];

        if ($repo->update($id, $data)) {
            (new SstAsoExamesRepository())->syncForAso($id, $complementares);
            (new SstAnexosUploadService())->processUploads('asos', $id);
            SstPendenciasService::invalidateDashboardCache();
            $_SESSION['msg'] = 'Resultados do ASO registrados com sucesso.';
            $_SESSION['msg_type'] = 'success';
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-view-aso/' . $id);
        } else {
            $_SESSION['msg'] = 'Erro ao salvar resultados.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-registrar-resultados-aso/' . $id);
        }
        exit;
    }

    /** @return list<array<string, mixed>> */
    private function parseComplementaresFromPost(): array
    {
        $rows = $_POST['complementares'] ?? [];
        if (!is_array($rows)) {
            return [];
        }
        $out = [];
        foreach ($rows as $row) {
            if (!is_array($row) || empty($row['adms_sst_exame_id'])) {
                continue;
            }
            $out[] = [
                'adms_sst_exame_id' => (int) $row['adms_sst_exame_id'],
                'data_realizacao' => $row['data_realizacao'] ?? null,
                'resultado' => $row['resultado'] ?? null,
                'exigencia' => $row['exigencia'] ?? null,
            ];
        }

        return $out;
    }
}
