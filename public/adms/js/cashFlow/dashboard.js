(function () {
  const root = document.getElementById('fcfDash');
  if (!root) return;

  const apiUrl = root.dataset.apiUrl || '';
  const syncUrl = root.dataset.syncUrl || '';
  const canSync = root.dataset.canSync === '1';
  const money = new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' });
  const moneyShort = new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL', maximumFractionDigits: 0 });

  let payload = null;
  let chartLine = null;
  let chartBars = null;
  const monthNames = ['Jan','Fev','Mar','Abr','Mai','Jun','Jul','Ago','Set','Out','Nov','Dez'];

  function $(id) { return document.getElementById(id); }
  function fmt(v, short) {
    if (v === null || v === undefined || Number.isNaN(Number(v))) return '—';
    return (short ? moneyShort : money).format(Number(v));
  }
  function cls(v) { return Number(v) < 0 ? 'neg' : (Number(v) > 0 ? 'pos' : ''); }
  function cellCls(v) { return Number(v) < 0 ? 'sneg' : (Number(v) > 0 ? 'spos' : ''); }

  function setLoading(on, text) {
    root.classList.toggle('loading', !!on);
    const sp = $('fcfSpinner');
    if (sp && text) sp.textContent = text;
  }
  function showError(msg) {
    const el = $('fcfError');
    if (!el) return;
    el.style.display = msg ? 'block' : 'none';
    el.textContent = msg || '';
  }
  function showWarn(msg) {
    const el = $('fcfWarn');
    if (!el) return;
    el.style.display = msg ? 'block' : 'none';
    el.textContent = msg || '';
  }

  function filters() {
    const periodo = ($('fPeriodo')?.value || '').split('-');
    return {
      year: periodo[0] || new Date().getFullYear(),
      month: periodo[1] || (new Date().getMonth() + 1),
      branch_id: $('fFilial')?.value || '',
      account: $('fConta')?.value || '',
      scenario: $('fCenario')?.value || 'both',
      horizon: $('fHorizonte')?.value || 30,
    };
  }

  async function load(skipAuto) {
    setLoading(true, 'Carregando…');
    showError('');
    try {
      const body = Object.assign({ skip_auto_sync: !!skipAuto }, filters());
      const res = await fetch(apiUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify(body),
      });
      const json = await res.json();
      if (!res.ok || json.success === false) {
        throw new Error(json.error || 'Falha ao carregar o dashboard.');
      }
      payload = json;
      fillFilters(json);
      render(json);
      showWarn(json.warning || (json.empty_cache ? 'Cache vazio. Clique em Sincronizar SAP para a primeira carga.' : ''));
    } catch (err) {
      showError(err.message || String(err));
    } finally {
      setLoading(false);
    }
  }

  function fillFilters(data) {
    const f = data.filters || {};
    if ($('fPeriodo') && f.year && f.month) {
      $('fPeriodo').value = String(f.year) + '-' + String(f.month).padStart(2, '0');
    }
    const fil = $('fFilial');
    if (fil && Array.isArray(data.branches)) {
      const cur = fil.value;
      fil.innerHTML = '<option value="">Todas</option>';
      data.branches.forEach(function (b) {
        const id = b.sap_bpl_id ?? b.sap_bpl_id;
        const opt = document.createElement('option');
        opt.value = id;
        opt.textContent = 'Filial ' + id;
        fil.appendChild(opt);
      });
      if (cur) fil.value = cur;
    }
    const acc = $('fConta');
    if (acc && Array.isArray(data.account_options)) {
      const cur = acc.value;
      acc.innerHTML = '<option value="">Todas</option>';
      data.account_options.forEach(function (a) {
        const opt = document.createElement('option');
        opt.value = a.gl;
        opt.textContent = a.label;
        acc.appendChild(opt);
      });
      if (cur) acc.value = cur;
    }
    const sync = data.sync || {};
    const meta = $('fcfSyncMeta');
    if (meta) {
      if (sync.last_success_at) {
        meta.textContent = 'Última sincronização concluída: ' + formatDt(sync.last_success_at)
          + ' · modo ' + (sync.last_mode || '—');
      } else {
        meta.textContent = 'Ainda não houve sincronização com sucesso.';
      }
    }
  }

  function formatDt(s) {
    if (!s) return '—';
    const d = new Date(String(s).replace(' ', 'T'));
    if (Number.isNaN(d.getTime())) return s;
    return d.toLocaleString('pt-BR');
  }

  function kpiCard(label, value, hint, kind) {
    return '<div class="fcf-kpi ' + (kind || '') + '"><div class="kt">' + label + '</div>'
      + '<div class="kv ' + cls(value) + '">' + fmt(value, true) + '</div>'
      + '<div class="kh">' + (hint || '') + '</div></div>';
  }

  function render(data) {
    const k = data.kpis || {};
    $('kpiRow1').innerHTML = [
      kpiCard('Saldo inicial', k.saldo_inicial, 'Posição no início do período', 'accent'),
      kpiCard('Acumulado efetivo', k.acumulado_efetivo, 'Até a data de corte', 'pos'),
      kpiCard('Projeção no fim do mês', k.projecao_fim, 'Após previstos do período'),
      kpiCard('Aplicações', k.aplicacoes, 'Locais + contas classificadas'),
      kpiCard('Limites disponíveis', k.limites, 'Crédito bancário cadastrado'),
    ].join('');
    $('kpiRow2').innerHTML = [
      kpiCard('A receber · horizonte', k.a_receber, 'Títulos em aberto por vencimento', 'pos'),
      kpiCard('A pagar · horizonte', k.a_pagar, 'Títulos em aberto por vencimento', 'neg'),
      kpiCard('Disponibilidade própria', k.disponibilidade_propria, 'Saldo financeiro + aplicações', 'pos'),
      kpiCard('Necessidade de caixa', k.necessidade_caixa, 'Receber × pagar no horizonte', Number(k.necessidade_caixa) < 0 ? 'neg' : 'pos'),
      kpiCard('Menor saldo projetado', k.menor_saldo_projetado, k.menor_saldo_data ? ('Em ' + k.menor_saldo_data.split('-').reverse().join('/')) : '', 'warn'),
    ].join('');

    renderDaily(data.daily || [], data.filters?.scenario || 'both');
    renderCharts(data.daily || []);
    renderMonthly(data.monthly || {});
    renderAccounts(data.accounts || []);
    renderInvestments(data.investments_monthly || {});
    renderDetail(data);
  }

  function renderDaily(rows, scenario) {
    const showEf = scenario !== 'pv';
    const showPv = scenario !== 'ef';
    let tRecE = 0, tDesE = 0, tRecP = 0, tDesP = 0;
    const ef = [];
    const pv = [];
    rows.forEach(function (r) {
      tRecE += Number(r.receita_ef || 0);
      tDesE += Number(r.despesa_ef || 0);
      tRecP += Number(r.receita_prev || 0);
      tDesP += Number(r.despesa_prev || 0);
      const today = r.is_today ? ' class="today-row"' : '';
      ef.push('<tr' + today + '><td>' + r.day + '</td>'
        + '<td class="drill" data-kind="receita_ef" data-date="' + r.date + '">' + fmt(r.receita_ef) + '</td>'
        + '<td class="drill" data-kind="despesa_ef" data-date="' + r.date + '">' + fmt(r.despesa_ef) + '</td>'
        + '<td class="' + cellCls(r.saldo_ef) + '">' + fmt(r.saldo_ef) + '</td>'
        + '<td>' + (r.acum_ef === null ? '—' : fmt(r.acum_ef)) + '</td></tr>');
      pv.push('<tr' + today + '><td>' + r.day + '</td>'
        + '<td class="drill" data-kind="receita_prev" data-date="' + r.date + '">' + fmt(r.receita_prev) + '</td>'
        + '<td class="drill" data-kind="despesa_prev" data-date="' + r.date + '">' + fmt(r.despesa_prev) + '</td>'
        + '<td class="' + cellCls(r.saldo_prev) + '">' + fmt(r.saldo_prev) + '</td>'
        + '<td>' + fmt(r.acum_proj) + '</td></tr>');
    });
    $('tblEf').innerHTML = ef.join('') || '<tr><td colspan="5">Sem movimentos efetivos no período.</td></tr>';
    $('tblPv').innerHTML = pv.join('') || '<tr><td colspan="5">Sem títulos previstos no período.</td></tr>';
    $('footEf').innerHTML = '<tr><td>Total</td><td>' + fmt(tRecE) + '</td><td>' + fmt(tDesE) + '</td><td class="' + cellCls(tRecE - tDesE) + '" colspan="2">' + fmt(tRecE - tDesE) + '</td></tr>';
    $('footPv').innerHTML = '<tr><td>Total</td><td>' + fmt(tRecP) + '</td><td>' + fmt(tDesP) + '</td><td class="' + cellCls(tRecP - tDesP) + '" colspan="2">' + fmt(tRecP - tDesP) + '</td></tr>';

    const combo = $('tblCombo');
    combo.querySelector('thead').innerHTML = '<tr><th>Dia</th>'
      + (showEf ? '<th>Receita Ef.</th><th>Despesa Ef.</th><th>Saldo Ef.</th>' : '')
      + (showPv ? '<th class="prev-col">Receita Prev.</th><th class="prev-col">Despesa Prev.</th><th class="prev-col">Saldo Prev.</th>' : '')
      + '<th>Acumulado projetado</th></tr>';
    combo.querySelector('tbody').innerHTML = rows.map(function (r) {
      return '<tr' + (r.is_today ? ' class="today-row"' : '') + '><td>' + r.day + '</td>'
        + (showEf ? '<td>' + fmt(r.receita_ef) + '</td><td>' + fmt(r.despesa_ef) + '</td><td class="' + cls(r.saldo_ef) + '">' + fmt(r.saldo_ef) + '</td>' : '')
        + (showPv ? '<td class="prev-col">' + fmt(r.receita_prev) + '</td><td class="prev-col">' + fmt(r.despesa_prev) + '</td><td class="prev-col ' + cls(r.saldo_prev) + '">' + fmt(r.saldo_prev) + '</td>' : '')
        + '<td class="' + cls(r.acum_proj) + '">' + fmt(r.acum_proj) + '</td></tr>';
    }).join('');
  }

  function renderCharts(rows) {
    if (typeof Chart === 'undefined') return;
    const labels = rows.map(function (r) { return String(r.day).padStart(2, '0'); });
    const efetivo = rows.map(function (r) { return r.is_effective ? r.acum_ef : null; });
    const proj = rows.map(function (r) { return r.acum_proj; });
    const rec = rows.map(function (r) { return r.receita_prev; });
    const des = rows.map(function (r) { return r.despesa_prev; });

    const lineCfg = {
      type: 'line',
      data: {
        labels: labels,
        datasets: [
          { label: 'Efetivo', data: efetivo, borderColor: '#12532F', backgroundColor: 'transparent', borderWidth: 3, spanGaps: false, tension: .2 },
          { label: 'Projetado', data: proj, borderColor: '#8A9189', backgroundColor: 'transparent', borderWidth: 3, borderDash: [7, 5], tension: .2 },
        ],
      },
      options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: true } }, scales: { y: { ticks: { callback: function (v) { return moneyShort.format(v); } } } } },
    };
    const barCfg = {
      type: 'bar',
      data: {
        labels: labels,
        datasets: [
          { label: 'Receita prev.', data: rec, backgroundColor: '#2F6FDE' },
          { label: 'Despesa prev.', data: des, backgroundColor: '#D14343' },
        ],
      },
      options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: true } }, scales: { y: { ticks: { callback: function (v) { return moneyShort.format(v); } } } } },
    };
    if (chartLine) chartLine.destroy();
    if (chartBars) chartBars.destroy();
    const c1 = $('chartLine');
    const c2 = $('chartBars');
    if (c1) chartLine = new Chart(c1, lineCfg);
    if (c2) chartBars = new Chart(c2, barCfg);
  }

  function renderMonthly(monthly) {
    const yearEl = $('monthlyYear');
    if (yearEl) yearEl.textContent = monthly.year || '';
    const tbl = $('tblMonth');
    if (!tbl) return;
    const cur = Number(monthly.current_month || 0);
    let head = '<thead><tr><th>Fluxo</th>';
    monthNames.forEach(function (n, i) {
      head += '<th' + (i + 1 === cur ? ' class="cur"' : '') + '>' + n + '</th>';
    });
    head += '</tr></thead>';
    const defs = [
      ['Saldo inicial', 'saldo_inicial'],
      ['Limites', 'limites'],
      ['Aplicações', 'aplicacoes'],
      ['Receitas efetivas', 'receitas_efetivas', 'pos'],
      ['Despesas efetivas', 'despesas_efetivas', 'neg'],
      ['Saldo financeiro', 'saldo_financeiro'],
      ['Receitas previstas', 'receitas_previstas', 'pos'],
      ['Despesas previstas', 'despesas_previstas', 'neg'],
      ['Necessidade de caixa', 'necessidade'],
      ['Saldo acumulado', 'saldo_acumulado'],
    ];
    const rows = monthly.rows || {};
    let body = '<tbody>';
    defs.forEach(function (def) {
      body += '<tr><td>' + def[0] + '</td>';
      for (let m = 1; m <= 12; m++) {
        const v = (rows[def[1]] || {})[m] ?? 0;
        body += '<td class="' + (def[2] || cls(v)) + '">' + fmt(v, true) + '</td>';
      }
      body += '</tr>';
    });
    body += '</tbody>';
    tbl.innerHTML = head + body;
  }

  function typeBadge(type) {
    const map = { BANK: ['banco', 'Conta corrente'], CASH: ['caixa', 'Caixa'], INVESTMENT: ['aplic', 'Aplicação'], TRANSIT: ['caixa', 'Trânsito'], OTHER: ['banco', 'Outra'] };
    const m = map[type] || ['banco', type || '—'];
    return '<span class="badge ' + m[0] + '">' + m[1] + '</span>';
  }

  function renderAccounts(rows) {
    $('tblAccounts').innerHTML = rows.map(function (r) {
      return '<tr><td>' + (r.gl || '') + '</td><td>' + (r.bank || '') + '</td><td>' + typeBadge(r.type) + '</td>'
        + '<td>' + fmt(r.saldo) + '</td><td>' + fmt(r.aplicacao) + '</td><td>' + fmt(r.limite) + '</td>'
        + '<td class="' + cls(r.disponibilidade) + '">' + fmt(r.disponibilidade) + '</td></tr>';
    }).join('') || '<tr><td colspan="7">Nenhuma conta parametrizada. Importe as contas do SAP.</td></tr>';
  }

  function renderInvestments(inv) {
    const wrap = $('invTables');
    if (!wrap) return;
    const tables = inv.tables || [];
    if (!tables.length) {
      wrap.innerHTML = '<p class="kh">Nenhum lançamento de aplicação. Use “Lançar aplicações” para registrar aplicação, resgate ou rendimento.</p>';
      return;
    }
    wrap.innerHTML = tables.map(function (t) {
      let html = '<h3 style="font-size:15px;margin:12px 0 8px;">' + t.title + '</h3>';
      html += '<div style="overflow:auto;"><table class="month"><thead><tr><th>Bancos</th>';
      monthNames.forEach(function (n) { html += '<th>' + n + '</th>'; });
      html += '<th>Total</th></tr></thead><tbody>';
      (t.banks || []).forEach(function (b) {
        html += '<tr><td>' + b.label + '</td>';
        for (let m = 1; m <= 12; m++) html += '<td>' + fmt(b.months[m] || 0, true) + '</td>';
        html += '<td>' + fmt(b.total || 0, true) + '</td></tr>';
      });
      html += '<tr><td>Saldo</td>';
      for (let m = 1; m <= 12; m++) html += '<td>' + fmt((t.saldo || {})[m] || 0, true) + '</td>';
      html += '<td>' + fmt(t.grand_total || 0, true) + '</td></tr></tbody></table></div>';
      return html;
    }).join('');
  }

  function renderDetail(data) {
    const rows = data.forecast_period || [];
    $('tblDetail').innerHTML = rows.map(function (r) {
      const tipo = r.source_type === 'AR' ? 'A receber' : 'A pagar';
      const due = String(r.due_date || '').split('-').reverse().join('/');
      return '<tr><td>' + due + '</td><td>' + tipo + '</td><td>' + (r.document || '') + '</td>'
        + '<td>' + (r.partner || '') + '</td><td>' + (r.installment || '') + '</td>'
        + '<td class="' + cls(r.open_amount) + '">' + fmt(r.open_amount) + '</td></tr>';
    }).join('') || '<tr><td colspan="6">Sem títulos previstos no mês. Clique em um valor da tabela diária para o detalhe por documento.</td></tr>';
  }

  async function openDrill(date, kind) {
    const labels = {
      receita_ef: 'Receitas efetivas',
      despesa_ef: 'Despesas efetivas',
      receita_prev: 'Receitas previstas',
      despesa_prev: 'Despesas previstas',
    };
    $('drillTitle').textContent = (labels[kind] || kind) + ' — ' + date.split('-').reverse().join('/');
    $('drillSub').textContent = 'Carregando documentos…';
    $('drillBody').innerHTML = '';
    $('fcfDrawer').style.display = 'block';
    try {
      const body = Object.assign({ action: 'drill', date: date, kind: kind }, filters());
      const res = await fetch(apiUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify(body),
      });
      const json = await res.json();
      const rows = json.rows || [];
      const isPrev = kind.indexOf('prev') >= 0;
      $('drillHead').innerHTML = isPrev
        ? '<tr><th>Documento</th><th>Parceiro</th><th>Parcela</th><th>Vencimento</th><th>Original</th><th>Pago</th><th>Saldo</th></tr>'
        : '<tr><th>Documento</th><th>Parceiro / origem</th><th>Conta</th><th>Histórico</th><th>Valor</th></tr>';
      $('drillBody').innerHTML = rows.map(function (r) {
        if (isPrev) {
          return '<tr><td>' + (r.document || '') + '</td><td>' + (r.partner || '') + '</td><td>' + (r.installment || '') + '</td>'
            + '<td>' + (r.due_date || '') + '</td><td>' + fmt(r.original) + '</td><td>' + fmt(r.paid) + '</td>'
            + '<td class="' + cls(r.amount) + '">' + fmt(r.amount) + '</td></tr>';
        }
        return '<tr><td>' + (r.document || '') + '</td><td>' + (r.partner || '') + '</td><td>' + (r.account || '') + '</td>'
          + '<td>' + (r.memo || '') + '</td><td class="' + cls(r.amount) + '">' + fmt(r.amount) + '</td></tr>';
      }).join('') || '<tr><td colspan="7">Nenhum documento encontrado para este valor.</td></tr>';
      $('drillSub').textContent = rows.length + ' documento(s). Origem: ' + (isPrev ? 'títulos em aberto no cache' : 'consulta pontual SAP / JDT1') + '.';
    } catch (err) {
      $('drillSub').textContent = err.message || 'Falha no detalhamento.';
    }
  }

  async function syncNow() {
    if (!canSync) return;
    setLoading(true, 'Sincronizando SAP…');
    showError('');
    try {
      const res = await fetch(syncUrl, { method: 'POST', headers: { 'Accept': 'application/json' } });
      const json = await res.json();
      if (!res.ok || json.success === false) {
        throw new Error(json.error || json.message || 'Falha na sincronização.');
      }
      showWarn(json.message || 'Sincronização concluída.');
      await load(true);
    } catch (err) {
      showError(err.message || String(err));
      setLoading(false);
    }
  }

  document.querySelectorAll('.tab').forEach(function (tab) {
    tab.addEventListener('click', function () {
      document.querySelectorAll('.tab').forEach(function (t) { t.classList.remove('active'); });
      tab.classList.add('active');
      ['daily', 'monthly', 'accounts', 'investments', 'detail'].forEach(function (id) {
        const el = document.getElementById('tab-' + id);
        if (el) el.classList.toggle('hidden', id !== tab.dataset.tab);
      });
    });
  });

  root.addEventListener('click', function (ev) {
    const t = ev.target.closest('.drill');
    if (!t) return;
    openDrill(t.dataset.date, t.dataset.kind);
  });

  $('btnAplicar')?.addEventListener('click', function () { load(true); });
  $('btnSync')?.addEventListener('click', syncNow);
  $('fcfCloseDrill')?.addEventListener('click', function () { $('fcfDrawer').style.display = 'none'; });
  $('fcfDrawer')?.addEventListener('click', function (ev) {
    if (ev.target.id === 'fcfDrawer') $('fcfDrawer').style.display = 'none';
  });

  load(false);
})();
