<?php

use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\SstCategoriaAsoHelper;

$previsao = $this->data['previsao'] ?? [];
$itens = $previsao['itens'] ?? [];
$filters = $this->data['filters'] ?? [];
$mes = (string) ($filters['mes'] ?? $previsao['mes'] ?? '');
$mesLabel = (string) ($previsao['mes_label'] ?? '');
$total = (int) ($this->data['previsao_total'] ?? count($itens));
$pagination = $this->data['pagination'] ?? [];
$paginationSettings = $this->data['paginationSettings'] ?? ['per_page' => 10, 'options' => [10, 20, 50, 100]];
$csrfAbrir = CSRFHelper::generateCSRFToken('sst_abrir_aso_pendencia');
$queryBase = [
    'visao' => 'previsao',
    'mes' => $mes,
    'search' => (string) ($filters['search'] ?? ''),
    'adms_user_id' => (string) ($filters['adms_user_id'] ?? ''),
    'adms_department_id' => (string) ($filters['adms_department_id'] ?? ''),
    'per_page' => (int) ($this->data['per_page'] ?? 10),
];
$redirect = 'sst-list-asos?' . http_build_query($queryBase);
$csvUrl = $_ENV['URL_ADM'] . 'sst-list-asos?' . http_build_query($queryBase + ['export' => 'csv']);
$badge = static function (string $sit): string {
    return match ($sit) {
        'vencido' => 'danger',
        'a_vencer' => 'warning',
        'na_fila' => 'primary',
        default => 'info',
    };
};
?>
<p class="text-muted small mb-3 d-print-none">
    Relação completa de ASOs <strong>periódicos previstos</strong>, ordenada pela data prevista
    (validade do último ASO concluído ou realização + 12 meses). Use os filtros e a paginação para combinar com as lideranças.
</p>
<div class="d-md-none mb-2 d-print-none">
    <button class="btn btn-outline-primary btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#sstFiltersAsoPrev">
        <i class="fa fa-filter me-1"></i> Filtros
    </button>
</div>
<div class="collapse d-md-block d-print-none" id="sstFiltersAsoPrev">
    <form method="get" class="row g-2 mb-3 align-items-end">
        <input type="hidden" name="visao" value="previsao">
        <div class="col-6 col-sm-4 col-md-2">
            <label class="form-label" style="font-size:.7rem;" for="mes">Mês</label>
            <select name="mes" id="mes" class="form-select form-select-sm">
                <?php foreach ($this->data['meses_opcoes'] ?? [] as $opt): ?>
                    <option value="<?= htmlspecialchars($opt['value']) ?>" <?= $mes === $opt['value'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($opt['label']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-6 col-sm-4 col-md-3">
            <label class="form-label" style="font-size:.7rem;">Pesquisar</label>
            <input type="text" name="search" class="form-control form-control-sm" value="<?= htmlspecialchars((string) ($filters['search'] ?? '')) ?>">
        </div>
        <div class="col-6 col-sm-4 col-md-3">
            <label class="form-label" style="font-size:.7rem;" for="adms_department_id">Departamento</label>
            <select name="adms_department_id" id="adms_department_id" class="form-select form-select-sm">
                <option value="">Todos</option>
                <?php foreach ($this->data['departments'] ?? [] as $d): ?>
                    <option value="<?= (int) $d['id'] ?>" <?= ((string) ($filters['adms_department_id'] ?? '') === (string) $d['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars((string) ($d['name'] ?? '')) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-6 col-sm-4 col-md-3">
            <label class="form-label" style="font-size:.7rem;" for="adms_user_id_prev">Colaborador</label>
            <select name="adms_user_id" id="adms_user_id_prev" class="form-select form-select-sm">
                <option value="">Todos</option>
                <?php foreach ($this->data['users'] ?? [] as $u): ?>
                    <option value="<?= (int) $u['id'] ?>" <?= ((string) ($filters['adms_user_id'] ?? '') === (string) $u['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars((string) ($u['name'] ?? '')) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-auto">
            <label class="form-label" style="font-size:.7rem;" for="per_page_prev">Mostrar</label>
            <select name="per_page" id="per_page_prev" class="form-select form-select-sm" style="min-width:80px;" onchange="this.form.submit()">
                <?php foreach (($paginationSettings['options'] ?? [10, 20, 50, 100]) as $opt): ?>
                    <option value="<?= (int) $opt ?>" <?= (int) ($this->data['per_page'] ?? 10) === (int) $opt ? 'selected' : '' ?>><?= (int) $opt ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-12 col-sm-auto d-flex gap-2 flex-wrap">
            <button type="submit" class="btn btn-primary btn-sm"><i class="fa fa-search"></i> Filtrar</button>
            <a href="<?= $_ENV['URL_ADM']; ?>sst-list-asos?visao=previsao" class="btn btn-secondary btn-sm">Limpar</a>
            <a href="<?= htmlspecialchars($csvUrl) ?>" class="btn btn-outline-success btn-sm"><i class="fa fa-file-csv"></i> CSV</a>
            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="window.print()"><i class="fa fa-print"></i> Imprimir</button>
        </div>
    </form>
</div>

<?php if ($itens === []): ?>
    <div class="alert alert-info mb-0">
        Nenhum ASO periódico previsto<?= $mes !== '' ? ' para <strong>' . htmlspecialchars($mesLabel) . '</strong>' : '' ?>.
        Quem já está com solicitação aberta aparece na aba <em>Fila de resultados</em>.
    </div>
<?php else: ?>
    <p class="mb-3">
        <strong><?= $total ?></strong> colaborador(es) previstos<?= $mes !== '' ? ' em <strong>' . htmlspecialchars($mesLabel) . '</strong>' : '' ?>,
        ordenados pela data prevista.
        <?php if (!empty($pagination['first_item'])): ?>
            Mostrando <?= (int) $pagination['first_item'] ?> a <?= (int) $pagination['last_item'] ?>.
        <?php endif; ?>
    </p>
    <div class="table-responsive">
        <table class="table table-bordered table-striped table-hover table-sm">
            <thead>
                <tr>
                    <th>Previsto</th>
                    <th>Colaborador</th>
                    <th>Departamento</th>
                    <th>Cargo</th>
                    <th>Última realização</th>
                    <th>Situação</th>
                    <th class="text-center d-print-none">Ações</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($itens as $item):
                $userId = (int) ($item['adms_user_id'] ?? 0);
                $asoId = (int) ($item['ultimo_aso_id'] ?? 0);
                $aguardandoId = (int) ($item['aso_aguardando_id'] ?? 0);
                $encUrl = $_ENV['URL_ADM'] . 'sst-encaminhamento-aso?adms_user_id=' . $userId
                    . '&categoria=' . rawurlencode(SstCategoriaAsoHelper::PERIODICO);
                $podeEnc = in_array('SstEncaminhamentoAso', $perms, true)
                    || in_array('SstExportEncaminhamentoAsoPdf', $perms, true);
                $depNome = (string) ($item['departamento_nome'] ?? '');
                ?>
                <tr>
                    <td><?= !empty($item['previsto_em']) ? date('d/m/Y', strtotime((string) $item['previsto_em'])) : '-' ?></td>
                    <td><?= htmlspecialchars((string) ($item['colaborador_nome'] ?? '')) ?></td>
                    <td><?= htmlspecialchars($depNome !== '' ? $depNome : 'Sem departamento') ?></td>
                    <td><?= htmlspecialchars((string) ($item['cargo_nome'] ?: '-')) ?></td>
                    <td><?= !empty($item['data_realizacao']) ? date('d/m/Y', strtotime((string) $item['data_realizacao'])) : '-' ?></td>
                    <td>
                        <span class="badge text-bg-<?= $badge((string) ($item['situacao'] ?? '')) ?>">
                            <?= htmlspecialchars((string) ($item['situacao_label'] ?? '')) ?>
                        </span>
                    </td>
                    <td class="text-center d-print-none">
                        <div class="btn-group btn-group-sm">
                            <?php if ($podeEnc && $userId > 0): ?>
                                <a href="<?= htmlspecialchars($encUrl) ?>" class="btn btn-primary btn-sm" title="PDF encaminhamento"><i class="fas fa-file-pdf"></i></a>
                            <?php endif; ?>
                            <?php if ($aguardandoId > 0 && in_array('SstRegistrarResultadosAso', $perms, true)): ?>
                                <a href="<?= $_ENV['URL_ADM']; ?>sst-registrar-resultados-aso/<?= $aguardandoId ?>" class="btn btn-warning btn-sm" title="Registrar resultados"><i class="fas fa-clipboard-check"></i></a>
                            <?php elseif (in_array('SstAbrirAsoPendencia', $perms, true) && $userId > 0): ?>
                                <form action="<?= $_ENV['URL_ADM']; ?>sst-abrir-aso-pendencia" method="POST" class="d-inline"
                                      onsubmit="return confirm('Abrir solicitação de ASO periódico para este colaborador?');">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfAbrir) ?>">
                                    <input type="hidden" name="adms_user_id" value="<?= $userId ?>">
                                    <input type="hidden" name="categoria" value="<?= htmlspecialchars(SstCategoriaAsoHelper::PERIODICO) ?>">
                                    <input type="hidden" name="redirect" value="<?= htmlspecialchars($redirect) ?>">
                                    <button type="submit" class="btn btn-success btn-sm" title="Abrir solicitação"><i class="fas fa-folder-plus"></i></button>
                                </form>
                            <?php endif; ?>
                            <?php if (in_array('SstViewAso', $perms, true) && $asoId > 0): ?>
                                <a href="<?= $_ENV['URL_ADM']; ?>sst-view-aso/<?= $asoId ?>" class="btn btn-info btn-sm" title="Último ASO"><i class="fa-regular fa-eye"></i></a>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="d-flex justify-content-end mt-2 d-none d-md-flex d-print-none"><?= $pagination['html'] ?? '' ?></div>
    <div class="d-flex justify-content-center mt-2 d-md-none d-print-none"><?= $pagination['html'] ?? '' ?></div>
<?php endif; ?>
