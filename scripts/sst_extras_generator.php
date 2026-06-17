<?php

declare(strict_types=1);

function generateSstExtras(string $root): void
{
    writeFile("{$root}/app/adms/Models/Services/SstDashboardService.php", <<<'PHP'
<?php

declare(strict_types=1);

namespace App\adms\Models\Services;

use App\adms\Models\Services\DbConnection;
use PDO;

class SstDashboardService extends DbConnection
{
    public function getPendingExamsCount(): int
    {
        $sql = "SELECT COUNT(*) AS total FROM adms_sst_asos
                WHERE data_validade IS NOT NULL AND data_validade < CURDATE()";
        $stmt = $this->getConnection()->query($sql);
        return (int) ($stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);
    }

    public function getExpiredEpisCount(): int
    {
        $sql = "SELECT COUNT(*) AS total FROM adms_sst_epi_entregas
                WHERE data_prevista_troca IS NOT NULL AND data_prevista_troca < CURDATE()
                AND tipo_movimento = 'Entrega'";
        $stmt = $this->getConnection()->query($sql);
        return (int) ($stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);
    }

    public function getOpenAccidentsCount(): int
    {
        $sql = "SELECT COUNT(*) AS total FROM adms_sst_acidentes WHERE status != 'Encerrado'";
        $stmt = $this->getConnection()->query($sql);
        return (int) ($stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);
    }

    public function getPendingExams(int $limit = 10): array
    {
        $sql = "SELECT a.*, u.name AS colaborador_nome
                FROM adms_sst_asos a
                LEFT JOIN adms_users u ON u.id = a.adms_user_id
                WHERE a.data_validade IS NOT NULL AND a.data_validade < CURDATE()
                ORDER BY a.data_validade ASC LIMIT :lim";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function getExpiredEpis(int $limit = 10): array
    {
        $sql = "SELECT e.*, u.name AS colaborador_nome, ep.nome AS epi_nome
                FROM adms_sst_epi_entregas e
                LEFT JOIN adms_users u ON u.id = e.adms_user_id
                LEFT JOIN adms_sst_epis ep ON ep.id = e.adms_sst_epi_id
                WHERE e.data_prevista_troca IS NOT NULL AND e.data_prevista_troca < CURDATE()
                AND e.tipo_movimento = 'Entrega'
                ORDER BY e.data_prevista_troca ASC LIMIT :lim";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function getOpenAccidents(int $limit = 10): array
    {
        $sql = "SELECT a.*, u.name AS colaborador_nome
                FROM adms_sst_acidentes a
                LEFT JOIN adms_users u ON u.id = a.adms_user_id
                WHERE a.status != 'Encerrado'
                ORDER BY a.data_ocorrencia DESC LIMIT :lim";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function getReportExames(array $filters = []): array
    {
        $where = ['1=1'];
        $params = [];
        if (!empty($filters['adms_user_id'])) {
            $where[] = 'a.adms_user_id = :uid';
            $params[':uid'] = (int) $filters['adms_user_id'];
        }
        if (!empty($filters['status_vencimento']) && $filters['status_vencimento'] === 'vencido') {
            $where[] = 'a.data_validade < CURDATE()';
        }
        $sql = "SELECT a.*, u.name AS colaborador_nome, ex.nome AS exame_nome
                FROM adms_sst_asos a
                LEFT JOIN adms_users u ON u.id = a.adms_user_id
                LEFT JOIN adms_sst_exames ex ON ex.id = a.adms_sst_exame_id
                WHERE " . implode(' AND ', $where) . "
                ORDER BY a.data_validade ASC, a.id DESC";
        $stmt = $this->getConnection()->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function getReportEpis(array $filters = []): array
    {
        $where = ['e.tipo_movimento = \'Entrega\''];
        $params = [];
        if (!empty($filters['adms_user_id'])) {
            $where[] = 'e.adms_user_id = :uid';
            $params[':uid'] = (int) $filters['adms_user_id'];
        }
        if (!empty($filters['status_vencimento']) && $filters['status_vencimento'] === 'vencido') {
            $where[] = 'e.data_prevista_troca < CURDATE()';
        }
        $sql = "SELECT e.*, u.name AS colaborador_nome, ep.nome AS epi_nome
                FROM adms_sst_epi_entregas e
                LEFT JOIN adms_users u ON u.id = e.adms_user_id
                LEFT JOIN adms_sst_epis ep ON ep.id = e.adms_sst_epi_id
                WHERE " . implode(' AND ', $where) . "
                ORDER BY e.data_prevista_troca ASC, e.id DESC";
        $stmt = $this->getConnection()->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function getReportPendencias(): array
    {
        return [
            'exames' => $this->getPendingExams(100),
            'epis' => $this->getExpiredEpis(100),
            'acidentes' => $this->getOpenAccidents(100),
        ];
    }
}
PHP);

    writeFile("{$root}/app/adms/Controllers/sst/SstDashboard.php", <<<'PHP'
<?php

declare(strict_types=1);

namespace App\adms\Controllers\sst;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Services\SstDashboardService;
use App\adms\Views\Services\LoadViewService;

class SstDashboard
{
    private array $data = [];

    public function index(): void
    {
        $service = new SstDashboardService();
        $this->data['pending_exams_count'] = $service->getPendingExamsCount();
        $this->data['expired_epis_count'] = $service->getExpiredEpisCount();
        $this->data['open_accidents_count'] = $service->getOpenAccidentsCount();
        $this->data['pending_exams'] = $service->getPendingExams(5);
        $this->data['expired_epis'] = $service->getExpiredEpis(5);
        $this->data['open_accidents'] = $service->getOpenAccidents(5);

        $pageElements = [
            'title_head' => 'Dashboard - SST',
            'menu' => 'sst-dashboard',
            'buttonPermission' => ['SstDashboard', 'SstReportPendencias', 'SstReportExames', 'SstReportEpis'],
        ];
        $this->data = array_merge($this->data, (new PageLayoutService())->configurePageElements($pageElements));
        (new LoadViewService('adms/Views/sst/dashboard', $this->data))->loadView();
    }
}
PHP);

    foreach ([
        'Pendencias' => ['method' => 'getReportPendencias', 'view' => 'report_pendencias', 'title' => 'Relatório de Pendências'],
        'Exames' => ['method' => 'getReportExames', 'view' => 'report_exames', 'title' => 'Relatório de Exames'],
        'Epis' => ['method' => 'getReportEpis', 'view' => 'report_epis', 'title' => 'Relatório de EPIs'],
    ] as $suffix => $cfg) {
        $ctrl = "SstReport{$suffix}";
        $method = $cfg['method'];
        $view = $cfg['view'];
        $title = $cfg['title'];
        $isPendencias = $suffix === 'Pendencias';

        $loadData = $isPendencias
            ? "\$this->data['report'] = \$service->{$method}();"
            : "\$filters = ['adms_user_id' => \$_GET['adms_user_id'] ?? '', 'status_vencimento' => \$_GET['status_vencimento'] ?? ''];
                \$this->data['filters'] = \$filters;
                \$this->data['items'] = \$service->{$method}(\$filters);";

        writeFile("{$root}/app/adms/Controllers/sst/{$ctrl}.php", <<<PHP
<?php

declare(strict_types=1);

namespace App\adms\Controllers\sst;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\UsersRepository;
use App\adms\Models\Services\SstDashboardService;
use App\adms\Views\Services\LoadViewService;

class {$ctrl}
{
    private array \$data = [];

    public function index(): void
    {
        \$service = new SstDashboardService();
        {$loadData}
        \$this->data['users'] = (new UsersRepository())->getAllUsersForSelect();
        \$pageElements = [
            'title_head' => '{$title} - SST',
            'menu' => 'sst-dashboard',
            'buttonPermission' => ['{$ctrl}'],
        ];
        \$this->data = array_merge(\$this->data, (new PageLayoutService())->configurePageElements(\$pageElements));
        (new LoadViewService('adms/Views/sst/{$view}', \$this->data))->loadView();
    }
}
PHP);
    }

    writeFile("{$root}/app/adms/Controllers/sst/SstEmployeeProfile.php", <<<'PHP'
<?php

declare(strict_types=1);

namespace App\adms\Controllers\sst;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\SstAcidentesRepository;
use App\adms\Models\Repository\SstAfastamentosRepository;
use App\adms\Models\Repository\SstAsosRepository;
use App\adms\Models\Repository\SstEpiEntregasRepository;
use App\adms\Models\Repository\UsersRepository;
use App\adms\Views\Services\LoadViewService;

class SstEmployeeProfile
{
    private array $data = [];

    public function index(string|int|null $id = null): void
    {
        if (!$id) {
            $_SESSION['msg'] = 'Colaborador não informado.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-users');
            exit;
        }
        $userId = (int) $id;
        $usersRepo = new UsersRepository();
        $this->data['user'] = $usersRepo->getUser($userId);
        if (!$this->data['user']) {
            $_SESSION['msg'] = 'Colaborador não encontrado.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-users');
            exit;
        }
        $this->data['asos'] = (new SstAsosRepository())->getByUserId($userId);
        $this->data['afastamentos'] = (new SstAfastamentosRepository())->getByUserId($userId);
        $this->data['epi_entregas'] = (new SstEpiEntregasRepository())->getByUserId($userId);
        $this->data['acidentes'] = (new SstAcidentesRepository())->getByUserId($userId);

        $pageElements = [
            'title_head' => 'SST - ' . ($this->data['user']['name'] ?? 'Colaborador'),
            'menu' => 'sst-dashboard',
            'buttonPermission' => ['SstEmployeeProfile', 'SstListAsos', 'SstListAfastamentos', 'SstListEpiEntregas', 'SstListAcidentes'],
        ];
        $this->data = array_merge($this->data, (new PageLayoutService())->configurePageElements($pageElements));
        (new LoadViewService('adms/Views/sst/employee_profile', $this->data))->loadView();
    }
}
PHP);

    writeFile("{$root}/app/adms/Views/sst/dashboard.php", <<<'PHP'
<?php ?>
<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3 mobile-hide-page-title"><i class="fas fa-heartbeat me-2"></i>Dashboard SST</h2>
        <ol class="breadcrumb mb-3 ms-auto mobile-hide-breadcrumb">
            <li class="breadcrumb-item"><a href="<?= $_ENV['URL_ADM']; ?>dashboard">Dashboard</a></li>
            <li class="breadcrumb-item">SST</li>
        </ol>
    </div>
    <?php include './app/adms/Views/partials/alerts.php'; ?>
    <div class="row mb-4">
        <div class="col-xl-4 col-md-6 mb-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="rounded-circle bg-warning bg-opacity-10 p-3 me-3"><i class="fas fa-stethoscope text-warning fa-2x"></i></div>
                    <div><h6 class="text-muted mb-1">Exames Vencidos</h6><h3 class="mb-0 fw-bold"><?= (int)($this->data['pending_exams_count'] ?? 0) ?></h3></div>
                </div>
            </div>
        </div>
        <div class="col-xl-4 col-md-6 mb-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="rounded-circle bg-danger bg-opacity-10 p-3 me-3"><i class="fas fa-hard-hat text-danger fa-2x"></i></div>
                    <div><h6 class="text-muted mb-1">EPIs Vencidos</h6><h3 class="mb-0 fw-bold"><?= (int)($this->data['expired_epis_count'] ?? 0) ?></h3></div>
                </div>
            </div>
        </div>
        <div class="col-xl-4 col-md-6 mb-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="rounded-circle bg-primary bg-opacity-10 p-3 me-3"><i class="fas fa-ambulance text-primary fa-2x"></i></div>
                    <div><h6 class="text-muted mb-1">Acidentes Abertos</h6><h3 class="mb-0 fw-bold"><?= (int)($this->data['open_accidents_count'] ?? 0) ?></h3></div>
                </div>
            </div>
        </div>
    </div>
    <div class="row mb-3">
        <div class="col-auto">
            <a href="<?= $_ENV['URL_ADM']; ?>sst-report-pendencias" class="btn btn-outline-primary btn-sm">Pendências</a>
            <a href="<?= $_ENV['URL_ADM']; ?>sst-report-exames" class="btn btn-outline-primary btn-sm">Exames</a>
            <a href="<?= $_ENV['URL_ADM']; ?>sst-report-epis" class="btn btn-outline-primary btn-sm">EPIs</a>
        </div>
    </div>
    <div class="row">
        <div class="col-lg-4 mb-3">
            <div class="card shadow-sm"><div class="card-header">Exames vencidos recentes</div><div class="card-body p-0">
                <?php if (empty($this->data['pending_exams'])): ?><p class="p-3 text-muted mb-0">Nenhum.</p>
                <?php else: foreach ($this->data['pending_exams'] as $r): ?>
                    <div class="px-3 py-2 border-bottom small"><?= htmlspecialchars($r['colaborador_nome'] ?? '') ?> — <?= !empty($r['data_validade']) ? date('d/m/Y', strtotime($r['data_validade'])) : '-' ?></div>
                <?php endforeach; endif; ?>
            </div></div>
        </div>
        <div class="col-lg-4 mb-3">
            <div class="card shadow-sm"><div class="card-header">EPIs vencidos</div><div class="card-body p-0">
                <?php if (empty($this->data['expired_epis'])): ?><p class="p-3 text-muted mb-0">Nenhum.</p>
                <?php else: foreach ($this->data['expired_epis'] as $r): ?>
                    <div class="px-3 py-2 border-bottom small"><?= htmlspecialchars($r['colaborador_nome'] ?? '') ?> — <?= htmlspecialchars($r['epi_nome'] ?? '') ?></div>
                <?php endforeach; endif; ?>
            </div></div>
        </div>
        <div class="col-lg-4 mb-3">
            <div class="card shadow-sm"><div class="card-header">Acidentes abertos</div><div class="card-body p-0">
                <?php if (empty($this->data['open_accidents'])): ?><p class="p-3 text-muted mb-0">Nenhum.</p>
                <?php else: foreach ($this->data['open_accidents'] as $r): ?>
                    <div class="px-3 py-2 border-bottom small"><?= htmlspecialchars($r['colaborador_nome'] ?? '') ?> — <?= htmlspecialchars($r['tipo'] ?? '') ?></div>
                <?php endforeach; endif; ?>
            </div></div>
        </div>
    </div>
</div>
PHP);

    writeFile("{$root}/app/adms/Views/sst/report_pendencias.php", <<<'PHP'
<?php $report = $this->data['report'] ?? []; ?>
<div class="container-fluid px-4">
    <h2 class="mt-3"><i class="fas fa-clipboard-list me-2"></i>Pendências SST</h2>
    <?php include './app/adms/Views/partials/alerts.php'; ?>
    <div class="row">
        <div class="col-lg-4 mb-3"><div class="card shadow-sm"><div class="card-header">Exames vencidos (<?= count($report['exames'] ?? []) ?>)</div><div class="card-body p-0 table-responsive"><table class="table table-sm mb-0"><tbody>
            <?php foreach ($report['exames'] ?? [] as $r): ?><tr><td><?= htmlspecialchars($r['colaborador_nome'] ?? '') ?></td><td><?= !empty($r['data_validade']) ? date('d/m/Y', strtotime($r['data_validade'])) : '-' ?></td></tr><?php endforeach; ?>
        </tbody></table></div></div></div>
        <div class="col-lg-4 mb-3"><div class="card shadow-sm"><div class="card-header">EPIs vencidos (<?= count($report['epis'] ?? []) ?>)</div><div class="card-body p-0 table-responsive"><table class="table table-sm mb-0"><tbody>
            <?php foreach ($report['epis'] ?? [] as $r): ?><tr><td><?= htmlspecialchars($r['colaborador_nome'] ?? '') ?></td><td><?= htmlspecialchars($r['epi_nome'] ?? '') ?></td></tr><?php endforeach; ?>
        </tbody></table></div></div></div>
        <div class="col-lg-4 mb-3"><div class="card shadow-sm"><div class="card-header">Acidentes abertos (<?= count($report['acidentes'] ?? []) ?>)</div><div class="card-body p-0 table-responsive"><table class="table table-sm mb-0"><tbody>
            <?php foreach ($report['acidentes'] ?? [] as $r): ?><tr><td><?= htmlspecialchars($r['colaborador_nome'] ?? '') ?></td><td><?= htmlspecialchars($r['status'] ?? '') ?></td></tr><?php endforeach; ?>
        </tbody></table></div></div></div>
    </div>
    <a href="<?= $_ENV['URL_ADM']; ?>sst-dashboard" class="btn btn-secondary">Voltar</a>
</div>
PHP);

    writeFile("{$root}/app/adms/Views/sst/report_exames.php", <<<'PHP'
<?php ?>
<div class="container-fluid px-4">
    <h2 class="mt-3"><i class="fas fa-stethoscope me-2"></i>Relatório de Exames</h2>
    <?php include './app/adms/Views/partials/alerts.php'; ?>
    <form method="get" class="row g-2 mb-3 align-items-end">
        <div class="col-md-3"><label class="form-label">Colaborador</label><select name="adms_user_id" class="form-select form-select-sm"><option value="">Todos</option>
            <?php foreach ($this->data['users'] ?? [] as $u): ?><option value="<?= (int)$u['id'] ?>" <?= ((string)($this->data['filters']['adms_user_id'] ?? '') === (string)$u['id']) ? 'selected' : '' ?>><?= htmlspecialchars($u['name'] ?? '') ?></option><?php endforeach; ?>
        </select></div>
        <div class="col-md-2"><label class="form-label">Situação</label><select name="status_vencimento" class="form-select form-select-sm"><option value="">Todos</option><option value="vencido" <?= ($this->data['filters']['status_vencimento'] ?? '') === 'vencido' ? 'selected' : '' ?>>Vencidos</option></select></div>
        <div class="col-auto"><button class="btn btn-primary btn-sm">Filtrar</button></div>
    </form>
    <div class="table-responsive"><table class="table table-bordered table-striped"><thead><tr><th>Colaborador</th><th>Exame</th><th>Tipo</th><th>Realização</th><th>Validade</th><th>Resultado</th></tr></thead><tbody>
        <?php foreach ($this->data['items'] ?? [] as $r): ?><tr>
            <td><?= htmlspecialchars($r['colaborador_nome'] ?? '') ?></td><td><?= htmlspecialchars($r['exame_nome'] ?? '') ?></td><td><?= htmlspecialchars($r['tipo'] ?? '') ?></td>
            <td><?= !empty($r['data_realizacao']) ? date('d/m/Y', strtotime($r['data_realizacao'])) : '-' ?></td>
            <td><?= !empty($r['data_validade']) ? date('d/m/Y', strtotime($r['data_validade'])) : '-' ?></td>
            <td><?= htmlspecialchars($r['resultado'] ?? '') ?></td>
        </tr><?php endforeach; ?>
    </tbody></table></div>
    <a href="<?= $_ENV['URL_ADM']; ?>sst-dashboard" class="btn btn-secondary">Voltar</a>
</div>
PHP);

    writeFile("{$root}/app/adms/Views/sst/report_epis.php", <<<'PHP'
<?php ?>
<div class="container-fluid px-4">
    <h2 class="mt-3"><i class="fas fa-hard-hat me-2"></i>Relatório de EPIs</h2>
    <?php include './app/adms/Views/partials/alerts.php'; ?>
    <form method="get" class="row g-2 mb-3 align-items-end">
        <div class="col-md-3"><label class="form-label">Colaborador</label><select name="adms_user_id" class="form-select form-select-sm"><option value="">Todos</option>
            <?php foreach ($this->data['users'] ?? [] as $u): ?><option value="<?= (int)$u['id'] ?>" <?= ((string)($this->data['filters']['adms_user_id'] ?? '') === (string)$u['id']) ? 'selected' : '' ?>><?= htmlspecialchars($u['name'] ?? '') ?></option><?php endforeach; ?>
        </select></div>
        <div class="col-md-2"><label class="form-label">Situação</label><select name="status_vencimento" class="form-select form-select-sm"><option value="">Todos</option><option value="vencido" <?= ($this->data['filters']['status_vencimento'] ?? '') === 'vencido' ? 'selected' : '' ?>>Vencidos</option></select></div>
        <div class="col-auto"><button class="btn btn-primary btn-sm">Filtrar</button></div>
    </form>
    <div class="table-responsive"><table class="table table-bordered table-striped"><thead><tr><th>Colaborador</th><th>EPI</th><th>Qtd</th><th>Entrega</th><th>Prev. troca</th></tr></thead><tbody>
        <?php foreach ($this->data['items'] ?? [] as $r): ?><tr>
            <td><?= htmlspecialchars($r['colaborador_nome'] ?? '') ?></td><td><?= htmlspecialchars($r['epi_nome'] ?? '') ?></td><td><?= (int)($r['quantidade'] ?? 0) ?></td>
            <td><?= !empty($r['data_movimento']) ? date('d/m/Y', strtotime($r['data_movimento'])) : '-' ?></td>
            <td><?= !empty($r['data_prevista_troca']) ? date('d/m/Y', strtotime($r['data_prevista_troca'])) : '-' ?></td>
        </tr><?php endforeach; ?>
    </tbody></table></div>
    <a href="<?= $_ENV['URL_ADM']; ?>sst-dashboard" class="btn btn-secondary">Voltar</a>
</div>
PHP);

    writeFile("{$root}/app/adms/Views/sst/employee_profile.php", <<<'PHP'
<?php $user = $this->data['user']; ?>
<div class="container-fluid px-4">
    <h2 class="mt-3"><i class="fas fa-user-shield me-2"></i>SST — <?= htmlspecialchars($user['name'] ?? '') ?></h2>
    <?php include './app/adms/Views/partials/alerts.php'; ?>
    <ul class="nav nav-tabs mb-3" role="tablist">
        <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-asos">ASOs</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-afast">Afastamentos</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-epi">EPIs</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-acid">Acidentes</button></li>
    </ul>
    <div class="tab-content">
        <div class="tab-pane fade show active" id="tab-asos"><div class="table-responsive"><table class="table table-sm table-bordered"><thead><tr><th>Tipo</th><th>Realização</th><th>Validade</th><th>Resultado</th></tr></thead><tbody>
            <?php foreach ($this->data['asos'] ?? [] as $r): ?><tr><td><?= htmlspecialchars($r['tipo'] ?? '') ?></td><td><?= !empty($r['data_realizacao']) ? date('d/m/Y', strtotime($r['data_realizacao'])) : '-' ?></td><td><?= !empty($r['data_validade']) ? date('d/m/Y', strtotime($r['data_validade'])) : '-' ?></td><td><?= htmlspecialchars($r['resultado'] ?? '') ?></td></tr><?php endforeach; ?>
        </tbody></table></div></div>
        <div class="tab-pane fade" id="tab-afast"><div class="table-responsive"><table class="table table-sm table-bordered"><thead><tr><th>Tipo</th><th>Início</th><th>Fim</th><th>Status</th></tr></thead><tbody>
            <?php foreach ($this->data['afastamentos'] ?? [] as $r): ?><tr><td><?= htmlspecialchars($r['tipo'] ?? '') ?></td><td><?= !empty($r['data_inicio']) ? date('d/m/Y', strtotime($r['data_inicio'])) : '-' ?></td><td><?= !empty($r['data_fim']) ? date('d/m/Y', strtotime($r['data_fim'])) : '-' ?></td><td><?= htmlspecialchars($r['status'] ?? '') ?></td></tr><?php endforeach; ?>
        </tbody></table></div></div>
        <div class="tab-pane fade" id="tab-epi"><div class="table-responsive"><table class="table table-sm table-bordered"><thead><tr><th>EPI</th><th>Movimento</th><th>Data</th><th>Prev. troca</th></tr></thead><tbody>
            <?php foreach ($this->data['epi_entregas'] ?? [] as $r): ?><tr><td><?= htmlspecialchars($r['epi_nome'] ?? '') ?></td><td><?= htmlspecialchars($r['tipo_movimento'] ?? '') ?></td><td><?= !empty($r['data_movimento']) ? date('d/m/Y', strtotime($r['data_movimento'])) : '-' ?></td><td><?= !empty($r['data_prevista_troca']) ? date('d/m/Y', strtotime($r['data_prevista_troca'])) : '-' ?></td></tr><?php endforeach; ?>
        </tbody></table></div></div>
        <div class="tab-pane fade" id="tab-acid"><div class="table-responsive"><table class="table table-sm table-bordered"><thead><tr><th>Tipo</th><th>Data</th><th>Status</th></tr></thead><tbody>
            <?php foreach ($this->data['acidentes'] ?? [] as $r): ?><tr><td><?= htmlspecialchars($r['tipo'] ?? '') ?></td><td><?= !empty($r['data_ocorrencia']) ? date('d/m/Y H:i', strtotime($r['data_ocorrencia'])) : '-' ?></td><td><?= htmlspecialchars($r['status'] ?? '') ?></td></tr><?php endforeach; ?>
        </tbody></table></div></div>
    </div>
    <a href="<?= $_ENV['URL_ADM']; ?>view-user/<?= (int)$user['id'] ?>" class="btn btn-secondary mt-3">Voltar ao colaborador</a>
</div>
PHP);
}
