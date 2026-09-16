.crm-sales-an {
  --csd-green:#1B7A49; --csd-green-dark:#12532F; --csd-green-tint:#E7F4EC;
  --csd-orange:#E67E2E; --csd-orange-tint:#FDEEE0;
  --csd-red:#D14343; --csd-blue:#2F6FDE; --csd-blue-tint:#E8F0FE;
  --csd-ink:#1B241E; --csd-ink-soft:#55605A; --csd-ink-mute:#8A9189;
  --csd-bg:#F1F4F1; --csd-surface:#FFFFFF; --csd-line:#E2E6E0;
  --csd-radius:14px;
  --csd-shadow:0 1px 2px rgba(18,30,22,0.05), 0 4px 16px rgba(18,30,22,0.06);
  color: var(--csd-ink); font-size:14px; line-height:1.5;
  max-width:1360px; margin:0 auto; padding:8px 4px 40px;
}
.csd-sales-nav{
  display:flex; flex-wrap:wrap; gap:6px; margin:0 0 12px;
}
.csd-sales-nav a{
  display:inline-flex; align-items:center; text-decoration:none !important;
  font-size:12px; font-weight:600; color:var(--csd-ink-soft);
  border:1px solid var(--csd-line); background:var(--csd-surface);
  border-radius:20px; padding:6px 12px;
}
.csd-sales-nav a:hover{background:var(--csd-green-tint); color:var(--csd-green-dark);}
.csd-sales-nav a.is-active{
  background:var(--csd-green); border-color:var(--csd-green); color:#fff;
}
.crm-sales-an .csd-banner{
  background:linear-gradient(135deg,var(--csd-green) 0%, var(--csd-green-dark) 100%);
  border-radius:var(--csd-radius); padding:18px 22px; color:#fff;
  display:flex; align-items:flex-start; justify-content:space-between; gap:16px;
  flex-wrap:wrap; margin-bottom:14px;
}
.crm-sales-an .csd-banner h1{margin:0; font-size:1.25rem; font-weight:600;}
.crm-sales-an .csd-banner p{margin:4px 0 0; font-size:13px; color:#DCEEE2;}
.crm-sales-an .csd-periodo-tag{
  background:rgba(255,255,255,0.16); border-radius:20px; padding:7px 16px;
  font-size:12.5px; font-weight:500; white-space:nowrap;
}
.crm-sales-an .csd-warn{
  background:#FDEEE0; border:1px solid #F3CFA3; color:#8A4413;
  border-radius:14px; padding:12px 14px; margin-bottom:12px;
}
.crm-sales-an .csd-toolbar{
  background:var(--csd-surface); border:1px solid var(--csd-line); border-radius:var(--csd-radius);
  box-shadow:var(--csd-shadow); padding:8px 10px; display:flex; gap:6px;
  flex-wrap:nowrap; align-items:flex-end; margin-bottom:10px;
  overflow-x:auto; scrollbar-width:thin;
}
.crm-sales-an .csd-toolbar::-webkit-scrollbar{height:6px;}
.crm-sales-an .csd-toolbar::-webkit-scrollbar-thumb{background:#C5CBC3; border-radius:6px;}
.crm-sales-an .csd-toolbar-fields{
  display:flex; flex-wrap:nowrap; align-items:flex-end; gap:6px; flex:1 1 auto; min-width:0;
}
.crm-sales-an .csd-toolbar-actions{
  display:flex; flex-wrap:nowrap; align-items:flex-end; gap:6px; flex:0 0 auto;
}
.crm-sales-an .csd-toolbar-fields > label,
.crm-sales-an .csd-toolbar-fields > .csd-field,
.crm-sales-an .csd-custom-dates > label{
  display:flex; flex-direction:column; gap:2px; font-size:10px; font-weight:600;
  color:var(--csd-ink-mute); margin:0;
  min-width:0; flex:1 1 120px; max-width:180px;
  text-transform:uppercase; letter-spacing:.03em;
}
.crm-sales-an .csd-toolbar select,
.crm-sales-an .csd-toolbar input[type="date"],
.crm-sales-an .csd-ms-btn{
  font-family:inherit; font-size:12px; color:var(--csd-ink); border:1px solid var(--csd-line);
  background:var(--csd-bg); border-radius:6px; padding:5px 7px; min-width:0; width:100%;
  outline:none; height:30px;
}
.crm-sales-an .csd-toolbar select:focus,
.crm-sales-an .csd-toolbar input[type="date"]:focus,
.crm-sales-an .csd-ms-btn:focus{border-color:var(--csd-green); box-shadow:0 0 0 2px var(--csd-green-tint);}
.crm-sales-an .csd-ms{position:relative; width:100%;}
.crm-sales-an .csd-ms-btn{
  text-align:left; cursor:pointer; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;
  padding-right:18px; background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='6'%3E%3Cpath fill='%2355605A' d='M0 0l5 6 5-6z'/%3E%3C/svg%3E");
  background-repeat:no-repeat; background-position:right 8px center;
}
.crm-sales-an .csd-ms-btn.has-value{border-color:#F3CFA3; background-color:var(--csd-orange-tint);}
.crm-sales-an .csd-ms-panel{
  display:none; position:fixed; z-index:60; background:#fff; border:1px solid var(--csd-line);
  border-radius:8px; box-shadow:var(--csd-shadow); max-height:280px; min-width:240px;
  padding:8px; flex-direction:column; gap:6px;
}
.crm-sales-an .csd-ms-panel.is-open{display:flex;}
.crm-sales-an .csd-ms-panel input[type="search"]{
  font-family:inherit; font-size:12px; border:1px solid var(--csd-line); border-radius:6px;
  padding:6px 8px; width:100%; height:28px; background:#fff; text-transform:none; letter-spacing:0;
}
.crm-sales-an .csd-ms-list{overflow:auto; max-height:210px; display:flex; flex-direction:column; gap:2px;}
.crm-sales-an .csd-ms-opt{
  display:flex; align-items:flex-start; gap:8px; font-size:12px; font-weight:500;
  color:var(--csd-ink); padding:5px 6px; border-radius:6px; cursor:pointer;
  text-transform:none; letter-spacing:0;
}
.crm-sales-an .csd-ms-opt:hover{background:var(--csd-bg);}
.crm-sales-an .csd-ms-opt input{margin-top:2px; flex:0 0 auto;}
.crm-sales-an .csd-ms-empty{font-size:12px; color:var(--csd-ink-mute); padding:8px;}
.crm-sales-an .csd-custom-dates{display:none; gap:6px; flex-wrap:nowrap; align-items:flex-end; flex:0 0 auto;}
.crm-sales-an .csd-custom-dates.is-visible{display:flex;}
.crm-sales-an .csd-custom-dates label{flex:0 0 118px; max-width:118px;}
.crm-sales-an .csd-toolbar button,
.crm-sales-an .csd-toolbar a.csd-btn-clear{
  border:1px solid var(--csd-line); background:var(--csd-bg); color:var(--csd-ink-soft);
  font-size:12px; font-weight:600; padding:5px 10px; border-radius:6px; cursor:pointer;
  height:30px; white-space:nowrap; flex:0 0 auto; text-decoration:none !important;
  display:inline-flex; align-items:center;
}
.crm-sales-an .csd-toolbar button:hover,
.crm-sales-an .csd-toolbar a.csd-btn-clear:hover{background:var(--csd-line);}
.crm-sales-an .csd-toolbar button.csd-btn-primary{
  background:var(--csd-green); border-color:var(--csd-green); color:#fff;
}
.crm-sales-an .csd-toolbar button.csd-btn-primary:hover{background:var(--csd-green-dark);}
.crm-sales-an .csd-chips{display:flex; gap:8px; flex-wrap:wrap; margin-bottom:14px; min-height:0;}
.crm-sales-an .csd-chip{
  display:flex; align-items:center; gap:6px; background:var(--csd-orange-tint); color:#8A4413;
  border:1px solid #F3CFA3; font-size:12px; font-weight:600; padding:6px 8px 6px 12px; border-radius:20px;
}
.crm-sales-an .csd-chip button{
  border:none; background:rgba(138,68,19,0.12); color:#8A4413; width:16px; height:16px;
  border-radius:50%; cursor:pointer; font-size:11px; line-height:1; display:flex;
  align-items:center; justify-content:center; padding:0;
}
.crm-sales-an .csd-kpi-row{display:grid; grid-template-columns:repeat(4,1fr); gap:12px; margin-bottom:14px;}
.crm-sales-an .csd-kpi-row.csd-kpi-row-3{grid-template-columns:repeat(3,1fr);}
.crm-sales-an .csd-kpi{
  background:var(--csd-surface); border:1px solid var(--csd-line); border-radius:var(--csd-radius);
  box-shadow:var(--csd-shadow); padding:12px 14px;
}
.crm-sales-an .csd-kpi.c-green{background:var(--csd-green-tint); border-color:#CDE9D6;}
.crm-sales-an .csd-kpi.c-orange{background:var(--csd-orange-tint); border-color:#F3CFA3;}
.crm-sales-an .csd-kpi.c-blue{background:var(--csd-blue-tint); border-color:#C7DBFB;}
.crm-sales-an .csd-kpi .label{font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.04em; color:var(--csd-ink-mute); margin:0;}
.crm-sales-an .csd-kpi .value{font-size:1.25rem; font-weight:700; margin:6px 0 0; color:var(--csd-green-dark);}
.crm-sales-an .csd-kpi .delta{font-size:12px; font-weight:600; margin-top:6px; color:var(--csd-ink-soft); line-height:1.35;}
.crm-sales-an .csd-note{font-size:12px; color:var(--csd-ink-mute); margin:0 0 12px;}
.crm-sales-an .csd-panel{
  background:var(--csd-surface); border:1px solid var(--csd-line); border-radius:var(--csd-radius);
  box-shadow:var(--csd-shadow); padding:14px 16px; margin-bottom:12px;
}
.crm-sales-an .csd-panel h2{font-size:15px; font-weight:700; margin:0 0 4px;}
.crm-sales-an .table-scroll{overflow:auto; max-height:520px;}
.crm-sales-an table{
  width:100% !important; min-width:960px; border-collapse:collapse;
  table-layout:auto !important; font-size:12.5px;
}
.crm-sales-an th,.crm-sales-an td{
  padding:8px 8px !important; border-bottom:1px solid var(--csd-line);
  text-align:left !important; vertical-align:top !important;
  white-space:normal !important; overflow:visible !important;
  text-overflow:clip !important; word-break:break-word; overflow-wrap:anywhere;
  font-size:12.5px !important; line-height:1.35;
}
.crm-sales-an th{
  position:sticky; top:0; z-index:1; background:#fff;
  font-size:10px !important; text-transform:uppercase; letter-spacing:.03em;
  color:var(--csd-ink-mute); font-weight:700;
}
.crm-sales-an td.num,.crm-sales-an th.num{
  text-align:right !important; white-space:nowrap !important;
  width:1%; font-variant-numeric:tabular-nums; font-weight:600;
  overflow:visible !important; text-overflow:clip !important;
}
.crm-sales-an .name-cell{min-width:220px;}
.crm-sales-an .col-text{min-width:140px;}
.crm-sales-an .cliente-code{
  display:inline-block; font-family:ui-monospace,Consolas,monospace;
  font-size:11px; font-weight:700; color:var(--csd-ink-mute); margin-right:6px;
  white-space:nowrap !important;
}
.crm-sales-an .cliente-nome{font-weight:600; color:var(--csd-ink);}
.crm-sales-an .abc{
  display:inline-flex; width:22px; height:22px; align-items:center; justify-content:center;
  border-radius:50%; font-size:11px; font-weight:700;
}
.crm-sales-an .abc.a{background:var(--csd-green-tint); color:var(--csd-green-dark);}
.crm-sales-an .abc.b{background:var(--csd-orange-tint); color:#8A4413;}
.crm-sales-an .abc.c{background:#EEE; color:#555;}
.crm-sales-an .tag-novo{background:var(--csd-blue-tint); color:#1D4489; font-size:11px; font-weight:700; border-radius:10px; padding:2px 7px;}
.crm-sales-an .tag-rec{background:#EEE; color:#555; font-size:11px; font-weight:700; border-radius:10px; padding:2px 7px;}
@media (max-width:900px){
  .crm-sales-an .csd-kpi-row,
  .crm-sales-an .csd-kpi-row.csd-kpi-row-3{grid-template-columns:1fr 1fr;}
}
@media (max-width:560px){
  .crm-sales-an .csd-kpi-row,
  .crm-sales-an .csd-kpi-row.csd-kpi-row-3{grid-template-columns:1fr;}
}
