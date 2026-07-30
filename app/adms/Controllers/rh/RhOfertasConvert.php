<?php

declare(strict_types=1);

namespace App\adms\Controllers\rh;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\DepartmentsRepository;
use App\adms\Models\Repository\PositionsRepository;
use App\adms\Models\Repository\RhConversoesAdmissaoRepository;
use App\adms\Models\Repository\RhOfertasRepository;
use App\adms\Models\Repository\UsersRepository;
use App\adms\Models\Services\RhConversaoAdmissaoService;
use App\adms\Models\Services\RhPermissionService;
use App\adms\Views\Services\LoadViewService;
use Exception;

final class RhOfertasConvert
{
    private array $data = [];

    public function index(int|string $id): void
    {
        $ofertaId = (int) $id;
        if ($ofertaId <= 0) {
            $_SESSION['msg'] = 'Oferta não encontrada.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . ($_ENV['URL_ADM'] ?? '') . 'rh-candidatos');
            exit;
        }

        if (strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) === 'POST') {
            $this->save($ofertaId);
            return;
        }

        $oferta = (new RhOfertasRepository())->getById($ofertaId);
        if ($oferta === null) {
            $_SESSION['msg'] = 'Oferta não encontrada.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . ($_ENV['URL_ADM'] ?? '') . 'rh-candidatos');
            exit;
        }

        if (!RhPermissionService::canManagePipelineByVagaId((int) $oferta['rh_vaga_id'])) {
            $_SESSION['msg'] = 'Sem permissão para converter esta oferta.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . ($_ENV['URL_ADM'] ?? '') . 'rh-ofertas-view/' . $ofertaId);
            exit;
        }

        $conversao = (new RhConversoesAdmissaoRepository())->getByOfertaId($ofertaId);
        if ($conversao !== null || !empty($oferta['rh_conversao_id'])) {
            $_SESSION['msg'] = 'Esta oferta já foi convertida.';
            $_SESSION['msg_type'] = 'info';
            header('Location: ' . ($_ENV['URL_ADM'] ?? '') . 'rh-ofertas-view/' . $ofertaId);
            exit;
        }

        if (($oferta['status'] ?? '') !== RhOfertasRepository::STATUS_ACEITA) {
            $_SESSION['msg'] = 'Somente ofertas aceitas podem ser convertidas.';
            $_SESSION['msg_type'] = 'warning';
            header('Location: ' . ($_ENV['URL_ADM'] ?? '') . 'rh-ofertas-view/' . $ofertaId);
            exit;
        }

        $docs = (new RhOfertasRepository())->listDocumentos($ofertaId);
        $pendencias = [];
        foreach ($docs as $doc) {
            if (!empty($doc['obrigatorio']) && ($doc['status'] ?? '') !== 'aprovado') {
                $pendencias[] = (string) ($doc['titulo'] ?? $doc['codigo'] ?? 'documento');
            }
        }

        $email = (string) ($oferta['candidato_email'] ?? '');
        $username = '';
        if ($email !== '' && str_contains($email, '@')) {
            $username = preg_replace('/[^a-zA-Z0-9._-]/', '', explode('@', $email)[0]) ?: '';
        }

        $this->data = [
            'title_head' => 'Converter oferta #' . $ofertaId,
            'menu' => 'rh-candidatos',
            'buttonPermission' => ['RhOfertasConvert', 'RhOfertasView', 'RhVagas'],
            'csrf_token' => CSRFHelper::generateCSRFToken('form_rh_oferta_convert'),
            'oferta' => $oferta,
            'documentos' => $docs,
            'pendencias_docs' => $pendencias,
            'listDepartments' => (new DepartmentsRepository())->getAllDepartmentsSelect(),
            'listPositions' => (new PositionsRepository())->getAllPositionsSelect(),
            'listSupervisors' => (new UsersRepository())->getAllUsersSelect(),
            'form' => [
                'modo' => RhConversoesAdmissaoRepository::MODO_CRIAR,
                'name' => (string) ($oferta['candidato_nome'] ?? ''),
                'email' => $email,
                'username' => $username,
                'user_department_id' => '',
                'user_position_id' => '',
                'immediate_supervisor_id' => '',
                'data_admissao' => date('Y-m-d'),
                'matricula' => '',
                'empresa_contratante' => '',
                'adms_user_id' => '',
                'observacoes' => '',
                'enviar_boas_vindas_email' => '0',
            ],
            'convert_flash' => null,
        ];

        if (!empty($_SESSION['rh_oferta_convert_form']) && is_array($_SESSION['rh_oferta_convert_form'])) {
            $this->data['form'] = array_merge(
                $this->data['form'],
                $_SESSION['rh_oferta_convert_form']
            );
            unset($_SESSION['rh_oferta_convert_form']);
        }
        if (!empty($_SESSION['rh_convert_flash']) && is_array($_SESSION['rh_convert_flash'])) {
            $this->data['convert_flash'] = $_SESSION['rh_convert_flash'];
            unset($_SESSION['rh_convert_flash'], $_SESSION['msg'], $_SESSION['msg_type']);
        }

        $pageLayout = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayout->configurePageElements($this->data));
        (new LoadViewService('adms/Views/rh/ofertas/convert', $this->data))->loadView();
    }

    private function save(int $ofertaId): void
    {
        if (!CSRFHelper::validateCSRFToken('form_rh_oferta_convert', (string) ($_POST['csrf_token'] ?? ''))) {
            $_SESSION['msg'] = 'Token inválido.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . ($_ENV['URL_ADM'] ?? '') . 'rh-ofertas-convert/' . $ofertaId);
            exit;
        }

        $postedForm = is_array($_POST['form'] ?? null) ? $_POST['form'] : [];

        try {
            $result = (new RhConversaoAdmissaoService())->converter(
                $ofertaId,
                $postedForm,
                (int) ($_SESSION['user_id'] ?? 0)
            );
            $_SESSION['msg'] = 'Conversão concluída. Usuário #' . $result['adms_user_id'] . ' vinculado.';
            $_SESSION['msg_type'] = 'success';
            header('Location: ' . ($_ENV['URL_ADM'] ?? '') . 'rh-ofertas-view/' . $ofertaId);
            exit;
        } catch (Exception $e) {
            $message = $e->getMessage();
            $_SESSION['rh_oferta_convert_form'] = $postedForm;
            $_SESSION['msg'] = $message;
            $_SESSION['msg_type'] = 'danger';

            if (preg_match('/Já existe usuário com este e-mail \(ID (\d+)\)/u', $message, $m) === 1) {
                $existingId = (int) $m[1];
                $_SESSION['rh_oferta_convert_form']['modo'] = RhConversoesAdmissaoRepository::MODO_VINCULAR;
                $_SESSION['rh_oferta_convert_form']['adms_user_id'] = (string) $existingId;
                $_SESSION['rh_convert_flash'] = [
                    'code' => 'email_exists',
                    'user_id' => $existingId,
                    'title' => 'Este e-mail já possui conta no sistema',
                    'message' => 'Encontramos o usuário #' . $existingId
                        . ' com o mesmo e-mail. Selecionei o modo Vincular e preenchi o ID — confirme se é a conta correta e converta.',
                ];
                unset($_SESSION['msg'], $_SESSION['msg_type']);
            } else {
                $_SESSION['rh_convert_flash'] = [
                    'code' => 'error',
                    'title' => 'Não foi possível converter',
                    'message' => $message,
                ];
                unset($_SESSION['msg'], $_SESSION['msg_type']);
            }

            header('Location: ' . ($_ENV['URL_ADM'] ?? '') . 'rh-ofertas-convert/' . $ofertaId);
            exit;
        }
    }
}
