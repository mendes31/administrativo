<?php
/** @var array $this->data */
$apiUrl = htmlspecialchars($this->data['api_url'] ?? '', ENT_QUOTES, 'UTF-8');
$syncUrl = htmlspecialchars($this->data['sync_url'] ?? '', ENT_QUOTES, 'UTF-8');
$invoicesUrl = htmlspecialchars($this->data['invoices_url'] ?? '', ENT_QUOTES, 'UTF-8');
$usagesUrl = htmlspecialchars($this->data['usages_url'] ?? '', ENT_QUOTES, 'UTF-8');
$canSync = !empty($this->data['can_sync']);
$canUsages = !empty($this->data['can_usages']);
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
  box-shadow:var(--csd-shadow); padding:8px 10px; display:flex; gap:6px;
  flex-wrap:nowrap; align-items:flex-end; margin-bottom:12px;
  overflow-x:auto; scrollbar-width:thin;
}
.crm-sales-dash .csd-toolbar::-webkit-scrollbar{height:6px;}
.crm-sales-dash .csd-toolbar::-webkit-scrollbar-thumb{background:#C5CBC3; border-radius:6px;}
.crm-sales-dash .csd-toolbar-fields{
  display:flex; flex-wrap:nowrap; align-items:flex-end; gap:6px; flex:1 1 auto; min-width:0;
}
.crm-sales-dash .csd-toolbar.is-collapsed .csd-toolbar-fields{display:none;}
.crm-sales-dash .csd-toolbar-summary{
  display:none; font-size:12px; color:var(--csd-ink-soft); white-space:nowrap;
  overflow:hidden; text-overflow:ellipsis; min-width:0; flex:1 1 auto;
  padding-bottom:4px;
}
.crm-sales-dash .csd-toolbar.is-collapsed .csd-toolbar-summary{display:block;}
.crm-sales-dash .csd-toolbar-actions{
  display:flex; flex-wrap:nowrap; align-items:flex-end; gap:6px; flex:0 0 auto;
}
.crm-sales-dash .csd-toolbar-fields > label,
.crm-sales-dash .csd-toolbar-fields > .csd-field,
.crm-sales-dash .csd-custom-dates > label{
  display:flex; flex-direction:column; gap:2px; font-size:10px; font-weight:600;
  color:var(--csd-ink-mute); margin:0;
  min-width:0; flex:1 1 108px; max-width:160px;
}
.crm-sales-dash .csd-toolbar-fields > label,
.crm-sales-dash .csd-toolbar-fields > .csd-field > span,
.crm-sales-dash .csd-custom-dates > label{
  text-transform:uppercase; letter-spacing:.03em;
}
.crm-sales-dash .csd-toolbar select,
.crm-sales-dash .csd-toolbar input[type="date"],
.crm-sales-dash .csd-ms-btn{
  font-family:inherit; font-size:12px; color:var(--csd-ink); border:1px solid var(--csd-line);
  background:var(--csd-bg); border-radius:6px; padding:5px 7px; min-width:0; width:100%;
  outline:none; height:30px;
}
.crm-sales-dash .csd-toolbar select:focus,
.crm-sales-dash .csd-toolbar input[type="date"]:focus,
.crm-sales-dash .csd-ms-btn:focus{border-color:var(--csd-green); box-shadow:0 0 0 2px var(--csd-green-tint);}
.crm-sales-dash .csd-ms{position:relative; width:100%;}
.crm-sales-dash .csd-ms-btn{
  text-align:left; cursor:pointer; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;
  padding-right:18px; background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='6'%3E%3Cpath fill='%2355605A' d='M0 0l5 6 5-6z'/%3E%3C/svg%3E");
  background-repeat:no-repeat; background-position:right 8px center;
}
.crm-sales-dash .csd-ms-btn.has-value{border-color:#F3CFA3; background-color:var(--csd-orange-tint);}
.crm-sales-dash .csd-ms-panel{
  display:none; position:fixed; z-index:50; background:#fff; border:1px solid var(--csd-line);
  border-radius:8px; box-shadow:var(--csd-shadow); max-height:280px; min-width:240px;
  padding:8px; flex-direction:column; gap:6px;
}
.crm-sales-dash .csd-ms-panel.is-open{display:flex;}
.crm-sales-dash .csd-ms-panel input[type="search"]{
  font-family:inherit; font-size:12px; border:1px solid var(--csd-line); border-radius:6px;
  padding:6px 8px; width:100%; height:28px; background:#fff;
}
.crm-sales-dash .csd-ms-list{overflow:auto; max-height:210px; display:flex; flex-direction:column; gap:2px;}
.crm-sales-dash .csd-ms-opt{
  display:flex; align-items:flex-start; gap:8px; font-size:12px; font-weight:500;
  color:var(--csd-ink); padding:5px 6px; border-radius:6px; cursor:pointer;
  text-transform:none; letter-spacing:0;
}
.crm-sales-dash .csd-ms-opt:hover{background:var(--csd-bg);}
.crm-sales-dash .csd-ms-opt input{margin-top:2px; flex:0 0 auto;}
.crm-sales-dash .csd-ms-empty{font-size:12px; color:var(--csd-ink-mute); padding:8px;}
.crm-sales-dash .csd-custom-dates{display:none; gap:6px; flex-wrap:nowrap; align-items:flex-end; flex:0 0 auto;}
.crm-sales-dash .csd-custom-dates.is-visible{display:flex;}
.crm-sales-dash .csd-custom-dates label{flex:0 0 118px; max-width:118px;}
.crm-sales-dash .csd-toolbar button{
  border:1px solid var(--csd-line); background:var(--csd-bg); color:var(--csd-ink-soft);
  font-size:12px; font-weight:600; padding:5px 10px; border-radius:6px; cursor:pointer;
  height:30px; white-space:nowrap; flex:0 0 auto;
}
.crm-sales-dash .csd-toolbar button:hover{background:var(--csd-line);}
.crm-sales-dash .csd-toolbar button.csd-btn-primary{
  background:var(--csd-green); border-color:var(--csd-green); color:#fff;
}
.crm-sales-dash .csd-toolbar button.csd-btn-primary:hover{background:var(--csd-green-dark);}
.crm-sales-dash .csd-toolbar button:disabled{opacity:.55; cursor:not-allowed;}
.crm-sales-dash .csd-toolbar button.csd-btn-toggle{
  background:transparent; border-color:var(--csd-line); color:var(--csd-ink-soft);
  padding:5px 8px;
}
.crm-sales-dash .csd-link-usages{
  font-size:12px; font-weight:600; color:var(--csd-green-dark);
  padding:5px 6px; white-space:nowrap; height:30px; display:inline-flex; align-items:center;
  text-decoration:none;
}
.crm-sales-dash .csd-link-usages:hover{text-decoration:underline;}
.crm-sales-dash .csd-sales-nav{
  display:flex; flex-wrap:wrap; gap:6px; margin:0 0 12px;
}
.crm-sales-dash .csd-sales-nav a{
  display:inline-flex; align-items:center; text-decoration:none !important;
  font-size:12px; font-weight:600; color:var(--csd-ink-soft);
  border:1px solid var(--csd-line); background:var(--csd-surface);
  border-radius:20px; padding:6px 12px;
}
.crm-sales-dash .csd-sales-nav a:hover{background:var(--csd-green-tint); color:var(--csd-green-dark);}
.crm-sales-dash .csd-sales-nav a.is-active{
  background:var(--csd-green); border-color:var(--csd-green); color:#fff;
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
.crm-sales-dash .csd-kpi-group{margin-bottom:16px;}
.crm-sales-dash .csd-kpi-group h2{
  font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.05em;
  color:var(--csd-ink-mute); margin:0 0 8px;
}
.crm-sales-dash .csd-kpi-row{display:grid; grid-template-columns:repeat(4,1fr); gap:12px; margin-bottom:0;}
.crm-sales-dash .csd-kpi-row.csd-kpi-row-3{grid-template-columns:repeat(3,1fr);}
.crm-sales-dash .csd-kpi{
  border-radius:var(--csd-radius); padding:14px 16px; box-shadow:var(--csd-shadow); border:1px solid var(--csd-line);
  position:relative;
}
.crm-sales-dash .csd-kpi.c-green{background:var(--csd-green-tint); border-color:#CDE9D6;}
.crm-sales-dash .csd-kpi.c-red{background:var(--csd-red-tint); border-color:#F4CDCD;}
.crm-sales-dash .csd-kpi.c-orange{background:var(--csd-orange-tint); border-color:#F3CFA3;}
.crm-sales-dash .csd-kpi.c-blue{background:var(--csd-blue-tint); border-color:#C7DBFB;}
.crm-sales-dash .csd-kpi.c-teal{background:#E6F6F4; border-color:#B7E2DC;}
.crm-sales-dash .csd-kpi.c-purple{background:#F3EEFB; border-color:#D9CBF3;}
.crm-sales-dash .csd-kpi .label{
  font-size:12px; font-weight:600; margin:0 0 6px; color:var(--csd-ink-soft);
  display:flex; align-items:center; justify-content:space-between; gap:8px;
}
.crm-sales-dash .csd-kpi-info{
  flex:0 0 auto; width:20px; height:20px; border-radius:50%;
  border:1px solid currentColor; background:rgba(255,255,255,.55);
  color:inherit; font-size:12px; font-weight:700; font-style:italic; font-family:Georgia,serif;
  line-height:1; cursor:pointer; padding:0;
  display:inline-flex; align-items:center; justify-content:center;
}
.crm-sales-dash .csd-kpi-info:hover,
.crm-sales-dash .csd-kpi-info[aria-expanded="true"]{background:#fff; box-shadow:0 0 0 3px rgba(27,36,30,.08);}
.crm-sales-dash .csd-kpi-tip{
  display:none; position:absolute; z-index:8; left:12px; right:12px; top:42px;
  background:#fff; color:var(--csd-ink); border:1px solid var(--csd-line);
  border-radius:10px; box-shadow:var(--csd-shadow); padding:10px 12px;
  font-size:12px; font-weight:500; line-height:1.45; text-align:left;
}
.crm-sales-dash .csd-kpi-tip.is-open{display:block;}
.crm-sales-dash .csd-kpi-tip strong{display:block; margin-bottom:4px; font-size:12px;}
.crm-sales-dash .csd-kpi .value{
  font-size:1.05rem; font-weight:700; margin:0; letter-spacing:-0.02em;
  font-variant-numeric:tabular-nums; line-height:1.25; word-break:break-word;
}
.crm-sales-dash .csd-kpi .delta{font-size:12px; font-weight:600; margin-top:6px; line-height:1.35;}
.crm-sales-dash .csd-kpi.c-green .value,.crm-sales-dash .csd-kpi.c-green .delta{color:var(--csd-green-dark);}
.crm-sales-dash .csd-kpi.c-red .value,.crm-sales-dash .csd-kpi.c-red .delta{color:#8A2323;}
.crm-sales-dash .csd-kpi.c-orange .value,.crm-sales-dash .csd-kpi.c-orange .delta{color:#8A4413;}
.crm-sales-dash .csd-kpi.c-blue .value,.crm-sales-dash .csd-kpi.c-blue .delta{color:#1D4489;}
.crm-sales-dash .csd-kpi.c-teal .value,.crm-sales-dash .csd-kpi.c-teal .delta{color:#0F6B61;}
.crm-sales-dash .csd-kpi.c-purple .value,.crm-sales-dash .csd-kpi.c-purple .delta{color:#5B3A9E;}
.crm-sales-dash .csd-grid{display:grid; grid-template-columns:1.6fr 1fr; gap:12px; margin-bottom:12px;}
.crm-sales-dash .csd-grid-2{display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-bottom:12px;}
.crm-sales-dash .csd-panel{
  background:var(--csd-surface); border:1px solid var(--csd-line); border-radius:var(--csd-radius);
  padding:16px 18px; box-shadow:var(--csd-shadow);
}
.crm-sales-dash .csd-panel h2{font-size:15px; font-weight:700; margin:0 0 2px;}
.crm-sales-dash .csd-panel-head{
  display:flex; align-items:flex-start; justify-content:space-between; gap:10px; flex-wrap:wrap;
  margin-bottom:2px;
}
.crm-sales-dash .csd-panel-head h2{margin:0;}
.crm-sales-dash .csd-tabs{display:flex; gap:4px; flex-wrap:wrap;}
.crm-sales-dash .csd-tab{
  border:1px solid var(--csd-line); background:#fff; color:var(--csd-ink-soft);
  font-size:12px; font-weight:600; padding:4px 10px; border-radius:999px; cursor:pointer; height:28px;
  font-family:inherit;
}
.crm-sales-dash .csd-tab:hover{background:var(--csd-bg);}
.crm-sales-dash .csd-tab.is-active{background:var(--csd-green); border-color:var(--csd-green); color:#fff;}
.crm-sales-dash .csd-kpi[data-open-natureza]{cursor:pointer;}
.crm-sales-dash .tbl-itens .col-vs{width:5.2rem; min-width:0 !important; font-size:11px !important;}
.crm-sales-dash .table-scroll.itens-scroll.is-wide{overflow-x:auto;}
.crm-sales-dash .csd-panel .panel-sub{font-size:12px; color:var(--csd-ink-mute); margin:0 0 10px;}
.crm-sales-dash .csd-panel-sub-row{
  display:flex; align-items:baseline; justify-content:space-between; gap:12px; flex-wrap:wrap;
}
.crm-sales-dash .csd-nfs-legend{
  font-size:11px; font-weight:700; letter-spacing:.04em; text-transform:uppercase;
  color:var(--csd-ink-mute); white-space:nowrap; padding-right:22px;
}
.crm-sales-dash .csd-chart{position:relative; width:100%; cursor:pointer;}
.crm-sales-dash .csd-chart-scroll{
  height:360px; max-height:360px; min-height:360px;
  overflow-y:hidden; overflow-x:hidden;
  border:1px solid var(--csd-line); border-radius:10px; background:#FAFBFA;
  scrollbar-width:thin;
}
.crm-sales-dash .csd-chart-scroll.is-scrollable{overflow-y:auto;}
.crm-sales-dash .csd-chart-scroll::-webkit-scrollbar{width:8px;}
.crm-sales-dash .csd-chart-scroll::-webkit-scrollbar-thumb{
  background:#C5CBC3; border-radius:8px;
}
.crm-sales-dash .csd-chart-inner{
  position:relative; width:100%; height:360px; min-height:360px;
}
.crm-sales-dash .csd-chart-inner canvas{display:block; width:100% !important;}
.crm-sales-dash .csd-chart-empty{
  display:flex; align-items:center; justify-content:center; min-height:160px;
  padding:16px; text-align:center; color:var(--csd-ink-mute); font-size:13px;
  background:var(--csd-bg); border-radius:10px; border:1px dashed var(--csd-line);
}
.crm-sales-dash table{width:100%; border-collapse:collapse; font-size:13px;}
.crm-sales-dash table.tbl-clientes{
  table-layout:auto !important; width:100% !important;
}
.crm-sales-dash .tbl-clientes th,
.crm-sales-dash .tbl-clientes td{
  overflow:visible !important;
  text-overflow:clip !important;
  vertical-align:top !important;
  font-size:12px !important;
  padding:8px 6px !important;
}
.crm-sales-dash thead th{
  text-align:left; font-size:11px; text-transform:uppercase; letter-spacing:.03em;
  color:var(--csd-ink-mute); font-weight:700; padding:0 8px 8px; border-bottom:1px solid var(--csd-line);
}
.crm-sales-dash .table-scroll thead th{
  position:sticky; top:0; z-index:1; background:var(--csd-surface); padding-top:4px !important;
  white-space:normal !important; line-height:1.2;
}
.crm-sales-dash tbody td{padding:9px 8px; border-bottom:1px solid var(--csd-bg); color:var(--csd-ink-soft); vertical-align:top;}
.crm-sales-dash .tbl-clientes td.num,
.crm-sales-dash .tbl-clientes th.col-num,
.crm-sales-dash .tbl-clientes tfoot td.num{
  text-align:right !important;
  color:var(--csd-ink);
  font-weight:600;
  font-variant-numeric:tabular-nums;
  white-space:nowrap !important;
  overflow:visible !important;
  text-overflow:clip !important;
  width:1%;
  min-width:9.75rem;
  padding-left:8px !important;
  padding-right:4px !important;
}
.crm-sales-dash tbody td.neg{color:var(--csd-red);}
.crm-sales-dash .tbl-clientes .col-grupo{
  width:1%; white-space:nowrap !important;
  font-size:9px !important; font-weight:500; line-height:1.2;
  text-align:left !important; color:var(--csd-ink-mute);
  overflow:visible !important; text-overflow:clip !important;
}
.crm-sales-dash .tbl-clientes .col-nfs{
  width:1%; min-width:3.6rem !important; text-align:right !important;
  font-variant-numeric:tabular-nums; font-weight:600;
}
.crm-sales-dash table.tbl-itens{
  table-layout:fixed !important;
}
.crm-sales-dash .tbl-itens td.num,
.crm-sales-dash .tbl-itens th.col-num,
.crm-sales-dash .tbl-itens tfoot td.num{
  min-width:0 !important;
  width:6.75rem;
}
.crm-sales-dash .tbl-itens th.col-nfs,
.crm-sales-dash .tbl-itens td.col-nfs,
.crm-sales-dash .tbl-itens tfoot td.col-nfs{
  width:3.4rem !important; min-width:0 !important; text-align:right !important;
  font-variant-numeric:tabular-nums; font-weight:600;
}
.crm-sales-dash .tbl-itens .col-qtd{width:5.1rem; min-width:0 !important;}
.crm-sales-dash .table-scroll.itens-scroll{overflow-x:hidden;}
.crm-sales-dash .name-cell{
  color:var(--csd-ink); font-weight:600;
  white-space:normal !important; overflow:visible !important;
  text-overflow:clip !important; line-height:1.3;
  word-break:break-word; overflow-wrap:anywhere;
  text-align:left !important;
}
.crm-sales-dash .cliente-code{
  display:inline-block; font-family:ui-monospace,Consolas,monospace; font-size:11px !important;
  font-weight:700; color:var(--csd-ink-mute); margin-right:6px; white-space:nowrap;
}
.crm-sales-dash .cliente-nome{font-weight:600; white-space:normal;}
.crm-sales-dash .tbl-itens .name-cell{
  white-space:nowrap !important; overflow:hidden !important; text-overflow:ellipsis !important;
  word-break:normal; overflow-wrap:normal;
}
.crm-sales-dash .tbl-itens .cliente-nome{white-space:nowrap; overflow:hidden; text-overflow:ellipsis;}
.crm-sales-dash .tbl-itens .bar-mini{margin-top:4px;}
.crm-sales-dash .bar-mini{height:5px; border-radius:3px; background:var(--csd-green-tint); margin-top:5px; overflow:hidden;}
.crm-sales-dash .bar-mini span{display:block; height:100%; background:var(--csd-green); border-radius:3px;}
.crm-sales-dash .tbl-clientes tbody tr[data-card-code],
.crm-sales-dash .tbl-clientes tbody tr[data-item-code]{cursor:pointer;}
.crm-sales-dash .tbl-clientes tbody tr[data-card-code]:hover,
.crm-sales-dash .tbl-clientes tbody tr[data-item-code]:hover{background:var(--csd-green-tint);}
.crm-sales-dash .tbl-clientes tbody tr.is-selected{background:var(--csd-orange-tint);}
.crm-sales-dash .tbl-clientes tbody tr.is-selected:hover{background:var(--csd-orange-tint);}
.crm-sales-dash .table-scroll{max-height:320px; overflow-y:auto; overflow-x:auto; scrollbar-width:thin;}
.crm-sales-dash .table-scroll::-webkit-scrollbar{width:8px; height:8px;}
.crm-sales-dash .table-scroll::-webkit-scrollbar-thumb{background:#C5CBC3; border-radius:8px;}
.crm-sales-dash .tbl-clientes tfoot td{
  position:sticky; bottom:0; background:var(--csd-surface); border-top:1px solid var(--csd-line);
  font-weight:700; padding:8px 6px !important; font-size:12px !important;
  white-space:nowrap !important; overflow:visible !important; text-overflow:clip !important;
}
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
.crm-sales-dash .csd-ok{
  background:var(--csd-green-tint); border:1px solid #B7DCC6; color:var(--csd-green-dark);
  border-radius:var(--csd-radius); padding:12px 14px; margin-bottom:12px; display:none;
}
@media (max-width:1000px){
  .crm-sales-dash .csd-grid,.crm-sales-dash .csd-grid-2{grid-template-columns:1fr;}
  .crm-sales-dash .csd-kpi-row,
  .crm-sales-dash .csd-kpi-row.csd-kpi-row-3{grid-template-columns:1fr 1fr;}
}
@media (max-width:560px){
  .crm-sales-dash .csd-kpi-row,
  .crm-sales-dash .csd-kpi-row.csd-kpi-row-3{grid-template-columns:1fr;}
}
</style>

<div class="container-fluid px-2 px-md-3">
  <?php include './app/adms/Views/partials/alerts.php'; ?>

  <div class="crm-sales-dash" id="crmSalesDash"
       data-api-url="<?= $apiUrl ?>"
       data-sync-url="<?= $syncUrl ?>"
       data-invoices-url="<?= $invoicesUrl ?>"
       data-usages-url="<?= $usagesUrl ?>"
       data-can-sync="<?= $canSync ? '1' : '0' ?>">
    <div class="csd-overlay" aria-live="polite"><div class="csd-spinner" id="csdSpinnerText">Carregando…</div></div>

    <div class="csd-banner">
      <div>
        <h1>Dashboard de vendas SAP</h1>
        <p>Produto acabado e materiais de uso/consumo · clique para filtrar · duplo clique em vendedor, cliente ou item para ver as notas</p>
      </div>
      <div class="csd-periodo-tag" id="periodoResumo">Carregando período…</div>
    </div>

    <?php include './app/adms/Views/partials/sales_nav.php'; ?>

    <div class="csd-error" id="csdError" role="alert"></div>
    <div class="csd-ok" id="csdOk" role="status"></div>
    <div class="csd-warn" id="csdWarn" role="status"></div>

    <div class="csd-toolbar" id="csdToolbar">
      <button type="button" class="csd-btn-toggle" id="btnToggleFiltros" aria-expanded="true" aria-controls="csdToolbarFields" title="Expandir ou colapsar os filtros">Colapsar</button>
      <span class="csd-toolbar-summary" id="csdToolbarSummary"></span>
      <div class="csd-toolbar-fields" id="csdToolbarFields">
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
        <div class="csd-field">
          <span>Vendedor</span>
          <div class="csd-ms" data-ms="vendedor">
            <button type="button" class="csd-ms-btn" id="fVendedorBtn" aria-haspopup="listbox" aria-expanded="false">Todos</button>
            <div class="csd-ms-panel" id="fVendedorPanel">
              <input type="search" id="fVendedorSearch" placeholder="Buscar vendedor" autocomplete="off">
              <div class="csd-ms-list" id="fVendedorList"></div>
            </div>
          </div>
        </div>
        <div class="csd-field">
          <span>Grupo</span>
          <div class="csd-ms" data-ms="grupo_cliente">
            <button type="button" class="csd-ms-btn" id="fGrupoClienteBtn" aria-haspopup="listbox" aria-expanded="false">Todos</button>
            <div class="csd-ms-panel" id="fGrupoClientePanel">
              <input type="search" id="fGrupoClienteSearch" placeholder="Buscar grupo" autocomplete="off">
              <div class="csd-ms-list" id="fGrupoClienteList"></div>
            </div>
          </div>
        </div>
        <div class="csd-field">
          <span>Região</span>
          <div class="csd-ms" data-ms="regiao">
            <button type="button" class="csd-ms-btn" id="fRegiaoBtn" aria-haspopup="listbox" aria-expanded="false">Todas</button>
            <div class="csd-ms-panel" id="fRegiaoPanel">
              <input type="search" id="fRegiaoSearch" placeholder="Buscar região" autocomplete="off">
              <div class="csd-ms-list" id="fRegiaoList"></div>
            </div>
          </div>
        </div>
        <button type="button" id="btnLimpar">Limpar</button>
        <button type="button" id="btnAtualizar">Atualizar</button>
      </div>
      <div class="csd-toolbar-actions">
        <?php if ($canSync): ?>
        <button type="button" id="btnSyncSap" class="csd-btn-primary" title="Consulta no SAP só os últimos dias (com 3 dias de sobreposição). Não recarrega o histórico completo.">Sync incremental</button>
        <?php endif; ?>
        <?php if ($canUsages && $usagesUrl !== ''): ?>
        <a href="<?= $usagesUrl ?>" class="csd-link-usages">Classificar utilizações</a>
        <?php endif; ?>
      </div>
    </div>

    <div class="csd-sync-meta" id="csdSyncMeta">Cache: —</div>

    <div class="csd-chips" id="chipsRow"></div>

    <div class="csd-kpi-group">
      <h2>Faturamento</h2>
      <section class="csd-kpi-row">
      <div class="csd-kpi c-green">
        <p class="label">
          Faturamento líquido
          <button type="button" class="csd-kpi-info" aria-expanded="false" aria-controls="tipFat" title="O que é este indicador?">i</button>
        </p>
        <div class="csd-kpi-tip" id="tipFat" role="tooltip">
          <strong>Já abate as devoluções</strong>
          Este valor é faturas menos devoluções (não some de novo o card Devoluções). Conta: faturas R$ (card de referência no rodapé) − devoluções R$ = este líquido. Só utilizações Venda e Devolução comercial; LineTotal − DiscSum; OITB 104 e 106. Bonificação, brinde e Ignorar ficam fora.
          <strong style="margin-top:8px;">Clientes ativos (rodapé)</strong>
          Parceiros distintos com pelo menos uma fatura — quem só devolveu não entra.
          <strong style="margin-top:8px;">Vs ano anterior</strong>
          O rodapé também mostra a variação percentual do líquido no mesmo intervalo deslocado um ano, quando o cache tem base. Sem custo nem margem.
        </div>
        <p class="value" id="kpiFaturamento">—</p>
        <p class="delta" id="kpiFaturamentoDelta"></p>
      </div>
      <div class="csd-kpi c-red">
        <p class="label">
          Devoluções
          <button type="button" class="csd-kpi-info" aria-expanded="false" aria-controls="tipDev" title="O que é este indicador?">i</button>
        </p>
        <div class="csd-kpi-tip" id="tipDev" role="tooltip">
          <strong>Este valor já saiu do faturamento líquido</strong>
          Soma em reais das notas de crédito (ORIN) de Venda ou Devolução comercial. Não some este card ao líquido — o líquido já é faturas menos isto.
          <strong style="margin-top:8px;">Rodapé</strong>
          Quantidade de unidades devolvidas (quantidade SAP) e quantas linhas de item existem nessas notas.
        </div>
        <p class="value" id="kpiDevolucao">—</p>
        <p class="delta" id="kpiDevolucaoDelta"></p>
      </div>
      <div class="csd-kpi c-orange">
        <p class="label">
          Taxa de devolução
          <button type="button" class="csd-kpi-info" aria-expanded="false" aria-controls="tipTaxa" title="O que é este indicador?">i</button>
        </p>
        <div class="csd-kpi-tip" id="tipTaxa" role="tooltip">
          <strong>Não usa o faturamento líquido</strong>
          Valor deste card Devoluções ÷ valor das faturas (antes de abater devolução) × 100. Meta de referência: 10%.
        </div>
        <p class="value" id="kpiTaxaDevolucao">—</p>
        <p class="delta" id="kpiTaxaDevolucaoDelta"></p>
      </div>
      <div class="csd-kpi c-blue">
        <p class="label">
          Ticket médio
          <button type="button" class="csd-kpi-info" aria-expanded="false" aria-controls="tipTicket" title="O que é este indicador?">i</button>
        </p>
        <div class="csd-kpi-tip" id="tipTicket" role="tooltip">
          <strong>Usa o líquido (já com devolução abatida)</strong>
          Faturamento líquido ÷ clientes ativos (parceiros com fatura). Não é ticket por nota fiscal.
        </div>
        <p class="value" id="kpiTicket">—</p>
        <p class="delta" id="kpiTicketDelta"></p>
      </div>
      </section>
    </div>

    <div class="csd-kpi-group">
      <h2>Venda e desconto</h2>
      <section class="csd-kpi-row csd-kpi-row-3">
      <div class="csd-kpi c-teal">
        <p class="label">
          Itens vendidos
          <button type="button" class="csd-kpi-info" aria-expanded="false" aria-controls="tipItensVend" title="O que é este indicador?">i</button>
        </p>
        <div class="csd-kpi-tip" id="tipItensVend" role="tooltip">
          <strong>Já abate as unidades devolvidas</strong>
          Quantidade líquida: unidades faturadas menos unidades devolvidas, só natureza venda. O rodapé mostra as duas parcelas.
        </div>
        <p class="value" id="kpiItensVendidos">—</p>
        <p class="delta" id="kpiItensVendidosDelta">Unidades líquidas</p>
      </div>
      <div class="csd-kpi c-red">
        <p class="label">
          Desconto
          <button type="button" class="csd-kpi-info" aria-expanded="false" aria-controls="tipDesc" title="O que é este indicador?">i</button>
        </p>
        <div class="csd-kpi-tip" id="tipDesc" role="tooltip">
          <strong>Já abate o desconto das devoluções</strong>
          Desconto concedido nas linhas de venda: (preço antes do desconto da linha × quantidade − LineTotal) + parcela do DiscSum. Faturas menos o desconto das notas de crédito. O rodapé mostra o bruto (preço antes do desconto × quantidade), já líquido de devolução.
        </div>
        <p class="value" id="kpiDesconto">—</p>
        <p class="delta" id="kpiDescontoDelta">Bruto sem desconto: —</p>
      </div>
      <div class="csd-kpi c-red">
        <p class="label">
          % Desconto
          <button type="button" class="csd-kpi-info" aria-expanded="false" aria-controls="tipPctDesc" title="O que é este indicador?">i</button>
        </p>
        <div class="csd-kpi-tip" id="tipPctDesc" role="tooltip">
          <strong>Usa valores já líquidos</strong>
          Desconto concedido (já abate devolução) ÷ bruto de venda (preço antes do desconto × quantidade, também líquido) × 100.
        </div>
        <p class="value" id="kpiPctDesconto">—</p>
        <p class="delta">Sobre o bruto líquido</p>
      </div>
      </section>
    </div>

    <div class="csd-kpi-group">
      <h2>Bonificação</h2>
      <section class="csd-kpi-row csd-kpi-row-3">
      <div class="csd-kpi c-orange" data-open-natureza="bonificacao">
        <p class="label">
          Bonificações
          <button type="button" class="csd-kpi-info" aria-expanded="false" aria-controls="tipBonifVal" title="O que é este indicador?">i</button>
        </p>
        <div class="csd-kpi-tip" id="tipBonifVal" role="tooltip">
          <strong>Não entra no faturamento</strong>
          Valor líquido das utilizações bonificação (fatura − devolução de bonificação). Não some este card ao faturamento líquido. Duplo clique abre as notas só com as linhas de bonificação.
        </div>
        <p class="value" id="kpiBonificacoes">—</p>
        <p class="delta" id="kpiBonificacoesDelta"></p>
      </div>
      <div class="csd-kpi c-orange" data-open-natureza="bonificacao">
        <p class="label">
          Itens bonificados
          <button type="button" class="csd-kpi-info" aria-expanded="false" aria-controls="tipItensBon" title="O que é este indicador?">i</button>
        </p>
        <div class="csd-kpi-tip" id="tipItensBon" role="tooltip">
          <strong>Já abate devolução de bonificação</strong>
          Quantidade líquida das linhas classificadas como bonificação. Duplo clique abre as notas desta natureza.
        </div>
        <p class="value" id="kpiItensBonificados">—</p>
        <p class="delta">Unidades líquidas</p>
      </div>
      <div class="csd-kpi c-orange" data-open-natureza="bonificacao">
        <p class="label">
          % Bonificações
          <button type="button" class="csd-kpi-info" aria-expanded="false" aria-controls="tipPctBonif" title="O que é este indicador?">i</button>
        </p>
        <div class="csd-kpi-tip" id="tipPctBonif" role="tooltip">
          <strong>Sobre o líquido de venda</strong>
          Valor de bonificações ÷ faturamento líquido × 100. Brindes não entram. Não some bonificação no faturamento.
        </div>
        <p class="value" id="kpiPctBonificacoes">—</p>
        <p class="delta">Sobre o faturamento líquido</p>
      </div>
      </section>
    </div>

    <div class="csd-kpi-group">
      <h2>Brindes</h2>
      <section class="csd-kpi-row csd-kpi-row-3">
      <div class="csd-kpi c-purple" data-open-natureza="brinde">
        <p class="label">
          Brindes
          <button type="button" class="csd-kpi-info" aria-expanded="false" aria-controls="tipBrindesVal" title="O que é este indicador?">i</button>
        </p>
        <div class="csd-kpi-tip" id="tipBrindesVal" role="tooltip">
          <strong>Não entra no faturamento</strong>
          Valor líquido das utilizações brinde (fatura − devolução de brinde). Não some este card ao faturamento líquido. Duplo clique abre as notas só com as linhas de brinde.
        </div>
        <p class="value" id="kpiBrindes">—</p>
        <p class="delta" id="kpiBrindesDelta"></p>
      </div>
      <div class="csd-kpi c-purple" data-open-natureza="brinde">
        <p class="label">
          Itens de brinde
          <button type="button" class="csd-kpi-info" aria-expanded="false" aria-controls="tipItensBrinde" title="O que é este indicador?">i</button>
        </p>
        <div class="csd-kpi-tip" id="tipItensBrinde" role="tooltip">
          <strong>Já abate devolução de brinde</strong>
          Quantidade líquida das linhas classificadas como brinde. Pode ficar negativa se, no período, devolveu mais do que faturou. Duplo clique abre as notas desta natureza.
        </div>
        <p class="value" id="kpiItensBrindes">—</p>
        <p class="delta">Unidades líquidas</p>
      </div>
      <div class="csd-kpi c-purple" data-open-natureza="brinde">
        <p class="label">
          % Brindes
          <button type="button" class="csd-kpi-info" aria-expanded="false" aria-controls="tipPctBrindes" title="O que é este indicador?">i</button>
        </p>
        <div class="csd-kpi-tip" id="tipPctBrindes" role="tooltip">
          <strong>Sobre o líquido de venda</strong>
          Valor de brindes ÷ faturamento líquido × 100. Bonificações não entram. Não some brinde no faturamento.
        </div>
        <p class="value" id="kpiPctBrindes">—</p>
        <p class="delta">Sobre o faturamento líquido</p>
      </div>
      </section>
    </div>

    <section class="csd-grid">
      <div class="csd-panel">
        <h2>Evolução mensal</h2>
        <p class="panel-sub">Faturamento líquido · clique para marcar um ou mais meses</p>
        <div class="csd-chart" style="height:250px;">
          <canvas id="chartEvolucao" aria-label="Evolução mensal do faturamento líquido"></canvas>
        </div>
      </div>
      <div class="csd-panel">
        <h2>Vendas por grupo de cliente</h2>
        <p class="panel-sub">Participação no faturamento líquido · clique para marcar um ou mais grupos</p>
        <div id="legendGrupo" class="csd-legend"></div>
        <div class="csd-chart" style="height:190px;">
          <canvas id="chartGrupoCliente" aria-label="Participação por grupo de cliente"></canvas>
        </div>
      </div>
    </section>

    <section class="csd-grid-2">
      <div class="csd-panel">
        <h2>Top vendedores</h2>
        <p class="panel-sub csd-panel-sub-row">
          <span>Faturamento líquido · clique para filtrar · duplo clique para ver as notas · top 10 visíveis, role para ver mais</span>
          <span class="csd-nfs-legend" data-nfs-legend="chartVendedores">NFs</span>
        </p>
        <div class="csd-chart csd-chart-scroll">
          <div class="csd-chart-inner">
            <canvas id="chartVendedores" aria-label="Faturamento por vendedor"></canvas>
          </div>
        </div>
      </div>
      <div class="csd-panel">
        <h2>Vendas por região</h2>
        <p class="panel-sub csd-panel-sub-row">
          <span>Por UF · clique para marcar uma ou mais · top 10 visíveis, role para ver mais</span>
          <span class="csd-nfs-legend" data-nfs-legend="chartRegiao">NFs</span>
        </p>
        <div class="csd-chart csd-chart-scroll">
          <div class="csd-chart-inner">
            <canvas id="chartRegiao" aria-label="Faturamento por região"></canvas>
          </div>
        </div>
      </div>
    </section>

    <section class="csd-grid-2">
      <div class="csd-panel">
        <h2>Top clientes</h2>
        <p class="panel-sub" id="subTopClientes">Todos os clientes do período · ordenado por faturamento líquido</p>
        <div class="table-scroll">
          <table class="tbl-clientes">
            <thead>
              <tr>
                <th>Parceiro</th>
                <th class="col-grupo">Grupo</th>
                <th class="col-num col-nfs">NFs</th>
                <th class="col-num">Líquido</th>
                <th class="col-num">Devolução</th>
              </tr>
            </thead>
            <tbody id="tblClientes"></tbody>
            <tfoot id="tblClientesFoot"></tfoot>
          </table>
        </div>
      </div>
      <div class="csd-panel">
        <div class="csd-panel-head">
          <h2 id="titTopItens">Itens vendidos</h2>
          <div class="csd-tabs" role="tablist" aria-label="Natureza dos itens">
            <button type="button" class="csd-tab is-active" data-itens-tab="venda" role="tab" aria-selected="true">Vendidos</button>
            <button type="button" class="csd-tab" data-itens-tab="bonificacao" role="tab" aria-selected="false">Bonificados</button>
            <button type="button" class="csd-tab" data-itens-tab="brinde" role="tab" aria-selected="false">Brindes</button>
          </div>
        </div>
        <p class="panel-sub" id="subTopItens">Todos os itens do período · ordenado por faturamento líquido</p>
        <div class="table-scroll itens-scroll" id="itensScroll">
          <table class="tbl-clientes tbl-itens">
            <thead id="tblItensHead">
              <tr>
                <th>Item</th>
                <th class="col-num col-nfs">NFs</th>
                <th class="col-num col-qtd">Qtd</th>
                <th class="col-num">Líquido</th>
                <th class="col-num">Devolução</th>
              </tr>
            </thead>
            <tbody id="tblItens"></tbody>
            <tfoot id="tblItensFoot"></tfoot>
          </table>
        </div>
      </div>
    </section>

    <footer class="csd-note">
      Fonte: cache MySQL a partir do SAP. Recorte: grupos de item <strong>104</strong> e <strong>106</strong>, notas de item não canceladas (exceto série 34), valor LineTotal − DiscSum. A natureza (venda, devolução comercial, bonificação, brinde, ignorar) é classificada no Portal.
      A sincronização lê faturas e devoluções no SAP. Painel: <strong id="csdSource">cache MySQL</strong>. Independente do Dashboard CRM de pipeline.
    </footer>
  </div>
</div>

<script src="<?= htmlspecialchars($_ENV['URL_ADM'] ?? '', ENT_QUOTES, 'UTF-8') ?>public/adms/vendor/chartjs/chart.umd.min.js"></script>
<script src="<?= htmlspecialchars($_ENV['URL_ADM'] ?? '', ENT_QUOTES, 'UTF-8') ?>public/adms/js/crm/sales-dashboard.js?v=33"></script>
