<?php
/** @var array $this->data */
$apiUrl = htmlspecialchars((string) ($this->data['api_url'] ?? ''), ENT_QUOTES, 'UTF-8');
$selfUrl = htmlspecialchars((string) ($this->data['self_url'] ?? ''), ENT_QUOTES, 'UTF-8');
$chave = (string) ($this->data['periodo_chave'] ?? '3');
$from = (string) ($this->data['data_inicio'] ?? '');
$to = (string) ($this->data['data_fim'] ?? '');
$unidade = (string) ($this->data['unidade'] ?? '');
$centroSel = (string) ($this->data['centro'] ?? '');
$visao = (string) ($this->data['visao'] ?? 'dashboard');
$periodos = $this->data['periodos'] ?? [];
?>
<style>
.fcc-dash{
  --fcc-green:#1B7A49; --fcc-green-dark:#12532F; --fcc-green-tint:#E7F4EC;
  --fcc-orange:#E67E2E; --fcc-orange-tint:#FDEEE0;
  --fcc-red:#D14343; --fcc-red-tint:#FBEAEA;
  --fcc-blue:#2F6FDE; --fcc-blue-tint:#E8F0FE;
  --fcc-ink:#1B241E; --fcc-ink-soft:#55605A; --fcc-ink-mute:#8A9189;
  --fcc-bg:#F1F4F1; --fcc-surface:#FFFFFF; --fcc-line:#E2E6E0;
  --fcc-radius:14px;
  --fcc-shadow:0 1px 2px rgba(18,30,22,0.05), 0 4px 16px rgba(18,30,22,0.06);
  color:var(--fcc-ink); font-size:14px; line-height:1.5;
  max-width:1700px; margin:0 auto; padding:4px 4px 28px; position:relative;
}
.fcc-dash .fcc-banner{
  background:linear-gradient(135deg,var(--fcc-green) 0%, var(--fcc-green-dark) 100%);
  border-radius:10px; padding:10px 14px; color:#fff;
  display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap; margin-bottom:8px;
}
.fcc-dash .fcc-banner h1{margin:0; font-size:1.05rem; font-weight:600;}
.fcc-dash .fcc-banner p{margin:2px 0 0; font-size:12px; color:#DCEEE2;}
.fcc-dash .fcc-banner-tags{display:flex; flex-wrap:wrap; gap:6px; justify-content:flex-end;}
.fcc-dash .fcc-periodo-tag{
  background:rgba(255,255,255,.16); border-radius:20px; padding:4px 10px;
  font-size:11.5px; font-weight:500; white-space:nowrap;
}
.fcc-dash .fcc-toolbar{
  background:var(--fcc-surface); border:1px solid var(--fcc-line); border-radius:10px;
  box-shadow:var(--fcc-shadow); padding:8px 10px; display:grid;
  grid-template-columns:1.1fr .9fr .9fr 1fr 1.2fr auto; gap:8px; align-items:end; margin-bottom:8px;
}
.fcc-dash .fcc-toolbar label{
  display:flex; flex-direction:column; gap:2px; font-size:10px; font-weight:600;
  color:var(--fcc-ink-mute); text-transform:uppercase; letter-spacing:.03em; margin:0;
}
.fcc-dash .fcc-toolbar select,.fcc-dash .fcc-toolbar input{
  font-family:inherit; font-size:12px; color:var(--fcc-ink); border:1px solid var(--fcc-line);
  background:var(--fcc-bg); border-radius:6px; padding:5px 7px; outline:none; min-width:0; height:30px;
}
.fcc-dash .fcc-toolbar select:focus,.fcc-dash .fcc-toolbar input:focus{
  border-color:var(--fcc-green); box-shadow:0 0 0 2px var(--fcc-green-tint);
}
.fcc-dash .fcc-toolbar button{
  border:1px solid var(--fcc-green); background:var(--fcc-green); color:#fff;
  font-size:12px; font-weight:600; padding:5px 12px; border-radius:6px; cursor:pointer; height:30px;
}
.fcc-dash .fcc-toolbar button:hover{background:var(--fcc-green-dark); border-color:var(--fcc-green-dark);}
.fcc-dash .fcc-kpis{display:grid; grid-template-columns:repeat(6,1fr); gap:8px; margin-bottom:8px;}
.fcc-dash .fcc-kpis.eq3{grid-template-columns:repeat(3,1fr);}
.fcc-dash .fcc-kpi{
  background:var(--fcc-surface); border:1px solid var(--fcc-line); border-radius:10px;
  padding:8px 10px; box-shadow:var(--fcc-shadow);
}
.fcc-dash .fcc-kpi.c-green{background:var(--fcc-green-tint); border-color:#CDE9D6;}
.fcc-dash .fcc-kpi.c-orange{background:var(--fcc-orange-tint); border-color:#F3CFA3;}
.fcc-dash .fcc-kpi.c-blue{background:var(--fcc-blue-tint); border-color:#C7DBFB;}
.fcc-dash .fcc-kpi.c-teal{background:#E6F6F4; border-color:#B7E2DC;}
.fcc-dash .fcc-kpi.c-purple{background:#F3EEFB; border-color:#D9CBF3;}
.fcc-dash .fcc-kpi.accent{background:var(--fcc-green-dark); border-color:var(--fcc-green-dark); color:#fff;}
.fcc-dash .kt{font-size:10px; text-transform:uppercase; letter-spacing:.04em; font-weight:700; color:var(--fcc-ink-mute);}
.fcc-dash .accent .kt{color:#CDE9D6;}
.fcc-dash .kv{font-size:16px; font-weight:800; margin-top:2px; letter-spacing:-.01em;}
.fcc-dash .kh{font-size:11px; color:var(--fcc-ink-mute); margin-top:2px;}
.fcc-dash .accent .kh{color:#DCEEE2;}
.fcc-dash .accent .kv,.fcc-dash .accent .pos,.fcc-dash .accent .neg{color:#fff!important;}
.fcc-dash .fcc-kpi.c-green .kv{color:var(--fcc-green-dark);}
.fcc-dash .fcc-kpi.c-orange .kv{color:#8A4413;}
.fcc-dash .fcc-kpi.c-blue .kv{color:#1D4489;}
.fcc-dash .fcc-kpi.c-teal .kv{color:#0F6B61;}
.fcc-dash .fcc-kpi.c-purple .kv{color:#5B3A9E;}
.fcc-dash .pos{color:var(--fcc-green)!important;} .fcc-dash .neg{color:var(--fcc-red)!important;}
.fcc-dash .tabs{display:flex; gap:6px; margin:0 0 8px; flex-wrap:wrap;}
.fcc-dash .tab{
  padding:6px 12px; background:#E8EEF4; border-radius:8px; font-size:12.5px; font-weight:600;
  color:var(--fcc-ink-soft); border:0; cursor:pointer;
}
.fcc-dash .tab:hover{background:var(--fcc-bg);}
.fcc-dash .tab.active{background:var(--fcc-green); color:#fff;}
.fcc-dash .card{
  background:var(--fcc-surface); border:1px solid var(--fcc-line); border-radius:var(--fcc-radius);
  box-shadow:var(--fcc-shadow); overflow:hidden; margin-bottom:12px;
}
.fcc-dash .cardh{padding:12px 16px; border-bottom:1px solid var(--fcc-line); font-weight:700; display:flex; justify-content:space-between; gap:12px; align-items:center;}
.fcc-dash .cardb{padding:14px 16px;}
.fcc-dash .grid2{display:grid; grid-template-columns:1.15fr .85fr; gap:12px;}
.fcc-dash .grid-charts{display:grid; grid-template-columns:1fr 1fr; gap:12px;}
.fcc-dash table{width:100%; border-collapse:collapse; font-size:12.5px; table-layout:auto !important;}
.fcc-dash th,.fcc-dash td{white-space:normal !important; overflow:visible !important; text-overflow:clip !important;}
.fcc-dash th{background:#FAFBFD; color:var(--fcc-ink-mute); padding:8px 8px; text-align:right; font-size:10.8px; text-transform:uppercase; letter-spacing:.02em; border-bottom:1px solid var(--fcc-line);}
.fcc-dash td{padding:7px 8px; border-bottom:1px solid #EEF1F5; text-align:right;}
.fcc-dash th:first-child,.fcc-dash td:first-child,.fcc-dash th.tl,.fcc-dash td.tl{text-align:left;}
.fcc-dash tbody tr:hover td{background:#FAFBFD;}
.fcc-dash tr.lvl0 td{font-weight:800; background:var(--fcc-green); color:#fff;}
.fcc-dash tr.lvl1 td{font-weight:700; background:var(--fcc-green-tint);}
.fcc-dash tr.lvl2 td:first-child,.fcc-dash tr.lvl3 td:first-child{font-family:ui-monospace,Consolas,monospace; white-space:pre !important;}
.fcc-dash .badge{font-size:10px; font-weight:700; text-transform:uppercase; padding:3px 8px; border-radius:20px; background:var(--fcc-orange-tint); color:var(--fcc-orange);}
.fcc-dash .note{font-size:12px; color:var(--fcc-ink-mute); background:var(--fcc-surface); border:1px dashed var(--fcc-line); border-radius:8px; padding:13px 15px; margin-top:8px;}
.fcc-dash .fcc-error{background:var(--fcc-red-tint); border:1px solid #F4CDCD; color:#8A2323; border-radius:var(--fcc-radius); padding:12px 14px; margin-bottom:12px; white-space:pre-wrap; display:none;}
.fcc-dash .fcc-warn{background:var(--fcc-orange-tint); border:1px solid #F3CFA3; color:#8A4413; border-radius:var(--fcc-radius); padding:12px 14px; margin-bottom:12px; display:none;}
.fcc-dash .muted{color:var(--fcc-ink-mute);}
.fcc-dash .mtx-wrap{overflow:auto; max-height:70vh;}
.fcc-dash .mtx th{position:sticky; top:0; z-index:2; background:var(--fcc-green); color:#fff;}
.fcc-dash .mtx th:first-child,.fcc-dash .mtx td:first-child{position:sticky; left:0; z-index:1; background:#fff; min-width:220px;}
.fcc-dash .mtx th:first-child{z-index:3; background:var(--fcc-green);}
.fcc-dash .mtx tfoot td{font-weight:800; background:var(--fcc-green-tint);}
.fcc-dash .bar-row{display:flex; align-items:center; gap:10px; margin:8px 0;}
.fcc-dash .bar-meta{min-width:160px; font-size:12px;}
.fcc-dash .bar-track{flex:1; background:#EEF1F5; border-radius:8px; height:18px; overflow:hidden;}
.fcc-dash .bar-fill{height:100%; background:var(--fcc-green); border-radius:8px;}
.fcc-dash .bar-val{min-width:110px; text-align:right; font-weight:700; font-size:12.5px;}
.fcc-dash .pane{display:none;}
.fcc-dash .pane.active{display:block;}
.fcc-dash.loading{opacity:.55; pointer-events:none;}
.fcc-dash .overlay{
  display:none; position:absolute; inset:0; z-index:5; background:rgba(241,244,241,.55);
  border-radius:var(--fcc-radius); align-items:flex-start; justify-content:center; padding-top:120px;
}
.fcc-dash.loading .overlay{display:flex;}
.fcc-dash .spinner{
  background:#fff; border:1px solid var(--fcc-line); box-shadow:var(--fcc-shadow);
  border-radius:12px; padding:14px 18px; font-size:13px; font-weight:600;
  display:flex; align-items:center; gap:10px;
}
.fcc-dash .spinner::before{
  content:''; width:16px; height:16px; border-radius:50%;
  border:2px solid var(--fcc-line); border-top-color:var(--fcc-green);
  animation:fcc-spin .7s linear infinite;
}
@keyframes fcc-spin{to{transform:rotate(360deg);}}
.fcc-dash .tree-tools{display:flex; gap:8px;}
.fcc-dash .tree-tools button{
  border:1px solid var(--fcc-line); background:#fff; color:var(--fcc-ink-soft);
  font-size:12px; font-weight:600; padding:5px 10px; border-radius:8px; cursor:pointer;
}
.fcc-dash .tree-unit{border:1px solid #CDE9D6; border-radius:10px; margin-bottom:10px; overflow:hidden; background:#fff;}
.fcc-dash .tree-h{
  display:flex; align-items:center; gap:8px; width:100%; text-align:left;
  border:0; cursor:pointer; font:inherit; color:inherit;
}
.fcc-dash .tree-unit > .tree-h{
  background:var(--fcc-green); color:#fff; padding:11px 12px;
}
.fcc-dash .tree-unit > .tree-h .muted{color:#DCEEE2;}
.fcc-dash .tree-unit > .tree-h .tree-val{color:#fff;}
.fcc-dash .tree-unit > .tree-h .chev{background:rgba(255,255,255,.18); color:#fff;}
.fcc-dash .tree-b.lvl-centros{padding:8px 8px 8px 18px; background:var(--fcc-green-tint); border-left:4px solid var(--fcc-green);}
.fcc-dash .tree-cc{
  background:#fff; border:1px solid #E2E8F0; border-radius:8px; margin:0 0 6px;
  border-left:4px solid #7B5EA7;
}
.fcc-dash .tree-cc .tree-h{background:#F8F5FC; padding:9px 10px;}
.fcc-dash .tree-cc .tree-h:hover{background:#F1EAF8;}
.fcc-dash .tree-unit > .tree-h:hover{background:var(--fcc-green-dark);}
.fcc-dash .lvl-tag{
  font-size:9px; font-weight:800; letter-spacing:.04em; text-transform:uppercase;
  padding:2px 6px; border-radius:4px; flex:0 0 auto;
}
.fcc-dash .tree-unit > .tree-h .lvl-tag{background:rgba(255,255,255,.16); color:#fff;}
.fcc-dash .tree-cc .lvl-tag{background:#EDE4F7; color:#5A3D86;}
.fcc-dash .tree-acc .lvl-tag{background:#E7F4EC; color:#1B7A49;}
.fcc-dash .chev{
  width:18px; height:18px; flex:0 0 18px; display:inline-flex; align-items:center; justify-content:center;
  border-radius:4px; background:var(--fcc-green-tint); font-size:11px; font-weight:800; color:var(--fcc-green-dark);
}
.fcc-dash .tree-cc .chev{background:#EDE4F7; color:#5A3D86;}
.fcc-dash .tree-unit.collapsed > .tree-b,
.fcc-dash .tree-cc.collapsed > .tree-b{display:none;}
.fcc-dash .tree-unit.collapsed > .tree-h .chev,
.fcc-dash .tree-cc.collapsed > .tree-h .chev{transform:rotate(-90deg);}
.fcc-dash .tree-title{flex:1; min-width:0;}
.fcc-dash .tree-title strong{display:block;}
.fcc-dash .tree-val{font-weight:800; white-space:nowrap;}
.fcc-dash .tree-b.lvl-contas{padding:0 0 4px 22px; background:#F3F8F4; border-left:3px solid var(--fcc-green);}
.fcc-dash .tree-acc{
  display:flex; align-items:center; gap:10px; padding:7px 10px;
  margin:4px 6px 0; background:#fff; border:1px dashed #C6E0D0; border-radius:6px; font-size:12.5px;
}
.fcc-dash .tree-acc .muted{flex:1;}
.fcc-dash .bar-meta{min-width:200px; max-width:240px;}
.fcc-dash.is-detail .kh{display:none;}
.fcc-dash.is-detail .fcc-kpi{padding:6px 8px;}
.fcc-dash.is-detail .kv{font-size:14px; margin-top:1px;}
.fcc-dash.is-detail .fcc-kpis{margin-bottom:6px; gap:6px;}
.fcc-dash .note{margin-top:8px;}
@media (max-width:1200px){
  .fcc-dash .fcc-kpis{grid-template-columns:1fr 1fr 1fr;}
  .fcc-dash .fcc-toolbar,.fcc-dash .grid2,.fcc-dash .grid-charts{grid-template-columns:1fr;}
}
@media (max-width:650px){.fcc-dash .fcc-kpis,.fcc-dash .fcc-toolbar{grid-template-columns:1fr;}}
</style>
<div class="container-fluid px-2 px-md-3">
<div class="fcc-dash<?= $visao !== 'dashboard' ? ' is-detail' : '' ?>" id="fcc-root">
  <div class="overlay"><div class="spinner">Consultando SAP…</div></div>
  <div class="fcc-banner">
    <div>
      <h1>Indicadores de Centros de Custo SAP</h1>
      <p id="fcc-subtitle">Contas 4. e 5. · Dimensão 1</p>
    </div>
    <div class="fcc-banner-tags" id="fcc-banner-tags">
      <span class="fcc-periodo-tag" id="fcc-periodo-tag">Carregando…</span>
    </div>
  </div>
  <div class="fcc-error" id="fcc-error"></div>
  <div class="fcc-warn" id="fcc-warn"></div>

  <form class="fcc-toolbar" id="fcc-filtros">
    <label>Período
      <select name="periodo" id="fcc-periodo">
        <?php foreach ($periodos as $key => $label): ?>
          <option value="<?= htmlspecialchars((string) $key, ENT_QUOTES, 'UTF-8') ?>" <?= $chave === (string) $key ? 'selected' : '' ?>><?= htmlspecialchars((string) $label, ENT_QUOTES, 'UTF-8') ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label>Início
      <input type="date" name="data_inicio" id="fcc-from" value="<?= htmlspecialchars($from, ENT_QUOTES, 'UTF-8') ?>">
    </label>
    <label>Fim
      <input type="date" name="data_fim" id="fcc-to" value="<?= htmlspecialchars($to, ENT_QUOTES, 'UTF-8') ?>">
    </label>
    <label>Unidade
      <select name="unidade" id="fcc-unidade">
        <option value="">Todas</option>
      </select>
    </label>
    <label>Centro de custo
      <select name="centro" id="fcc-centro">
        <option value="">Todos</option>
      </select>
    </label>
    <button type="submit">Aplicar</button>
  </form>

  <div class="fcc-kpis" id="fcc-kpis"></div>
  <div class="tabs" id="fcc-tabs">
    <button type="button" class="tab <?= $visao === 'dashboard' ? 'active' : '' ?>" data-visao="dashboard">Indicadores</button>
    <button type="button" class="tab <?= $visao === 'meses' ? 'active' : '' ?>" data-visao="meses">Meses</button>
    <button type="button" class="tab <?= $visao === 'centros' ? 'active' : '' ?>" data-visao="centros">Centros de custo</button>
    <button type="button" class="tab <?= $visao === 'equipes' ? 'active' : '' ?>" data-visao="equipes">Equipes</button>
    <button type="button" class="tab <?= $visao === 'hierarquia' ? 'active' : '' ?>" data-visao="hierarquia">Hierarquia</button>
    <button type="button" class="tab <?= $visao === 'lancamentos' ? 'active' : '' ?>" data-visao="lancamentos">Lançamentos</button>
  </div>
  <div id="fcc-panes"></div>
  <p class="note">KPIs, gráficos, ranking e hierarquia usam unidade → centro. A aba Equipes soma o sufixo entre unidades (AFR_TI + BIO_TI + TIA_TI = TI) sem alterar as demais visões. Pendências de rateio não são omitidas.</p>
</div>
</div>
<script src="<?php echo $_ENV['URL_ADM']; ?>public/adms/vendor/chartjs/chart.umd.min.js"></script>
<script>
(function () {
  var API = <?= json_encode((string) ($this->data['api_url'] ?? ''), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
  var SELF = <?= json_encode((string) ($this->data['self_url'] ?? ''), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
  var visao = <?= json_encode($visao, JSON_UNESCAPED_UNICODE) ?>;
  var selUnidade = <?= json_encode($unidade, JSON_UNESCAPED_UNICODE) ?>;
  var selCentro = <?= json_encode($centroSel, JSON_UNESCAPED_UNICODE) ?>;
  var payload = null;
  var charts = {};
  var loadSeq = 0;
  var palette = ['#1B7A49','#2F6FDE','#E67E2E','#7B5EA7','#D14343','#1A8A8A','#12532F','#9A6B00'];

  var root = document.getElementById('fcc-root');
  var form = document.getElementById('fcc-filtros');
  var periodo = document.getElementById('fcc-periodo');
  var from = document.getElementById('fcc-from');
  var to = document.getElementById('fcc-to');
  var unidadeEl = document.getElementById('fcc-unidade');
  var centroEl = document.getElementById('fcc-centro');

  function esc(v) {
    return String(v == null ? '' : v).replace(/[&<>"']/g, function (c) {
      return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'})[c];
    });
  }
  function money(n) {
    return Number(n || 0).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
  }
  function cls(n) {
    n = Number(n || 0);
    return n < 0 ? 'neg' : (n > 0 ? 'pos' : '');
  }
  function brDate(iso) {
    if (!iso) return '';
    var p = String(iso).slice(0, 10).split('-');
    return p.length === 3 ? p[2] + '/' + p[1] + '/' + p[0] : iso;
  }
  function moneyTick(v) {
    return Number(v).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL', maximumFractionDigits: 0 });
  }
  function syncDates() {
    var custom = periodo.value === 'personalizado';
    from.disabled = !custom;
    to.disabled = !custom;
  }
  function destroyCharts() {
    Object.keys(charts).forEach(function (k) { try { charts[k].destroy(); } catch (e) {} });
    charts = {};
  }
  function showErr(msg) {
    var el = document.getElementById('fcc-error');
    el.style.display = msg ? 'block' : 'none';
    el.textContent = msg || '';
  }
  function showWarn(msg) {
    var el = document.getElementById('fcc-warn');
    el.style.display = msg ? 'block' : 'none';
    el.textContent = msg || '';
  }
  function fillSelect(sel, items, value, allLabel, getVal, getLabel) {
    var cur = value;
    sel.innerHTML = '<option value="">' + esc(allLabel) + '</option>';
    items.forEach(function (it) {
      var v = getVal(it);
      var o = document.createElement('option');
      o.value = v;
      o.textContent = getLabel(it);
      if (v === cur) o.selected = true;
      sel.appendChild(o);
    });
    if (cur && !items.some(function (it) { return getVal(it) === cur; })) {
      var extra = document.createElement('option');
      extra.value = cur;
      extra.textContent = cur;
      extra.selected = true;
      sel.appendChild(extra);
    }
  }
  function heat(n, maxAbs) {
    n = Number(n || 0);
    if (maxAbs <= 0 || n === 0) return '';
    var p = Math.min(1, Math.abs(n) / maxAbs);
    var a = 0.12 + (p * 0.45);
    return n < 0
      ? 'background:rgba(27,122,73,' + a + ');'
      : 'background:rgba(47,111,222,' + a + ');';
  }

  function renderKpis(k) {
    k = k || {};
    var meses = Number(k.qtd_meses || 1);
    var quinto = meses > 1
      ? kpi('Média mensal', money(k.media_mensal), cls(k.media_mensal), meses + ' meses no recorte', 'c-teal')
      : kpi('Média por centro', money(k.ticket_medio), cls(k.ticket_medio), (k.qtd_centros_total || 0) + ' centros no recorte', 'c-teal');
    var sexto = meses > 1
      ? kpi('Maior mês', money(k.maior_mes_valor), cls(k.maior_mes_valor), k.maior_mes_label || '—', 'c-purple')
      : kpi('Concentração top 3', Number(k.pct_top3 || 0).toLocaleString('pt-BR') + '%', '', 'Participação dos 3 maiores centros', 'c-purple');
    document.getElementById('fcc-kpis').innerHTML =
      kpi('Total no período', money(k.total), cls(k.total), (k.qtd_centros || 0) + ' alocados · ' + (k.qtd_centros_total || 0) + ' no recorte', 'accent') +
      kpi('Alocado', money(k.alocado), cls(k.alocado), Number(k.pct_alocado || 0).toLocaleString('pt-BR') + '% do total', 'c-green') +
      kpi('Pendente / revisar', money(k.pendente), cls(k.pendente), Number(k.pct_pendente || 0).toLocaleString('pt-BR') + '% do total', 'c-orange') +
      kpi('Maior centro', esc(k.top_centro || '—'), '', money(k.top_centro_valor) + ' · ' + Number(k.top_centro_pct || 0).toLocaleString('pt-BR') + '%', 'c-blue') +
      quinto + sexto;
  }
  function kpi(t, v, c, h, tone) {
    return '<div class="fcc-kpi ' + (tone || '') + '"><div class="kt">' + esc(t) + '</div><div class="kv ' + c + '">' + v + '</div><div class="kh">' + h + '</div></div>';
  }

  function renderPanes(data) {
    var panes = document.getElementById('fcc-panes');
    var ranking = data.ranking || [];
    var top = ranking.slice(0, 12);
    var maxRank = 0;
    top.forEach(function (r) { maxRank = Math.max(maxRank, Math.abs(Number(r.valor || 0))); });
    var bars = top.map(function (r) {
      var val = Number(r.valor || 0);
      var w = maxRank > 0 ? Math.min(100, (Math.abs(val) / maxRank) * 100) : 0;
      return '<div class="bar-row"><div class="bar-meta"><strong>' + esc(r.centro) + '</strong><br><span class="muted">' + esc(r.nome || r.unidade) + ' · ' + Number(r.pct).toLocaleString('pt-BR') + '%</span></div><div class="bar-track"><div class="bar-fill" style="width:' + w.toFixed(1) + '%"></div></div><div class="bar-val ' + cls(val) + '">' + money(val) + '</div></div>';
    }).join('') || '<p class="muted">Nenhum centro no recorte.</p>';

    var matrix = data.matrix || { meses: [], linhas: [], totais: [] };
    var maxAbs = 0;
    (matrix.linhas || []).forEach(function (ln) {
      (ln.valores || []).forEach(function (v) { maxAbs = Math.max(maxAbs, Math.abs(Number(v))); });
    });
    var headMes = (matrix.meses || []).map(function (m) { return '<th>' + esc(m.label) + '</th>'; }).join('');
    var rowsMes = (matrix.linhas || []).map(function (ln) {
      var cells = (ln.valores || []).map(function (v) {
        v = Number(v || 0);
        return '<td class="' + cls(v) + '" style="' + heat(v, maxAbs) + '">' + money(v) + '</td>';
      }).join('');
      return '<tr><td class="tl"><strong>' + esc(ln.centro) + '</strong><div class="muted">' + esc(ln.unidade) + ' · ' + esc(ln.nome) + '</div></td>' + cells + '<td class="' + cls(ln.total) + '"><strong>' + money(ln.total) + '</strong></td></tr>';
    }).join('') || '<tr><td class="tl muted" colspan="' + (2 + (matrix.meses || []).length) + '">Nenhum movimento no período.</td></tr>';
    var footMes = (matrix.linhas || []).length ? '<tfoot><tr><td class="tl">TOTAL</td>' + (matrix.totais || []).map(function (v) { return '<td>' + money(v) + '</td>'; }).join('') + '<td>' + money((data.kpis || {}).total) + '</td></tr></tfoot>' : '';

    var rankRows = ranking.map(function (r) {
      return '<tr><td>' + esc(r.pos) + '</td><td class="tl"><strong>' + esc(r.centro) + '</strong><div class="muted">' + esc(r.nome) + '</div></td><td class="tl">' + esc(r.unidade) + '</td><td>' + Number(r.pct).toLocaleString('pt-BR') + '%</td><td class="' + cls(r.valor) + '">' + money(r.valor) + '</td></tr>';
    }).join('') || '<tr><td colspan="5" class="tl muted">Nenhum centro no recorte.</td></tr>';

    var eqRank = data.ranking_equipes || [];
    var eqTop = eqRank.slice(0, 12);
    var maxEq = 0;
    eqTop.forEach(function (r) { maxEq = Math.max(maxEq, Math.abs(Number(r.valor || 0))); });
    var eqBars = eqTop.map(function (r) {
      var val = Number(r.valor || 0);
      var w = maxEq > 0 ? Math.min(100, (Math.abs(val) / maxEq) * 100) : 0;
      var centrosTxt = (r.centros || []).map(function (c) { return c.centro; }).join(' + ') || '—';
      return '<div class="bar-row"><div class="bar-meta"><strong>' + esc(r.equipe) + '</strong><br><span class="muted">' + esc(r.nome) + ' · ' + Number(r.pct).toLocaleString('pt-BR') + '%<br>' + esc(centrosTxt) + '</span></div><div class="bar-track"><div class="bar-fill" style="width:' + w.toFixed(1) + '%"></div></div><div class="bar-val ' + cls(val) + '">' + money(val) + '</div></div>';
    }).join('') || '<p class="muted">Nenhuma equipe no recorte.</p>';
    var eqRows = eqRank.map(function (r) {
      var centrosTxt = (r.centros || []).map(function (c) { return c.centro; }).join(', ');
      var unidTxt = (r.unidades || []).map(function (u) { return u.unidade; }).join(', ');
      return '<tr><td>' + esc(r.pos) + '</td><td class="tl"><strong>' + esc(r.equipe) + '</strong><div class="muted">' + esc(r.nome) + '</div></td><td class="tl">' + esc(unidTxt) + '</td><td class="tl">' + esc(centrosTxt) + '</td><td>' + Number(r.pct).toLocaleString('pt-BR') + '%</td><td class="' + cls(r.valor) + '">' + money(r.valor) + '</td></tr>';
    }).join('') || '<tr><td colspan="6" class="tl muted">Nenhuma equipe no recorte.</td></tr>';
    var eqTree = (data.arvore_equipes || []).map(function (e) {
      var unidHtml = (e.unidades || []).map(function (u) {
        var centrosHtml = (u.centros || []).map(function (c) {
          return '<div class="tree-acc"><span class="lvl-tag">Centro</span><span>' + esc(c.centro) + '</span><span class="muted">' + esc(c.nome) + '</span><span class="tree-val ' + cls(c.valor) + '">' + money(c.valor) + '</span></div>';
        }).join('');
        return '<div class="tree-cc collapsed" data-node="' + esc(u.id) + '">' +
          '<button type="button" class="tree-h" data-toggle="cc"><span class="chev">▾</span><span class="lvl-tag">Unidade</span><span class="tree-title"><strong>' + esc(u.unidade) + '</strong><span class="muted">' + (u.qtd_centros || 0) + ' centro(s)</span></span><span class="tree-val ' + cls(u.valor) + '">' + money(u.valor) + '</span></button>' +
          '<div class="tree-b lvl-contas">' + (centrosHtml || '<div class="tree-acc muted">Sem centros.</div>') + '</div></div>';
      }).join('');
      return '<div class="tree-unit collapsed" data-node="' + esc(e.id) + '">' +
        '<button type="button" class="tree-h" data-toggle="unit"><span class="chev">▾</span><span class="lvl-tag">Equipe</span><span class="tree-title"><strong>' + esc(e.equipe) + ' — ' + esc(e.nome) + '</strong><span class="muted">' + (e.qtd_unidades || 0) + ' unidade(s)</span></span><span class="tree-val ' + cls(e.valor) + '">' + money(e.valor) + '</span></button>' +
        '<div class="tree-b lvl-centros">' + unidHtml + '</div></div>';
    }).join('') || '<p class="muted" style="padding:12px;">Nenhuma equipe no período.</p>';
    var topEq = eqRank[0] || {};
    var eqCentrosTop = (topEq.centros || []).map(function (c) { return c.centro; }).join(' + ') || '—';
    var eqKpis =
      kpi('Maior equipe', esc(topEq.equipe || '—'), '', money(topEq.valor || 0) + ' · ' + Number(topEq.pct || 0).toLocaleString('pt-BR') + '%', 'accent') +
      kpi('Equipes no recorte', String(eqRank.length), '', 'Sufixo do centro entre unidades', 'c-green') +
      kpi('Centros na maior', String(topEq.qtd_centros || 0), '', eqCentrosTop, 'c-teal');

    var arvore = data.arvore || [];
    var treeHtml = arvore.map(function (u) {
      var centrosHtml = (u.centros || []).map(function (c) {
        var badge = (c.status === 'Pendente' || c.status === 'Sem classificacao') ? ' <span class="badge">' + esc(c.status) + '</span>' : '';
        var contasHtml = (c.contas || []).map(function (a) {
          return '<div class="tree-acc"><span class="lvl-tag">Conta</span><span>' + esc(a.account) + '</span><span class="muted">' + esc(a.acct_name) + '</span><span class="tree-val ' + cls(a.valor) + '">' + money(a.valor) + '</span></div>';
        }).join('');
        var regra = c.regra_revisar ? ' · regra ' + c.regra_revisar : '';
        return '<div class="tree-cc collapsed" data-node="' + esc(c.id) + '">' +
          '<button type="button" class="tree-h" data-toggle="cc"><span class="chev">▾</span><span class="lvl-tag">Centro</span><span class="tree-title"><strong>' + esc(c.centro) + badge + '</strong><span class="muted">' + esc(c.nome) + regra + (c.contas && c.contas.length ? ' · ' + c.contas.length + ' conta(s)' : '') + '</span></span><span class="tree-val ' + cls(c.valor) + '">' + money(c.valor) + '</span></button>' +
          '<div class="tree-b lvl-contas">' + (contasHtml || '<div class="tree-acc muted">Sem contas neste centro.</div>') + '</div></div>';
      }).join('');
      return '<div class="tree-unit" data-node="' + esc(u.id) + '">' +
        '<button type="button" class="tree-h" data-toggle="unit"><span class="chev">▾</span><span class="lvl-tag">Unidade</span><span class="tree-title"><strong>' + esc(u.unidade) + '</strong><span class="muted">' + (u.qtd_centros || 0) + ' centro(s)</span></span><span class="tree-val ' + cls(u.valor) + '">' + money(u.valor) + '</span></button>' +
        '<div class="tree-b lvl-centros">' + centrosHtml + '</div></div>';
    }).join('') || '<p class="muted" style="padding:12px;">Nenhum lançamento no período.</p>';

    var mesesQtd = ((data.serie_mensal || {}).labels || []).length;
    var chartMesTitle = mesesQtd > 1 ? 'Evolução mensal' : 'Composição do período';

    var lanc = data.lancamentos || [];
    var lancRows = lanc.map(function (r) {
      var trans = r.trans_id || '—';
      var linha = r.line_id ? '/' + r.line_id : '';
      var doc = r.base_ref || '—';
      return '<tr><td class="tl">' + esc(r.unidade) + '</td><td class="tl">' + esc(r.centro) + ' — ' + esc(r.nome_centro) + '</td><td class="tl">' + esc(r.ref_date) + '</td><td class="tl"><strong>' + esc(trans) + '</strong>' + (linha ? '<span class="muted">' + esc(linha) + '</span>' : '') + '</td><td class="tl">' + esc(doc) + '</td><td class="tl">' + esc(r.account) + '</td><td class="tl">' + esc(r.natureza) + '</td><td class="tl">' + esc(r.historico) + '</td><td class="' + cls(r.valor) + '">' + money(r.valor) + '</td><td class="tl">' + esc(r.status) + '</td></tr>';
    }).join('') || '<tr><td colspan="10" class="tl muted">Nenhum lançamento no período.</td></tr>';

    panes.innerHTML =
      '<div class="pane' + (visao === 'dashboard' ? ' active' : '') + '" data-pane="dashboard">' +
        '<div class="grid-charts"><div class="card"><div class="cardh">' + chartMesTitle + '</div><div class="cardb"><canvas id="fccMesChart" height="220"></canvas></div></div>' +
        '<div class="card"><div class="cardh">Unidades × mês</div><div class="cardb"><canvas id="fccStackChart" height="220"></canvas></div></div></div>' +
        '<div class="grid2"><div class="card"><div class="cardh"><span>Maiores centros de custo</span><span class="muted" style="font-weight:500;font-size:12px;">Top 12 do período</span></div><div class="cardb">' + bars + '</div></div>' +
        '<div class="card"><div class="cardh">Por unidade (consolidado)</div><div class="cardb"><canvas id="fccChart" height="220"></canvas></div></div></div>' +
      '</div>' +
      '<div class="pane' + (visao === 'meses' ? ' active' : '') + '" data-pane="meses"><div class="card"><div class="cardh"><span>Centros de custo × meses</span><span class="muted" style="font-weight:500;font-size:12px;">Valor líquido. Orçamento ainda não entra nesta matriz.</span></div><div class="mtx-wrap"><table class="mtx"><thead><tr><th class="tl">Centro de custo</th>' + headMes + '<th>Total</th></tr></thead><tbody>' + rowsMes + '</tbody>' + footMes + '</table></div></div></div>' +
      '<div class="pane' + (visao === 'centros' ? ' active' : '') + '" data-pane="centros"><div class="card"><div class="cardh">Ranking de centros</div><div class="cardb" style="padding:0;overflow:auto;max-height:75vh;"><table><thead><tr><th>#</th><th class="tl">Centro</th><th class="tl">Unidade</th><th>%</th><th>Valor líquido</th></tr></thead><tbody>' + rankRows + '</tbody></table></div></div></div>' +
      '<div class="pane' + (visao === 'equipes' ? ' active' : '') + '" data-pane="equipes">' +
        '<p class="note" style="margin:0 0 8px;">Esta visão soma o sufixo do centro entre unidades. Ex.: AFR_TI + BIO_TI + TIA_TI = equipe TI. Indicadores, meses, centros e hierarquia continuam por centro/unidade.</p>' +
        '<div class="fcc-kpis eq3">' + eqKpis + '</div>' +
        '<div class="grid2"><div class="card"><div class="cardh"><span>Maiores equipes</span><span class="muted" style="font-weight:500;font-size:12px;">Consolidado entre unidades</span></div><div class="cardb">' + eqBars + '</div></div>' +
        '<div class="card"><div class="cardh">Participação</div><div class="cardb"><canvas id="fccEqChart" height="220"></canvas></div></div></div>' +
        '<div class="card"><div class="cardh">Ranking de equipes</div><div class="cardb" style="padding:0;overflow:auto;max-height:50vh;"><table><thead><tr><th>#</th><th class="tl">Equipe</th><th class="tl">Unidades</th><th class="tl">Centros somados</th><th>%</th><th>Valor líquido</th></tr></thead><tbody>' + eqRows + '</tbody></table></div></div>' +
        '<div class="card"><div class="cardh"><span>Equipe → unidade → centro</span><span class="tree-tools"><button type="button" id="fcc-eq-exp">Expandir tudo</button><button type="button" id="fcc-eq-col">Recolher tudo</button></span></div><div class="cardb" style="overflow:auto;max-height:60vh;">' + eqTree + '</div></div>' +
      '</div>' +
      '<div class="pane' + (visao === 'hierarquia' ? ' active' : '') + '" data-pane="hierarquia"><div class="card"><div class="cardh"><span>Hierarquia · unidade → centro → conta</span><span class="tree-tools"><button type="button" id="fcc-exp-all">Expandir tudo</button><button type="button" id="fcc-col-all">Recolher tudo</button></span></div><div class="cardb" style="overflow:auto;max-height:75vh;">' + treeHtml + '</div></div></div>' +
      '<div class="pane' + (visao === 'lancamentos' ? ' active' : '') + '" data-pane="lancamentos"><div class="card"><div class="cardh"><span>Lançamentos atribuídos</span><span class="muted" style="font-weight:500;font-size:12px;">' + lanc.length + ' linhas</span></div><div class="cardb" style="padding:0;overflow:auto;max-height:70vh;"><table><thead><tr><th class="tl">Unidade</th><th class="tl">Centro</th><th class="tl">Data</th><th class="tl">Transação</th><th class="tl">Documento</th><th class="tl">Conta</th><th class="tl">Natureza</th><th class="tl">Histórico</th><th>Valor líquido</th><th class="tl">Status</th></tr></thead><tbody>' + lancRows + '</tbody></table></div></div></div>';

    destroyCharts();
    if (typeof Chart === 'undefined') return;
    var serie = data.serie_mensal || {};
    var mesCanvas = document.getElementById('fccMesChart');
    if (mesCanvas && typeof Chart !== 'undefined') {
      if ((serie.labels || []).length > 1) {
        charts.mes = new Chart(mesCanvas, {
          type: 'bar',
          data: {
            labels: serie.labels,
            datasets: [
              { type: 'bar', label: 'Alocado', data: serie.alocado, backgroundColor: '#1B7A49' },
              { type: 'bar', label: 'Pendente', data: serie.pendente, backgroundColor: '#E67E2E' },
              { type: 'line', label: 'Total', data: serie.total, borderColor: '#12532F', tension: 0.25, pointRadius: 3 }
            ]
          },
          options: { plugins: { legend: { position: 'bottom' } }, scales: { y: { ticks: { callback: moneyTick } } } }
        });
      } else {
        charts.mes = new Chart(mesCanvas, {
          type: 'doughnut',
          data: {
            labels: ['Alocado', 'Pendente / revisar'],
            datasets: [{ data: [(data.kpis || {}).alocado || 0, (data.kpis || {}).pendente || 0], backgroundColor: ['#1B7A49', '#E67E2E'] }]
          },
          options: { plugins: { legend: { position: 'bottom' } } }
        });
      }
    }
    var stacked = data.stacked_unidades || {};
    var stackCanvas = document.getElementById('fccStackChart');
    if (stackCanvas && (stacked.datasets || []).length) {
      charts.stack = new Chart(stackCanvas, {
        type: 'bar',
        data: {
          labels: stacked.labels,
          datasets: (stacked.datasets || []).map(function (d, i) {
            return { label: d.label, data: d.data, backgroundColor: palette[i % palette.length], stack: 'unidades' };
          })
        },
        options: { plugins: { legend: { position: 'bottom' } }, scales: { x: { stacked: true }, y: { stacked: true, ticks: { callback: moneyTick } } } }
      });
    }
    var chart = data.chart || {};
    var unitCanvas = document.getElementById('fccChart');
    if (unitCanvas && (chart.labels || []).length) {
      charts.unit = new Chart(unitCanvas, {
        type: 'bar',
        data: { labels: chart.labels, datasets: [{ data: chart.valores, backgroundColor: (chart.labels || []).map(function (_, i) { return palette[i % palette.length]; }) }] },
        options: { indexAxis: 'y', plugins: { legend: { display: false } }, scales: { x: { ticks: { callback: moneyTick } } } }
      });
    }
    var eqCanvas = document.getElementById('fccEqChart');
    if (eqCanvas && eqTop.length) {
      charts.eq = new Chart(eqCanvas, {
        type: 'doughnut',
        data: {
          labels: eqTop.slice(0, 8).map(function (r) { return r.equipe; }),
          datasets: [{ data: eqTop.slice(0, 8).map(function (r) { return r.valor; }), backgroundColor: eqTop.slice(0, 8).map(function (_, i) { return palette[i % palette.length]; }) }]
        },
        options: { plugins: { legend: { position: 'bottom' } } }
      });
    }
  }

  function applyPayload(data) {
    payload = data;
    var p = data.periodo || {};
    selUnidade = data.unidade || '';
    selCentro = data.centro || '';
    document.getElementById('fcc-subtitle').textContent = 'Contas 4. e 5. · Dimensão 1';
    renderApplied(data);
    showWarn(p.aviso || '');
    fillSelect(unidadeEl, data.unidades || [], selUnidade, 'Todas', function (x) { return x; }, function (x) { return x; });
    fillSelect(centroEl, data.centros || [], selCentro, 'Todos', function (x) { return x.codigo; }, function (x) { return x.codigo + ' — ' + x.nome; });
    renderKpis(data.kpis);
    renderPanes(data);
    bindTree();
    if (p.from) from.value = p.from;
    if (p.to) to.value = p.to;
    if (p.chave) periodo.value = p.chave;
    syncUrl(data);
  }

  function renderApplied(data) {
    var wrap = document.getElementById('fcc-banner-tags');
    if (!wrap) return;
    var p = data.periodo || {};
    var html = '<span class="fcc-periodo-tag">' + esc(brDate(p.from) + ' a ' + brDate(p.to)) + '</span>';
    if (data.unidade) html += '<span class="fcc-periodo-tag">' + esc(data.unidade) + '</span>';
    if (data.centro) html += '<span class="fcc-periodo-tag">' + esc(data.centro) + '</span>';
    wrap.innerHTML = html;
  }
  function syncUrl(data) {
    if (!SELF || !window.history || !window.history.replaceState) return;
    try {
      var url = new URL(SELF, window.location.href);
      ['periodo', 'data_inicio', 'data_fim', 'unidade', 'centro', 'visao'].forEach(function (k) {
        url.searchParams.delete(k);
      });
      var p = data.periodo || {};
      if (p.chave) url.searchParams.set('periodo', p.chave);
      if (p.chave === 'personalizado') {
        if (p.from) url.searchParams.set('data_inicio', p.from);
        if (p.to) url.searchParams.set('data_fim', p.to);
      }
      if (data.unidade) url.searchParams.set('unidade', data.unidade);
      if (data.centro) url.searchParams.set('centro', data.centro);
      if (visao && visao !== 'dashboard') url.searchParams.set('visao', visao);
      window.history.replaceState({}, '', url.pathname + url.search);
    } catch (e) {}
  }

  function bindTree() {
    document.querySelectorAll('#fcc-panes [data-toggle]').forEach(function (btn) {
      btn.addEventListener('click', function (e) {
        e.preventDefault();
        var box = btn.closest('.tree-unit, .tree-cc');
        if (box) box.classList.toggle('collapsed');
      });
    });
    var exp = document.getElementById('fcc-exp-all');
    var col = document.getElementById('fcc-col-all');
    if (exp) exp.addEventListener('click', function () {
      document.querySelectorAll('#fcc-panes .pane[data-pane="hierarquia"] .tree-unit, #fcc-panes .pane[data-pane="hierarquia"] .tree-cc').forEach(function (n) { n.classList.remove('collapsed'); });
    });
    if (col) col.addEventListener('click', function () {
      document.querySelectorAll('#fcc-panes .pane[data-pane="hierarquia"] .tree-unit, #fcc-panes .pane[data-pane="hierarquia"] .tree-cc').forEach(function (n) { n.classList.add('collapsed'); });
    });
    var eqExp = document.getElementById('fcc-eq-exp');
    var eqCol = document.getElementById('fcc-eq-col');
    if (eqExp) eqExp.addEventListener('click', function () {
      document.querySelectorAll('#fcc-panes .pane[data-pane="equipes"] .tree-unit, #fcc-panes .pane[data-pane="equipes"] .tree-cc').forEach(function (n) { n.classList.remove('collapsed'); });
    });
    if (eqCol) eqCol.addEventListener('click', function () {
      document.querySelectorAll('#fcc-panes .pane[data-pane="equipes"] .tree-unit, #fcc-panes .pane[data-pane="equipes"] .tree-cc').forEach(function (n) { n.classList.add('collapsed'); });
    });
  }

  function qs() {
    return {
      periodo: periodo.value,
      data_inicio: from.value,
      data_fim: to.value,
      unidade: unidadeEl.value,
      centro: centroEl.value,
      visao: visao
    };
  }

  function load() {
    selUnidade = unidadeEl.value;
    selCentro = centroEl.value;
    var seq = ++loadSeq;
    root.classList.add('loading');
    showErr('');
    fetch(API, {
      method: 'POST',
      headers: { 'Accept': 'application/json', 'Content-Type': 'application/json' },
      body: JSON.stringify(qs())
    })
      .then(function (r) { return r.json().then(function (j) { return { ok: r.ok, json: j }; }); })
      .then(function (res) {
        if (seq !== loadSeq) return;
        if (!res.json || res.json.success === false) {
          throw new Error((res.json && res.json.error) || 'Falha ao consultar o SAP.');
        }
        applyPayload(res.json);
      })
      .catch(function (e) {
        if (seq !== loadSeq) return;
        showErr(e.message || String(e));
      })
      .finally(function () {
        if (seq !== loadSeq) return;
        root.classList.remove('loading');
      });
  }

  function setVisao(next) {
    visao = next;
    root.classList.toggle('is-detail', visao !== 'dashboard');
    document.querySelectorAll('#fcc-tabs .tab').forEach(function (t) {
      t.classList.toggle('active', t.getAttribute('data-visao') === visao);
    });
    document.querySelectorAll('#fcc-panes .pane').forEach(function (p) {
      p.classList.toggle('active', p.getAttribute('data-pane') === visao);
    });
    if (payload) syncUrl(payload);
    if (visao === 'lancamentos' && payload && !(payload.lancamentos || []).length) {
      load();
    }
  }

  periodo.addEventListener('change', function () {
    syncDates();
    load();
  });
  unidadeEl.addEventListener('change', function () {
    centroEl.value = '';
    selCentro = '';
    load();
  });
  centroEl.addEventListener('change', load);
  from.addEventListener('change', function () {
    if (periodo.value === 'personalizado') load();
  });
  to.addEventListener('change', function () {
    if (periodo.value === 'personalizado') load();
  });
  syncDates();
  form.addEventListener('submit', function (e) {
    e.preventDefault();
    load();
  });
  document.querySelectorAll('#fcc-tabs .tab').forEach(function (t) {
    t.addEventListener('click', function () { setVisao(t.getAttribute('data-visao')); });
  });
  load();
})();
</script>
