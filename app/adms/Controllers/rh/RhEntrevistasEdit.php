<?php

namespace App\adms\Controllers\rh;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\GenerateLog;
use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\RhEntrevistasRepository;
use App\adms\Models\Repository\RhEntrevistaAvaliadoresRepository;
use App\adms\Models\Repository\RhEntrevistaScorecardRepository;
use App\adms\Models\Repository\RhCandidatosRepository;
use App\adms\Models\Repository\RhVagasRepository;
use App\adms\Views\Services\LoadViewService;

class RhEntrevistasEdit
{
    private array|string|null $data = null;

    public function index(int|string $id): void
    {
        $id = (int) $id;
        if ($id <= 0) {
            $_SESSION['error'] = "Entrevista não encontrada!";
            header("Location: {$_ENV['URL_ADM']}rh-entrevistas");
            return;
        }

        $repo = new RhEntrevistasRepository();
        $entrevista = $repo->getById($id);
        if (!$entrevista) {
            $_SESSION['error'] = "Entrevista não encontrada!";
            header("Location: {$_ENV['URL_ADM']}rh-entrevistas");
            return;
        }

        if (!\App\adms\Models\Services\RhPermissionService::canManageEntrevista($entrevista)) {
            $_SESSION['error'] = 'Você não tem permissão para editar esta entrevista.';
            header('Location: ' . $_ENV['URL_ADM'] . 'rh-entrevistas');
            return;
        }

        $avaliadorId = (int) ($_SESSION['user_id'] ?? 0);
        $this->data['entrevista'] = $entrevista;
        $this->data['form'] = $_POST['form'] ?? [
            'rh_candidato_id'   => $entrevista['rh_candidato_id'],
            'rh_vaga_id'        => $entrevista['rh_vaga_id'] ?? '',
            'tipo'              => $entrevista['tipo'] ?? 'presencial',
            'entrevistador_id'  => $entrevista['entrevistador_id'] ?? '',
            'data_hora'         => $entrevista['data_hora'] ?? '',
            'local'             => $entrevista['local'] ?? '',
            'observacoes'       => $entrevista['observacoes'] ?? '',
            'resultado'         => $entrevista['resultado'] ?? '',
            'feedback'          => $entrevista['feedback'] ?? '',
            'avaliadores_adicionais' => $this->loadAvaliadoresAdicionaisIds($id),
        ];
        if (!isset($this->data['form']['avaliadores_adicionais'])) {
            $this->data['form']['avaliadores_adicionais'] = [];
        }
        $this->data['scorecard'] = isset($_POST['scorecard'])
            ? $this->normalizeScorecardPost($_POST['scorecard'])
            : $this->loadScorecardForm($id, $avaliadorId);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $csrfToken = $_POST['csrf_token'] ?? '';
            if (!CSRFHelper::validateCSRFToken('form_edit_rh_entrevista', $csrfToken)) {
                $_SESSION['error'] = "Token de segurança inválido ou expirado.";
                $this->viewForm($id);
                return;
            }
            $form = $_POST['form'] ?? [];
            $form['avaliadores_adicionais'] = array_values(array_filter(
                array_map('intval', (array) ($form['avaliadores_adicionais'] ?? [])),
                static fn (int $v): bool => $v > 0
            ));
            $scorecardPost = $this->normalizeScorecardPost($_POST['scorecard'] ?? []);
            $this->data['form'] = $form;
            $this->data['scorecard'] = $scorecardPost;
            if (empty($form['data_hora'])) {
                $_SESSION['error'] = "Data/hora é obrigatória.";
                $this->viewForm($id);
                return;
            }
            // Ao agendar (preencher data/hora) com entrevista ainda pendente, mudar para "agendado"
            $resultadoAtual = $entrevista['resultado'] ?? '';
            $resultadoForm = trim($form['resultado'] ?? '');
            if (($resultadoAtual === '' || $resultadoAtual === 'pendente') && $resultadoForm !== 'aprovado' && $resultadoForm !== 'reprovado') {
                $form['resultado'] = 'agendado';
            }
            try {
                $movimentacao = new \App\adms\Models\Services\RhCandidaturaMovimentacaoService();
                $movimentacao->atualizarEntrevistaComReflexoPipeline($id, $form);
            } catch (\Throwable $e) {
                GenerateLog::generateLog('error', 'Erro ao atualizar entrevista com reflexo no pipeline.', [
                    'entrevista_id' => $id,
                    'error' => $e->getMessage(),
                ]);
                $_SESSION['error'] = $e->getMessage() !== ''
                    ? $e->getMessage()
                    : 'Erro ao atualizar entrevista.';
                $this->data['form'] = $form;
                $this->data['scorecard'] = $scorecardPost;
                $this->viewForm($id);
                return;
            }

            $warnings = [];
            try {
                $principalId = !empty($form['entrevistador_id']) ? (int) $form['entrevistador_id'] : null;
                (new RhEntrevistaAvaliadoresRepository())->syncPainel(
                    $id,
                    $principalId,
                    $form['avaliadores_adicionais'] ?? [],
                    $avaliadorId
                );
            } catch (\Throwable $e) {
                GenerateLog::generateLog('error', 'Entrevista salva, mas painel de avaliadores falhou.', [
                    'entrevista_id' => $id,
                    'error' => $e->getMessage(),
                ]);
                $warnings[] = 'painel de avaliadores: ' . $e->getMessage();
            }

            if ($avaliadorId > 0 && !empty($scorecardPost['itens'])) {
                try {
                    $itens = [];
                    foreach (($scorecardPost['itens'] ?? []) as $item) {
                        $itens[] = [
                            'codigo' => (string) ($item['codigo'] ?? ''),
                            'nota' => $item['nota'] ?? null,
                            'comentario' => $item['comentario'] ?? null,
                        ];
                    }
                    (new RhEntrevistaScorecardRepository())->saveForAvaliador($id, $avaliadorId, [
                        'parecer' => $scorecardPost['parecer'] ?? null,
                        'finalizar' => !empty($scorecardPost['finalizar']),
                        'itens' => $itens,
                    ]);
                } catch (\Throwable $e) {
                    GenerateLog::generateLog('error', 'Entrevista salva, mas scorecard falhou.', [
                        'entrevista_id' => $id,
                        'error' => $e->getMessage(),
                    ]);
                    $warnings[] = 'scorecard: ' . $e->getMessage();
                }
            }

            if ($warnings !== []) {
                $_SESSION['error'] = 'Entrevista atualizada, porém falhou ao salvar: ' . implode('; ', $warnings);
            } else {
                $_SESSION['success'] = "Entrevista atualizada com sucesso!";
            }
            header("Location: {$_ENV['URL_ADM']}rh-entrevistas-view/$id");
            return;
        }

        $this->viewForm($id);
    }

    /**
     * @return list<int>
     */
    private function loadAvaliadoresAdicionaisIds(int $entrevistaId): array
    {
        try {
            return (new RhEntrevistaAvaliadoresRepository())->listIdsAdicionaisAtivos($entrevistaId);
        } catch (\Throwable $e) {
            GenerateLog::generateLog('warning', 'Painel de avaliadores indisponível ao carregar edição.', [
                'entrevista_id' => $entrevistaId,
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Normaliza POST do scorecard para o formato da view (lista com label/peso).
     *
     * @param array<string, mixed> $raw
     * @return array{parecer: string, finalizar: bool, itens: list<array<string, mixed>>, nota_ponderada: float|null, status: string}
     */
    private function normalizeScorecardPost(array $raw): array
    {
        $posted = [];
        foreach (($raw['itens'] ?? []) as $key => $item) {
            if (is_array($item) && isset($item['codigo'])) {
                $codigo = (string) $item['codigo'];
            } else {
                $codigo = (string) $key;
            }
            if ($codigo === '' || !is_array($item)) {
                continue;
            }
            $posted[$codigo] = $item;
        }

        $itens = [];
        foreach (\App\adms\Models\Services\RhEntrevistaScorecardCatalog::defaultCriteria() as $crit) {
            $prev = $posted[$crit['codigo']] ?? [];
            $itens[] = [
                'codigo' => $crit['codigo'],
                'label' => $crit['label'],
                'peso' => $crit['peso'],
                'nota' => $prev['nota'] ?? '',
                'comentario' => $prev['comentario'] ?? '',
            ];
        }

        return [
            'parecer' => (string) ($raw['parecer'] ?? ''),
            'finalizar' => !empty($raw['finalizar']),
            'itens' => $itens,
            'nota_ponderada' => null,
            'status' => !empty($raw['finalizar']) ? 'finalizado' : 'rascunho',
        ];
    }

    /**
     * @return array{parecer: string, finalizar: bool, itens: list<array<string, mixed>>, nota_ponderada: float|null, status: string}
     */
    private function loadScorecardForm(int $entrevistaId, int $avaliadorId): array
    {
        if ($avaliadorId <= 0) {
            return [
                'parecer' => '',
                'finalizar' => false,
                'itens' => [],
                'nota_ponderada' => null,
                'status' => 'rascunho',
            ];
        }

        try {
            return (new RhEntrevistaScorecardRepository())->buildFormState($entrevistaId, $avaliadorId);
        } catch (\Throwable $e) {
            // Tabelas ainda não migradas: UI mostra critérios vazios sem bloquear edição.
            GenerateLog::generateLog('warning', 'Scorecard indisponível ao carregar edição de entrevista.', [
                'entrevista_id' => $entrevistaId,
                'error' => $e->getMessage(),
            ]);
            $itens = [];
            foreach (\App\adms\Models\Services\RhEntrevistaScorecardCatalog::defaultCriteria() as $crit) {
                $itens[] = [
                    'codigo' => $crit['codigo'],
                    'label' => $crit['label'],
                    'peso' => $crit['peso'],
                    'nota' => '',
                    'comentario' => '',
                ];
            }

            return [
                'parecer' => '',
                'finalizar' => false,
                'itens' => $itens,
                'nota_ponderada' => null,
                'status' => 'rascunho',
            ];
        }
    }

    private function viewForm(int $id): void
    {
        $candRepo = new RhCandidatosRepository();
        $vagaRepo = new RhVagasRepository();
        $userRepo = new \App\adms\Models\Repository\UsersRepository();

        $this->data['candidatos'] = ($candRepo->getAll([], 1, 1000))['data'] ?? [];
        $this->data['vagas'] = ($vagaRepo->getAll([], 1, 1000))['data'] ?? [];
        $this->data['users'] = $userRepo->getAllUsersSelect() ?: [];
        if (empty($this->data['scorecard'])) {
            $this->data['scorecard'] = $this->loadScorecardForm($id, (int) ($_SESSION['user_id'] ?? 0));
        }

        $pageElements = [
            'title_head' => 'Editar Entrevista',
            'menu'       => 'rh-entrevistas',
            'buttonPermission' => ['RhEntrevistas', 'RhEntrevistasEdit'],
        ];

        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data ?? [], $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService('adms/Views/rh/entrevistas/edit', $this->data);
        $loadView->loadView();
    }
}
