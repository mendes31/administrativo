<?php

declare(strict_types=1);

namespace App\adms\Controllers\portaria;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\DepartmentsRepository;
use App\adms\Models\Repository\PortariaAutorizacoesRepository;
use App\adms\Models\Repository\PortariaVisitantesRepository;
use App\adms\Models\Repository\UsersRepository;
use App\adms\Models\Services\PortariaNotificacaoAnfitriaoService;
use App\adms\Views\Services\LoadViewService;

final class PortariaAutorizacoesCreate
{
    private array $data = [];

    public function index(): void
    {
        $modoRapido = (string) ($_GET['modo'] ?? '') === 'rapido'
            || (string) ($_POST['origem'] ?? '') === 'nao_programada';

        $defaults = [
            'visitante_modo' => 'novo',
            'visitante_id' => (int) ($_GET['visitante_id'] ?? 0),
            'data_inicio' => date('Y-m-d'),
            'data_fim' => date('Y-m-d'),
            'hora_inicio' => date('H:i'),
            'origem' => $modoRapido || (string) ($_GET['origem'] ?? '') === 'nao_programada'
                ? 'nao_programada'
                : 'agendada',
            'tipo' => 'periodo',
            'status' => 'aguardando',
            'novo_nome' => '',
            'novo_documento' => '',
            'novo_telefone' => '',
            'novo_empresa' => '',
        ];
        if ((int) $defaults['visitante_id'] > 0) {
            $defaults['visitante_modo'] = 'existente';
        }

        $this->data['form'] = filter_input_array(INPUT_POST, FILTER_DEFAULT) ?: $defaults;
        $this->data['modo_rapido'] = (($this->data['form']['origem'] ?? '') === 'nao_programada');

        if (isset($this->data['form']['csrf_token'])
            && CSRFHelper::validateCSRFToken('form_portaria_autorizacao', (string) $this->data['form']['csrf_token'])) {
            $errors = [];
            $visitantesRepo = new PortariaVisitantesRepository();
            $visitanteId = $this->resolveVisitanteId($visitantesRepo, $errors);

            if ((int) ($this->data['form']['anfitriao_user_id'] ?? 0) <= 0) {
                $errors[] = 'Selecione o anfitrião.';
            }
            if (empty($this->data['form']['data_inicio']) || empty($this->data['form']['data_fim'])) {
                $errors[] = 'Informe o período da autorização.';
            }
            if ($errors === [] && $visitanteId > 0) {
                $payload = $this->data['form'];
                $payload['visitante_id'] = $visitanteId;
                $payload['criado_por_user_id'] = (int) ($_SESSION['user_id'] ?? 0);
                $payload['status'] = ($payload['origem'] ?? '') === 'nao_programada' ? 'aguardando' : ($payload['status'] ?? 'aguardando');
                if (($payload['origem'] ?? '') === 'nao_programada' && empty($payload['data_fim'])) {
                    $payload['data_fim'] = $payload['data_inicio'];
                }
                $id = (new PortariaAutorizacoesRepository())->create($payload);
                if ($id) {
                    $notificar = ($payload['origem'] ?? '') === 'nao_programada'
                        || (int) ($payload['anfitriao_user_id'] ?? 0) !== (int) ($payload['criado_por_user_id'] ?? 0);
                    $extra = '';
                    if ($notificar && ($payload['status'] ?? '') === 'aguardando') {
                        $notif = (new PortariaNotificacaoAnfitriaoService())
                            ->notificarSolicitacao((int) $id, (int) ($_SESSION['user_id'] ?? 0));
                        $extra = ' ' . implode(' ', $notif['mensagens']);
                    }
                    $_SESSION['msg'] = (($payload['origem'] ?? '') === 'nao_programada')
                        ? 'Liberação registrada e anfitrião notificado.' . $extra
                        : 'Autorização criada com sucesso.' . $extra;
                    $_SESSION['msg_type'] = 'success';
                    header('Location: ' . ($_ENV['URL_ADM'] ?? '') . 'portaria-autorizacoes-view/' . $id);
                    return;
                }
                $errors[] = 'Não foi possível criar a autorização.';
            }
            $this->data['errors'] = $errors;
        }

        $this->data['visitantes'] = (new PortariaVisitantesRepository())->getAll(['ativo' => 1]);
        $this->data['usuarios'] = (new UsersRepository())->getAllUsersSelect();
        $this->data['departamentos'] = (new DepartmentsRepository())->getAllDepartmentsSelect();
        $title = !empty($this->data['modo_rapido']) ? 'Liberação rápida (não programado)' : 'Agendar / cadastrar autorização';
        $this->data = array_merge($this->data, (new PageLayoutService())->configurePageElements([
            'title_head' => $title,
            'menu' => 'portaria-autorizacoes',
            'buttonPermission' => ['PortariaAutorizacoes', 'PortariaVisitantesCreate'],
        ]));
        (new LoadViewService('adms/Views/portaria/autorizacoes/create', $this->data))->loadView();
    }

    /**
     * @param list<string> $errors
     */
    private function resolveVisitanteId(PortariaVisitantesRepository $repo, array &$errors): int
    {
        $modo = (string) ($this->data['form']['visitante_modo'] ?? 'existente');
        if ($modo === 'existente') {
            $id = (int) ($this->data['form']['visitante_id'] ?? 0);
            if ($id <= 0) {
                $errors[] = 'Selecione o visitante cadastrado.';
            }
            return $id;
        }

        $nome = trim((string) ($this->data['form']['novo_nome'] ?? ''));
        $documento = trim((string) ($this->data['form']['novo_documento'] ?? ''));
        if ($nome === '') {
            $errors[] = 'Informe o nome do visitante.';
            return 0;
        }

        if ($documento !== '') {
            $existente = $repo->findByDocumento($documento);
            if ($existente !== null) {
                return (int) $existente['id'];
            }
        }

        $novoId = $repo->create([
            'nome' => $nome,
            'documento' => $documento,
            'telefone' => trim((string) ($this->data['form']['novo_telefone'] ?? '')),
            'empresa' => trim((string) ($this->data['form']['novo_empresa'] ?? '')),
            'email' => '',
            'observacoes' => 'Cadastrado na liberação/agendamento de portaria.',
            'ativo' => 1,
        ]);
        if (!$novoId) {
            $errors[] = 'Não foi possível cadastrar o visitante.';
            return 0;
        }

        return (int) $novoId;
    }
}
