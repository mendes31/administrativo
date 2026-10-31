<?php
/** @var array $this->data */
$aba = (string) ($this->data['aba'] ?? 'dashboard');
$reports = $this->data['reports'] ?? [];
$e = static fn ($v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
?>
<style>
.spj{
  --spj-green:#1B7A49; --spj-green-dark:#12532F; --spj-green-tint:#E7F4EC;
  --spj-orange:#E67E2E; --spj-orange-tint:#FDEEE0;
  --spj-red:#D14343; --spj-red-tint:#FBEAEA;
  --spj-blue:#2F6FDE; --spj-blue-tint:#E8F0FE;
  --spj-ink:#1B241E; --spj-ink-soft:#55605A; --spj-ink-mute:#8A9189;
  --spj-bg:#F1F4F1; --spj-surface:#FFFFFF; --spj-line:#E2E6E0;
  --spj-radius:14px;
  --spj-shadow:0 1px 2px rgba(18,30,22,0.05), 0 4px 16px rgba(18,30,22,0.06);
  color:var(--spj-ink); font-size:14px; line-height:1.5;
  max-width:1700px; margin:0 auto; padding:4px 4px 28px; position:relative;
}
.spj .spj-banner{
  background:linear-gradient(135deg,var(--spj-green) 0%, var(--spj-green-dark) 100%);
  border-radius:10px; padding:10px 14px; color:#fff;
  display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap; margin-bottom:8px;
}
.spj .spj-banner h1{margin:0; font-size:1.05rem; font-weight:600;}
.spj .spj-banner p{margin:2px 0 0; font-size:12px; color:#DCEEE2;}
.spj .spj-tag{background:rgba(255,255,255,.16); border-radius:20px; padding:4px 10px; font-size:11.5px; font-weight:500; white-space:nowrap;}
.spj .tabs{display:flex; gap:6px; margin:0 0 8px; flex-wrap:wrap;}
.spj .tab{padding:6px 12px; background:#E8EEF4; border-radius:8px; font-size:12.5px; font-weight:600; color:var(--spj-ink-soft); border:0; cursor:pointer;}
.spj .tab.active{background:var(--spj-green); color:#fff;}
.spj .toolbar{
  background:var(--spj-surface); border:1px solid var(--spj-line); border-radius:10px; box-shadow:var(--spj-shadow);
  padding:8px 10px; display:grid; grid-template-columns:repeat(auto-fit,minmax(150px,1fr)); gap:8px; align-items:end; margin-bottom:8px;
}
.spj .toolbar label{display:flex; flex-direction:column; gap:2px; font-size:10px; font-weight:600; color:var(--spj-ink-mute); text-transform:uppercase; letter-spacing:.03em; margin:0;}
.spj .toolbar label.off{opacity:.45;}
.spj .toolbar select,.spj .toolbar input{
  font-family:inherit; font-size:12px; color:var(--spj-ink); border:1px solid var(--spj-line);
  background:var(--spj-bg); border-radius:6px; padding:5px 7px; outline:none; min-width:0; height:30px;
}
.spj .toolbar select:focus,.spj .toolbar input:focus{border-color:var(--spj-green); box-shadow:0 0 0 2px var(--spj-green-tint);}
.spj .actions{display:flex; gap:6px; flex-wrap:wrap;}
.spj .btn-g,.spj .btn-o{font-size:12px; font-weight:600; padding:5px 12px; border-radius:6px; cursor:pointer; height:30px; white-space:nowrap;}
.spj .btn-g{border:1px solid var(--spj-green); background:var(--spj-green); color:#fff;}
.spj .btn-g:hover{background:var(--spj-green-dark);}
.spj .btn-o{border:1px solid var(--spj-line); background:#fff; color:var(--spj-ink-soft);}
.spj .btn-o:hover{border-color:var(--spj-green); color:var(--spj-green-dark);}
.spj .btn-o:disabled{opacity:.5; cursor:not-allowed;}
.spj .kpis{display:grid; grid-template-columns:repeat(6,1fr); gap:8px; margin-bottom:8px;}
.spj .kpi{background:var(--spj-surface); border:1px solid var(--spj-line); border-radius:10px; padding:8px 10px; box-shadow:var(--spj-shadow);}
.spj .kpi.c-green{background:var(--spj-green-tint); border-color:#CDE9D6;}
.spj .kpi.c-blue{background:var(--spj-blue-tint); border-color:#C7DBFB;}
.spj .kpi.c-alert{background:var(--spj-red-tint); border-color:#F4CDCD;}
.spj .kpi.c-warn{background:var(--spj-orange-tint); border-color:#F3CFA3;}
.spj .kt{font-size:10px; text-transform:uppercase; letter-spacing:.04em; font-weight:700; color:var(--spj-ink-mute);}
.spj .kv{font-size:17px; font-weight:800; margin-top:2px; letter-spacing:-.01em;}
.spj .kh{font-size:11px; color:var(--spj-ink-mute); margin-top:2px;}
.spj .kpi.c-alert .kv{color:#8A2323;} .spj .kpi.c-warn .kv{color:#8A4413;}
.spj .card{background:var(--spj-surface); border:1px solid var(--spj-line); border-radius:var(--spj-radius); box-shadow:var(--spj-shadow); overflow:hidden; margin-bottom:12px;}
.spj .cardh{padding:10px 14px; border-bottom:1px solid var(--spj-line); font-weight:700; display:flex; justify-content:space-between; gap:12px; align-items:center; flex-wrap:wrap;}
.spj .cardh small{font-weight:500; color:var(--spj-ink-mute);}
.spj .cardb{padding:12px 14px;}
.spj .charts{display:grid; grid-template-columns:1.3fr 1fr 1fr; gap:12px;}
.spj .chart-box{position:relative; height:260px;}
.spj .tbl-wrap{overflow:auto; max-height:70vh;}
.spj table{width:100%; border-collapse:collapse; font-size:12.5px;}
.spj th{position:sticky; top:0; z-index:1; background:#FAFBFD; color:var(--spj-ink-mute); padding:8px; text-align:left; font-size:10.8px; text-transform:uppercase; letter-spacing:.02em; border-bottom:1px solid var(--spj-line); white-space:nowrap;}
.spj td{padding:7px 8px; border-bottom:1px solid #EEF1F5; vertical-align:top;}
.spj td.n,.spj th.n{text-align:right; white-space:nowrap;}
.spj tbody tr:hover td{background:#FAFBFD;}
.spj tr.click{cursor:pointer;}
.spj tr.excl td{color:var(--spj-ink-mute); text-decoration:line-through; background:#F7F7F7;}
.spj tfoot td{font-weight:800; background:var(--spj-green-tint); border-top:2px solid #CDE9D6;}
.spj .badge-w{display:inline-block; font-size:10px; font-weight:700; padding:2px 7px; border-radius:20px; background:var(--spj-orange-tint); color:#8A4413; text-decoration:none;}
.spj .badge-r{display:inline-block; font-size:10px; font-weight:700; padding:2px 7px; border-radius:20px; background:var(--spj-red-tint); color:#8A2323;}
.spj .msg{border-radius:10px; padding:10px 14px; margin-bottom:8px; display:none; white-space:pre-wrap;}
.spj .msg.err{background:var(--spj-red-tint); border:1px solid #F4CDCD; color:#8A2323;}
.spj .msg.warn{background:var(--spj-orange-tint); border:1px solid #F3CFA3; color:#8A4413;}
.spj .msg.info{background:var(--spj-blue-tint); border:1px solid #C7DBFB; color:#1D4489;}
.spj .muted{color:var(--spj-ink-mute);}
.spj .empty{padding:24px; text-align:center; color:var(--spj-ink-mute);}
.spj .pane{display:none;} .spj .pane.active{display:block;}
.spj.loading .overlay{display:flex;}
.spj .overlay{display:none; position:absolute; inset:0; z-index:20; background:rgba(241,244,241,.55); border-radius:var(--spj-radius); align-items:flex-start; justify-content:center; padding-top:140px;}
.spj .spinner{background:#fff; border:1px solid var(--spj-line); box-shadow:var(--spj-shadow); border-radius:12px; padding:14px 18px; font-size:13px; font-weight:600; display:flex; align-items:center; gap:10px;}
.spj .spinner::before{content:''; width:16px; height:16px; border-radius:50%; border:2px solid var(--spj-line); border-top-color:var(--spj-green); animation:spj-spin .7s linear infinite;}
@keyframes spj-spin{to{transform:rotate(360deg);}}
.spj-drawer-bg{position:fixed; inset:0; background:rgba(18,30,22,.35); z-index:1050; display:none;}
.spj-drawer{position:fixed; top:0; right:0; bottom:0; width:min(1100px,96vw); background:#F1F4F1; z-index:1051; display:none; flex-direction:column; box-shadow:-8px 0 24px rgba(0,0,0,.15);}
.spj-drawer.open,.spj-drawer-bg.open{display:flex;}
.spj-drawer .dh{background:#1B7A49; color:#fff; padding:12px 16px; display:flex; justify-content:space-between; align-items:center; gap:12px;}
.spj-drawer .dh h2{font-size:1rem; margin:0; font-weight:600;}
.spj-drawer .dh-actions{display:flex; gap:6px; align-items:center; flex-wrap:wrap;}
.spj-drawer .dh button{background:rgba(255,255,255,.18); border:0; color:#fff; border-radius:6px; padding:4px 10px; cursor:pointer;}
.spj-drawer .dh button:hover{background:rgba(255,255,255,.28);}
.spj-drawer .dh a.btn-pdf{background:#fff; color:var(--spj-green-dark); text-decoration:none; border-radius:6px; padding:4px 10px; font-size:12px; font-weight:700;}
.spj-drawer .dh a.btn-pdf[aria-disabled="true"]{opacity:.45; pointer-events:none;}
.spj-drawer .db{padding:12px 16px; overflow:auto; flex:1;}
.spj .ficha-meta{display:grid; grid-template-columns:repeat(auto-fit,minmax(140px,1fr)); gap:8px; margin-bottom:10px;}
.spj .ficha-meta .kpi{padding:6px 8px;}
.spj .ficha-hint{font-size:12px; color:var(--spj-ink-mute); margin:0 0 10px;}
.spj .id-card{background:#fff; border:1px solid var(--spj-line); border-radius:10px; padding:12px 14px; margin-bottom:10px;}
.spj .id-card h3{margin:0 0 8px; font-size:13px; font-weight:700; color:var(--spj-green-dark);}
.spj .id-grid{display:grid; grid-template-columns:repeat(auto-fit,minmax(200px,1fr)); gap:8px 14px;}
.spj .id-item .l{font-size:10px; text-transform:uppercase; letter-spacing:.03em; font-weight:700; color:var(--spj-ink-mute);}
.spj .id-item .v{font-size:13px; font-weight:600; margin-top:2px; word-break:break-word;}
.spj .id-obs{margin-top:10px; padding-top:10px; border-top:1px solid var(--spj-line); font-size:12.5px; color:var(--spj-ink-soft); white-space:pre-wrap;}
.spj .resumo-grid{display:grid; grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); gap:8px; margin-bottom:4px;}
.spj .resumo-item{background:#fff; border:1px solid var(--spj-line); border-radius:8px; padding:10px 12px;}
.spj .resumo-item .l{font-size:10px; text-transform:uppercase; letter-spacing:.03em; font-weight:700; color:var(--spj-ink-mute);}
.spj .resumo-item .v{font-size:14px; font-weight:700; margin-top:4px;}
.spj .resumo-item.warn-flag .v{color:#8A4413;}
.spj .resumo-item.tone-good{border-color:#B7DCC6; background:#F3FAF5;}
.spj .resumo-item.tone-good .v{color:#146C3A;}
.spj .resumo-item.tone-bad{border-color:#E8B4B4; background:#FDF5F5;}
.spj .resumo-item.tone-bad .v{color:#B42318;}
.spj .resumo-item.tone-neutral .v{color:var(--spj-ink);}
.spj .partner{border:1px solid var(--spj-line); border-radius:10px; background:#fff; margin-bottom:10px; overflow:hidden;}
.spj .partner > summary{list-style:none; cursor:pointer; display:flex; justify-content:space-between; gap:12px; align-items:center; flex-wrap:wrap; padding:10px 12px; background:var(--spj-green-tint); font-weight:700;}
.spj .partner > summary::-webkit-details-marker{display:none;}
.spj .partner > summary .sub{font-weight:500; color:var(--spj-ink-mute); font-size:12px;}
.spj .partner-body{padding:8px 12px 12px;}
.spj .doc-block{border-left:3px solid var(--spj-green); padding:6px 0 6px 10px; margin:8px 0;}
.spj .doc-block.excl{border-left-color:var(--spj-ink-mute); opacity:.75;}
.spj .doc-h{font-weight:700; font-size:12.5px; margin-bottom:4px;}
.spj .doc-meta{font-size:12px; color:var(--spj-ink-soft); margin-bottom:6px;}
.spj tr.group td{background:var(--spj-green-tint); font-weight:700; border-bottom:1px solid #CDE9D6; color:var(--spj-green-dark); padding:9px 8px;}
.spj tr.group:hover td{background:var(--spj-green-tint);}
@media (max-width:1200px){.spj .kpis{grid-template-columns:repeat(3,1fr);} .spj .charts{grid-template-columns:1fr;}}
@media (max-width:650px){.spj .kpis{grid-template-columns:1fr 1fr;}}
</style>
<div class="container-fluid px-2 px-md-3">
<div class="spj" id="spj-root">
  <div class="overlay"><div class="spinner">Consultando SAP…</div></div>
  <div class="spj-banner">
    <div>
      <h1>Projetos SAP B1</h1>
      <p>Gerenciamento de Projetos (OPMG) · valores sem impostos (base PMG4) · notas canceladas fora dos totais</p>
    </div>
    <span class="spj-tag" id="spj-stamp">Consulta ao vivo</span>
  </div>

  <div class="tabs">
    <?php if (!empty($this->data['can_dashboard'])): ?>
      <button type="button" class="tab <?= $aba === 'dashboard' ? 'active' : '' ?>" data-aba="dashboard">Dashboard</button>
    <?php endif; ?>
    <?php if (!empty($this->data['can_reports'])): ?>
      <button type="button" class="tab <?= $aba === 'relatorios' ? 'active' : '' ?>" data-aba="relatorios">Relatórios</button>
    <?php endif; ?>
  </div>

  <div class="msg err" id="spj-error"></div>
  <div class="msg warn" id="spj-warn"></div>

  <!-- ===================== DASHBOARD ===================== -->
  <section class="pane <?= $aba === 'dashboard' ? 'active' : '' ?>" id="pane-dashboard">
    <form class="toolbar" id="dash-form">
      <label>Status<select name="status" data-opt="status"><option value="">Todos</option></select></label>
      <label>Tipo<select name="tipo" data-opt="tipos"><option value="">Todos</option></select></label>
      <label>Cliente<select name="cardcode" data-opt="clientes"><option value="">Todos</option></select></label>
      <label>Filial<select name="filial" data-opt="filiais"><option value="">Todas</option></select></label>
      <label>Ano de início do projeto<select name="ano_projeto" data-opt="anos_projeto"><option value="">Todos</option></select></label>
      <label>Documento de<input type="date" name="data_de"></label>
      <label>Documento até<input type="date" name="data_ate"></label>
      <div class="actions">
        <button type="submit" class="btn-g">Aplicar</button>
        <button type="button" class="btn-o" id="dash-clear">Limpar filtros</button>
      </div>
    </form>
    <div class="msg info" id="dash-docnote">O período do documento filtra o gráfico por período. Cards, gráficos por status/ano de início e a tabela de projetos usam só os filtros de projeto.</div>

    <div class="kpis" id="dash-kpis"></div>

    <div class="charts">
      <div class="card"><div class="cardh">Valor vinculado <small>por data do documento (mês)</small></div><div class="cardb"><div class="chart-box"><canvas id="ch-periodo"></canvas></div></div></div>
      <div class="card"><div class="cardh">Projetos e valor <small>por status</small></div><div class="cardb"><div class="chart-box"><canvas id="ch-status"></canvas></div></div></div>
      <div class="card"><div class="cardh">Carteira <small>por início do projeto (ano)</small></div><div class="cardb"><div class="chart-box"><canvas id="ch-ano"></canvas></div></div></div>
    </div>

    <div class="card">
      <div class="cardh">Projetos <small>clique na linha para abrir o resumo e a ficha daquele projeto</small></div>
      <div class="tbl-wrap"><table id="dash-table"></table></div>
    </div>
  </section>

  <!-- ===================== RELATÓRIOS ===================== -->
  <section class="pane <?= $aba === 'relatorios' ? 'active' : '' ?>" id="pane-relatorios">
    <form class="toolbar" id="rep-form">
      <label>Relatório
        <select name="report" id="rep-select">
          <?php foreach ($reports as $key => $r): ?>
            <option value="<?= $e($key) ?>"><?= $e($r['title']) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <label data-f="agrupar">Agrupar por<select name="agrupar"><option value="mes">Mês</option><option value="ano">Ano</option></select></label>
      <label data-f="projeto">Projeto<select name="projeto" data-opt="projetos"><option value="">Todos</option></select></label>
      <label data-f="status">Status<select name="status" data-opt="status"><option value="">Todos</option></select></label>
      <label data-f="tipo">Tipo<select name="tipo" data-opt="tipos"><option value="">Todos</option></select></label>
      <label data-f="cardcode">Cliente<select name="cardcode" data-opt="clientes"><option value="">Todos</option></select></label>
      <label data-f="filial">Filial<select name="filial" data-opt="filiais"><option value="">Todas</option></select></label>
      <label data-f="ano_projeto">Ano início projeto<select name="ano_projeto" data-opt="anos_projeto"><option value="">Todos</option></select></label>
      <label data-f="data_de">Documento de<input type="date" name="data_de"></label>
      <label data-f="data_ate">Documento até<input type="date" name="data_ate"></label>
      <label data-f="fornecedor">Fornecedor<select name="fornecedor" data-opt="fornecedores"><option value="">Todos</option></select></label>
      <label data-f="item">Item (código)<input type="text" name="item" maxlength="50" autocomplete="off"></label>
      <div class="actions">
        <button type="submit" class="btn-g">Gerar</button>
        <button type="button" class="btn-o" id="rep-xlsx" disabled>Excel</button>
        <button type="button" class="btn-o" id="rep-csv" disabled>CSV</button>
      </div>
    </form>
    <div class="msg warn" id="rep-aviso"></div>
    <div class="card">
      <div class="cardh"><span id="rep-title">Selecione um relatório e clique em Gerar</span><small id="rep-meta"></small></div>
      <div class="tbl-wrap"><table id="rep-table"></table></div>
    </div>
  </section>
</div>
</div>

<div class="spj-drawer-bg" id="spj-drawer-bg"></div>
<div class="spj-drawer spj" id="spj-drawer" style="max-width:none; padding:0;">
  <div class="dh">
    <h2 id="drawer-title">Projeto</h2>
    <div class="dh-actions">
      <a class="btn-pdf" id="drawer-pdf" href="#" target="_blank" rel="noopener" aria-disabled="true">PDF da ficha</a>
      <button type="button" id="drawer-close">Fechar ✕</button>
    </div>
  </div>
  <div class="db">
    <p class="ficha-hint" id="drawer-hint">Ficha exclusiva do projeto selecionado na tabela (AbsEntry / Nº).</p>
    <div id="drawer-identificacao"></div>
    <div class="ficha-meta" id="drawer-meta"></div>
    <div class="tabs">
      <button type="button" class="tab active" data-dtab="resumo">Resumo financeiro</button>
      <button type="button" class="tab" data-dtab="parceiros">Por parceiro</button>
      <button type="button" class="tab" data-dtab="documentos">Documentos</button>
      <button type="button" class="tab" data-dtab="itens">Itens</button>
    </div>
    <div class="msg warn" id="drawer-aviso" style="display:none"></div>
    <div id="drawer-resumo" class="resumo-grid"></div>
    <div id="drawer-parceiros" style="display:none"></div>
    <div class="card" id="drawer-flat" style="display:none"><div class="tbl-wrap"><table id="drawer-table"></table></div></div>
  </div>
</div>

<script src="<?php echo $_ENV['URL_ADM']; ?>public/adms/vendor/chartjs/chart.umd.min.js"></script>
<script>
(function () {
  var API = <?= json_encode((string) ($this->data['api_url'] ?? ''), JSON_UNESCAPED_SLASHES) ?>;
  var REPORTS = <?= json_encode($reports, JSON_UNESCAPED_UNICODE) ?>;
  var FILTER_SCOPES = <?= json_encode($this->data['filter_scopes'] ?? [], JSON_UNESCAPED_UNICODE) ?>;
  var root = document.getElementById('spj-root');
  var charts = {};
  var pending = 0;
  var lastReport = null;
  var lastReportParams = null;
  var detail = null;
  var detailProjectId = 0;
  var detailLoaded = { summary: false, documents: false, items: false };
  var detailFetch = null;
  var dashLoaded = false;
  var palette = ['#1B7A49', '#2F6FDE', '#E67E2E', '#7B5EA7', '#D14343', '#1A8A8A'];

  function esc(v) {
    return String(v == null ? '' : v).replace(/[&<>"']/g, function (c) {
      return ({'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'})[c];
    });
  }
  function money(n) { return Number(n || 0).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' }); }
  function num(n, d) { return Number(n || 0).toLocaleString('pt-BR', { minimumFractionDigits: d, maximumFractionDigits: d }); }
  function brDate(iso) {
    if (!iso) return '';
    var p = String(iso).slice(0, 10).split('-');
    return p.length === 3 ? p[2] + '/' + p[1] + '/' + p[0] : iso;
  }
  function moneyTick(v) { return Number(v).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL', maximumFractionDigits: 0 }); }
  function show(id, msg) {
    var el = document.getElementById(id);
    el.style.display = msg ? 'block' : 'none';
    el.textContent = msg || '';
  }
  function busy(on) {
    pending += on ? 1 : -1;
    root.classList.toggle('loading', pending > 0);
  }
  function formParams(form) {
    var p = {};
    Array.prototype.forEach.call(form.elements, function (el) {
      if (!el.name || el.disabled) return;
      var v = String(el.value || '').trim();
      if (v !== '') p[el.name] = v;
    });
    return p;
  }
  function api(params) {
    var qs = new URLSearchParams(params).toString();
    busy(true);
    return fetch(API + (API.indexOf('?') >= 0 ? '&' : '?') + qs, { credentials: 'same-origin', headers: { 'Accept': 'application/json' } })
      .then(function (r) {
        return r.text().then(function (t) {
          var j;
          try { j = JSON.parse(t); } catch (e) { throw new Error('Resposta inválida do servidor (HTTP ' + r.status + ').'); }
          if (!j.success) throw new Error(j.error || 'Falha ao consultar o SAP.');
          return j;
        });
      })
      .finally(function () { busy(false); });
  }
  function stamp(j) {
    if (!j || !j.generated_at) return;
    var t = String(j.generated_at).slice(11, 16);
    document.getElementById('spj-stamp').textContent = 'Consultado no SAP às ' + t + (j.execution_time != null ? ' · ' + num(j.execution_time, 1) + 's' : '');
  }

  // ---------- células ----------
  function cell(col, row) {
    var v = row[col.key];
    switch (col.type) {
      case 'money': return '<td class="n">' + money(v) + '</td>';
      case 'int': return '<td class="n">' + (v == null || v === '' ? '' : esc(v)) + '</td>';
      case 'number': return '<td class="n">' + num(v, 2) + '</td>';
      case 'date': return '<td>' + esc(brDate(v)) + '</td>';
      case 'flag': return '<td>' + (v === 'Sim' ? '<span class="badge-w" title="Abrir e salvar o projeto no SAP para atualizar a aba Resumo">⚠ Sim</span>' : esc(v)) + '</td>';
      default:
        var extra = (col.key === 'TipoDocumento' && row._aviso) ? ' <span class="badge-w">verificar</span>' : '';
        return '<td>' + esc(v) + extra + '</td>';
    }
  }
  function renderTable(el, table, opts) {
    opts = opts || {};
    var cols = table.columns || [];
    if (!table.rows || !table.rows.length) {
      el.innerHTML = '<tbody><tr><td class="empty">Nenhum registro para os filtros informados.</td></tr></tbody>';
      return;
    }
    var groupByProject = !!opts.groupByProject;
    var h = '<thead><tr>' + cols.map(function (c) {
      return '<th class="' + (['money', 'int', 'number'].indexOf(c.type) >= 0 ? 'n' : '') + '">' + esc(c.label) + '</th>';
    }).join('') + '</tr></thead><tbody>';
    var lastGroup = null;
    table.rows.forEach(function (row, i) {
      if (groupByProject) {
        var g = String(row.NumProjeto != null ? row.NumProjeto : '') + '|' + String(row.NomeProjeto || '');
        if (g !== lastGroup) {
          lastGroup = g;
          var title = (row.NumProjeto != null ? 'Projeto ' + row.NumProjeto : 'Projeto')
            + (row.NomeProjeto ? ' — ' + row.NomeProjeto : '');
          h += '<tr class="group"><td colspan="' + cols.length + '">' + esc(title) + '</td></tr>';
        }
      }
      var cls = [];
      if (row._excluido) cls.push('excl');
      if (opts.onClick) cls.push('click');
      var chaveAttr = row.Chave != null ? ' data-chave="' + esc(row.Chave) + '"' : '';
      h += '<tr class="' + cls.join(' ') + '" data-i="' + i + '"' + chaveAttr
        + (row._excluido ? ' title="Nota cancelada: fora do total"' : '') + '>'
        + cols.map(function (c) { return cell(c, row); }).join('') + '</tr>';
    });
    h += '</tbody>';
    if (table.totals && Object.keys(table.totals).length) {
      h += '<tfoot><tr>' + cols.map(function (c, idx) {
        if (Object.prototype.hasOwnProperty.call(table.totals, c.key)) {
          var t = table.totals[c.key];
          return '<td class="n">' + (c.type === 'money' ? money(t) : num(t, 0)) + '</td>';
        }
        return '<td>' + (idx === 0 ? 'Total' : '') + '</td>';
      }).join('') + '</tr></tfoot>';
    }
    el.innerHTML = h;
    if (opts.onClick) {
      el.querySelectorAll('tbody tr.click').forEach(function (tr) {
        tr.addEventListener('click', function () {
          var idx = Number(tr.getAttribute('data-i'));
          var row = table.rows[idx];
          if (!row) return;
          if (row.Chave == null && tr.getAttribute('data-chave')) {
            row = Object.assign({}, row, { Chave: Number(tr.getAttribute('data-chave')) });
          }
          opts.onClick(row);
        });
      });
    }
  }

  // ---------- combos ----------
  function fillOptions(opts) {
    document.querySelectorAll('select[data-opt]').forEach(function (sel) {
      var list = opts[sel.getAttribute('data-opt')] || [];
      var first = sel.options[0] ? sel.options[0].outerHTML : '';
      var cur = sel.value;
      sel.innerHTML = first + list.map(function (o) {
        return '<option value="' + esc(o.value) + '"' + (o.value === cur ? ' selected' : '') + '>' + esc(o.label) + '</option>';
      }).join('');
    });
  }

  // ---------- dashboard ----------
  var DASH_COLS = [
    { key: 'NumProjeto', label: 'Nº', type: 'int' },
    { key: 'NomeProjeto', label: 'Nome', type: 'text' },
    { key: 'NomePN', label: 'Cliente', type: 'text' },
    { key: 'Status', label: 'Status', type: 'text' },
    { key: 'NivelRisco', label: 'Risco', type: 'text' },
    { key: 'DataInicio', label: 'Início', type: 'date' },
    { key: 'DataVencimento', label: 'Vencimento', type: 'date' },
    { key: 'DiasAtraso', label: 'Dias em atraso', type: 'int' },
    { key: 'QtdDocumentos', label: 'Docs', type: 'int' },
    { key: 'ValorVinculado', label: 'Valor vinculado', type: 'money' },
    { key: 'ResumoDesatualizado', label: 'Resumo desatualizado', type: 'flag' }
  ];
  function kpi(cls, title, value, hint, tip) {
    return '<div class="kpi ' + cls + '"' + (tip ? ' title="' + esc(tip) + '"' : '') + '><div class="kt">' + esc(title) + '</div><div class="kv">' + value + '</div><div class="kh">' + esc(hint) + '</div></div>';
  }
  function renderKpis(k) {
    document.getElementById('dash-kpis').innerHTML =
      kpi('c-green', 'Projetos', num(k.QtdProjetos, 0), k.QtdIniciados + ' iniciados') +
      kpi('c-green', 'Valor vinculado', money(k.ValorVinculado), 'sem impostos — base SAP') +
      kpi('c-blue', 'Valor total das notas', money(k.ValorTotalNotas), 'com impostos/despesas') +
      kpi('', 'Documentos', num(k.QtdDocumentos, 0), 'vinculados aos projetos') +
      kpi(k.QtdVencidos > 0 ? 'c-alert' : '', 'Projetos vencidos', num(k.QtdVencidos, 0), 'vencimento passado sem encerramento') +
      kpi(k.QtdResumoDesatualizado > 0 ? 'c-warn' : '', 'Resumo desatualizado', num(k.QtdResumoDesatualizado, 0), 'PMG8 ≠ notas vinculadas',
        'Abrir e salvar o projeto no SAP para atualizar a aba Resumo');
  }
  function barChart(id, labels, datasets, moneyAxis) {
    if (charts[id]) { charts[id].destroy(); }
    if (typeof Chart === 'undefined') return;
    charts[id] = new Chart(document.getElementById(id), {
      type: 'bar',
      data: { labels: labels, datasets: datasets },
      options: {
        responsive: true, maintainAspectRatio: false,
        plugins: {
          legend: { display: datasets.length > 1, position: 'bottom' },
          tooltip: { callbacks: { label: function (c) {
            var isMoney = c.dataset.yAxisID !== 'yq' && moneyAxis;
            return c.dataset.label + ': ' + (isMoney ? money(c.parsed.y) : num(c.parsed.y, 0));
          } } }
        },
        scales: (function () {
          var s = { y: { beginAtZero: true, ticks: { callback: moneyAxis ? moneyTick : undefined } } };
          if (datasets.some(function (d) { return d.yAxisID === 'yq'; })) {
            s.yq = { position: 'right', beginAtZero: true, grid: { drawOnChartArea: false }, ticks: { precision: 0 } };
          }
          return s;
        })()
      }
    });
  }
  function renderCharts(j) {
    barChart('ch-periodo', j.por_periodo.map(function (r) { return r.Periodo; }),
      [{ label: 'Valor no projeto', data: j.por_periodo.map(function (r) { return r.ValorNoProjeto; }), backgroundColor: palette[0] }], true);
    barChart('ch-status', j.por_status.map(function (r) { return r.Status; }), [
      { label: 'Valor vinculado', data: j.por_status.map(function (r) { return r.ValorVinculado; }), backgroundColor: palette[0] },
      { label: 'Projetos', data: j.por_status.map(function (r) { return r.QtdProjetos; }), backgroundColor: palette[1], yAxisID: 'yq' }
    ], true);
    barChart('ch-ano', j.por_ano_projeto.map(function (r) { return r.AnoInicio; }), [
      { label: 'Valor vinculado', data: j.por_ano_projeto.map(function (r) { return r.ValorVinculado; }), backgroundColor: palette[0] },
      { label: 'Projetos', data: j.por_ano_projeto.map(function (r) { return r.QtdProjetos; }), backgroundColor: palette[2], yAxisID: 'yq' }
    ], true);
  }
  function loadDashboard() {
    var params = formParams(document.getElementById('dash-form'));
    params.action = 'dashboard';
    show('spj-error', '');
    return api(params).then(function (j) {
      dashLoaded = true;
      stamp(j);
      show('spj-warn', (j.warnings || []).join('\n'));
      document.getElementById('dash-docnote').style.display = j.doc_filters_only ? 'block' : 'none';
      renderKpis(j.kpis);
      renderCharts(j);
      renderTable(document.getElementById('dash-table'), { columns: DASH_COLS, rows: j.projetos }, { onClick: openDetail });
    }).catch(function (e) { show('spj-error', e.message); });
  }

  // ---------- detalhe do projeto ----------
  function setPdfLink(projectId) {
    var a = document.getElementById('drawer-pdf');
    if (!projectId) {
      a.href = '#';
      a.setAttribute('aria-disabled', 'true');
      return;
    }
    var qs = new URLSearchParams({ action: 'pdf', projeto: String(projectId) }).toString();
    a.href = API + (API.indexOf('?') >= 0 ? '&' : '?') + qs;
    a.setAttribute('aria-disabled', 'false');
  }
  function formatFieldValue(f) {
    if (!f) return '—';
    if (f.type === 'money') {
      var m = money(f.value);
      if (f.tone === 'good' && Number(f.value) > 0) return '+' + m;
      return m;
    }
    if (f.type === 'int') return num(f.value, 0);
    if (f.type === 'number') return num(f.value, 2);
    if (f.type === 'date') return esc(brDate(f.value));
    return esc(f.value == null || f.value === '' ? '—' : f.value);
  }
  function renderDrawerIdentificacao(j) {
    var el = document.getElementById('drawer-identificacao');
    var p = (j && j.projeto) || {};
    var fields = (j && j.identificacao) || [];
    if (!fields.length && !p.Chave) {
      el.innerHTML = '';
      return;
    }
    var obsKeys = { ObservacoesVisaoGeral: 1, ObservacoesAba: 1 };
    var main = fields.filter(function (f) { return !obsKeys[f.key]; });
    var obs = fields.filter(function (f) { return obsKeys[f.key]; });
    var html = '<div class="id-card"><h3>Dados do projeto selecionado'
      + (p.Chave ? ' <small class="muted">(AbsEntry ' + esc(p.Chave) + ')</small>' : '')
      + '</h3><div class="id-grid">';
    main.forEach(function (f) {
      html += '<div class="id-item"><div class="l">' + esc(f.label) + '</div><div class="v">' + formatFieldValue(f) + '</div></div>';
    });
    html += '</div>';
    obs.forEach(function (f) {
      html += '<div class="id-obs"><strong>' + esc(f.label) + ':</strong> ' + formatFieldValue(f) + '</div>';
    });
    html += '</div>';
    el.innerHTML = html;
  }
  function renderDrawerMeta(j) {
    var p = (j && j.projeto) || {};
    var t = (j && j.totais) || {};
    document.getElementById('drawer-meta').innerHTML =
      kpi('', 'Nº / nome', esc(p.NumProjeto || '—'), esc(p.NomeProjeto || '')) +
      kpi('', 'Cliente', esc(p.NomePN || p.CodigoPN || '—'), esc(p.CodigoPN || '')) +
      kpi('c-green', 'Valor vinculado', money(p.ValorVinculado != null ? p.ValorVinculado : t.ValorNoProjeto),
        'NF ' + money(p.ValorNFEntrada || 0) + ' · outros ' + money(p.ValorOutrosVinculos || 0)
        + ' · ' + (t.QtdDocumentos || p.QtdDocumentos || 0) + ' doc(s)') +
      kpi(p.ResumoDesatualizado === 'Sim' ? 'c-warn' : 'c-blue', 'Resumo SAP',
        p.ResumoDesatualizado === 'Sim' ? 'Desatualizado' : 'Atualizado',
        money(p.ResumoFaturadoCP || 0) + ' faturado C/P (PMG8)');
  }
  function renderProjectResumo(resumo) {
    var el = document.getElementById('drawer-resumo');
    if (!resumo || !resumo.length) {
      el.innerHTML = '<div class="empty">Sem dados de resumo financeiro para este projeto.</div>';
      return;
    }
    el.innerHTML = resumo.map(function (f) {
      var cls = 'resumo-item';
      if (f.key === 'ResumoDesatualizado' && f.value === 'Sim') cls += ' warn-flag';
      if (f.tone === 'good' || f.tone === 'bad' || f.tone === 'neutral') cls += ' tone-' + f.tone;
      var hint = '';
      if (f.key === 'ResumoDesvioCP') {
        hint = f.tone === 'good' ? '<div class="l" style="margin-top:4px;font-weight:600;text-transform:none">Economia vs orçamento</div>'
          : (f.tone === 'bad' ? '<div class="l" style="margin-top:4px;font-weight:600;text-transform:none">Acima do orçamento</div>' : '');
      }
      return '<div class="' + cls + '"><div class="l">' + esc(f.label) + '</div><div class="v">' + formatFieldValue(f) + '</div>' + hint + '</div>';
    }).join('');
  }
  function itemCols() {
    return [
      { key: 'CodigoItem', label: 'Item', type: 'text' },
      { key: 'DescricaoItem', label: 'Descrição', type: 'text' },
      { key: 'Quantidade', label: 'Qtd', type: 'number' },
      { key: 'TotalLinha', label: 'Total linha', type: 'money' },
      { key: 'LinhaVinculada', label: 'Vinculada', type: 'text' }
    ];
  }
  function renderPartnerTree(parceiros) {
    var el = document.getElementById('drawer-parceiros');
    if (!parceiros || !parceiros.length) {
      el.innerHTML = '<div class="empty">Nenhum documento vinculado a este projeto.</div>';
      return;
    }
    var html = '';
    parceiros.forEach(function (partner, pi) {
      var title = partner.Codigo
        ? esc(partner.Codigo) + ' — ' + esc(partner.Nome || '')
        : esc(partner.Nome || 'Sem fornecedor');
      html += '<details class="partner"' + (pi < 3 ? ' open' : '') + '>'
        + '<summary><span>' + title + '</span>'
        + '<span class="sub">' + (partner.QtdDocumentos || 0) + ' doc(s) · '
        + money(partner.ValorNoProjeto) + '</span></summary><div class="partner-body">';
      (partner.documentos || []).forEach(function (doc) {
        var excl = !!doc._excluido;
        html += '<div class="doc-block' + (excl ? ' excl' : '') + '">'
          + '<div class="doc-h">' + esc(doc.TipoDocumento || 'Documento')
          + ' · NF ' + esc(doc.NumNF) + ' · Doc ' + esc(doc.NumDocumento)
          + (excl ? ' <span class="badge-w">cancelada</span>' : '') + '</div>'
          + '<div class="doc-meta">' + esc(brDate(doc.DataDocumento))
          + ' · no projeto ' + money(doc.ValorNoProjeto)
          + ' · total nota ' + money(doc.ValorTotalNota)
          + (doc.StatusDocumento ? ' · ' + esc(doc.StatusDocumento) : '') + '</div>';
        if (doc.itens && doc.itens.length) {
          var cols = itemCols();
          html += '<div class="tbl-wrap"><table><thead><tr>' + cols.map(function (c) {
            return '<th class="' + (['money', 'int', 'number'].indexOf(c.type) >= 0 ? 'n' : '') + '">' + esc(c.label) + '</th>';
          }).join('') + '</tr></thead><tbody>';
          doc.itens.forEach(function (row) {
            html += '<tr class="' + (row._excluido ? 'excl' : '') + '">'
              + cols.map(function (c) { return cell(c, row); }).join('') + '</tr>';
          });
          html += '</tbody></table></div>';
        } else {
          html += '<div class="muted" style="font-size:12px">Sem itens de linha nesta nota.</div>';
        }
        html += '</div>';
      });
      html += '</div></details>';
    });
    el.innerHTML = html;
  }
  function applyDetailPayload(j) {
    if (!detail) {
      detail = j;
    } else {
      ['projeto', 'identificacao', 'resumo', 'parceiros', 'totais', 'documentos', 'itens', 'aviso_itens'].forEach(function (k) {
        if (j[k] !== undefined) detail[k] = j[k];
      });
      detail.warnings = (detail.warnings || []).concat(j.warnings || []);
      detail.generated_at = j.generated_at || detail.generated_at;
    }
    var loaded = j.loaded || {};
    if (loaded.summary) detailLoaded.summary = true;
    if (loaded.documents) detailLoaded.documents = true;
    if (loaded.items) detailLoaded.items = true;
  }
  function withForTab(tab) {
    if (tab === 'resumo') return 'summary';
    if (tab === 'documentos') return 'documents';
    return 'documents,items';
  }
  function tabNeeds(tab) {
    if (tab === 'resumo') return detailLoaded.summary;
    if (tab === 'documentos') return detailLoaded.documents;
    return detailLoaded.items;
  }
  function ensureDetail(tab) {
    if (!detailProjectId) return Promise.reject(new Error('Projeto não selecionado.'));
    if (tabNeeds(tab)) return Promise.resolve(detail);
    var withParam = withForTab(tab);
    if (detailFetch && detailFetch.with === withParam) return detailFetch.promise;
    var target = tab === 'parceiros' ? document.getElementById('drawer-parceiros')
      : (tab === 'resumo' ? document.getElementById('drawer-resumo') : null);
    if (target) target.innerHTML = '<div class="empty">Consultando SAP…</div>';
    else if (tab === 'documentos' || tab === 'itens') {
      renderTable(document.getElementById('drawer-table'), { columns: [], rows: [] });
      document.getElementById('drawer-aviso').style.display = 'block';
      document.getElementById('drawer-aviso').textContent = 'Consultando documentos/itens no SAP…';
    }
    var promise = api({ action: 'detail', projeto: detailProjectId, with: withParam }).then(function (j) {
      var got = Number((j.projeto && j.projeto.Chave) || 0);
      if (got && got !== detailProjectId) {
        throw new Error('A ficha retornou o projeto ' + got + ' em vez do selecionado (' + detailProjectId + ').');
      }
      applyDetailPayload(j);
      if (detailLoaded.documents) {
        renderDrawerMeta(detail);
        renderDrawerIdentificacao(detail);
      }
      return detail;
    }).finally(function () {
      if (detailFetch && detailFetch.with === withParam) detailFetch = null;
    });
    detailFetch = { with: withParam, promise: promise };
    return promise;
  }
  function renderDetail(tab) {
    document.querySelectorAll('[data-dtab]').forEach(function (b) { b.classList.toggle('active', b.getAttribute('data-dtab') === tab); });
    var aviso = document.getElementById('drawer-aviso');
    var flat = document.getElementById('drawer-flat');
    var tree = document.getElementById('drawer-parceiros');
    var resumoEl = document.getElementById('drawer-resumo');
    resumoEl.style.display = tab === 'resumo' ? 'grid' : 'none';
    tree.style.display = tab === 'parceiros' ? 'block' : 'none';
    flat.style.display = (tab === 'documentos' || tab === 'itens') ? 'block' : 'none';
    if (tab === 'resumo') {
      aviso.style.display = 'none';
      renderProjectResumo(detail ? detail.resumo : []);
      return;
    }
    if (tab === 'parceiros') {
      aviso.style.display = detail && detail.aviso_itens ? 'block' : 'none';
      aviso.textContent = detail ? (detail.aviso_itens || '') : '';
      renderPartnerTree(detail ? detail.parceiros : []);
      return;
    }
    aviso.style.display = tab === 'itens' ? 'block' : 'none';
    aviso.textContent = detail ? detail.aviso_itens : '';
    renderTable(document.getElementById('drawer-table'), (detail && detail[tab]) || { columns: [], rows: [] });
  }
  function openDetail(row) {
    detailProjectId = Number(row.Chave || row.AbsEntry || 0);
    if (!detailProjectId) {
      show('spj-error', 'Não foi possível identificar o projeto selecionado (AbsEntry ausente).');
      return;
    }
    document.getElementById('drawer-title').textContent = 'Ficha do projeto ' + (row.NumProjeto || '') + ' — ' + (row.NomeProjeto || '');
    document.getElementById('drawer-hint').textContent =
      'Consultando AbsEntry ' + detailProjectId + (row.NumProjeto ? ' · Nº ' + row.NumProjeto : '') + '…';
    document.getElementById('drawer-identificacao').innerHTML = '';
    document.getElementById('drawer-meta').innerHTML = '';
    document.getElementById('drawer-resumo').innerHTML = '<div class="empty">Consultando SAP…</div>';
    document.getElementById('drawer-resumo').style.display = 'grid';
    document.getElementById('drawer-parceiros').style.display = 'none';
    document.getElementById('drawer-flat').style.display = 'none';
    setPdfLink(0);
    document.getElementById('spj-drawer').classList.add('open');
    document.getElementById('spj-drawer-bg').classList.add('open');
    detail = null;
    detailLoaded = { summary: false, documents: false, items: false };
    detailFetch = null;
    ensureDetail('resumo').then(function (j) {
      setPdfLink(detailProjectId);
      document.getElementById('drawer-hint').textContent =
        'Ficha exclusiva do projeto AbsEntry ' + detailProjectId
        + (j.projeto && j.projeto.NumProjeto ? ' · Nº ' + j.projeto.NumProjeto : '')
        + (j.projeto && j.projeto.NomeProjeto ? ' — ' + j.projeto.NomeProjeto : '')
        + ' · abas Documentos/Itens carregam sob demanda.';
      document.getElementById('drawer-title').textContent =
        'Ficha do projeto ' + ((j.projeto && j.projeto.NumProjeto) || row.NumProjeto || '')
        + ' — ' + ((j.projeto && j.projeto.NomeProjeto) || row.NomeProjeto || '');
      renderDrawerIdentificacao(j);
      renderDrawerMeta(j);
      renderDetail('resumo');
    }).catch(function (e) {
      document.getElementById('drawer-resumo').innerHTML = '<div class="empty">' + esc(e.message) + '</div>';
    });
  }
  function closeDetail() {
    document.getElementById('spj-drawer').classList.remove('open');
    document.getElementById('spj-drawer-bg').classList.remove('open');
    setPdfLink(0);
  }

  // ---------- relatórios ----------
  function syncReportFilters() {
    var key = document.getElementById('rep-select').value;
    var scopes = (REPORTS[key] || {}).scopes || [];
    document.querySelectorAll('#rep-form [data-f]').forEach(function (label) {
      var f = label.getAttribute('data-f');
      var on = f === 'agrupar' ? key === 'periodo' : scopes.indexOf(FILTER_SCOPES[f]) >= 0;
      label.classList.toggle('off', !on);
      label.querySelectorAll('select,input').forEach(function (el) { el.disabled = !on; });
    });
  }
  function generateReport() {
    var params = formParams(document.getElementById('rep-form'));
    params.action = 'report';
    show('spj-error', '');
    return api(params).then(function (j) {
      lastReport = j;
      lastReportParams = params;
      stamp(j);
      show('spj-warn', (j.warnings || []).join('\n'));
      show('rep-aviso', j.aviso || '');
      document.getElementById('rep-title').textContent = j.title;
      document.getElementById('rep-meta').textContent = j.rows_count + ' linha(s)' + (j.filters_text.length ? ' · ' + j.filters_text.join(' · ') : '');
      var repKey = (lastReportParams && lastReportParams.report) || '';
      renderTable(document.getElementById('rep-table'), j, {
        groupByProject: repKey === 'documentos' || repKey === 'itens'
      });
      document.getElementById('rep-xlsx').disabled = false;
      document.getElementById('rep-csv').disabled = false;
    }).catch(function (e) { show('spj-error', e.message); });
  }
  function exportCsv() {
    if (!lastReport) return;
    var j = lastReport;
    var q = function (v) { return '"' + String(v == null ? '' : v).replace(/"/g, '""') + '"'; };
    var fmt = function (c, v) {
      if (c.type === 'money' || c.type === 'number') return num(v, 2);
      if (c.type === 'date') return brDate(v);
      return v;
    };
    var lines = [
      q(j.title + ' — SAP B1 Gerenciamento de Projetos'),
      q('Gerado em ' + brDate(j.generated_at) + ' ' + String(j.generated_at).slice(11, 16)),
      q('Filtros: ' + (j.filters_text.length ? j.filters_text.join(' · ') : 'nenhum'))
    ];
    if (j.aviso) lines.push(q('Aviso: ' + j.aviso));
    lines.push('');
    lines.push(j.columns.map(function (c) { return q(c.label); }).join(';'));
    j.rows.forEach(function (r) { lines.push(j.columns.map(function (c) { return q(fmt(c, r[c.key])); }).join(';')); });
    lines.push(j.columns.map(function (c, i) {
      if (Object.prototype.hasOwnProperty.call(j.totals, c.key)) return q(fmt({ type: c.type === 'money' ? 'money' : 'int' }, j.totals[c.key]));
      return q(i === 0 ? 'Total (sem cancelados)' : '');
    }).join(';'));
    var blob = new Blob(['﻿' + lines.join('\r\n')], { type: 'text/csv;charset=utf-8' });
    var a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = 'projetos_sap_' + (lastReportParams.report || 'relatorio') + '.csv';
    document.body.appendChild(a);
    a.click();
    setTimeout(function () { URL.revokeObjectURL(a.href); a.remove(); }, 0);
  }
  function exportXlsx() {
    if (!lastReportParams) return;
    var p = Object.assign({}, lastReportParams, { action: 'export' });
    window.location.href = API + (API.indexOf('?') >= 0 ? '&' : '?') + new URLSearchParams(p).toString();
  }

  // ---------- abas ----------
  function setAba(aba) {
    document.querySelectorAll('[data-aba]').forEach(function (b) { b.classList.toggle('active', b.getAttribute('data-aba') === aba); });
    document.getElementById('pane-dashboard').classList.toggle('active', aba === 'dashboard');
    document.getElementById('pane-relatorios').classList.toggle('active', aba === 'relatorios');
    if (aba === 'dashboard' && !dashLoaded) loadDashboard();
  }

  document.querySelectorAll('[data-aba]').forEach(function (b) {
    b.addEventListener('click', function () { setAba(b.getAttribute('data-aba')); });
  });
  document.getElementById('dash-form').addEventListener('submit', function (ev) { ev.preventDefault(); loadDashboard(); });
  document.getElementById('dash-clear').addEventListener('click', function () {
    document.getElementById('dash-form').reset();
    loadDashboard();
  });
  document.getElementById('rep-form').addEventListener('submit', function (ev) { ev.preventDefault(); generateReport(); });
  document.getElementById('rep-select').addEventListener('change', function () {
    syncReportFilters();
    lastReport = null;
    document.getElementById('rep-xlsx').disabled = true;
    document.getElementById('rep-csv').disabled = true;
  });
  document.getElementById('rep-csv').addEventListener('click', exportCsv);
  document.getElementById('rep-xlsx').addEventListener('click', exportXlsx);
  document.getElementById('drawer-close').addEventListener('click', closeDetail);
  document.getElementById('spj-drawer-bg').addEventListener('click', closeDetail);
  document.querySelectorAll('[data-dtab]').forEach(function (b) {
    b.addEventListener('click', function () {
      if (!detailProjectId) return;
      var tab = b.getAttribute('data-dtab');
      document.querySelectorAll('[data-dtab]').forEach(function (x) {
        x.classList.toggle('active', x.getAttribute('data-dtab') === tab);
      });
      ensureDetail(tab).then(function () { renderDetail(tab); }).catch(function (e) {
        if (tab === 'resumo') {
          document.getElementById('drawer-resumo').innerHTML = '<div class="empty">' + esc(e.message) + '</div>';
        } else if (tab === 'parceiros') {
          document.getElementById('drawer-parceiros').innerHTML = '<div class="empty">' + esc(e.message) + '</div>';
        } else {
          show('drawer-aviso', e.message);
        }
      });
    });
  });
  document.addEventListener('keydown', function (ev) { if (ev.key === 'Escape') closeDetail(); });

  syncReportFilters();
  api({ action: 'options' }).then(fillOptions).catch(function (e) { show('spj-error', 'Filtros: ' + e.message); });
  if (<?= json_encode($aba) ?> === 'dashboard') loadDashboard();
})();
</script>
