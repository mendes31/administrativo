<?php
use App\adms\Helpers\CSRFHelper;
?>

<div class="container-fluid px-4">
    <h1 class="mt-4 mobile-hide-page-title">Relatório de Política Interna</h1>

    <ol class="breadcrumb mb-4 mobile-hide-breadcrumb">
        <li class="breadcrumb-item"><a href="<?php echo $_ENV['URL_ADM']; ?>dashboard">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="<?php echo $_ENV['URL_ADM']; ?>list-policies">Políticas Internas</a></li>
        <li class="breadcrumb-item"><a href="<?php echo $_ENV['URL_ADM']; ?>relatorio-policy">Relatório</a></li>
        <li class="breadcrumb-item active">Resultado</li>
    </ol>

    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h4 class="mb-0">
                <i class="fas fa-file-contract me-2"></i>
                <?php echo htmlspecialchars($this->data['policy']['titulo'] ?? ''); ?>
            </h4>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <p><strong>Categoria:</strong> <?php echo htmlspecialchars($this->data['policy']['categoria_nome'] ?? $this->data['policy']['categoria'] ?? ''); ?></p>
                    <p><strong>Departamento:</strong> <?php echo htmlspecialchars($this->data['policy']['department_name'] ?? 'N/A'); ?></p>
                    <p><strong>Urgente:</strong>
                        <?php if (!empty($this->data['policy']['urgente'])): ?>
                            <span class="badge bg-danger">SIM</span>
                        <?php else: ?>
                            <span class="badge bg-secondary">NÃO</span>
                        <?php endif; ?>
                    </p>
                </div>
                <div class="col-md-6">
                    <p><strong>Exige Ciência:</strong>
                        <?php
                        $requiresAckVal = $this->data['policy']['requires_ack'] ?? null;
                        $requiresAck = ($requiresAckVal === 1 || $requiresAckVal === '1' || $requiresAckVal === true || $requiresAckVal === 'true' || $requiresAckVal === 'Sim' || $requiresAckVal === 'sim');
                        ?>
                        <?php if ($requiresAck): ?>
                            <span class="badge bg-warning text-dark">SIM</span>
                        <?php else: ?>
                            <span class="badge bg-secondary">NÃO</span>
                        <?php endif; ?>
                    </p>
                    <p><strong>Status:</strong>
                        <?php if (!empty($this->data['policy']['ativo'])): ?>
                            <span class="badge bg-success">ATIVA</span>
                        <?php else: ?>
                            <span class="badge bg-secondary">INATIVA</span>
                        <?php endif; ?>
                    </p>
                    <p><strong>Criada em:</strong> <?php echo date('d/m/Y H:i:s', strtotime($this->data['policy']['created_at'])); ?></p>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-md-6">
            <a href="<?php echo $_ENV['URL_ADM']; ?>relatorio-policy" class="btn btn-secondary d-none d-md-inline-block">
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
            <a href="<?php echo $_ENV['URL_ADM']; ?>export-relatorio-policy-pdf?policy_id=<?php echo (int)$this->data['policy']['id']; ?>"
               class="btn btn-outline-danger btn-sm d-inline-flex align-items-center gap-2"
               title="Baixar relatório em PDF"
               onclick="event.preventDefault(); const q=(typeof getRelatorioPolicyUserFilterValue === 'function') ? getRelatorioPolicyUserFilterValue() : ''; const href=this.getAttribute('href'); if (q) { window.location.href = href + '&usuario_filter=' + encodeURIComponent(q); } else { window.location.href = href; }">
                <i class="fas fa-file-pdf me-1"></i>
                PDF
            </a>
            <a href="<?php echo $_ENV['URL_ADM']; ?>export-relatorio-policy-excel?policy_id=<?php echo (int)$this->data['policy']['id']; ?>"
               class="btn btn-outline-success btn-sm d-inline-flex align-items-center gap-2"
               title="Baixar relatório em Excel (.xlsx)"
               onclick="event.preventDefault(); const q=(typeof getRelatorioPolicyUserFilterValue === 'function') ? getRelatorioPolicyUserFilterValue() : ''; const href=this.getAttribute('href'); if (q) { window.location.href = href + '&usuario_filter=' + encodeURIComponent(q); } else { window.location.href = href; }">
                <i class="fas fa-file-excel me-1"></i>
                <span>Excel (XLSX)</span>
            </a>
        </div>
    </div>

    <?php
    $requiresAckCard = $requiresAck;
    ?>
    <div class="row mt-0 mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body text-center">
                    <h3><?php echo count($this->data['dados_relatorio']); ?></h3>
                    <p class="mb-0">Total de Usuários</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body text-center">
                    <h3><?php echo count(array_filter($this->data['dados_relatorio'], fn($d) => $d['visualizou'] === 'SIM')); ?></h3>
                    <p class="mb-0">Visualizaram</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-dark">
                <div class="card-body text-center">
                    <h3><?php echo count(array_filter($this->data['dados_relatorio'], fn($d) => $d['status'] === 'PENDENTE')); ?></h3>
                    <p class="mb-0">Pendentes</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body text-center">
                    <h3>
                        <?php
                        if ($requiresAckCard) {
                            echo count(array_filter($this->data['dados_relatorio'], fn($d) => $d['status'] === 'CIENTE'));
                        } else {
                            echo 'N/A';
                        }
                        ?>
                    </h3>
                    <p class="mb-0">Cientes</p>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="fas fa-users me-1"></i>
                Status dos Usuários
            </h5>
        </div>
        <div class="card-body">
            <div class="mb-3">
                <label for="relatorioUserFilterPolicy" class="form-label mb-1">Filtrar por usuário</label>
                <input
                    type="text"
                    id="relatorioUserFilterPolicy"
                    class="form-control"
                    placeholder="Digite nome ou email"
                    oninput="filterRelatorioPolicyUsuarios()"
                >
            </div>
            <div class="table-responsive d-none d-md-block">
                <table class="table table-striped table-bordered" id="tabela-relatorio-policy">
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
                        <?php if (!empty($this->data['dados_relatorio'])): ?>
                            <?php foreach ($this->data['dados_relatorio'] as $dado): ?>
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
                                        <?php echo $dado['data_visualizacao']; ?>
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
                                        <?php echo $dado['data_ciencia']; ?>
                                    </td>
                                    <td class="text-start align-middle ps-3">
                                        <?php
                                        $statusClass = match($dado['status']) {
                                            'PENDENTE' => 'bg-danger',
                                            'VISUALIZOU' => 'bg-info',
                                            'VISUALIZOU MAS NÃO CIENTE' => 'bg-warning text-dark',
                                            'CIENTE' => 'bg-success',
                                            default => 'bg-secondary'
                                        };
                                        ?>
                                        <span class="badge <?php echo $statusClass; ?>">
                                            <?php echo htmlspecialchars($dado['status']); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center">Nenhum usuário encontrado</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Mobile: mini-cards (evita scroll lateral) -->
            <div class="d-block d-md-none">
                <?php if (!empty($this->data['dados_relatorio'])): ?>
                    <?php foreach ($this->data['dados_relatorio'] as $dado): ?>
                        <?php
                        $visualizouSim = ($dado['visualizou'] === 'SIM');
                        $estaCiente = $dado['esta_ciente'] ?? 'N/A';
                        $cieStatus = $estaCiente === 'N/A' ? 'bg-secondary' : ($estaCiente === 'SIM' ? 'bg-success' : 'bg-warning text-dark');
                        $statusClass = match($dado['status']) {
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
                                        Visualização em: <?php echo htmlspecialchars($dado['data_visualizacao']); ?>
                                    </small>
                                    <small class="text-muted d-block">
                                        Ciência em: <?php echo htmlspecialchars($dado['data_ciencia']); ?>
                                    </small>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="text-center text-muted py-3">Nenhum usuário encontrado</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
function getRelatorioPolicyUserFilterValue() {
    const input = document.getElementById('relatorioUserFilterPolicy');
    return (input?.value || '').toString().toLowerCase().trim();
}

function filterRelatorioPolicyUsuarios() {
    const q = getRelatorioPolicyUserFilterValue();

    // Desktop: filtra linhas da tabela
    const table = document.getElementById('tabela-relatorio-policy');
    if (table) {
        const tbodyRows = table.querySelectorAll('tbody tr');
        tbodyRows.forEach(tr => {
            const userStrong = tr.querySelector('td.ps-3 strong')?.textContent || '';
            const emailSmall = tr.querySelector('td.ps-3 small')?.textContent || '';
            const haystack = (userStrong + ' ' + emailSmall).toLowerCase();
            tr.style.display = (!q || haystack.includes(q)) ? '' : 'none';
        });
    }

    // Mobile: filtra cards
    const cards = document.querySelectorAll('.relatorio-user-card');
    cards.forEach(card => {
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

    // Sempre tenta carregar de novo para evitar “promise rejeitada” em cliques repetidos.
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

async function exportarExcelRelatorioPolicy() {
    const table = document.getElementById('tabela-relatorio-policy');
    if (!table) return;

    // Garante que o filtro já foi aplicado antes de exportar.
    if (typeof filterRelatorioPolicyUsuarios === 'function') {
        filterRelatorioPolicyUsuarios();
    }

    const btn = document.getElementById('btnExportExcelRelatorioPolicy');
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

        const fileName = 'relatorio_politica_<?php echo (int)$this->data['policy']['id']; ?>_<?php echo date('Y-m-d_H-i-s'); ?>.xlsx';
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

