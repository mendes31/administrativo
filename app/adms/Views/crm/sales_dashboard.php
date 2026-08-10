<?php
/** @var array $this->data */
$apiUrl = htmlspecialchars($this->data['api_url'] ?? '', ENT_QUOTES, 'UTF-8');
$syncUrl = htmlspecialchars($this->data['sync_url'] ?? '', ENT_QUOTES, 'UTF-8');
$canSync = !empty($this->data['can_sync']);
?>

<style>
.crm-sales-dash {
  --csd-green:#1B7A49; --csd-green-dark:#12532F; --csd-green-tint:#E7F4EC;
  --csd-orange:#E67E2E; --csd-orange-tint:#FDEEE0;
  --csd-red:#D14343; --csd-red-tint:#FBEAEA;
  --csd-blue:#2F6FDE; --csd-blue-tint:#E8F0FE;
  --csd-gray-mute:#D8DDD6; --csd-ink:#1B241E; --csd-ink-soft:#55605A; --csd-ink-mute:#8A9189;
  --csd-bg:#F1F4F1; --csd-surface:#FFFFFF; --csd-line:#E2E6E0;
  --csd-radius:14px;
  --csd-shadow:0 1px 2px rgba(18,30,22,0.05), 0 4px 16px rgba(18,30,22,0.06);
  color: var(--csd-ink); font-size:14px; line-height:1.5;
  max-width:1360px; margin:0 auto; padding:8px 4px 40px;
  position:relative;
}
.crm-sales-dash .csd-banner{
  background:linear-gradient(135deg,var(--csd-green) 0%, var(--csd-green-dark) 100%);
  border-radius:var(--csd-radius); padding:18px 22px; color:#fff;
  display:flex; align-items:center; justify-content:space-between; gap:16px;
  flex-wrap:wrap; margin-bottom:14px;
}
.crm-sales-dash .csd-banner h1{margin:0; font-size:1.25rem; font-weight:600;}
.crm-sales-dash .csd-banner p{margin:4px 0 0; font-size:13px; color:#DCEEE2;}
.crm-sales-dash .csd-periodo-tag{
  background:rgba(255,255,255,0.16); border-radius:20px; padding:7px 16px;
  font-size:12.5px; font-weight:500; white-space:nowrap;
}
.crm-sales-dash .csd-toolbar{
  background:var(--csd-surface); border:1px solid var(--csd-line); border-radius:var(--csd-radius);
  box-shadow:var(--csd-shadow); padding:14px 16px; display:flex; gap:12px;
  flex-wrap:wrap; align-items:flex-end; margin-bottom:12px;
}
.crm-sales-dash .csd-toolbar label{
  display:flex; flex-direction:column; gap:4px; font-size:11px; font-weight:600;
  color:var(--csd-ink-mute); text-transform:uppercase; letter-spacing:.03em; margin:0;
}
.crm-sales-dash .csd-toolbar select,
.crm-sales-dash .csd-toolbar input[type="date"]{
  font-family:inherit; font-size:13px; color:var(--csd-ink); border:1px solid var(--csd-line);
  background:var(--csd-bg); border-radius:8px; padding:8px 10px; min-width:140px; outline:none;
}
.crm-sales-dash .csd-toolbar select:focus,
.crm-sales-dash .csd-toolbar input[type="date"]:focus{border-color:var(--csd-green); box-shadow:0 0 0 3px var(--csd-green-tint);}
.crm-sales-dash .csd-custom-dates{display:none; gap:12px; flex-wrap:wrap; align-items:flex-end;}
.crm-sales-dash .csd-custom-dates.is-visible{display:flex;}
.crm-sales-dash .csd-toolbar button{
  border:1px solid var(--csd-line); background:var(--csd-bg); color:var(--csd-ink-soft);
  font-size:12.5px; font-weight:600; padding:9px 14px; border-radius:8px; cursor:pointer;
}
.crm-sales-dash .csd-toolbar button:hover{background:var(--csd-line);}
.crm-sales-dash .csd-toolbar button.csd-btn-primary{
  background:var(--csd-green); border-color:var(--csd-green); color:#fff;
}
.crm-sales-dash .csd-toolbar button.csd-btn-primary:hover{background:var(--csd-green-dark);}
.crm-sales-dash .csd-toolbar button:disabled{opacity:.55; cursor:not-allowed;}
.crm-sales-dash .csd-toolbar .csd-hint{
  margin-left:auto; font-size:12px; color:var(--csd-ink-mute); max-width:280px;
}
.crm-sales-dash .csd-sync-meta{
  font-size:12px; color:var(--csd-ink-mute); margin-bottom:12px;
}
.crm-sales-dash .csd-chips{display:flex; gap:8px; flex-wrap:wrap; margin-bottom:14px; min-height:1px;}
.crm-sales-dash .csd-chip{
  display:flex; align-items:center; gap:6px; background:var(--csd-orange-tint); color:#8A4413;
  border:1px solid #F3CFA3; font-size:12px; font-weight:600; padding:6px 8px 6px 12px; border-radius:20px;
}
.crm-sales-dash .csd-chip button{
  border:none; background:rgba(138,68,19,0.12); color:#8A4413; width:16px; height:16px;
  border-radius:50%; cursor:pointer; font-size:11px; line-height:1; display:flex;
  align-items:center; justify-content:center; padding:0;
}
.crm-sales-dash .csd-kpi-row{display:grid; grid-template-columns:repeat(4,1fr); gap:12px; margin-bottom:14px;}
.crm-sales-dash .csd-kpi{
  border-radius:var(--csd-radius); padding:14px 16px; box-shadow:var(--csd-shadow); border:1px solid var(--csd-line);
}
.crm-sales-dash .csd-kpi.c-green{background:var(--csd-green-tint); border-color:#CDE9D6;}
.crm-sales-dash .csd-kpi.c-red{background:var(--csd-red-tint); border-color:#F4CDCD;}
.crm-sales-dash .csd-kpi.c-orange{background:var(--csd-orange-tint); border-color:#F3CFA3;}
.crm-sales-dash .csd-kpi.c-blue{background:var(--csd-blue-tint); border-color:#C7DBFB;}
.crm-sales-dash .csd-kpi .label{font-size:12px; font-weight:600; margin:0 0 6px; color:var(--csd-ink-soft);}
.crm-sales-dash .csd-kpi .value{font-size:1.35rem; font-weight:700; margin:0; letter-spacing:-0.01em;}
.crm-sales-dash .csd-kpi .delta{font-size:12px; font-weight:600; margin-top:6px;}
.crm-sales-dash .csd-kpi.c-green .value,.crm-sales-dash .csd-kpi.c-green .delta{color:var(--csd-green-dark);}
.crm-sales-dash .csd-kpi.c-red .value,.crm-sales-dash .csd-kpi.c-red .delta{color:#8A2323;}
.crm-sales-dash .csd-kpi.c-orange .value,.crm-sales-dash .csd-kpi.c-orange .delta{color:#8A4413;}
.crm-sales-dash .csd-kpi.c-blue .value,.crm-sales-dash .csd-kpi.c-blue .delta{color:#1D4489;}
.crm-sales-dash .csd-grid{display:grid; grid-template-columns:1.6fr 1fr; gap:12px; margin-bottom:12px;}
.crm-sales-dash .csd-grid-2{display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-bottom:12px;}
.crm-sales-dash .csd-panel{
  background:var(--csd-surface); border:1px solid var(--csd-line); border-radius:var(--csd-radius);
  padding:16px 18px; box-shadow:var(--csd-shadow);
}
.crm-sales-dash .csd-panel h2{font-size:15px; font-weight:700; margin:0 0 2px;}
.crm-sales-dash .csd-panel .panel-sub{font-size:12px; color:var(--csd-ink-mute); margin:0 0 10px;}
.crm-sales-dash .csd-chart{position:relative; width:100%; cursor:pointer;}
.crm-sales-dash .csd-chart-scroll{
  max-height:300px; overflow-y:auto; overflow-x:hidden;
  border:1px solid var(--csd-line); border-radius:10px; background:#FAFBFA;
  scrollbar-width:thin;
}
.crm-sales-dash .csd-chart-scroll::-webkit-scrollbar{width:8px;}
.crm-sales-dash .csd-chart-scroll::-webkit-scrollbar-thumb{
  background:#C5CBC3; border-radius:8px;
}
.crm-sales-dash .csd-chart-inner{position:relative; width:100%; min-height:120px;}
.crm-sales-dash .csd-chart-empty{
  display:flex; align-items:center; justify-content:center; min-height:160px;
  padding:16px; text-align:center; color:var(--csd-ink-mute); font-size:13px;
  background:var(--csd-bg); border-radius:10px; border:1px dashed var(--csd-line);
}
.crm-sales-dash table{width:100%; border-collapse:collapse; font-size:13px;}
.crm-sales-dash thead th{
  text-align:left; font-size:11px; text-transform:uppercase; letter-spacing:.03em;
  color:var(--csd-ink-mute); font-weight:700; padding:0 8px 8px; border-bottom:1px solid var(--csd-line);
}
.crm-sales-dash tbody td{padding:9px 8px; border-bottom:1px solid var(--csd-bg); color:var(--csd-ink-soft);}
.crm-sales-dash tbody td.num{text-align:right; color:var(--csd-ink); font-weight:600;}
.crm-sales-dash tbody td.neg{color:var(--csd-red);}
.crm-sales-dash .name-cell{
  color:var(--csd-ink); font-weight:600;
  max-width:280px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;
}
.crm-sales-dash .bar-mini{height:5px; border-radius:3px; background:var(--csd-green-tint); margin-top:5px; overflow:hidden;}
.crm-sales-dash .bar-mini span{display:block; height:100%; background:var(--csd-green); border-radius:3px;}
.crm-sales-dash .table-scroll{max-height:320px; overflow-y:auto;}
.crm-sales-dash .csd-legend{display:flex; flex-wrap:wrap; gap:10px; font-size:12px; color:var(--csd-ink-soft); margin-bottom:8px;}
.crm-sales-dash .csd-legend .item{display:flex; align-items:center; cursor:pointer; padding:3px 6px; border-radius:6px;}
.crm-sales-dash .csd-legend .item:hover{background:var(--csd-bg);}
.crm-sales-dash .csd-legend .item.dim{opacity:.4;}
.crm-sales-dash .csd-legend .dot{width:9px; height:9px; border-radius:2px; display:inline-block; margin-right:5px;}
.crm-sales-dash .csd-note{margin-top:18px; font-size:11.5px; color:var(--csd-ink-mute); border-top:1px solid var(--csd-line); padding-top:12px;}
.crm-sales-dash.csd-loading{opacity:.55; pointer-events:none;}
.crm-sales-dash .csd-overlay{
  display:none; position:absolute; inset:0; z-index:5;
  background:rgba(241,244,241,0.55); border-radius:var(--csd-radius);
  align-items:flex-start; justify-content:center; padding-top:120px;
}
.crm-sales-dash.csd-loading .csd-overlay{display:flex;}
.crm-sales-dash .csd-spinner{
  background:var(--csd-surface); border:1px solid var(--csd-line); box-shadow:var(--csd-shadow);
  border-radius:12px; padding:14px 18px; font-size:13px; font-weight:600; color:var(--csd-ink-soft);
  display:flex; align-items:center; gap:10px;
}
.crm-sales-dash .csd-spinner::before{
  content:''; width:16px; height:16px; border-radius:50%;
  border:2px solid var(--csd-line); border-top-color:var(--csd-green);
  animation:csd-spin .7s linear infinite;
}
@keyframes csd-spin{to{transform:rotate(360deg);}}
.crm-sales-dash .csd-error{
  background:var(--csd-red-tint); border:1px solid #F4CDCD; color:#8A2323;
  border-radius:var(--csd-radius); padding:12px 14px; margin-bottom:12px; display:none;
}
.crm-sales-dash .csd-warn{
  background:var(--csd-orange-tint); border:1px solid #F3CFA3; color:#8A4413;
  border-radius:var(--csd-radius); padding:12px 14px; margin-bottom:12px; display:none;
}
@media (max-width:1000px){
  .crm-sales-dash .csd-grid,.crm-sales-dash .csd-grid-2{grid-template-columns:1fr;}
  .crm-sales-dash .csd-kpi-row{grid-template-columns:1fr 1fr;}
  .crm-sales-dash .csd-toolbar .csd-hint{margin-left:0; max-width:100%;}
}
@media (max-width:560px){
  .crm-sales-dash .csd-kpi-row{grid-template-columns:1fr;}
  .crm-sales-dash .csd-toolbar select,
  .crm-sales-dash .csd-toolbar input[type="date"]{min-width:100%; width:100%;}
}
</style>

<div class="container-fluid px-2 px-md-3">
  <?php include './app/adms/Views/partials/alerts.php'; ?>

  <div class="crm-sales-dash" id="crmSalesDash"
       data-api-url="<?= $apiUrl ?>"
       data-sync-url="<?= $syncUrl ?>"
       data-can-sync="<?= $canSync ? '1' : '0' ?>">
    <div class="csd-overlay" aria-live="polite"><div class="csd-spinner" id="csdSpinnerText">Carregando…</div></div>

    <div class="csd-banner">
      <div>
        <h1>Dashboard de vendas SAP</h1>
        <p>Faturamento líquido, devoluções e desempenho comercial · clique nos gráficos para filtrar</p>
      </div>
      <div class="csd-periodo-tag" id="periodoResumo">Carregando período…</div>
    </div>

    <div class="csd-error" id="csdError" role="alert"></div>
    <div class="csd-warn" id="csdWarn" role="status"></div>

    <div class="csd-toolbar">
      <label>Período
        <select id="fPeriodo">
          <option value="mes_atual">Mês atual</option>
          <option value="mes_anterior">Mês anterior</option>
          <option value="3">Últimos 3 meses</option>
          <option value="6">Últimos 6 meses</option>
          <option value="12" selected>Últimos 12 meses</option>
          <option value="24">Últimos 24 meses</option>
          <option value="36">Últimos 36 meses</option>
          <option value="ano_atual">Ano atual (YTD)</option>
          <option value="ano_anterior">Ano anterior</option>
          <option value="personalizado">Personalizado…</option>
        </select>
      </label>
      <div class="csd-custom-dates" id="customDates" aria-hidden="true">
        <label>Data início
          <input type="date" id="fDateFrom" autocomplete="off">
        </label>
        <label>Data fim
          <input type="date" id="fDateTo" autocomplete="off">
        </label>
      </div>
      <label>Vendedor
        <select id="fVendedor"><option value="">Todos</option></select>
      </label>
      <label>Grupo de cliente
        <select id="fGrupoCliente"><option value="">Todos</option></select>
      </label>
      <label>Região
        <select id="fRegiao"><option value="">Todas</option></select>
      </label>
      <button type="button" id="btnLimpar">Limpar tudo</button>
      <button type="button" id="btnAtualizar">Atualizar</button>
      <?php if ($canSync): ?>
      <button type="button" id="btnSyncSap" class="csd-btn-primary" title="Sincroniza apenas o incremento (últimos dias desde o último sync)">Atualizar agora (incremental)</button>
      <?php endif; ?>
      <span class="csd-hint">Clique em uma barra, fatia ou ponto para filtrar. Clique de novo para desfazer. No personalizado, use Atualizar.</span>
    </div>

    <div class="csd-sync-meta" id="csdSyncMeta">Cache: —</div>

    <div class="csd-chips" id="chipsRow"></div>

    <section class="csd-kpi-row">
      <div class="csd-kpi c-green">
        <p class="label">Faturamento líquido</p>
        <p class="value" id="kpiFaturamento">—</p>
        <p class="delta" id="kpiFaturamentoDelta"></p>
      </div>
      <div class="csd-kpi c-red">
        <p class="label">Devoluções</p>
        <p class="value" id="kpiDevolucao">—</p>
        <p class="delta" id="kpiDevolucaoDelta"></p>
      </div>
      <div class="csd-kpi c-orange">
        <p class="label">Taxa de devolução</p>
        <p class="value" id="kpiTaxaDevolucao">—</p>
        <p class="delta" id="kpiTaxaDevolucaoDelta"></p>
      </div>
      <div class="csd-kpi c-blue">
        <p class="label">Ticket médio</p>
        <p class="value" id="kpiTicket">—</p>
        <p class="delta" id="kpiTicketDelta"></p>
      </div>
    </section>

    <section class="csd-grid">
      <div class="csd-panel">
        <h2>Evolução mensal</h2>
        <p class="panel-sub">Faturamento líquido · clique em um ponto para isolar o mês</p>
        <div class="csd-chart" style="height:250px;">
          <canvas id="chartEvolucao" aria-label="Evolução mensal do faturamento líquido"></canvas>
        </div>
      </div>
      <div class="csd-panel">
        <h2>Vendas por grupo de cliente</h2>
        <p class="panel-sub">Participação no faturamento líquido</p>
        <div id="legendGrupo" class="csd-legend"></div>
        <div class="csd-chart" style="height:190px;">
          <canvas id="chartGrupoCliente" aria-label="Participação por grupo de cliente"></canvas>
        </div>
      </div>
    </section>

    <section class="csd-grid-2">
      <div class="csd-panel">
        <h2>Top vendedores</h2>
        <p class="panel-sub">Faturamento líquido · top 10 visíveis, role para ver mais</p>
        <div class="csd-chart csd-chart-scroll">
          <div class="csd-chart-inner" style="height:300px;">
            <canvas id="chartVendedores" aria-label="Faturamento por vendedor"></canvas>
          </div>
        </div>
      </div>
      <div class="csd-panel">
        <h2>Vendas por região</h2>
        <p class="panel-sub">Por UF · top 10 visíveis, role para ver mais</p>
        <div class="csd-chart csd-chart-scroll">
          <div class="csd-chart-inner" style="height:300px;">
            <canvas id="chartRegiao" aria-label="Faturamento por região"></canvas>
          </div>
        </div>
      </div>
    </section>

    <section class="csd-grid">
      <div class="csd-panel">
        <h2>Top clientes</h2>
        <p class="panel-sub">Ordenado por faturamento líquido</p>
        <div class="table-scroll">
          <table>
            <thead><tr><th>Cliente</th><th>Grupo</th><th style="text-align:right;">Líquido</th><th style="text-align:right;">Devolução</th></tr></thead>
            <tbody id="tblClientes"></tbody>
          </table>
        </div>
      </div>
      <div class="csd-panel">
        <h2>Vendas por grupo de item</h2>
        <p class="panel-sub">Participação · top 10 visíveis, role para ver mais</p>
        <div class="csd-chart csd-chart-scroll">
          <div class="csd-chart-inner" style="height:300px;">
            <canvas id="chartGrupoItem" aria-label="Faturamento por grupo de item"></canvas>
          </div>
        </div>
      </div>
    </section>

    <footer class="csd-note">
      Fonte: cache MySQL sincronizado a partir do SAP Business One (HANA). Preferência pela VIEW <code>VW_CRM_VENDAS_LINHA</code> no sync; se indisponível, o sync usa CTE com filtro de data.
      Painel: <strong id="csdSource">MySQL</strong>. Este painel é independente do Dashboard CRM de pipeline.
    </footer>
  </div>
</div>

<script src="<?= htmlspecialchars($_ENV['URL_ADM'] ?? '', ENT_QUOTES, 'UTF-8') ?>public/adms/vendor/chartjs/chart.umd.min.js"></script>
<script src="<?= htmlspecialchars($_ENV['URL_ADM'] ?? '', ENT_QUOTES, 'UTF-8') ?>public/adms/js/crm/sales-dashboard.js?v=6"></script>
