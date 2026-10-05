<?php
use App\adms\Models\Services\InformativoRelatorioService;

$kpis = $this->data['relatorio_kpis'] ?? InformativoRelatorioService::kpisFromAtivos(
    $this->data['dados_relatorio'] ?? [],
    (bool) ($this->data['requires_ack'] ?? false)
);
$requiresAckCard = (bool) ($this->data['requires_ack'] ?? false);
if (!$requiresAckCard) {
    $val = $this->data['informativo']['requires_ack'] ?? null;
    $requiresAckCard = InformativoRelatorioService::requiresAck($val);
}
$dadosAtivos = $this->data['dados_relatorio'] ?? [];
$dadosInativos = $this->data['dados_inativos_historico'] ?? [];
$excluidos = (int) ($this->data['excluidos_sem_historico'] ?? 0);
$pctVis = InformativoRelatorioService::formatPct($kpis['pct_visualizacao'] ?? 0.0);
$pctCie = $requiresAckCard ? InformativoRelatorioService::formatPct($kpis['pct_ciencia'] ?? 0.0) : '';

if (!function_exists('admsRenderRelatorioInformativoRows')) {
    /**
     * @param list<array<string, mixed>> $rows
     */
    function admsRenderRelatorioInformativoRows(array $rows): void
    {
        if ($rows === []) {
            echo '<tr><td colspan="6" class="text-center">Nenhum usuário encontrado</td></tr>';
            return;
        }
        foreach ($rows as $dado) {
            $statusClass = match ($dado['status']) {
                'PENDENTE' => 'bg-danger',
                'VISUALIZOU' => 'bg-info',
                'VISUALIZOU MAS NÃO CIENTE' => 'bg-warning text-dark',
                'CIENTE' => 'bg-success',
                default => 'bg-secondary'
            };
            ?>
            <tr>
                <td class="text-start align-middle ps-3">
                    <strong><?php echo htmlspecialchars($dado['usuario_nome']); ?></strong><br>
                    <small class="text-muted"><?php echo htmlspecialchars($dado['usuario_email']); ?></small>
                </td>
                <td class="text-start align-middle ps-3">
                    <?php if ($dado['visualizou'] === 'SIM'): ?>
                        <span class="badge bg-success">SIM</span>
                    <?php else: ?>
                        <span class="badge bg-danger">NÃO</span>
                    <?php endif; ?>
                </td>
                <td class="text-start align-middle ps-3">
                    <?php echo htmlspecialchars((string) $dado['data_visualizacao']); ?>
                </td>
                <td class="text-start align-middle ps-3">
                    <?php if ($dado['esta_ciente'] === 'N/A'): ?>
                        <span class="badge bg-secondary">N/A</span>
                    <?php elseif ($dado['esta_ciente'] === 'SIM'): ?>
                        <span class="badge bg-success">SIM</span>
                    <?php else: ?>
                        <span class="badge bg-warning text-dark">NÃO</span>
                    <?php endif; ?>
                </td>
                <td class="text-start align-middle ps-3">
                    <?php echo htmlspecialchars((string) $dado['data_ciencia']); ?>
                </td>
                <td class="text-start align-middle ps-3">
                    <span class="badge <?php echo $statusClass; ?>">
                        <?php echo htmlspecialchars($dado['status']); ?>
                    </span>
                </td>
            </tr>
            <?php
        }
    }

    /**
     * @param list<array<string, mixed>> $rows
     */
    function admsRenderRelatorioInformativoCards(array $rows): void
    {
        if ($rows === []) {
            echo '<div class="text-center text-muted py-3">Nenhum usuário encontrado</div>';
            return;
        }
        foreach ($rows as $dado) {
            $visualizouSim = ($dado['visualizou'] === 'SIM');
            $estaCiente = $dado['esta_ciente'] ?? 'N/A';
            $cieStatus = $estaCiente === 'N/A' ? 'bg-secondary' : ($estaCiente === 'SIM' ? 'bg-success' : 'bg-warning text-dark');
            $statusClass = match ($dado['status']) {
                'PENDENTE' => 'bg-danger',
                'VISUALIZOU' => 'bg-info',
                'VISUALIZOU MAS NÃO CIENTE' => 'bg-warning text-dark',
                'CIENTE' => 'bg-success',
                default => 'bg-secondary'
            };
            ?>
            <div
                class="card mb-3 shadow-sm relatorio-user-card"
                style="border-radius: 12px;"
                data-user="<?php echo htmlspecialchars($dado['usuario_nome'] ?? ''); ?>"
                data-email="<?php echo htmlspecialchars($dado['usuario_email'] ?? ''); ?>"
            >
                <div class="card-body" style="padding: 14px;">
                    <div class="d-flex justify-content-between align-items-start gap-2">
                        <div class="flex-grow-1">
                            <strong><?php echo htmlspecialchars($dado['usuario_nome']); ?></strong>
                            <div class="text-muted small"><?php echo htmlspecialchars($dado['usuario_email']); ?></div>
                        </div>
                        <span class="badge <?php echo $statusClass; ?>">
                            <?php echo htmlspecialchars($dado['status']); ?>
                        </span>
                    </div>
                    <div class="mt-2 d-flex flex-wrap gap-1">
                        <span class="badge <?php echo $visualizouSim ? 'bg-success' : 'bg-danger'; ?>">
                            Visualizou: <?php echo $visualizouSim ? 'SIM' : 'NÃO'; ?>
                        </span>
                        <span class="badge <?php echo $cieStatus; ?>">
                            Ciência: <?php echo htmlspecialchars($estaCiente); ?>
                        </span>
                    </div>
                    <div class="mt-2">
                        <small class="text-muted d-block">
                            Visualização em: <?php echo htmlspecialchars((string) $dado['data_visualizacao']); ?>
                        </small>
                        <small class="text-muted d-block">
                            Ciência em: <?php echo htmlspecialchars((string) $dado['data_ciencia']); ?>
                        </small>
                    </div>
                </div>
            </div>
            <?php
        }
    }
}
?>

<div class="container-fluid px-4">
    <h1 class="mt-4 mobile-hide-page-title">Relatório de Informativo</h1>
    
    <ol class="breadcrumb mb-4 mobile-hide-breadcrumb">
        <li class="breadcrumb-item"><a href="<?php echo $_ENV['URL_ADM']; ?>dashboard">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="<?php echo $_ENV['URL_ADM']; ?>list-informativos">Informativos</a></li>
        <li class="breadcrumb-item"><a href="<?php echo $_ENV['URL_ADM']; ?>relatorio-informativo">Relatório</a></li>
        <li class="breadcrumb-item active">Resultado</li>
    </ol>

    <!-- Cabeçalho do Informativo -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h4 class="mb-0">
                <i class="fas fa-file-alt me-2"></i>
                <?php echo htmlspecialchars($this->data['informativo']['titulo']); ?>
            </h4>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <p><strong>Categoria:</strong> <?php echo htmlspecialchars($this->data['informativo']['categoria_nome'] ?? $this->data['informativo']['categoria']); ?></p>
                    <p><strong>Departamento:</strong> <?php echo htmlspecialchars($this->data['informativo']['department_name'] ?? 'N/A'); ?></p>
                    <p><strong>Urgente:</strong> 
                        <?php if ($this->data['informativo']['urgente']): ?>
                            <span class="badge bg-danger">SIM</span>
                        <?php else: ?>
                            <span class="badge bg-secondary">NÃO</span>
                        <?php endif; ?>
                    </p>
                </div>
                <div class="col-md-6">
                    <p><strong>Exige Ciência:</strong> 
                        <?php if ($requiresAckCard): ?>
                            <span class="badge bg-warning text-dark">SIM</span>
                        <?php else: ?>
                            <span class="badge bg-secondary">NÃO</span>
                        <?php endif; ?>
                    </p>
                    <p><strong>Status:</strong> 
                        <?php if ($this->data['informativo']['ativo']): ?>
                            <span class="badge bg-success">ATIVO</span>
                        <?php else: ?>
                            <span class="badge bg-secondary">INATIVO</span>
                        <?php endif; ?>
                    </p>
                    <p><strong>Criado em:</strong> <?php echo date('d/m/Y H:i:s', strtotime($this->data['informativo']['created_at'])); ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Botões de Ação -->
    <div class="row mb-3">
        <div class="col-md-6">
            <a href="<?php echo $_ENV['URL_ADM']; ?>relatorio-informativo" class="btn btn-secondary d-none d-md-inline-block">
                <i class="fas fa-arrow-left me-1"></i>
                Voltar
            </a>
            <button type="button"
                    class="btn btn-secondary d-inline d-md-none"
                    onclick="window.history.back();"
                    aria-label="Voltar">
                <i class="fas fa-arrow-left me-1"></i>
                Voltar
            </button>
        </div>
        <div class="col-md-6 text-end">
            <a href="<?php echo $_ENV['URL_ADM']; ?>export-relatorio-informativo-pdf?informativo_id=<?php echo $this->data['informativo']['id']; ?>"
               class="btn btn-outline-danger btn-sm d-inline-flex align-items-center gap-2"
               title="Baixar relatório em PDF"
               onclick="goRelatorioInformativoExport(event, this);">
                <i class="fas fa-file-pdf me-1"></i>
                PDF
            </a>
            <a href="<?php echo $_ENV['URL_ADM']; ?>export-relatorio-informativo-excel?informativo_id=<?php echo (int)$this->data['informativo']['id']; ?>"
               class="btn btn-outline-success btn-sm d-inline-flex align-items-center gap-2"
               title="Baixar relatório em Excel (.xlsx)"
               onclick="goRelatorioInformativoExport(event, this);">
                <i class="fas fa-file-excel me-1"></i>
                <span>Excel (XLSX)</span>
            </a>
        </div>
    </div>

    <div class="row mt-0 mb-3">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body text-center">
                    <h3><?php echo (int) $kpis['total']; ?></h3>
                    <p class="mb-0">Total de ativos</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body text-center">
                    <h3 class="mb-1"><?php echo (int) $kpis['visualizaram']; ?></h3>
                    <p class="mb-0">Visualizaram <span class="fw-semibold"><?php echo htmlspecialchars($pctVis); ?></span></p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-dark">
                <div class="card-body text-center">
                    <h3><?php echo (int) $kpis['pendentes']; ?></h3>
                    <p class="mb-0">Pendentes</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body text-center">
                    <?php if ($requiresAckCard): ?>
                        <h3 class="mb-1"><?php echo (int) $kpis['cientes']; ?></h3>
                        <p class="mb-0">Cientes <span class="fw-semibold"><?php echo htmlspecialchars($pctCie); ?></span></p>
                    <?php else: ?>
                        <h3>N/A</h3>
                        <p class="mb-0">Cientes</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <p class="text-muted small mb-4">
        Os percentuais usam somente colaboradores <strong>ativos</strong>.
        Inativos sem visualização ou ciência foram omitidos
        (<?php echo $excluidos; ?>).
        Inativos que já visualizaram ou deram ciência aparecem na aba de histórico
        (<?php echo count($dadosInativos); ?>).
    </p>

    <!-- Tabela do Relatório -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="fas fa-users me-1"></i>
                Status dos Usuários
            </h5>
        </div>
        <div class="card-body">
            <div class="mb-3">
                <label for="relatorioUserFilterInformativo" class="form-label mb-1">Filtrar por usuário</label>
                <input
                    type="text"
                    id="relatorioUserFilterInformativo"
                    class="form-control"
                    placeholder="Digite nome ou email"
                    oninput="filterRelatorioInformativoUsuarios()"
                >
            </div>

            <ul class="nav nav-tabs mb-3" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="tab-ativos" data-bs-toggle="tab" data-bs-target="#pane-ativos" type="button" role="tab">
                        Ativos (<?php echo count($dadosAtivos); ?>)
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="tab-inativos" data-bs-toggle="tab" data-bs-target="#pane-inativos" type="button" role="tab">
                        Inativos com histórico (<?php echo count($dadosInativos); ?>)
                    </button>
                </li>
            </ul>

            <div class="tab-content">
                <div class="tab-pane fade show active" id="pane-ativos" role="tabpanel">
                    <div class="table-responsive d-none d-md-block">
                        <table class="table table-striped table-bordered tabela-relatorio-informativo" id="tabela-relatorio">
                            <thead class="table-dark">
                                <tr>
                                    <th class="text-start ps-3" style="min-width: 220px;">Usuário</th>
                                    <th class="text-start ps-3" style="min-width: 120px;">Visualizou</th>
                                    <th class="text-start ps-3" style="min-width: 170px;">Data Visualização</th>
                                    <th class="text-start ps-3" style="min-width: 140px;">Está Ciente?</th>
                                    <th class="text-start ps-3" style="min-width: 170px;">Data da Ciência</th>
                                    <th class="text-start ps-3" style="min-width: 170px;">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php admsRenderRelatorioInformativoRows($dadosAtivos); ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="d-block d-md-none">
                        <?php admsRenderRelatorioInformativoCards($dadosAtivos); ?>
                    </div>
                </div>
                <div class="tab-pane fade" id="pane-inativos" role="tabpanel">
                    <p class="text-muted small">Colaboradores inativos que já haviam visualizado ou dado ciência. Não entram no percentual.</p>
                    <div class="table-responsive d-none d-md-block">
                        <table class="table table-striped table-bordered tabela-relatorio-informativo" id="tabela-relatorio-inativos">
                            <thead class="table-dark">
                                <tr>
                                    <th class="text-start ps-3" style="min-width: 220px;">Usuário</th>
                                    <th class="text-start ps-3" style="min-width: 120px;">Visualizou</th>
                                    <th class="text-start ps-3" style="min-width: 170px;">Data Visualização</th>
                                    <th class="text-start ps-3" style="min-width: 140px;">Está Ciente?</th>
                                    <th class="text-start ps-3" style="min-width: 170px;">Data da Ciência</th>
                                    <th class="text-start ps-3" style="min-width: 170px;">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php admsRenderRelatorioInformativoRows($dadosInativos); ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="d-block d-md-none">
                        <?php admsRenderRelatorioInformativoCards($dadosInativos); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>

function getRelatorioInformativoUserFilterValue() {
    const input = document.getElementById('relatorioUserFilterInformativo');
    return (input?.value || '').toString().toLowerCase().trim();
}

function goRelatorioInformativoExport(event, el) {
    event.preventDefault();
    const filtroUsuario = (typeof getRelatorioInformativoUserFilterValue === 'function')
        ? getRelatorioInformativoUserFilterValue()
        : '';
    const destino = el.getAttribute('href') || '';
    window.location.href = filtroUsuario
        ? destino + '&usuario_filter=' + encodeURIComponent(filtroUsuario)
        : destino;
}

function filterRelatorioInformativoUsuarios() {
    const q = getRelatorioInformativoUserFilterValue();

    document.querySelectorAll('.tabela-relatorio-informativo').forEach(table => {
        const tbodyRows = table.querySelectorAll('tbody tr');
        tbodyRows.forEach(tr => {
            const userCell = tr.querySelector('td.ps-3 strong')?.textContent || '';
            const emailCell = tr.querySelector('td.ps-3 small')?.textContent || '';
            const haystack = (userCell + ' ' + emailCell).toLowerCase();
            tr.style.display = (!q || haystack.includes(q)) ? '' : 'none';
        });
    });

    document.querySelectorAll('.relatorio-user-card').forEach(card => {
        const user = (card.dataset.user || '').toLowerCase();
        const email = (card.dataset.email || '').toLowerCase();
        const haystack = user + ' ' + email;
        card.style.display = (!q || haystack.includes(q)) ? '' : 'none';
    });
}

/* eslint-disable no-undef */
const SHEETJS_SRC = 'https://cdn.jsdelivr.net/npm/xlsx@0.20.2/dist/xlsx.full.min.js';

function loadSheetJs() {
    if (window.XLSX) return Promise.resolve(window.XLSX);

    const existing = document.getElementById('sheetjs-xlsx-cdn');
    if (existing) existing.remove();

    return new Promise((resolve, reject) => {
        const s = document.createElement('script');
        s.id = 'sheetjs-xlsx-cdn';
        s.src = SHEETJS_SRC;
        s.async = true;
        s.onload = () => {
            if (window.XLSX) resolve(window.XLSX);
            else reject(new Error('XLSX carregou, mas não apareceu em window'));
        };
        s.onerror = () => reject(new Error('Falha ao carregar SheetJS (XLSX)'));
        document.head.appendChild(s);
    });
}

async function exportarExcelRelatorioInformativo() {
    const table = document.getElementById('tabela-relatorio');
    if (!table) return;

    if (typeof filterRelatorioInformativoUsuarios === 'function') {
        filterRelatorioInformativoUsuarios();
    }

    const btn = document.getElementById('btnExportExcelRelatorioInformativo');
    const originalBtnHTML = btn ? btn.innerHTML : '';
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Gerando Excel...';
    }

    try {
        const XLSX = await Promise.race([
            loadSheetJs(),
            new Promise((_, reject) => setTimeout(() => reject(new Error('timeout')), 30000))
        ]);

        if (!XLSX || !XLSX.utils || typeof XLSX.utils.aoa_to_sheet !== 'function') {
            throw new Error('SheetJS carregado, mas utils.aoa_to_sheet não está disponível');
        }

        const tbodyRows = Array.from(table.querySelectorAll('tbody tr'))
            .filter(tr => tr.style.display !== 'none');

        const headers = Array.from(table.querySelectorAll('thead th'))
            .map(th => th.textContent.trim());

        const aoa = [headers];

        tbodyRows.forEach(tr => {
            const cells = Array.from(tr.querySelectorAll('td'))
                .map(td => td.textContent.trim().replace(/\s+/g, ' '));
            aoa.push(cells);
        });

        const ws = XLSX.utils.aoa_to_sheet(aoa);
        const wb = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(wb, ws, 'Relatório');

        const fileName = 'relatorio_informativo_<?php echo $this->data['informativo']['id']; ?>_<?php echo date('Y-m-d_H-i-s'); ?>.xlsx';
        XLSX.writeFile(wb, fileName);
    } catch (e) {
        const msg = (e && e.message) ? e.message : String(e);
        alert('Falha ao gerar o Excel. Tente novamente.\n\nDetalhe: ' + msg);
    } finally {
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = originalBtnHTML;
        }
    }
}
</script>
