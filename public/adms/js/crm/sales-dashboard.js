(function () {
  'use strict';

  const root = document.getElementById('crmSalesDash');
  if (!root) return;

  const apiUrl = root.getAttribute('data-api-url') || '';
  const syncUrl = root.getAttribute('data-sync-url') || '';
  const canSync = root.getAttribute('data-can-sync') === '1';
  const COR_BASE = '#1B7A49';
  const COR_SELECIONADO = '#E67E2E';
  const COR_DIM = '#D8DDD6';
  const PALETA_GRUPO = ['#1B7A49', '#E67E2E', '#2F6FDE', '#D14343', '#8A9189'];
  const PALETA_BARRAS = [
    '#1B7A49', '#2F9E62', '#E67E2E', '#2F6FDE', '#D14343',
    '#7C3AED', '#0D9488', '#CA8A04', '#DB2777', '#475569',
    '#65A30D', '#0284C7', '#EA580C', '#8B5CF6', '#059669'
  ];
  const BAR_ROW_PX = 36;
  const BAR_VISIBLE_ROWS = 10;
  const BAR_VIEW_H = BAR_VISIBLE_ROWS * BAR_ROW_PX;
  const rotulos = {
    vendedor: 'Vendedor',
    grupo_cliente: 'Grupo de cliente',
    regiao: 'Região',
    grupo_item: 'Grupo de item',
    ano_mes: 'Mês'
  };

  function defaultCustomRange() {
    const to = new Date();
    const from = new Date(to.getFullYear(), to.getMonth() - 11, 1);
    return {
      date_from: toYmd(from),
      date_to: toYmd(to)
    };
  }

  function toYmd(d) {
    const y = d.getFullYear();
    const m = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    return y + '-' + m + '-' + day;
  }

  const customDefaults = defaultCustomRange();

  let filtro = {
    periodo: '12',
    date_from: '',
    date_to: '',
    vendedor: null,
    grupo_cliente: null,
    regiao: null,
    grupo_item: null,
    ano_mes: null
  };
  let charts = {};
  let abortCtrl = null;
  let opcoesCache = null;
  let opcoesPeriodoKey = '';

  const fmtMoedaCompacta = (v) => {
    const sinal = v < 0 ? '-' : '';
    const abs = Math.abs(v);
    if (abs >= 1000000) return sinal + 'R$ ' + (abs / 1000000).toFixed(1).replace('.', ',') + 'M';
    if (abs >= 1000) return sinal + 'R$ ' + (abs / 1000).toFixed(0) + 'k';
    return sinal + 'R$ ' + Math.round(abs);
  };
  const fmtMoeda = (v) => (v < 0 ? '-' : '') + 'R$ ' + Math.abs(Math.round(v)).toLocaleString('pt-BR');

  function showError(msg) {
    const el = document.getElementById('csdError');
    if (!el) return;
    if (!msg) {
      el.style.display = 'none';
      el.textContent = '';
      return;
    }
    el.style.display = 'block';
    el.textContent = msg;
  }

  function showWarn(msg) {
    const el = document.getElementById('csdWarn');
    if (!el) return;
    if (!msg) {
      el.style.display = 'none';
      el.textContent = '';
      return;
    }
    el.style.display = 'block';
    el.textContent = msg;
  }

  function setLoading(on, label) {
    root.classList.toggle('csd-loading', !!on);
    const spin = document.getElementById('csdSpinnerText');
    if (spin && label) spin.textContent = label;
  }

  function periodoKey() {
    if (isPersonalizado()) {
      return 'custom:' + (filtro.date_from || '') + ':' + (filtro.date_to || '');
    }
    return 'p:' + String(filtro.periodo || '12');
  }

  function destruirCharts() {
    Object.values(charts).forEach((c) => c && c.destroy());
    charts = {};
  }

  function isPersonalizado() {
    return filtro.periodo === 'personalizado';
  }

  function toggleCustomDates() {
    const box = document.getElementById('customDates');
    const show = isPersonalizado();
    box.classList.toggle('is-visible', show);
    box.setAttribute('aria-hidden', show ? 'false' : 'true');
    if (show) {
      const fromEl = document.getElementById('fDateFrom');
      const toEl = document.getElementById('fDateTo');
      if (!fromEl.value) fromEl.value = filtro.date_from || customDefaults.date_from;
      if (!toEl.value) toEl.value = filtro.date_to || customDefaults.date_to;
      filtro.date_from = fromEl.value;
      filtro.date_to = toEl.value;
    }
  }

  function preencherSelect(id, itens, selecionado) {
    const el = document.getElementById(id);
    if (!el) return;
    const current = selecionado == null ? el.value : selecionado;
    el.innerHTML = '<option value="">' + (id === 'fRegiao' ? 'Todas' : 'Todos') + '</option>';
    (itens || []).forEach((v) => {
      const opt = document.createElement('option');
      opt.value = v;
      opt.textContent = v;
      el.appendChild(opt);
    });
    el.value = current || '';
  }

  function sincronizarToolbar() {
    document.getElementById('fPeriodo').value = String(filtro.periodo || '12');
    document.getElementById('fDateFrom').value = filtro.date_from || '';
    document.getElementById('fDateTo').value = filtro.date_to || '';
    document.getElementById('fVendedor').value = filtro.vendedor || '';
    document.getElementById('fGrupoCliente').value = filtro.grupo_cliente || '';
    document.getElementById('fRegiao').value = filtro.regiao || '';
    toggleCustomDates();
  }

  function renderChips() {
    const campos = ['vendedor', 'grupo_cliente', 'regiao', 'grupo_item', 'ano_mes'];
    const ativos = campos.filter((c) => filtro[c]);
    const el = document.getElementById('chipsRow');
    el.innerHTML = ativos.map((c) =>
      '<span class="csd-chip">' + rotulos[c] + ': ' + filtro[c] +
      '<button type="button" data-campo="' + c + '" aria-label="Remover filtro">×</button></span>'
    ).join('');
    el.querySelectorAll('button').forEach((btn) => {
      btn.addEventListener('click', () => {
        filtro[btn.getAttribute('data-campo')] = null;
        sincronizarToolbar();
        carregar();
      });
    });
  }

  function corCategoria(campo, valor, corPadrao) {
    if (!filtro[campo]) return corPadrao;
    return filtro[campo] === valor ? COR_SELECIONADO : COR_DIM;
  }

  function corBarra(campo, valor, index) {
    const base = PALETA_BARRAS[index % PALETA_BARRAS.length];
    if (!filtro[campo]) return base;
    return filtro[campo] === valor ? COR_SELECIONADO : COR_DIM;
  }

  function truncLabel(s, max) {
    const t = String(s == null ? '' : s);
    const lim = max || 34;
    if (t.length <= lim) return t;
    return t.slice(0, lim - 1) + '…';
  }

  function ensureBarScrollHost(canvasId) {
    const canvas = document.getElementById(canvasId);
    if (!canvas) return null;
    let inner = canvas.parentElement;
    if (!inner.classList.contains('csd-chart-inner')) {
      const scroll = document.createElement('div');
      scroll.className = 'csd-chart csd-chart-scroll';
      const newInner = document.createElement('div');
      newInner.className = 'csd-chart-inner';
      const old = canvas.parentElement;
      old.parentNode.insertBefore(scroll, old);
      scroll.appendChild(newInner);
      newInner.appendChild(canvas);
      old.remove();
      inner = newInner;
    }
    return {
      canvas: canvas,
      inner: inner,
      scroll: inner.parentElement
    };
  }

  function setBarChartViewport(canvasId, rowCount) {
    const host = ensureBarScrollHost(canvasId);
    if (!host) return BAR_VIEW_H;
    const n = Math.max(1, rowCount || 1);
    const needsScroll = n > BAR_VISIBLE_ROWS;
    // Até 10 itens: canvas = área fixa do painel (sem scroll).
    // Acima de 10: canvas cresce 1 linha por item e o painel rola.
    const contentH = needsScroll ? n * BAR_ROW_PX : BAR_VIEW_H;

    host.scroll.style.height = BAR_VIEW_H + 'px';
    host.scroll.style.minHeight = BAR_VIEW_H + 'px';
    host.scroll.style.maxHeight = BAR_VIEW_H + 'px';
    host.scroll.classList.toggle('is-scrollable', needsScroll);
    host.scroll.style.overflowY = needsScroll ? 'auto' : 'hidden';
    host.scroll.style.overflowX = 'hidden';
    host.scroll.scrollTop = 0;

    host.inner.style.height = contentH + 'px';
    host.inner.style.minHeight = contentH + 'px';
    host.inner.style.maxHeight = needsScroll ? 'none' : BAR_VIEW_H + 'px';

    host.canvas.removeAttribute('height');
    host.canvas.removeAttribute('width');
    host.canvas.style.display = 'block';
    host.canvas.style.height = contentH + 'px';
    host.canvas.style.width = '100%';
    return contentH;
  }

  function emptySeriesMessage(canvasId, msg) {
    const host = ensureBarScrollHost(canvasId);
    const wrap = host ? host.scroll : (document.getElementById(canvasId) || {}).parentElement;
    if (!wrap) return;
    let note = wrap.parentElement
      ? wrap.parentElement.querySelector('.csd-chart-empty[data-for="' + canvasId + '"]')
      : null;
    if (!note && wrap.parentElement) {
      note = wrap.parentElement.querySelector('.csd-chart-empty');
    }
    if (!msg) {
      if (note) note.remove();
      wrap.style.display = '';
      if (host) host.canvas.style.display = '';
      return;
    }
    wrap.style.display = 'none';
    const panel = wrap.closest('.csd-panel') || wrap.parentElement;
    if (!note) {
      note = document.createElement('div');
      note.className = 'csd-chart-empty';
      note.setAttribute('data-for', canvasId);
      panel.appendChild(note);
    }
    note.textContent = msg;
  }

  function categoryTickOpts(maxLen) {
    return {
      autoSkip: false,
      font: { size: 11 },
      callback: function (value) {
        const label = this.getLabelForValue(value);
        return truncLabel(label, maxLen || 34);
      }
    };
  }

  /**
   * Limita a escala quando há outlier extremo, sem mensagem na UI.
   * Valor real permanece no rótulo/tooltip.
   */
  function adaptiveAxisMax(values, ratio) {
    const nums = (values || [])
      .map(Number)
      .filter((v) => Number.isFinite(v) && v > 0)
      .sort((a, b) => b - a);
    if (!nums.length) {
      return { max: 1, clipped: false };
    }
    const max = nums[0];
    const second = nums.length > 1 ? nums[1] : max;
    const lim = ratio || 2.2;
    if (nums.length >= 2 && max > second * lim) {
      return {
        max: Math.max(second * 1.28, second + 1),
        clipped: true
      };
    }
    return { max: max * 1.12, clipped: false };
  }

  const barValueLabelsPlugin = {
    id: 'csdBarValueLabels',
    afterDatasetsDraw: function (chart) {
      const meta = chart.getDatasetMeta(0);
      if (!meta || meta.hidden) return;
      const raw = chart.data.datasets[0].data || [];
      const horizontal = chart.options.indexAxis === 'y';
      const ctx = chart.ctx;
      const axisMax = horizontal ? chart.scales.x.max : chart.scales.y.max;
      ctx.save();
      ctx.font = '600 11px system-ui,Segoe UI,sans-serif';
      ctx.fillStyle = '#3D4841';
      meta.data.forEach(function (el, i) {
        const val = Number(raw[i]) || 0;
        if (!el || !Number.isFinite(val)) return;
        const clipped = chart.$csdClipped && val > axisMax * 0.999;
        const text = fmtMoedaCompacta(val) + (clipped ? ' ▸' : '');
        const pos = el.tooltipPosition();
        if (horizontal) {
          ctx.textAlign = 'left';
          ctx.textBaseline = 'middle';
          const x = Math.min(pos.x + 6, chart.chartArea.right - 4);
          ctx.fillText(text, x, pos.y);
        } else {
          ctx.textAlign = 'center';
          ctx.textBaseline = 'bottom';
          ctx.fillText(text, pos.x, pos.y - 4);
        }
      });
      ctx.restore();
    }
  };

  function createHorizontalBarChart(canvasId, rows, campoFiltro, labelMaxLen) {
    emptySeriesMessage(canvasId, rows.length ? '' : 'Sem dados para os filtros aplicados');
    if (!rows.length) return null;

    const contentH = setBarChartViewport(canvasId, rows.length);
    const values = rows.map((r) => r.valor);
    const scale = adaptiveAxisMax(values);
    const fewBars = rows.length <= BAR_VISIBLE_ROWS;

    const canvas = document.getElementById(canvasId);
    const chart = new Chart(canvas, {
      type: 'bar',
      data: {
        labels: rows.map((r) => r.label),
        datasets: [{
          data: values,
          backgroundColor: rows.map((r, i) => corBarra(campoFiltro, r.label, i)),
          borderRadius: 4,
          barPercentage: fewBars ? 0.55 : 0.7,
          categoryPercentage: 0.85,
          maxBarThickness: fewBars ? 28 : 22
        }]
      },
      options: {
        indexAxis: 'y',
        responsive: true,
        maintainAspectRatio: false,
        layout: { padding: { left: 2, right: 56, top: 8, bottom: 8 } },
        onClick: onClickChart(campoFiltro),
        plugins: {
          legend: { display: false },
          tooltip: {
            callbacks: {
              title: (items) => (items[0] && items[0].label) || '',
              label: (c) => fmtMoeda(c.parsed.x)
            }
          }
        },
        scales: {
          x: {
            beginAtZero: true,
            max: scale.max,
            grace: '0%',
            ticks: { callback: (v) => fmtMoedaCompacta(v), maxTicksLimit: 5 },
            grid: { color: '#E2E6E0' }
          },
          y: {
            grid: { display: false },
            ticks: categoryTickOpts(labelMaxLen || 34)
          }
        }
      },
      plugins: [barValueLabelsPlugin]
    });
    chart.$csdClipped = !!scale.clipped;
    requestAnimationFrame(function () {
      setBarChartViewport(canvasId, rows.length);
      if (chart && typeof chart.resize === 'function') {
        chart.resize();
      }
    });
    return chart;
  }

  function toggleFiltro(campo, valor) {
    filtro[campo] = filtro[campo] === valor ? null : valor;
    sincronizarToolbar();
    carregar();
  }

  function onClickChart(campo) {
    return (evt, elements, chart) => {
      if (!elements.length) return;
      const idx = elements[0].index;
      const label = chart.data.labels[idx];
      if (!label) return;
      toggleFiltro(campo, label);
    };
  }

  function buildQuery() {
    const params = new URLSearchParams();
    params.set('periodo', String(filtro.periodo || '12'));
    if (isPersonalizado()) {
      if (filtro.date_from) params.set('date_from', filtro.date_from);
      if (filtro.date_to) params.set('date_to', filtro.date_to);
    }
    if (filtro.vendedor) params.set('vendedor', filtro.vendedor);
    if (filtro.grupo_cliente) params.set('grupo_cliente', filtro.grupo_cliente);
    if (filtro.regiao) params.set('regiao', filtro.regiao);
    if (filtro.grupo_item) params.set('grupo_item', filtro.grupo_item);
    if (filtro.ano_mes) params.set('ano_mes', filtro.ano_mes);
    return params.toString();
  }

  function validarPeriodoCustom() {
    if (!isPersonalizado()) return true;
    if (!filtro.date_from || !filtro.date_to) {
      showError('Informe data início e data fim para o período personalizado.');
      return false;
    }
    if (filtro.date_from > filtro.date_to) {
      showError('A data início não pode ser posterior à data fim.');
      return false;
    }
    return true;
  }

  function fmtDt(s) {
    if (!s) return '—';
    const m = String(s).match(/^(\d{4})-(\d{2})-(\d{2})(?:[ T](\d{2}):(\d{2}))?/);
    if (!m) return s;
    let out = m[3] + '/' + m[2] + '/' + m[1];
    if (m[4]) out += ' ' + m[4] + ':' + m[5];
    return out;
  }

  function renderSyncMeta(sync) {
    const el = document.getElementById('csdSyncMeta');
    if (!el) return;
    if (!sync) {
      el.textContent = 'Cache: —';
      return;
    }
    const parts = [
      'Cache MySQL',
      sync.last_success_at ? ('atualizado em ' + fmtDt(sync.last_success_at)) : 'ainda não sincronizado',
      sync.last_source ? ('fonte SAP: ' + String(sync.last_source).toUpperCase()) : null,
      sync.rows_cached != null ? (Number(sync.rows_cached).toLocaleString('pt-BR') + ' fatos') : null,
      (sync.cache_from && sync.cache_to) ? (fmtDt(sync.cache_from) + ' → ' + fmtDt(sync.cache_to)) : null
    ].filter(Boolean);
    el.textContent = parts.join(' · ');
  }

  function renderFromPayload(data) {
    const meses = (data.periodo && data.periodo.meses) || [];
    const kpis = data.kpis || {};
    const series = data.series || {};
    const pKey = periodoKey();

    if (data.periodo) {
      if (data.periodo.chave) filtro.periodo = String(data.periodo.chave);
      if (filtro.periodo === 'personalizado') {
        filtro.date_from = data.periodo.date_from || filtro.date_from;
        filtro.date_to = data.periodo.date_to || filtro.date_to;
      }
    }

    // Só atualiza opções de filtro quando o período muda (evita refetch visual desnecessário)
    if (data.filtros_opcoes && (opcoesPeriodoKey !== pKey || !opcoesCache)) {
      opcoesCache = data.filtros_opcoes;
      opcoesPeriodoKey = pKey;
      preencherSelect('fVendedor', opcoesCache.vendedores || [], filtro.vendedor);
      preencherSelect('fGrupoCliente', opcoesCache.grupos_cliente || [], filtro.grupo_cliente);
      preencherSelect('fRegiao', opcoesCache.regioes || [], filtro.regiao);
    } else {
      document.getElementById('fVendedor').value = filtro.vendedor || '';
      document.getElementById('fGrupoCliente').value = filtro.grupo_cliente || '';
      document.getElementById('fRegiao').value = filtro.regiao || '';
    }

    document.getElementById('periodoResumo').textContent =
      meses.length ? (meses[0] + ' a ' + meses[meses.length - 1]) : 'Sem período';
    document.getElementById('csdSource').textContent =
      'MySQL cache' + (data.sync && data.sync.last_source
        ? (' (sync via ' + String(data.sync.last_source).toUpperCase() + ')')
        : '');

    renderSyncMeta(data.sync || null);
    showWarn(data.warning || (data.cache_empty ? 'Cache vazio. Execute a sincronização SAP.' : ''));

    sincronizarToolbar();
    renderChips();

    document.getElementById('kpiFaturamento').textContent = fmtMoedaCompacta(kpis.faturamento_liquido || 0);
    document.getElementById('kpiDevolucao').textContent = fmtMoedaCompacta(kpis.devolucoes || 0);
    document.getElementById('kpiTaxaDevolucao').textContent =
      ((kpis.taxa_devolucao || 0).toFixed(1).replace('.', ',')) + '%';
    document.getElementById('kpiTicket').textContent = fmtMoedaCompacta(kpis.ticket_medio || 0);
    document.getElementById('kpiFaturamentoDelta').textContent = (kpis.clientes_ativos || 0) + ' clientes ativos';
    document.getElementById('kpiDevolucaoDelta').textContent = (kpis.qtd_devolucoes || 0) + ' devoluções no período';
    document.getElementById('kpiTaxaDevolucaoDelta').textContent =
      (kpis.taxa_devolucao || 0) > 10 ? 'Acima da meta de 10%' : 'Dentro da meta de 10%';
    document.getElementById('kpiTicketDelta').textContent = 'Por cliente ativo';

    destruirCharts();
    document.querySelectorAll('#crmSalesDash .csd-scale-note').forEach((el) => el.remove());
    if (typeof Chart === 'undefined') {
      showError('Chart.js não carregou. Verifique o vendor chartjs.');
      return;
    }

    const evolucao = series.evolucao || [];
    charts.evolucao = new Chart(document.getElementById('chartEvolucao'), {
      type: 'line',
      data: {
        labels: evolucao.map((x) => x.label),
        datasets: [{
          label: 'Faturamento líquido',
          data: evolucao.map((x) => x.valor),
          borderColor: COR_BASE,
          backgroundColor: 'rgba(27,122,73,0.08)',
          fill: true,
          tension: 0.35,
          borderWidth: 2,
          pointRadius: evolucao.map((x) => (filtro.ano_mes === x.label ? 6 : 3)),
          pointBackgroundColor: evolucao.map((x) => corCategoria('ano_mes', x.label, COR_BASE))
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        onClick: onClickChart('ano_mes'),
        plugins: { legend: { display: false }, tooltip: { callbacks: { label: (c) => fmtMoeda(c.parsed.y) } } },
        scales: {
          y: { ticks: { callback: (v) => fmtMoedaCompacta(v) }, grid: { color: '#E2E6E0' } },
          x: { grid: { display: false } }
        }
      }
    });

    const grupos = series.grupo_cliente || [];
    const totalGrupos = grupos.reduce((s, g) => s + g.valor, 0) || 1;
    document.getElementById('legendGrupo').innerHTML = grupos.map((g, i) =>
      '<span class="item' + (filtro.grupo_cliente && filtro.grupo_cliente !== g.label ? ' dim' : '') +
      '" data-valor="' + g.label.replace(/"/g, '&quot;') + '"><span class="dot" style="background:' +
      PALETA_GRUPO[i % PALETA_GRUPO.length] + '"></span>' + g.label + ' · ' +
      Math.round(g.valor / totalGrupos * 100) + '%</span>'
    ).join('');
    document.querySelectorAll('#legendGrupo .item').forEach((it) => {
      it.addEventListener('click', () => toggleFiltro('grupo_cliente', it.getAttribute('data-valor')));
    });
    charts.grupoCliente = new Chart(document.getElementById('chartGrupoCliente'), {
      type: 'doughnut',
      data: {
        labels: grupos.map((g) => g.label),
        datasets: [{
          data: grupos.map((g) => g.valor),
          backgroundColor: grupos.map((g, i) =>
            filtro.grupo_cliente
              ? corCategoria('grupo_cliente', g.label, PALETA_GRUPO[i % PALETA_GRUPO.length])
              : PALETA_GRUPO[i % PALETA_GRUPO.length]
          ),
          borderColor: '#fff',
          borderWidth: 2
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        cutout: '62%',
        onClick: onClickChart('grupo_cliente'),
        plugins: { legend: { display: false }, tooltip: { callbacks: { label: (c) => c.label + ': ' + fmtMoeda(c.parsed) } } }
      }
    });

    const vendedores = series.vendedores || [];
    charts.vendedores = createHorizontalBarChart('chartVendedores', vendedores, 'vendedor', 36);

    const regioes = series.regiao || [];
    charts.regiao = createHorizontalBarChart('chartRegiao', regioes, 'regiao', 12);

    const itens = series.grupo_item || [];
    charts.grupoItem = createHorizontalBarChart('chartGrupoItem', itens, 'grupo_item', 40);

    const clientes = data.top_clientes || [];
    const clienteVals = clientes.map((c) => c.liquido);
    const clienteScale = adaptiveAxisMax(clienteVals);
    const maxClienteVisual = clienteScale.max || 1;
    document.getElementById('tblClientes').innerHTML = clientes.length
      ? clientes.map((info) => {
        const pct = Math.max(4, Math.min(100, Math.round(info.liquido / maxClienteVisual * 100)));
        const clipped = clienteScale.clipped && info.liquido > maxClienteVisual * 0.999;
        return '<tr><td class="name-cell" title="' + escapeHtml(info.cliente) + '">' + escapeHtml(info.cliente) +
          '<div class="bar-mini"><span style="width:' + pct + '%"></span></div></td><td>' + escapeHtml(info.grupo) + '</td>' +
          '<td class="num">' + fmtMoeda(info.liquido) + (clipped ? ' ▸' : '') + '</td>' +
          '<td class="num neg">' + (info.devolucao > 0 ? '-' + fmtMoeda(info.devolucao) : '—') + '</td></tr>';
      }).join('')
      : '<tr><td colspan="4" style="text-align:center;color:var(--csd-ink-mute);padding:20px;">Sem resultados para os filtros aplicados</td></tr>';
  }

  function escapeHtml(s) {
    return String(s == null ? '' : s)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  async function carregar(skipAutoSync) {
    if (!apiUrl) {
      showError('URL da API não configurada.');
      return;
    }
    if (!validarPeriodoCustom()) return;
    if (abortCtrl) abortCtrl.abort();
    abortCtrl = new AbortController();
    setLoading(true, skipAutoSync ? 'Carregando dashboard…' : 'Carregando (sync diário se necessário)…');
    showError('');
    try {
      let url = apiUrl + '?' + buildQuery();
      if (skipAutoSync) {
        url += (url.indexOf('?') >= 0 ? '&' : '?') + 'skip_auto_sync=1';
      }
      const res = await fetch(url, {
        credentials: 'same-origin',
        headers: { Accept: 'application/json' },
        signal: abortCtrl.signal
      });
      const data = await res.json();
      if (!res.ok || !data.success) {
        throw new Error(data.error || ('Erro HTTP ' + res.status));
      }
      renderFromPayload(data);
    } catch (err) {
      if (err.name === 'AbortError') return;
      showError(err.message || 'Falha ao carregar o dashboard.');
      document.getElementById('periodoResumo').textContent = 'Erro ao carregar';
    } finally {
      setLoading(false);
    }
  }

  async function sincronizarSap() {
    if (!canSync || !syncUrl) return;
    const btn = document.getElementById('btnSyncSap');
    if (btn) btn.disabled = true;
    setLoading(true, 'Sincronizando incremento SAP → MySQL…');
    showError('');
    showWarn('');
    try {
      const res = await fetch(syncUrl, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
          Accept: 'application/json',
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({ mode: 'incremental' })
      });
      const data = await res.json();
      if (!res.ok || !data.success) {
        throw new Error(data.error || data.message || ('Erro HTTP ' + res.status));
      }
      opcoesCache = null;
      opcoesPeriodoKey = '';
      showWarn(data.message || 'Sincronização incremental concluída.');
      await carregar(true);
    } catch (err) {
      showError(err.message || 'Falha na sincronização SAP.');
      setLoading(false);
    } finally {
      if (btn) btn.disabled = false;
    }
  }

  document.getElementById('fPeriodo').addEventListener('change', (e) => {
    filtro.periodo = e.target.value || '12';
    opcoesCache = null;
    opcoesPeriodoKey = '';
    if (isPersonalizado()) {
      if (!filtro.date_from) filtro.date_from = customDefaults.date_from;
      if (!filtro.date_to) filtro.date_to = customDefaults.date_to;
      sincronizarToolbar();
      showError('');
      return;
    }
    filtro.date_from = '';
    filtro.date_to = '';
    sincronizarToolbar();
    carregar();
  });
  document.getElementById('fDateFrom').addEventListener('change', (e) => {
    filtro.date_from = e.target.value || '';
    opcoesCache = null;
  });
  document.getElementById('fDateTo').addEventListener('change', (e) => {
    filtro.date_to = e.target.value || '';
    opcoesCache = null;
  });
  document.getElementById('fVendedor').addEventListener('change', (e) => {
    filtro.vendedor = e.target.value || null;
    carregar();
  });
  document.getElementById('fGrupoCliente').addEventListener('change', (e) => {
    filtro.grupo_cliente = e.target.value || null;
    carregar();
  });
  document.getElementById('fRegiao').addEventListener('change', (e) => {
    filtro.regiao = e.target.value || null;
    carregar();
  });
  document.getElementById('btnLimpar').addEventListener('click', () => {
    filtro = {
      periodo: '12',
      date_from: '',
      date_to: '',
      vendedor: null,
      grupo_cliente: null,
      regiao: null,
      grupo_item: null,
      ano_mes: null
    };
    opcoesCache = null;
    opcoesPeriodoKey = '';
    sincronizarToolbar();
    carregar();
  });
  document.getElementById('btnAtualizar').addEventListener('click', () => {
    if (isPersonalizado()) {
      filtro.date_from = document.getElementById('fDateFrom').value || '';
      filtro.date_to = document.getElementById('fDateTo').value || '';
      opcoesCache = null;
      opcoesPeriodoKey = '';
    }
    carregar();
  });
  const btnSync = document.getElementById('btnSyncSap');
  if (btnSync) {
    btnSync.addEventListener('click', sincronizarSap);
  }

  sincronizarToolbar();
  carregar();
})();
