<?php
/** @var array $this->data */
$apiUrl = htmlspecialchars($this->data['api_url'] ?? '', ENT_QUOTES, 'UTF-8');
$syncUrl = htmlspecialchars($this->data['sync_url'] ?? '', ENT_QUOTES, 'UTF-8');
$accountsUrl = htmlspecialchars($this->data['accounts_url'] ?? '', ENT_QUOTES, 'UTF-8');
$investmentsUrl = htmlspecialchars($this->data['investments_url'] ?? '', ENT_QUOTES, 'UTF-8');
$canSync = !empty($this->data['can_sync']);
$canAccounts = !empty($this->data['can_accounts']);
$canInvestments = !empty($this->data['can_investments']);
$year = (int) date('Y');
$month = (int) date('n');
?>
<style>
.fcf-dash{
  --fcf-green:#1B7A49; --fcf-green-dark:#12532F; --fcf-green-tint:#E7F4EC;
  --fcf-orange:#E67E2E; --fcf-orange-tint:#FDEEE0;
  --fcf-red:#D14343; --fcf-red-tint:#FBEAEA;
  --fcf-blue:#2F6FDE; --fcf-blue-tint:#E8F0FE;
  --fcf-ink:#1B241E; --fcf-ink-soft:#55605A; --fcf-ink-mute:#8A9189;
  --fcf-bg:#F1F4F1; --fcf-surface:#FFFFFF; --fcf-line:#E2E6E0;
  --fcf-radius:14px;
  --fcf-shadow:0 1px 2px rgba(18,30,22,0.05), 0 4px 16px rgba(18,30,22,0.06);
  color:var(--fcf-ink); font-size:14px; line-height:1.5;
  max-width:1700px; margin:0 auto; padding:8px 4px 40px; position:relative;
}
.fcf-dash .fcf-banner{
  background:linear-gradient(135deg,var(--fcf-green) 0%, var(--fcf-green-dark) 100%);
  border-radius:var(--fcf-radius); padding:18px 22px; color:#fff;
  display:flex; align-items:center; justify-content:space-between; gap:16px; flex-wrap:wrap; margin-bottom:14px;
}
.fcf-dash .fcf-banner h1{margin:0; font-size:1.25rem; font-weight:600;}
.fcf-dash .fcf-banner p{margin:4px 0 0; font-size:13px; color:#DCEEE2;}
.fcf-dash .fcf-actions{display:flex; gap:8px; flex-wrap:wrap; align-items:center;}
.fcf-dash .fcf-actions a,.fcf-dash .fcf-actions button{
  border:1px solid rgba(255,255,255,.28); background:rgba(255,255,255,.12); color:#fff;
  font-size:12.5px; font-weight:600; padding:8px 13px; border-radius:8px; cursor:pointer; text-decoration:none;
}
.fcf-dash .fcf-actions button.primary{background:#fff; color:var(--fcf-green-dark); border-color:#fff;}
.fcf-dash .fcf-actions a:hover,.fcf-dash .fcf-actions button:hover{background:rgba(255,255,255,.22);}
.fcf-dash .fcf-actions button:disabled{opacity:.55; cursor:not-allowed;}
.fcf-dash .fcf-sync{font-size:11.5px; color:#DCEEE2; margin-top:8px; text-align:right;}
.fcf-dash .fcf-dot{width:7px; height:7px; border-radius:50%; background:#8EE0B0; display:inline-block; margin-right:5px;}
.fcf-dash .fcf-toolbar{
  background:var(--fcf-surface); border:1px solid var(--fcf-line); border-radius:var(--fcf-radius);
  box-shadow:var(--fcf-shadow); padding:14px 16px; display:grid;
  grid-template-columns:1.1fr 1fr 1.3fr 1fr 1fr auto; gap:12px; align-items:end; margin-bottom:12px;
}
.fcf-dash .fcf-toolbar label{
  display:flex; flex-direction:column; gap:4px; font-size:11px; font-weight:600;
  color:var(--fcf-ink-mute); text-transform:uppercase; letter-spacing:.03em; margin:0;
}
.fcf-dash .fcf-toolbar select,.fcf-dash .fcf-toolbar input{
  font-family:inherit; font-size:13px; color:var(--fcf-ink); border:1px solid var(--fcf-line);
  background:var(--fcf-bg); border-radius:8px; padding:8px 10px; outline:none; min-width:0;
}
.fcf-dash .fcf-toolbar button{
  border:1px solid var(--fcf-green); background:var(--fcf-green); color:#fff;
  font-size:12.5px; font-weight:600; padding:9px 14px; border-radius:8px; cursor:pointer; height:38px;
}
.fcf-dash .fcf-legend{display:flex; gap:18px; flex-wrap:wrap; font-size:12px; color:var(--fcf-ink-mute); margin:0 0 12px;}
.fcf-dash .fcf-sw{width:20px; height:0; border-top:3px solid var(--fcf-ink); display:inline-block; margin-right:6px; vertical-align:middle;}
.fcf-dash .fcf-sw.dashed{border-top:3px dashed var(--fcf-blue);}
.fcf-dash .fcf-kpis{display:grid; grid-template-columns:repeat(5,1fr); gap:11px; margin-bottom:12px;}
.fcf-dash .fcf-kpi{
  background:var(--fcf-surface); border:1px solid var(--fcf-line); border-radius:var(--fcf-radius);
  padding:14px 15px; box-shadow:var(--fcf-shadow); border-left:4px solid var(--fcf-blue);
}
.fcf-dash .fcf-kpi.pos{border-left-color:var(--fcf-green);}
.fcf-dash .fcf-kpi.neg{border-left-color:var(--fcf-red);}
.fcf-dash .fcf-kpi.warn{border-left-color:var(--fcf-orange);}
.fcf-dash .fcf-kpi.accent{background:var(--fcf-green-dark); border-color:var(--fcf-green-dark); color:#fff; border-left:0;}
.fcf-dash .kt{font-size:10.5px; text-transform:uppercase; letter-spacing:.04em; font-weight:700; color:var(--fcf-ink-mute);}
.fcf-dash .accent .kt{color:#CDE9D6;}
.fcf-dash .kv{font-size:20px; font-weight:800; margin-top:5px; letter-spacing:-.01em;}
.fcf-dash .kh{font-size:11px; color:var(--fcf-ink-mute); margin-top:3px;}
.fcf-dash .accent .kh{color:#DCEEE2;}
.fcf-dash .pos{color:var(--fcf-green)!important;} .fcf-dash .neg{color:var(--fcf-red)!important;}
.fcf-dash .tabs{display:flex; gap:6px; margin:12px 0 10px; flex-wrap:wrap;}
.fcf-dash .tab{padding:9px 14px; background:#E8EEF4; border-radius:8px; font-size:13px; font-weight:600; cursor:pointer; color:var(--fcf-ink-soft);}
.fcf-dash .tab.active{background:var(--fcf-green); color:#fff;}
.fcf-dash .card{
  background:var(--fcf-surface); border:1px solid var(--fcf-line); border-radius:var(--fcf-radius);
  box-shadow:var(--fcf-shadow); overflow:hidden; margin-bottom:12px;
}
.fcf-dash .cardh{padding:12px 16px; border-bottom:1px solid var(--fcf-line); font-weight:700; display:flex; justify-content:space-between; gap:12px; align-items:center;}
.fcf-dash .cardb{padding:14px 16px;}
.fcf-dash .grid2{display:grid; grid-template-columns:1.18fr .82fr; gap:12px;}
.fcf-dash .regimes{display:grid; grid-template-columns:1fr 1fr; gap:12px;}
.fcf-dash table{width:100%; border-collapse:collapse; font-size:12.5px;}
.fcf-dash th{background:#FAFBFD; color:var(--fcf-ink-mute); padding:8px 8px; text-align:right; font-size:10.8px; text-transform:uppercase; letter-spacing:.02em; border-bottom:1px solid var(--fcf-line);}
.fcf-dash td{padding:7px 8px; border-bottom:1px solid #EEF1F5; text-align:right; white-space:nowrap;}
.fcf-dash th:first-child,.fcf-dash td:first-child{text-align:left; font-weight:600;}
.fcf-dash tbody tr:hover td{background:#FAFBFD;}
.fcf-dash tfoot td{font-weight:700; background:#FAFBFD; border-top:2px solid var(--fcf-line);}
.fcf-dash .spos{background:var(--fcf-green-tint); color:var(--fcf-green); font-weight:800;}
.fcf-dash .sneg{background:var(--fcf-red-tint); color:var(--fcf-red); font-weight:800;}
.fcf-dash .drill{cursor:pointer; text-decoration:underline dotted; text-underline-offset:3px;}
.fcf-dash .today-row{outline:2px solid var(--fcf-green); outline-offset:-2px;}
.fcf-dash th.prev-col, .fcf-dash td.prev-col{border-left:2px dashed #CBD2DE;}
.fcf-dash .month th{background:var(--fcf-green); color:#fff; text-align:center;}
.fcf-dash .month th:first-child,.fcf-dash .month td:first-child{text-align:left; position:sticky; left:0; background:#fff; z-index:1;}
.fcf-dash .month th:first-child{background:var(--fcf-green);}
.fcf-dash .month td{min-width:96px;}
.fcf-dash .month th.cur{background:var(--fcf-green-dark);}
.fcf-dash .badge{font-size:10px; font-weight:700; text-transform:uppercase; padding:3px 8px; border-radius:20px;}
.fcf-dash .badge.banco{background:var(--fcf-blue-tint); color:var(--fcf-blue);}
.fcf-dash .badge.caixa{background:#FFF3DC; color:#9A6B00;}
.fcf-dash .badge.aplic{background:var(--fcf-green-tint); color:var(--fcf-green);}
.fcf-dash .hidden{display:none;}
.fcf-dash .note{font-size:12px; color:var(--fcf-ink-mute); background:var(--fcf-surface); border:1px dashed var(--fcf-line); border-radius:8px; padding:13px 15px; margin-top:8px;}
.fcf-dash .fcf-error,.fcf-dash .fcf-warn{
  border-radius:var(--fcf-radius); padding:12px 14px; margin-bottom:12px; display:none;
}
.fcf-dash .fcf-error{background:var(--fcf-red-tint); border:1px solid #F4CDCD; color:#8A2323;}
.fcf-dash .fcf-warn{background:var(--fcf-orange-tint); border:1px solid #F3CFA3; color:#8A4413;}
.fcf-dash.loading{opacity:.55; pointer-events:none;}
.fcf-dash .overlay{
  display:none; position:absolute; inset:0; z-index:5; background:rgba(241,244,241,.55);
  border-radius:var(--fcf-radius); align-items:flex-start; justify-content:center; padding-top:120px;
}
.fcf-dash.loading .overlay{display:flex;}
.fcf-dash .spinner{
  background:#fff; border:1px solid var(--fcf-line); box-shadow:var(--fcf-shadow);
  border-radius:12px; padding:14px 18px; font-size:13px; font-weight:600;
  display:flex; align-items:center; gap:10px;
}
.fcf-dash .spinner::before{
  content:''; width:16px; height:16px; border-radius:50%;
  border:2px solid var(--fcf-line); border-top-color:var(--fcf-green);
  animation:fcf-spin .7s linear infinite;
}
@keyframes fcf-spin{to{transform:rotate(360deg);}}
.fcf-drawer{display:none; position:fixed; inset:0; background:rgba(14,31,50,.38); z-index:40;}
.fcf-drawerbox{position:absolute; right:0; top:0; height:100%; width:min(640px,95vw); background:#fff; padding:20px; overflow:auto; box-shadow:-8px 0 24px rgba(0,0,0,.12);}
.fcf-drawerbox h2{margin-top:0;}
@media(max-width:1200px){.fcf-dash .fcf-kpis{grid-template-columns:repeat(3,1fr);} .fcf-dash .fcf-toolbar{grid-template-columns:repeat(3,1fr);}}
@media(max-width:900px){.fcf-dash .regimes,.fcf-dash .grid2{grid-template-columns:1fr;} .fcf-dash .fcf-kpis{grid-template-columns:repeat(2,1fr);}}
@media(max-width:650px){.fcf-dash .fcf-kpis,.fcf-dash .fcf-toolbar{grid-template-columns:1fr;}}
</style>

<div class="container-fluid px-2 px-md-3">
  <?php include './app/adms/Views/partials/alerts.php'; ?>

  <div class="fcf-dash" id="fcfDash"
       data-api-url="<?= $apiUrl ?>"
       data-sync-url="<?= $syncUrl ?>"
       data-can-sync="<?= $canSync ? '1' : '0' ?>">
    <div class="overlay"><div class="spinner" id="fcfSpinner">Carregando…</div></div>

    <div class="fcf-banner">
      <div>
        <h1>Fluxo de Caixa</h1>
        <p>Efetivo + previsto · visão diária e mensal · origem SAP Business One (cache local)</p>
      </div>
      <div>
        <div class="fcf-actions">
          <?php if ($canAccounts): ?>
            <a href="<?= $accountsUrl ?>">Contas financeiras</a>
          <?php endif; ?>
          <?php if ($canInvestments): ?>
            <a href="<?= $investmentsUrl ?>">Lançar aplicações</a>
          <?php endif; ?>
          <?php if ($canSync): ?>
            <button type="button" class="primary" id="btnSync">Sincronizar SAP</button>
          <?php endif; ?>
        </div>
        <div class="fcf-sync"><span class="fcf-dot"></span><span id="fcfSyncMeta">Aguardando sincronização…</span></div>
      </div>
    </div>

    <div class="fcf-error" id="fcfError" role="alert"></div>
    <div class="fcf-warn" id="fcfWarn" role="status"></div>

    <div class="fcf-toolbar">
      <label>Período
        <input type="month" id="fPeriodo" value="<?= sprintf('%04d-%02d', $year, $month) ?>">
      </label>
      <label>Filial
        <select id="fFilial"><option value="">Todas</option></select>
      </label>
      <label>Banco / Conta
        <select id="fConta"><option value="">Todas</option></select>
      </label>
      <label>Cenário
        <select id="fCenario">
          <option value="both">Efetivo + Previsto</option>
          <option value="ef">Somente Efetivo</option>
          <option value="pv">Somente Previsto</option>
        </select>
      </label>
      <label>Horizonte
        <select id="fHorizonte">
          <option value="7">7 dias</option>
          <option value="15">15 dias</option>
          <option value="30" selected>30 dias</option>
          <option value="60">60 dias</option>
          <option value="90">90 dias</option>
        </select>
      </label>
      <button type="button" id="btnAplicar">Aplicar filtros</button>
    </div>

    <div class="fcf-legend">
      <div><span class="fcf-sw"></span>Efetivo — já lançado no SAP</div>
      <div><span class="fcf-sw dashed"></span>Previsto — títulos em aberto por vencimento</div>
    </div>

    <div class="fcf-kpis" id="kpiRow1"></div>
    <div class="fcf-kpis" id="kpiRow2"></div>

    <div class="tabs">
      <div class="tab active" data-tab="daily">Fluxo Diário</div>
      <div class="tab" data-tab="monthly">Fluxo Mensal</div>
      <div class="tab" data-tab="accounts">Bancos / Contas</div>
      <div class="tab" data-tab="investments">Aplicações</div>
      <div class="tab" data-tab="detail">Detalhamento</div>
    </div>

    <section id="tab-daily">
      <div class="grid2">
        <div class="card">
          <div class="cardh">Saldo efetivo × saldo projetado <span class="kh">linha cheia = realizado · tracejada = projeção</span></div>
          <div class="cardb" style="height:250px;"><canvas id="chartLine"></canvas></div>
        </div>
        <div class="card">
          <div class="cardh">Receitas × despesas previstas <span class="kh">clique nos valores da tabela para detalhar</span></div>
          <div class="cardb" style="height:250px;"><canvas id="chartBars"></canvas></div>
        </div>
      </div>
      <div class="regimes">
        <div class="card">
          <div class="cardh">Valores efetivos <span class="badge banco">Realizado · SAP</span></div>
          <div style="overflow:auto;max-height:420px;"><table><thead><tr><th>Dia</th><th>Receita</th><th>Despesa</th><th>Saldo</th><th>Acumulado</th></tr></thead><tbody id="tblEf"></tbody><tfoot id="footEf"></tfoot></table></div>
        </div>
        <div class="card">
          <div class="cardh">Valores previstos <span class="badge aplic">Em aberto · SAP</span></div>
          <div style="overflow:auto;max-height:420px;"><table><thead><tr><th>Dia</th><th>Receita</th><th>Despesa</th><th>Saldo</th><th>Acumulado</th></tr></thead><tbody id="tblPv"></tbody><tfoot id="footPv"></tfoot></table></div>
        </div>
      </div>
      <div class="card">
        <div class="cardh">Visão combinada diária</div>
        <div style="overflow:auto;"><table id="tblCombo"><thead></thead><tbody></tbody><tfoot></tfoot></table></div>
      </div>
    </section>

    <section id="tab-monthly" class="hidden">
      <div class="card">
        <div class="cardh">Fluxo mensal consolidado — <span id="monthlyYear"><?= $year ?></span></div>
        <div style="overflow:auto;"><table class="month" id="tblMonth"></table></div>
      </div>
    </section>

    <section id="tab-accounts" class="hidden">
      <div class="card">
        <div class="cardh">Bancos e contas financeiras</div>
        <div style="overflow:auto;"><table><thead><tr><th>Conta</th><th>Banco</th><th>Tipo</th><th>Saldo atual</th><th>Aplicação</th><th>Limite</th><th>Disponibilidade</th></tr></thead><tbody id="tblAccounts"></tbody></table></div>
      </div>
    </section>

    <section id="tab-investments" class="hidden">
      <div class="card">
        <div class="cardh">Aplicações por banco — lançamentos locais + contas SAP classificadas
          <?php if ($canInvestments): ?><a class="btn btn-sm btn-success" href="<?= $investmentsUrl ?>">Novo lançamento</a><?php endif; ?>
        </div>
        <div class="cardb" id="invTables"></div>
      </div>
    </section>

    <section id="tab-detail" class="hidden">
      <div class="card">
        <div class="cardh">Títulos previstos em aberto no período</div>
        <div style="overflow:auto;max-height:480px;"><table><thead><tr><th>Vencimento</th><th>Tipo</th><th>Documento</th><th>Parceiro</th><th>Parcela</th><th>Saldo aberto</th></tr></thead><tbody id="tblDetail"></tbody></table></div>
      </div>
    </section>

    <div class="note">Os números vêm do cache local sincronizado com o SAP. Não é consulta em tempo real. Transferências entre contas financeiras são neutralizadas no consolidado. Aplicações, resgates e rendimentos são lançados neste sistema — o SAP hoje registra principalmente os rendimentos mensais.</div>
  </div>
</div>

<div id="fcfDrawer" class="fcf-drawer">
  <div class="fcf-drawerbox">
    <button type="button" class="btn btn-sm btn-light float-end" id="fcfCloseDrill">Fechar</button>
    <h2 id="drillTitle">Detalhamento</h2>
    <p class="kh" id="drillSub"></p>
    <div style="overflow:auto;"><table><thead id="drillHead"></thead><tbody id="drillBody"></tbody></table></div>
  </div>
</div>

<script src="<?= htmlspecialchars($_ENV['URL_ADM'] ?? '', ENT_QUOTES, 'UTF-8') ?>public/adms/vendor/chartjs/chart.umd.min.js"></script>
<script src="<?= htmlspecialchars($_ENV['URL_ADM'] ?? '', ENT_QUOTES, 'UTF-8') ?>public/adms/js/cashFlow/dashboard.js?v=1"></script>
