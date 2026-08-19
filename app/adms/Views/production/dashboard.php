<?php
/** @var array $this->data */
$apiUrl = htmlspecialchars($this->data['api_url'] ?? '', ENT_QUOTES, 'UTF-8');
$syncUrl = htmlspecialchars($this->data['sync_url'] ?? '', ENT_QUOTES, 'UTF-8');
$canSync = !empty($this->data['can_sync']);
?>
<style>
.prd-dash{
  --prd-green:#1B7A49; --prd-green-dark:#12532F; --prd-green-tint:#E7F4EC;
  --prd-orange:#E67E2E; --prd-orange-tint:#FDEEE0;
  --prd-red:#D14343; --prd-red-tint:#FBEAEA;
  --prd-blue:#2F6FDE; --prd-blue-tint:#E8F0FE;
  --prd-ink:#1B241E; --prd-ink-soft:#55605A; --prd-ink-mute:#8A9189;
  --prd-bg:#F1F4F1; --prd-surface:#FFFFFF; --prd-line:#E2E6E0;
  --prd-radius:14px;
  --prd-shadow:0 1px 2px rgba(18,30,22,0.05), 0 4px 16px rgba(18,30,22,0.06);
  color:var(--prd-ink); font-size:14px; line-height:1.5;
  max-width:1360px; margin:0 auto; padding:8px 4px 40px; position:relative;
}
.prd-dash .prd-banner{
  background:linear-gradient(135deg,var(--prd-green) 0%, var(--prd-green-dark) 100%);
  border-radius:var(--prd-radius); padding:18px 22px; color:#fff;
  display:flex; align-items:center; justify-content:space-between; gap:16px;
  flex-wrap:wrap; margin-bottom:14px;
}
.prd-dash .prd-banner h1{margin:0; font-size:1.25rem; font-weight:600;}
.prd-dash .prd-banner p{margin:4px 0 0; font-size:13px; color:#DCEEE2;}
.prd-dash .prd-periodo-tag{
  background:rgba(255,255,255,0.16); border-radius:20px; padding:7px 16px;
  font-size:12.5px; font-weight:500; white-space:nowrap;
}
.prd-dash .prd-toolbar{
  background:var(--prd-surface); border:1px solid var(--prd-line); border-radius:var(--prd-radius);
  box-shadow:var(--prd-shadow); padding:14px 16px; display:flex; gap:12px;
  flex-wrap:wrap; align-items:flex-end; margin-bottom:12px;
}
.prd-dash .prd-toolbar label{
  display:flex; flex-direction:column; gap:4px; font-size:11px; font-weight:600;
  color:var(--prd-ink-mute); text-transform:uppercase; letter-spacing:.03em; margin:0;
}
.prd-dash .prd-toolbar select,
.prd-dash .prd-toolbar input[type="date"]{
  font-family:inherit; font-size:13px; color:var(--prd-ink); border:1px solid var(--prd-line);
  background:var(--prd-bg); border-radius:8px; padding:8px 10px; min-width:140px; outline:none;
}
.prd-dash .prd-custom-dates{display:none; gap:12px; flex-wrap:wrap; align-items:flex-end;}
.prd-dash .prd-custom-dates.is-visible{display:flex;}
.prd-dash .prd-toolbar button{
  border:1px solid var(--prd-line); background:var(--prd-bg); color:var(--prd-ink-soft);
  font-size:12.5px; font-weight:600; padding:9px 14px; border-radius:8px; cursor:pointer;
}
.prd-dash .prd-toolbar button.prd-btn-primary{background:var(--prd-green); border-color:var(--prd-green); color:#fff;}
.prd-dash .prd-toolbar button:disabled{opacity:.55; cursor:not-allowed;}
.prd-dash .prd-sync-meta{font-size:12px; color:var(--prd-ink-mute); margin-bottom:12px;}
.prd-dash .prd-kpi-row{display:grid; grid-template-columns:repeat(4,1fr); gap:12px; margin-bottom:12px;}
.prd-dash .prd-kpi{
  background:var(--prd-surface); border:1px solid var(--prd-line); border-radius:var(--prd-radius);
  padding:14px 16px; box-shadow:var(--prd-shadow); border-left:4px solid var(--prd-blue);
}
.prd-dash .prd-kpi.c-green{border-left-color:var(--prd-green);}
.prd-dash .prd-kpi.c-orange{border-left-color:var(--prd-orange);}
.prd-dash .prd-kpi.c-red{border-left-color:var(--prd-red);}
.prd-dash .prd-kpi .label{font-size:12px; font-weight:600; margin:0 0 6px; color:var(--prd-ink-soft);}
.prd-dash .prd-kpi .value{font-size:1.35rem; font-weight:700; margin:0; letter-spacing:-0.01em;}
.prd-dash .prd-kpi .value small{font-size:13px; font-weight:600; color:var(--prd-ink-mute);}
.prd-dash .prd-kpi .delta{font-size:12px; font-weight:600; margin-top:6px;}
.prd-dash .prd-kpi .delta.up{color:var(--prd-green);}
.prd-dash .prd-kpi .delta.down{color:var(--prd-red);}
.prd-dash .prd-kpi .delta.flat{color:var(--prd-ink-mute);}
.prd-dash .prd-grid{display:grid; grid-template-columns:1.5fr 1fr; gap:12px; margin-bottom:12px;}
.prd-dash .prd-grid-2{display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-bottom:12px;}
.prd-dash .prd-panel{
  background:var(--prd-surface); border:1px solid var(--prd-line); border-radius:var(--prd-radius);
  padding:16px 18px; box-shadow:var(--prd-shadow); overflow:visible;
}
.prd-dash .prd-panel h2{font-size:15px; font-weight:700; margin:0 0 2px;}
.prd-dash .prd-panel .panel-sub{font-size:12px; color:var(--prd-ink-mute); margin:0 0 10px;}
.prd-dash .prd-chart{position:relative; width:100%; height:250px;}
.prd-dash .prd-legend{display:flex; flex-wrap:wrap; gap:12px; font-size:12px; color:var(--prd-ink-soft); margin-bottom:8px;}
.prd-dash .prd-legend .dot{width:9px; height:9px; border-radius:2px; display:inline-block; margin-right:5px;}
.prd-dash .prd-meter{display:flex; align-items:center; justify-content:space-between; gap:10px; padding:9px 0; border-bottom:1px solid var(--prd-line); font-size:12.5px;}
.prd-dash .prd-meter:last-child{border-bottom:0;}
.prd-dash .prd-track{height:8px; border-radius:4px; background:var(--prd-line); overflow:hidden; width:90px; display:inline-block; vertical-align:middle; margin-right:8px;}
.prd-dash .prd-fill{height:100%; border-radius:4px; background:var(--prd-blue);}
.prd-dash .prd-gauge-wrap{display:grid; place-items:center; margin:4px 0 16px;}
.prd-dash .prd-gauge{
  width:150px; aspect-ratio:1; border-radius:50%; display:grid; place-items:center;
  background:conic-gradient(var(--prd-orange) 0 0%, #e8ebf0 0); position:relative;
}
.prd-dash .prd-gauge::before{content:""; width:108px; aspect-ratio:1; border-radius:50%; background:#fff; position:absolute;}
.prd-dash .prd-gauge-value{z-index:1; text-align:center;}
.prd-dash .prd-gauge-value strong{display:block; font-size:28px; letter-spacing:-.04em;}
.prd-dash .prd-gauge-value span{color:var(--prd-ink-mute); font-size:11px;}
.prd-dash .prd-pill{display:inline-flex; align-items:center; gap:5px; font-size:11px; font-weight:700; padding:3px 8px; border-radius:999px;}
.prd-dash .prd-pill::before{content:""; width:6px; height:6px; border-radius:50%; background:currentColor;}
.prd-dash .prd-pill.running{color:var(--prd-blue); background:var(--prd-blue-tint);}
.prd-dash .prd-pill.late{color:var(--prd-red); background:var(--prd-red-tint);}
.prd-dash .prd-pill.done{color:var(--prd-green); background:var(--prd-green-tint);}
.prd-dash table{width:100%; border-collapse:collapse; font-size:13px; table-layout:auto;}
.prd-dash thead th{
  text-align:left; font-size:11px; text-transform:uppercase; letter-spacing:.03em;
  color:var(--prd-ink-mute); font-weight:700; padding:0 8px 8px; border-bottom:1px solid var(--prd-line);
}
.prd-dash tbody td{padding:9px 8px; border-bottom:1px solid var(--prd-bg); color:var(--prd-ink-soft); vertical-align:top;}
.prd-dash tbody td.num{text-align:right; color:var(--prd-ink); font-weight:600; white-space:nowrap;}
.prd-dash tbody td.wrap{white-space:normal; word-break:break-word; line-height:1.35;}
.prd-dash tbody td.compact{white-space:nowrap; font-size:12px;}
.prd-dash .table-scroll{
  max-height:320px; overflow-y:auto; overflow-x:auto; -webkit-overflow-scrolling:touch;
  width:100%; max-width:100%;
}
.prd-dash .table-scroll table.prd-table-orders{min-width:640px; width:max-content;}
.prd-dash .prd-table-skus col.col-sku{width:28%;}
.prd-dash .prd-table-skus col.col-item{width:52%;}
.prd-dash .prd-table-skus col.col-vol{width:20%;}
.prd-dash tbody td.nowrap{white-space:nowrap;}
.prd-dash .prd-note{margin-top:18px; font-size:11.5px; color:var(--prd-ink-mute); border-top:1px solid var(--prd-line); padding-top:12px;}
.prd-dash .prd-empty{
  display:flex; align-items:center; justify-content:center; min-height:120px;
  padding:16px; text-align:center; color:var(--prd-ink-mute); font-size:13px;
  background:var(--prd-bg); border-radius:10px; border:1px dashed var(--prd-line);
}
.prd-dash.prd-loading{opacity:.55; pointer-events:none;}
.prd-dash .prd-overlay{
  display:none; position:absolute; inset:0; z-index:5;
  background:rgba(241,244,241,0.55); border-radius:var(--prd-radius);
  align-items:flex-start; justify-content:center; padding-top:120px;
}
.prd-dash.prd-loading .prd-overlay{display:flex;}
.prd-dash .prd-spinner{
  background:var(--prd-surface); border:1px solid var(--prd-line); box-shadow:var(--prd-shadow);
  border-radius:12px; padding:14px 18px; font-size:13px; font-weight:600; color:var(--prd-ink-soft);
  display:flex; align-items:center; gap:10px;
}
.prd-dash .prd-spinner::before{
  content:''; width:16px; height:16px; border-radius:50%;
  border:2px solid var(--prd-line); border-top-color:var(--prd-green);
  animation:prd-spin .7s linear infinite;
}
@keyframes prd-spin{to{transform:rotate(360deg);}}
.prd-dash .prd-error,.prd-dash .prd-warn{
  border-radius:var(--prd-radius); padding:12px 14px; margin-bottom:12px; display:none;
}
.prd-dash .prd-error{background:var(--prd-red-tint); border:1px solid #F4CDCD; color:#8A2323;}
.prd-dash .prd-warn{background:var(--prd-orange-tint); border:1px solid #F3CFA3; color:#8A4413;}
@media (max-width:1000px){
  .prd-dash .prd-grid,.prd-dash .prd-grid-2{grid-template-columns:1fr;}
  .prd-dash .prd-kpi-row{grid-template-columns:1fr 1fr;}
}
@media (max-width:768px){
  .prd-dash{padding:4px 0 28px;}
  .prd-dash .prd-banner{padding:14px 16px; flex-direction:column; align-items:flex-start;}
  .prd-dash .prd-banner h1{font-size:1.15rem;}
  .prd-dash .prd-banner p{font-size:12px; line-height:1.45;}
  .prd-dash .prd-periodo-tag{align-self:stretch; text-align:center; white-space:normal;}
  .prd-dash .prd-toolbar{flex-direction:column; align-items:stretch; gap:10px;}
  .prd-dash .prd-toolbar label{width:100%;}
  .prd-dash .prd-toolbar select,
  .prd-dash .prd-toolbar input[type="date"]{min-width:0; width:100%; box-sizing:border-box;}
  .prd-dash .prd-toolbar button{width:100%;}
  .prd-dash .prd-custom-dates.is-visible{width:100%;}
  .prd-dash .prd-custom-dates.is-visible label{flex:1; min-width:0;}
  .prd-dash .prd-panel{padding:14px 12px;}
  .prd-dash .prd-panel .panel-sub{line-height:1.45;}
  .prd-dash .prd-chart{height:220px;}
  .prd-dash .table-scroll{margin:0; padding-bottom:4px; overflow-x:auto;}
  .prd-dash .table-scroll table.prd-table-orders{min-width:0; width:100%;}
  .prd-dash .prd-table-mobile-stack thead{display:none;}
  .prd-dash .prd-table-mobile-stack tbody tr{
    display:block; border:1px solid var(--prd-line); border-radius:10px;
    margin-bottom:8px; padding:6px 8px; background:var(--prd-bg);
  }
  .prd-dash .prd-table-mobile-stack tbody td{
    display:flex; justify-content:space-between; align-items:flex-start; gap:8px;
    padding:5px 0; border-bottom:0; font-size:11px; text-align:right;
  }
  .prd-dash .prd-table-mobile-stack tbody td::before{
    content:attr(data-label); font-weight:700; font-size:10px;
    text-transform:uppercase; letter-spacing:.02em; color:var(--prd-ink-mute);
    flex:0 0 38%; max-width:38%; text-align:left;
  }
  .prd-dash .prd-table-mobile-stack tbody td.num{font-weight:700;}
  .prd-dash .prd-table-mobile-stack .table-scroll{overflow-x:visible;}
  .prd-dash .prd-table-skus{table-layout:fixed; width:100%;}
  .prd-dash .prd-table-skus thead th,
  .prd-dash .prd-table-skus tbody td{padding:6px 4px; font-size:10.5px; line-height:1.35;}
  .prd-dash .prd-table-skus thead th:nth-child(1),
  .prd-dash .prd-table-skus tbody td:nth-child(1){width:26%;}
  .prd-dash .prd-table-skus thead th:nth-child(2),
  .prd-dash .prd-table-skus tbody td:nth-child(2){width:54%;}
  .prd-dash .prd-table-skus thead th:nth-child(3),
  .prd-dash .prd-table-skus tbody td:nth-child(3){width:20%;}
  .prd-dash .prd-pill{font-size:9px; padding:2px 5px;}
  .prd-dash .prd-meter{flex-wrap:wrap; gap:6px;}
  .prd-dash .prd-gauge{width:130px;}
  .prd-dash .prd-gauge::before{width:94px;}
  .prd-dash .prd-gauge-value strong{font-size:24px;}
}
@media (max-width:560px){
  .prd-dash .prd-kpi-row{grid-template-columns:1fr;}
  .prd-dash .prd-kpi .value{font-size:1.2rem;}
  .prd-dash .prd-kpi .delta{font-size:11px;}
  .prd-dash .prd-chart{height:200px;}
  .prd-dash .prd-table-mobile-stack tbody td{font-size:10.5px;}
  .prd-dash .prd-table-skus thead th,
  .prd-dash .prd-table-skus tbody td{font-size:10px; padding:5px 3px;}
  .prd-dash .prd-legend{gap:8px; font-size:11px;}
}
</style>

<div class="container-fluid px-2 px-md-3">
  <?php include './app/adms/Views/partials/alerts.php'; ?>

  <div class="prd-dash" id="prdDash"
       data-api-url="<?= $apiUrl ?>"
       data-sync-url="<?= $syncUrl ?>"
       data-can-sync="<?= $canSync ? '1' : '0' ?>">
    <div class="prd-overlay" aria-live="polite"><div class="prd-spinner" id="prdSpinnerText">Carregando…</div></div>

    <div class="prd-banner">
      <div>
        <h1>Dashboard de Produção</h1>
        <p>Unidades recebidas, produtos distintos, ordens e aderência · origem SAP/BEAS (cache local)</p>
      </div>
      <div class="prd-periodo-tag" id="periodoResumo">Carregando período…</div>
    </div>

    <div class="prd-error" id="prdError" role="alert"></div>
    <div class="prd-warn" id="prdWarn" role="status"></div>

    <div class="prd-toolbar">
      <label>Período
        <select id="fPeriodo">
          <option value="7">Últimos 7 dias</option>
          <option value="30" selected>Últimos 30 dias</option>
          <option value="90">Últimos 90 dias</option>
          <option value="mes_atual">Mês atual</option>
          <option value="mes_anterior">Mês anterior</option>
          <option value="ano_atual">Ano atual (YTD)</option>
          <option value="personalizado">Personalizado…</option>
        </select>
      </label>
      <div class="prd-custom-dates" id="customDates" aria-hidden="true">
        <label>Data início
          <input type="date" id="fDateFrom" autocomplete="off">
        </label>
        <label>Data fim
          <input type="date" id="fDateTo" autocomplete="off">
        </label>
      </div>
      <label>Depósito
        <select id="fLinha"><option value="">Ambos (TJQP + APQP)</option></select>
      </label>
      <button type="button" id="btnAtualizar">Atualizar</button>
      <?php if ($canSync): ?>
      <button type="button" id="btnSyncSap" class="prd-btn-primary" title="Sincroniza o incremento desde o último sync">Atualizar agora (incremental)</button>
      <?php endif; ?>
    </div>

    <div class="prd-sync-meta" id="prdSyncMeta">Cache: —</div>

    <section class="prd-kpi-row">
      <div class="prd-kpi c-green">
        <p class="label">SKUs produzidos</p>
        <p class="value" id="kpiSkus">—</p>
        <p class="delta" id="kpiSkusDelta"></p>
      </div>
      <div class="prd-kpi c-orange">
        <p class="label">Produtos produzidos</p>
        <p class="value" id="kpiProdutos">—</p>
        <p class="delta" id="kpiProdutosDelta"></p>
      </div>
      <div class="prd-kpi">
        <p class="label">Volume produzido</p>
        <p class="value" id="kpiVolume">—</p>
        <p class="delta" id="kpiVolumeDelta"></p>
      </div>
      <div class="prd-kpi c-green">
        <p class="label">Ordens iniciadas</p>
        <p class="value" id="kpiIniciadas">—</p>
        <p class="delta" id="kpiIniciadasDelta"></p>
      </div>
    </section>

    <section class="prd-kpi-row">
      <div class="prd-kpi c-green">
        <p class="label">Ordens concluídas</p>
        <p class="value" id="kpiConcluidas">—</p>
        <p class="delta" id="kpiConcluidasDelta"></p>
      </div>
      <div class="prd-kpi">
        <p class="label">Ordens em andamento</p>
        <p class="value" id="kpiAndamento">—</p>
        <p class="delta flat" id="kpiAndamentoHint">snapshot agora</p>
      </div>
      <div class="prd-kpi c-red">
        <p class="label">Ordens em atraso</p>
        <p class="value" id="kpiAtraso">—</p>
        <p class="delta" id="kpiAtrasoHint"></p>
      </div>
      <div class="prd-kpi c-orange">
        <p class="label">Aderência ao plano</p>
        <p class="value" id="kpiAderencia">—</p>
        <p class="delta" id="kpiAderenciaDelta"></p>
      </div>
    </section>

    <section class="prd-grid">
      <div class="prd-panel">
        <h2>SKUs e produtos por período</h2>
        <p class="panel-sub">Unidades recebidas (OIGN) e ItemCodes distintos no recorte mensal</p>
        <div class="prd-legend">
          <span><i class="dot" style="background:#2F6FDE"></i>SKUs (unidades)</span>
          <span><i class="dot" style="background:#E67E2E"></i>Produtos (códigos)</span>
        </div>
        <div class="prd-chart"><canvas id="chartSkus" aria-label="SKUs e produtos por mês"></canvas></div>
      </div>
      <div class="prd-panel">
        <h2>Realizado × planejado</h2>
        <p class="panel-sub">Quantidade recebida em estoque versus MENGE planejada da posição principal</p>
        <div class="prd-legend">
          <span><i class="dot" style="background:#12532F"></i>Realizado</span>
          <span><i class="dot" style="background:#C5CBC3"></i>Planejado</span>
        </div>
        <div class="prd-chart"><canvas id="chartVolume" aria-label="Volume realizado versus planejado"></canvas></div>
      </div>
    </section>

    <section class="prd-grid">
      <div class="prd-panel">
        <h2>Ordens iniciadas vs. concluídas</h2>
        <p class="panel-sub">Tendência no período</p>
        <div class="prd-legend">
          <span><i class="dot" style="background:#1B7A49"></i>Iniciadas</span>
          <span><i class="dot" style="background:#E67E2E"></i>Concluídas</span>
        </div>
        <div class="prd-chart"><canvas id="chartOrdens" aria-label="Ordens iniciadas e concluídas"></canvas></div>
      </div>
      <div class="prd-panel">
        <h2>Aderência por linha</h2>
        <p class="panel-sub">Concluído / planejado no período</p>
        <div id="metersLinhas"><div class="prd-empty">Sem dados no período.</div></div>
      </div>
    </section>

    <section class="prd-grid">
      <div class="prd-panel">
        <h2>Ordens em aberto</h2>
        <p class="panel-sub">Em produção e atrasadas (snapshot)</p>
        <div class="table-scroll">
          <table class="prd-table-orders prd-table-mobile-stack">
            <thead><tr><th>Ordem</th><th>SKU</th><th>Linha</th><th>Prazo</th><th>Progresso</th><th>Status</th></tr></thead>
            <tbody id="tblOrdens"></tbody>
          </table>
        </div>
      </div>
      <div class="prd-panel">
        <h2>Ranking de SKUs</h2>
        <p class="panel-sub">Maior volume concluído no período</p>
        <div class="table-scroll">
          <table class="prd-table-skus">
            <colgroup>
              <col class="col-sku">
              <col class="col-item">
              <col class="col-vol">
            </colgroup>
            <thead><tr><th>SKU</th><th>Item</th><th style="text-align:right;">Volume</th></tr></thead>
            <tbody id="tblSkus"></tbody>
          </table>
        </div>
      </div>
    </section>

    <section class="prd-grid">
      <div class="prd-panel">
        <h2>Eficiência global (OEE)</h2>
        <p class="panel-sub">Disponibilidade × performance × qualidade — V1.1</p>
        <div class="prd-gauge-wrap">
          <div class="prd-gauge" id="oeeGauge">
            <div class="prd-gauge-value"><strong id="kpiOee">—</strong><span>Aguardando apontamento BEAS</span></div>
          </div>
        </div>
        <div id="oeeComponents" class="prd-empty">Entra quando houver paradas e ciclo no cache.</div>
      </div>
      <div class="prd-panel">
        <h2>Indicadores de chão de fábrica</h2>
        <p class="panel-sub">Refugo, lead time e paradas</p>
        <div class="prd-meter"><span>Taxa de refugo</span><strong id="kpiRefugo">—</strong></div>
        <div class="prd-meter"><span>Lead time médio</span><strong id="kpiLead">—</strong></div>
        <div class="prd-meter"><span>Tempo de parada</span><strong id="kpiDowntime">—</strong></div>
        <p class="panel-sub" id="shopFloorNote" style="margin-top:12px;"></p>
      </div>
    </section>

    <footer class="prd-note">
      Fonte: cache MySQL sincronizado a partir do SAP Business One / BEAS (HANA).
      Produzido = entradas <code>OIGN</code>/<code>IGN1</code> ligadas ao BEAS (<code>U_beas_belnrid</code>),
      item <code>4%</code>, depósitos TJQP/APQP e posição <code>STUFE = 0</code>.
      Início real = primeiro apontamento em <code>BEAS_ARBZEIT</code>.
      Painel: <strong id="prdSource">MySQL</strong>. Custeio fabril permanece no módulo de Estoque/Custos.
    </footer>
  </div>
</div>

<script src="<?= htmlspecialchars($_ENV['URL_ADM'] ?? '', ENT_QUOTES, 'UTF-8') ?>public/adms/vendor/chartjs/chart.umd.min.js"></script>
<script src="<?= htmlspecialchars($_ENV['URL_ADM'] ?? '', ENT_QUOTES, 'UTF-8') ?>public/adms/js/production/dashboard.js?v=4"></script>
