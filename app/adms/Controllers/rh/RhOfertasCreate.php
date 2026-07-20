<?php

declare(strict_types=1);

namespace App\adms\Controllers\rh;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\RhOfertasRepository;
use App\adms\Models\Repository\RhVagasRepository;
use App\adms\Models\Services\RhOfertaService;
use App\adms\Models\Services\RhPermissionService;
use App\adms\Views\Services\LoadViewService;
use Exception;
use PDO;

final class RhOfertasCreate
{
    private array $data = [];

    public function index(int|string $id = 0): void
    {
        $candidaturaId = (int) ($id ?: ($_GET['candidatura_id'] ?? 0));
        if ($candidaturaId <= 0) {
            $_SESSION['msg'] = 'Informe a candidatura para criar a oferta.';
            $_SESSION['msg_type'] = 'warning';
            header('Location: ' . ($_ENV['URL_ADM'] ?? '') . 'rh-candidatos');
            exit;
        }

        if (strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) === 'POST') {
            $this->save($candidaturaId);
            return;
        }

        $vinculo = $this->loadVinculo($candidaturaId);
        if ($vinculo === null) {
            $_SESSION['msg'] = 'Candidatura não encontrada.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . ($_ENV['URL_ADM'] ?? '') . 'rh-candidatos');
            exit;
        }

        if (!RhPermissionService::canManagePipelineByVagaId((int) $vinculo['rh_vaga_id'])) {
            $_SESSION['msg'] = 'Sem permissão para criar oferta nesta vaga.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . ($_ENV['URL_ADM'] ?? '') . 'rh-candidatos-view/' . (int) $vinculo['rh_candidato_id']);
            exit;
        }

        $ativa = (new RhOfertasRepository())->findAtivaByCandidatura($candidaturaId);
        if ($ativa !== null) {
            header('Location: ' . ($_ENV['URL_ADM'] ?? '') . 'rh-ofertas-view/' . (int) $ativa['id']);
            exit;
        }

        $this->data = [
            'title_head' => 'Criar Oferta',
            'menu' => 'rh-candidatos',
            'buttonPermission' => ['RhOfertasCreate', 'RhCandidatos', 'RhVagas'],
            'csrf_token' => CSRFHelper::generateCSRFToken('form_rh_oferta_create'),
            'vinculo' => $vinculo,
            'form' => [
                'tipo_contrato' => $vinculo['vaga_tipo_contrato'] ?? '',
                'salario_oferecido' => '',
                'data_inicio_prevista' => '',
                'validade_ate' => '',
                'observacoes' => '',
            ],
        ];

        $pageLayout = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayout->configurePageElements($this->data));
        (new LoadViewService('adms/Views/rh/ofertas/create', $this->data))->loadView();
    }

    private function save(int $candidaturaId): void
    {
        if (!CSRFHelper::validateCSRFToken('form_rh_oferta_create', (string) ($_POST['csrf_token'] ?? ''))) {
            $_SESSION['msg'] = 'Token inválido.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . ($_ENV['URL_ADM'] ?? '') . 'rh-ofertas-create/' . $candidaturaId);
            exit;
        }

        try {
            $result = (new RhOfertaService())->criar(
                $candidaturaId,
                $_POST['form'] ?? [],
                (int) ($_SESSION['user_id'] ?? 0)
            );
            $_SESSION['msg'] = 'Oferta criada em rascunho.';
            $_SESSION['msg_type'] = 'success';
            header('Location: ' . ($_ENV['URL_ADM'] ?? '') . 'rh-ofertas-view/' . $result['oferta_id']);
            exit;
        } catch (Exception $e) {
            $_SESSION['msg'] = $e->getMessage();
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . ($_ENV['URL_ADM'] ?? '') . 'rh-ofertas-create/' . $candidaturaId);
            exit;
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    private function loadVinculo(int $candidaturaId): ?array
    {
        $stmt = (new RhVagasRepository())->getConnection()->prepare(
            'SELECT cv.id, cv.rh_candidato_id, cv.rh_vaga_id, cv.status,
                    c.nome AS candidato_nome, c.email AS candidato_email,
                    v.titulo AS vaga_titulo, v.tipo_contrato AS vaga_tipo_contrato
             FROM rh_candidatos_vagas cv
             INNER JOIN rh_candidatos c ON c.id = cv.rh_candidato_id
             INNER JOIN rh_vagas v ON v.id = cv.rh_vaga_id
             WHERE cv.id = :id
             LIMIT 1'
        );
        $stmt->bindValue(':id', $candidaturaId, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return is_array($row) ? $row : null;
    }
}
