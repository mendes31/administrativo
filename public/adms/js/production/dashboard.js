(function () {
  'use strict';

  const root = document.getElementById('prdDash');
  if (!root) return;

  const apiUrl = root.getAttribute('data-api-url') || '';
  const syncUrl = root.getAttribute('data-sync-url') || '';
  const canSync = root.getAttribute('data-can-sync') === '1';
  const COR_SKU = '#2F6FDE';
  const COR_PROD = '#E67E2E';
  const COR_INI = '#1B7A49';
  const COR_CONC = '#E67E2E';
  const COR_REAL = '#12532F';
  const COR_PLAN = '#C5CBC3';

  let filtro = { periodo: '30', date_from: '', date_to: '', linha: null };
  let charts = {};
  let lastChartData = null;
  let abortCtrl = null;

  const fmtInt = (v) => (v === null || v === undefined) ? '—' : Number(v).toLocaleString('pt-BR');
  const fmtNum = (v, d) => {
    if (v === null || v === undefined || Number.isNaN(Number(v))) return '—';
    return Number(v).toLocaleString('pt-BR', { maximumFractionDigits: d ?? 1, minimumFractionDigits: 0 });
  };
  const fmtPct = (v) => (v === null || v === undefined) ? '—' : fmtNum(v, 1) + '%';

  function $(id) { return document.getElementById(id); }

  function showError(msg) {
    const el = $('prdError');
    if (!el) return;
    el.style.display = msg ? 'block' : 'none';
    el.textContent = msg || '';
  }
  function showWarn(msg) {
    const el = $('prdWarn');
    if (!el) return;
    el.style.display = msg ? 'block' : 'none';
    el.textContent = msg || '';
  }
  function setLoading(on, label) {
    root.classList.toggle('prd-loading', !!on);
    const spin = $('prdSpinnerText');
    if (spin && label) spin.textContent = label;
  }

  function isPersonalizado() { return filtro.periodo === 'personalizado'; }

  function toggleCustomDates() {
    const box = $('customDates');
    const show = isPersonalizado();
    box.classList.toggle('is-visible', show);
    box.setAttribute('aria-hidden', show ? 'false' : 'true');
  }

  function deltaText(prefix, delta, suffix) {
    if (!delta || delta.dir === 'flat' || (delta.abs === 0)) {
      return { cls: 'flat', text: 'estável vs. período anterior' };
    }
    const sign = delta.abs > 0 ? '▲ ' : '▼ ';
    return { cls: delta.dir, text: sign + prefix + Math.abs(delta.abs).toLocaleString('pt-BR') + (suffix || '') + ' vs. período anterior' };
  }
  function deltaPctText(pct, invertGood) {
    if (pct === null || pct === undefined) return { cls: 'flat', text: '' };
    const up = pct > 0;
    const cls = invertGood ? (up ? 'down' : 'up') : (up ? 'up' : (pct < 0 ? 'down' : 'flat'));
    const sign = up ? '▲ ' : (pct < 0 ? '▼ ' : '');
    return { cls: cls, text: sign + Math.abs(pct).toLocaleString('pt-BR') + '% vs. período anterior' };
  }

  function setDelta(id, info) {
    const el = $(id);
    if (!el) return;
    el.className = 'delta ' + (info.cls || 'flat');
    el.textContent = info.text || '';
  }

  function destruirCharts() {
    Object.values(charts).forEach((c) => c && c.destroy());
    charts = {};
  }

  function isMobileView() {
    return window.matchMedia('(max-width: 768px)').matches;
  }

  function chartOpts() {
    const mobile = isMobileView();
    return {
      responsive: true,
      maintainAspectRatio: false,
      plugins: { legend: { display: false } },
      scales: {
        x: {
          grid: { display: false },
          ticks: {
            color: '#8A9189',
            font: { size: mobile ? 9 : 11 },
            maxRotation: mobile ? 45 : 0,
            minRotation: mobile ? 45 : 0,
            autoSkip: true,
            maxTicksLimit: mobile ? 6 : undefined
          }
        },
        y: {
          beginAtZero: true,
          grid: { color: '#E2E6E0' },
          ticks: {
            color: '#8A9189',
            font: { size: mobile ? 9 : 11 },
            maxTicksLimit: mobile ? 5 : undefined
          }
        }
      }
    };
  }

  function renderCharts(data) {
    if (typeof Chart === 'undefined') {
      showError('Chart.js não carregou. Verifique o vendor chartjs.');
      return;
    }
    lastChartData = data;
    destruirCharts();
    const s = data.series || {};
    const labels = s.labels || [];

    const optsSkus = chartOpts();
    const mobile = isMobileView();
    optsSkus.scales.y.title = { display: !mobile, text: 'Unidades', color: '#8A9189', font: { size: 10 } };
    optsSkus.scales.y2 = {
      beginAtZero: true,
      position: 'right',
      grid: { drawOnChartArea: false },
      ticks: { color: '#8A9189', font: { size: mobile ? 9 : 11 }, maxTicksLimit: mobile ? 5 : undefined },
      title: { display: !mobile, text: 'Produtos', color: '#8A9189', font: { size: 10 } }
    };
    charts.skus = new Chart($('chartSkus'), {
      type: 'bar',
      data: {
        labels,
        datasets: [
          { label: 'SKUs (unidades)', data: s.skus || [], backgroundColor: COR_SKU, borderRadius: 3, maxBarThickness: mobile ? 16 : 22, yAxisID: 'y' },
          { label: 'Produtos', data: s.produtos || [], backgroundColor: COR_PROD, borderRadius: 3, maxBarThickness: mobile ? 16 : 22, yAxisID: 'y2' }
        ]
      },
      options: optsSkus
    });

    const barThickness = mobile ? 16 : 22;
    charts.volume = new Chart($('chartVolume'), {
      type: 'bar',
      data: {
        labels,
        datasets: [
          { label: 'Realizado', data: s.volume || [], backgroundColor: COR_REAL, borderRadius: 3, maxBarThickness: barThickness },
          { label: 'Planejado', data: s.planejado || [], backgroundColor: COR_PLAN, borderRadius: 3, maxBarThickness: barThickness }
        ]
      },
      options: chartOpts()
    });

    charts.ordens = new Chart($('chartOrdens'), {
      type: 'line',
      data: {
        labels,
        datasets: [
          {
            label: 'Iniciadas', data: s.iniciadas || [], borderColor: COR_INI, backgroundColor: COR_INI,
            tension: 0.25, fill: false, pointRadius: mobile ? 3 : 4, pointHoverRadius: mobile ? 4 : 5
          },
          {
            label: 'Concluídas', data: s.concluidas || [], borderColor: COR_CONC, backgroundColor: COR_CONC,
            tension: 0.25, fill: false, pointRadius: mobile ? 3 : 4, pointHoverRadius: mobile ? 4 : 5
          }
        ]
      },
      options: chartOpts()
    });
  }

  function renderMeters(linhas) {
    const el = $('metersLinhas');
    if (!linhas || !linhas.length) {
      el.innerHTML = '<div class="prd-empty">Sem dados no período.</div>';
      return;
    }
    el.innerHTML = linhas.map((l) => {
      const pct = l.planejado > 0 ? Math.min(100, Math.round((l.concluido / l.planejado) * 100)) : 0;
      return '<div class="prd-meter"><span>' + escapeHtml(l.linha) + '</span>'
        + '<span><span class="prd-track"><span class="prd-fill" style="width:' + pct + '%"></span></span><strong>'
        + pct + '%</strong></span></div>';
    }).join('');
  }

  function fmtDate(iso) {
    if (!iso) return '—';
    const p = String(iso).slice(0, 10).split('-');
    if (p.length !== 3) return iso;
    return p[2] + '/' + p[1] + '/' + p[0];
  }

  function escapeHtml(s) {
    return String(s).replace(/[&<>"']/g, (c) => ({
      '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
    }[c]));
  }

  function renderOrders(rows) {
    const tb = $('tblOrdens');
    if (!rows || !rows.length) {
      tb.innerHTML = '<tr><td colspan="6">Nenhuma ordem em aberto.</td></tr>';
      return;
    }
    tb.innerHTML = rows.map((o) => {
      const pill = o.status === 'late' ? 'late' : 'running';
      const op = '<strong>OP-' + escapeHtml(o.op) + '</strong>';
      const status = '<span class="prd-pill ' + pill + '">' + escapeHtml(o.status_label) + '</span>';
      return '<tr>'
        + '<td data-label="Ordem">' + op + '</td>'
        + '<td data-label="SKU" class="compact">' + escapeHtml(o.sku) + '</td>'
        + '<td data-label="Linha">' + escapeHtml(o.linha) + '</td>'
        + '<td data-label="Prazo" class="compact">' + fmtDate(o.prazo) + '</td>'
        + '<td data-label="Progresso" class="num">' + fmtNum(o.progresso, 0) + '%</td>'
        + '<td data-label="Status">' + status + '</td></tr>';
    }).join('');
  }

  function renderTopSkus(rows) {
    const tb = $('tblSkus');
    if (!rows || !rows.length) {
      tb.innerHTML = '<tr><td colspan="3">Sem volume no período.</td></tr>';
      return;
    }
    tb.innerHTML = rows.map((r) =>
      '<tr><td class="compact">' + escapeHtml(r.sku) + '</td>'
      + '<td class="wrap">' + escapeHtml(r.item) + '</td>'
      + '<td class="num">' + fmtNum(r.volume, 2) + '</td></tr>'
    ).join('');
  }

  function fillLinhas(itens, selecionado) {
    const el = $('fLinha');
    const current = selecionado == null ? el.value : selecionado;
    el.innerHTML = '<option value="">Ambos (TJQP + APQP)</option>';
    (itens || []).forEach((v) => {
      const opt = document.createElement('option');
      opt.value = v;
      opt.textContent = v;
      el.appendChild(opt);
    });
    el.value = current || '';
  }

  function render(data) {
    const k = data.kpis || {};
    $('kpiSkus').textContent = fmtNum(k.skus, 0);
    setDelta('kpiSkusDelta', deltaText('', k.skus_delta, ' un.'));
    $('kpiProdutos').textContent = fmtInt(k.produtos);
    setDelta('kpiProdutosDelta', deltaText('', k.produtos_delta, ' produtos'));
    $('kpiVolume').textContent = fmtNum(k.volume, 2);
    setDelta('kpiVolumeDelta', deltaPctText(k.volume_delta_pct));
    $('kpiIniciadas').textContent = fmtInt(k.iniciadas);
    setDelta('kpiIniciadasDelta', deltaPctText(k.iniciadas_delta_pct));
    $('kpiConcluidas').innerHTML = fmtInt(k.concluidas)
      + (k.taxa_conclusao != null ? ' <small>' + fmtPct(k.taxa_conclusao) + ' das iniciadas</small>' : '');
    setDelta('kpiConcluidasDelta', deltaPctText(k.concluidas_delta_pct));
    $('kpiAndamento').textContent = fmtInt(k.andamento);
    $('kpiAtraso').textContent = fmtInt(k.atraso);
    $('kpiAtrasoHint').className = 'delta ' + (k.atraso > 0 ? 'down' : 'flat');
    $('kpiAtrasoHint').textContent = k.atraso > 0 ? 'Atenção' : 'dentro do prazo';
    $('kpiAderencia').textContent = fmtPct(k.aderencia);
    if (k.aderencia_delta_pp != null) {
      const d = k.aderencia_delta_pp;
      setDelta('kpiAderenciaDelta', {
        cls: d >= 0 ? 'up' : 'down',
        text: (d >= 0 ? '▲ ' : '▼ ') + Math.abs(d).toLocaleString('pt-BR') + ' p.p. vs. período anterior'
      });
    } else {
      setDelta('kpiAderenciaDelta', { cls: 'flat', text: '' });
    }

    $('kpiRefugo').textContent = fmtPct(k.taxa_refugo);
    $('kpiLead').textContent = k.lead_dias != null ? fmtNum(k.lead_dias, 1) + ' dias' : '—';
    $('kpiDowntime').textContent = k.downtime_horas != null ? fmtNum(k.downtime_horas, 1) + ' h' : '—';
    $('kpiOee').textContent = k.oee != null ? fmtPct(k.oee) : '—';

    const shop = data.shop_floor || {};
    $('shopFloorNote').textContent = shop.note || '';

    const periodo = data.periodo || {};
    $('periodoResumo').textContent = periodo.label || '—';
    const sync = data.sync || {};
    const src = sync.last_source ? String(sync.last_source).toUpperCase() : 'MySQL';
    $('prdSource').textContent = src;
    let meta = 'Cache: ' + (sync.cache_receipts || 0) + ' entradas · ' + (sync.cache_rows || 0) + ' ordens';
    if (sync.last_success_at) meta += ' · última sync ' + sync.last_success_at.replace('T', ' ').slice(0, 16);
    if (sync.beas_tables) meta += ' · BEAS: ' + sync.beas_tables;
    $('prdSyncMeta').textContent = meta;

    fillLinhas((data.filtros_opcoes || {}).linhas, filtro.linha);
    renderCharts(data);
    renderMeters(data.linhas || []);
    renderOrders(data.ordens_abertas || []);
    renderTopSkus(data.top_skus || []);
  }

  async function load(skipAuto) {
    if (!apiUrl) {
      showError('URL da API não configurada.');
      return;
    }
    if (abortCtrl) abortCtrl.abort();
    abortCtrl = new AbortController();
    setLoading(true, skipAuto ? 'Atualizando…' : 'Sincronizando e carregando…');
    showError('');
    try {
      const body = {
        periodo: filtro.periodo,
        date_from: filtro.date_from,
        date_to: filtro.date_to,
        linha: filtro.linha,
        skip_auto_sync: !!skipAuto
      };
      const res = await fetch(apiUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify(body),
        signal: abortCtrl.signal
      });
      const json = await res.json();
      if (!res.ok || json.success === false) {
        throw new Error(json.error || 'Falha ao carregar o dashboard.');
      }
      render(json);
      showWarn(json.warning || '');
    } catch (err) {
      if (err.name === 'AbortError') return;
      showError(err.message || String(err));
    } finally {
      setLoading(false);
    }
  }

  async function syncNow() {
    if (!canSync || !syncUrl) return;
    setLoading(true, 'Sincronizando SAP/BEAS…');
    showError('');
    try {
      const res = await fetch(syncUrl, {
        method: 'POST',
        headers: { 'Accept': 'application/json' }
      });
      const json = await res.json();
      if (!res.ok || json.success === false) {
        throw new Error(json.error || json.message || 'Falha no sync.');
      }
      showWarn(json.message || 'Sincronização concluída.');
      await load(true);
    } catch (err) {
      showError(err.message || String(err));
      setLoading(false);
    }
  }

  $('fPeriodo').addEventListener('change', function () {
    filtro.periodo = this.value;
    toggleCustomDates();
    if (!isPersonalizado()) load(true);
  });
  $('fLinha').addEventListener('change', function () {
    filtro.linha = this.value || null;
    load(true);
  });
  $('btnAtualizar').addEventListener('click', function () {
    if (isPersonalizado()) {
      filtro.date_from = $('fDateFrom').value;
      filtro.date_to = $('fDateTo').value;
    }
    load(true);
  });
  if (canSync && $('btnSyncSap')) {
    $('btnSyncSap').addEventListener('click', syncNow);
  }

  toggleCustomDates();
  load(false);

  let resizeTimer;
  window.addEventListener('resize', function () {
    if (!lastChartData) return;
    clearTimeout(resizeTimer);
    resizeTimer = setTimeout(function () {
      renderCharts(lastChartData);
    }, 200);
  });
})();
