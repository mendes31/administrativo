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
    ano_mes: 'Mês',
    card_code: 'Cliente',
    item_code: 'Item'
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
    vendedor: [],
    grupo_cliente: [],
    regiao: [],
    grupo_item: [],
    ano_mes: [],
    card_code: [],
    card_labels: {},
    item_code: [],
    item_labels: {}
  };
  const MULTI_FILTERS = [
    { campo: 'vendedor', btn: 'fVendedorBtn', panel: 'fVendedorPanel', list: 'fVendedorList', search: 'fVendedorSearch', empty: 'Todos', opcao: 'vendedores' },
    { campo: 'grupo_cliente', btn: 'fGrupoClienteBtn', panel: 'fGrupoClientePanel', list: 'fGrupoClienteList', search: 'fGrupoClienteSearch', empty: 'Todos', opcao: 'grupos_cliente' },
    { campo: 'regiao', btn: 'fRegiaoBtn', panel: 'fRegiaoPanel', list: 'fRegiaoList', search: 'fRegiaoSearch', empty: 'Todas', opcao: 'regioes' }
  ];
  let msDebounce = null;

  function valoresFiltro(campo) {
    const v = filtro[campo];
    if (v == null || v === '') return [];
    return Array.isArray(v) ? v.filter((x) => x != null && x !== '') : [v];
  }

  function filtroAtivo(campo) {
    return valoresFiltro(campo).length > 0;
  }

  function filtroTem(campo, valor) {
    return valoresFiltro(campo).indexOf(valor) >= 0;
  }

  function setListaFiltro(campo, lista) {
    const uniq = [];
    (lista || []).forEach((v) => {
      if (v != null && v !== '' && uniq.indexOf(v) < 0) uniq.push(v);
    });
    filtro[campo] = uniq;
  }

  function rotuloLista(campo, emptyLabel) {
    const vals = valoresFiltro(campo);
    if (!vals.length) return emptyLabel;
    if (vals.length === 1) return labelFiltro(campo, vals[0]);
    return vals.length + ' selecionados';
  }

  function labelFiltro(campo, valor) {
    if (campo === 'card_code') return (filtro.card_labels && filtro.card_labels[valor]) || valor;
    if (campo === 'item_code') return (filtro.item_labels && filtro.item_labels[valor]) || valor;
    return valor;
  }
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
  const fmtMoeda = (v) => Number(v || 0).toLocaleString('pt-BR', {
    style: 'currency',
    currency: 'BRL',
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  });
  const fmtQtd = (v) => Number(v || 0).toLocaleString('pt-BR', {
    maximumFractionDigits: 0
  });
  const fmtQtdItem = (v) => Number(v || 0).toLocaleString('pt-BR', {
    minimumFractionDigits: 0,
    maximumFractionDigits: 2
  });
  const fmtPct = (v) => ((v || 0).toFixed(2).replace('.', ',')) + '%';
  const fmtMoedaTabela = (v) => Number(v || 0).toLocaleString('pt-BR', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  });

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

  function preencherMulti(cfg, itens) {
    const list = document.getElementById(cfg.list);
    const search = document.getElementById(cfg.search);
    if (!list) return;
    const q = ((search && search.value) || '').trim().toLowerCase();
    const selected = valoresFiltro(cfg.campo);
    const rows = (itens || []).filter((v) => !q || String(v).toLowerCase().indexOf(q) >= 0);
    if (!rows.length) {
      list.innerHTML = '<div class="csd-ms-empty">Nenhuma opção</div>';
      atualizarBotaoMulti(cfg);
      return;
    }
    list.innerHTML = rows.map((v) => {
      const checked = selected.indexOf(v) >= 0 ? ' checked' : '';
      return '<label class="csd-ms-opt"><input type="checkbox" value="' + escapeHtml(v) + '"' + checked + '>' +
        '<span>' + escapeHtml(v) + '</span></label>';
    }).join('');
    list.querySelectorAll('input[type="checkbox"]').forEach((cb) => {
      cb.addEventListener('change', () => {
        const atual = valoresFiltro(cfg.campo).slice();
        const val = cb.value;
        const i = atual.indexOf(val);
        if (cb.checked && i < 0) atual.push(val);
        if (!cb.checked && i >= 0) atual.splice(i, 1);
        setListaFiltro(cfg.campo, atual);
        atualizarBotaoMulti(cfg);
        atualizarResumoFiltros();
        aplicarFiltrosDebounced();
      });
    });
    atualizarBotaoMulti(cfg);
  }

  function atualizarBotaoMulti(cfg) {
    const btn = document.getElementById(cfg.btn);
    if (!btn) return;
    const txt = rotuloLista(cfg.campo, cfg.empty);
    btn.textContent = txt;
    btn.title = valoresFiltro(cfg.campo).join(', ') || cfg.empty;
    btn.classList.toggle('has-value', filtroAtivo(cfg.campo));
  }

  function fecharPaineisMulti(exceto) {
    MULTI_FILTERS.forEach((cfg) => {
      const panel = document.getElementById(cfg.panel);
      const btn = document.getElementById(cfg.btn);
      if (!panel || cfg === exceto) return;
      panel.classList.remove('is-open');
      if (btn) btn.setAttribute('aria-expanded', 'false');
    });
  }

  function posicionarPainelMulti(btn, panel) {
    const r = btn.getBoundingClientRect();
    const width = Math.max(r.width, 260);
    let left = r.left;
    if (left + width > window.innerWidth - 8) {
      left = Math.max(8, window.innerWidth - width - 8);
    }
    panel.style.left = left + 'px';
    panel.style.top = (r.bottom + 4) + 'px';
    panel.style.minWidth = width + 'px';
  }

  function aplicarFiltrosDebounced() {
    if (msDebounce) clearTimeout(msDebounce);
    msDebounce = setTimeout(() => {
      msDebounce = null;
      carregar();
    }, 350);
  }

  function sincronizarToolbar() {
    document.getElementById('fPeriodo').value = String(filtro.periodo || '12');
    document.getElementById('fDateFrom').value = filtro.date_from || '';
    document.getElementById('fDateTo').value = filtro.date_to || '';
    MULTI_FILTERS.forEach(atualizarBotaoMulti);
    toggleCustomDates();
    atualizarResumoFiltros();
  }

  function atualizarResumoFiltros() {
    const el = document.getElementById('csdToolbarSummary');
    if (!el) return;
    const periodoEl = document.getElementById('fPeriodo');
    const partes = [];
    if (periodoEl && periodoEl.selectedIndex >= 0) {
      partes.push(periodoEl.options[periodoEl.selectedIndex].text);
    }
    if (filtroAtivo('vendedor')) partes.push(rotuloLista('vendedor', 'Vendedor'));
    if (filtroAtivo('grupo_cliente')) partes.push(rotuloLista('grupo_cliente', 'Grupo'));
    if (filtroAtivo('regiao')) partes.push(rotuloLista('regiao', 'Região'));
    if (filtroAtivo('card_code')) partes.push(rotuloLista('card_code', 'Cliente'));
    if (filtroAtivo('item_code')) partes.push(rotuloLista('item_code', 'Item'));
    el.textContent = partes.join(' · ');
  }

  function aplicarEstadoFiltros(open) {
    const bar = document.getElementById('csdToolbar');
    const btn = document.getElementById('btnToggleFiltros');
    if (!bar || !btn) return;
    bar.classList.toggle('is-collapsed', !open);
    btn.setAttribute('aria-expanded', open ? 'true' : 'false');
    btn.textContent = open ? 'Colapsar' : 'Expandir';
    try {
      localStorage.setItem('crmSalesFiltrosOpen', open ? '1' : '0');
    } catch (e) { /* ignore */ }
    atualizarResumoFiltros();
  }

  function renderChips() {
    const campos = ['vendedor', 'grupo_cliente', 'regiao', 'grupo_item', 'ano_mes', 'card_code', 'item_code'];
    const el = document.getElementById('chipsRow');
    const html = [];
    campos.forEach((c) => {
      valoresFiltro(c).forEach((valor) => {
        html.push(
          '<span class="csd-chip">' + rotulos[c] + ': ' + escapeHtml(labelFiltro(c, valor)) +
          '<button type="button" data-campo="' + c + '" data-valor="' + escapeHtml(valor) +
          '" aria-label="Remover filtro">×</button></span>'
        );
      });
    });
    el.innerHTML = html.join('');
    el.querySelectorAll('button').forEach((btn) => {
      btn.addEventListener('click', () => {
        const campo = btn.getAttribute('data-campo');
        const valor = btn.getAttribute('data-valor');
        removerValorFiltro(campo, valor);
        sincronizarToolbar();
        if (opcoesCache) {
          MULTI_FILTERS.forEach((cfg) => {
            if (cfg.campo === campo) preencherMulti(cfg, opcoesCache[cfg.opcao] || []);
          });
        }
        carregar();
      });
    });
  }

  function removerValorFiltro(campo, valor) {
    setListaFiltro(campo, valoresFiltro(campo).filter((v) => v !== valor));
    if (campo === 'card_code' && filtro.card_labels) delete filtro.card_labels[valor];
    if (campo === 'item_code' && filtro.item_labels) delete filtro.item_labels[valor];
  }

  function corCategoria(campo, valor, corPadrao) {
    if (!filtroAtivo(campo)) return corPadrao;
    return filtroTem(campo, valor) ? COR_SELECIONADO : COR_DIM;
  }

  function corBarra(campo, valor, index) {
    const base = PALETA_BARRAS[index % PALETA_BARRAS.length];
    if (!filtroAtivo(campo)) return base;
    return filtroTem(campo, valor) ? COR_SELECIONADO : COR_DIM;
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

  function contrastInk(hex) {
    const h = String(hex || '').replace('#', '');
    if (h.length < 6) return '#fff';
    const r = parseInt(h.slice(0, 2), 16);
    const g = parseInt(h.slice(2, 4), 16);
    const b = parseInt(h.slice(4, 6), 16);
    const lum = (0.299 * r + 0.587 * g + 0.114 * b) / 255;
    return lum > 0.62 ? '#1B241E' : '#FFFFFF';
  }

  const barValueLabelsPlugin = {
    id: 'csdBarValueLabels',
    afterDatasetsDraw: function (chart) {
      const meta = chart.getDatasetMeta(0);
      if (!meta || meta.hidden) return;
      const raw = chart.data.datasets[0].data || [];
      const colors = chart.data.datasets[0].backgroundColor;
      const horizontal = chart.options.indexAxis === 'y';
      const ctx = chart.ctx;
      const area = chart.chartArea;
      if (!area) return;
      ctx.save();
      ctx.font = '600 10px system-ui,Segoe UI,sans-serif';
      ctx.textBaseline = 'middle';
      meta.data.forEach(function (el, i) {
        const val = Number(raw[i]) || 0;
        if (!el || !Number.isFinite(val)) return;
        const text = fmtMoeda(val);
        const textW = ctx.measureText(text).width;
        const pad = 8;
        if (horizontal) {
          const p = typeof el.getProps === 'function'
            ? el.getProps(['x', 'y', 'base'], true)
            : el;
          const visStart = Math.max(Math.min(p.x, p.base), area.left);
          const visEnd = Math.min(Math.max(p.x, p.base), area.right);
          const visW = visEnd - visStart;
          const y = p.y;
          const roomOutside = area.right - visEnd;
          const fitsInside = visW >= textW + pad * 2;
          const fitsOutside = roomOutside >= textW + pad + 4;
          if (fitsInside || !fitsOutside) {
            const fill = Array.isArray(colors) ? colors[i] : colors;
            ctx.fillStyle = contrastInk(fill);
            ctx.textAlign = visW >= textW + pad * 2 ? 'right' : 'left';
            const xInside = visW >= textW + pad * 2
              ? visEnd - pad
              : visStart + pad;
            ctx.fillText(text, xInside, y);
          } else {
            ctx.fillStyle = '#3D4841';
            ctx.textAlign = 'left';
            ctx.fillText(text, visEnd + 6, y);
          }
        } else {
          const barTop = Math.max(el.y, area.top);
          const barH = Math.abs(el.base - el.y);
          const fitsInside = barH >= 16 && el.width >= textW + pad * 2;
          if (fitsInside) {
            const fill = Array.isArray(colors) ? colors[i] : colors;
            ctx.fillStyle = contrastInk(fill);
            ctx.textAlign = 'center';
            ctx.fillText(text, el.x, barTop + 10);
          } else {
            ctx.fillStyle = '#3D4841';
            ctx.textAlign = 'center';
            ctx.textBaseline = 'bottom';
            ctx.fillText(text, el.x, barTop - 4);
            ctx.textBaseline = 'middle';
          }
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
        layout: { padding: { left: 2, right: 92, top: 8, bottom: 8 } },
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
    if (msDebounce) {
      clearTimeout(msDebounce);
      msDebounce = null;
    }
    if (!valor) return;
    const atual = valoresFiltro(campo).slice();
    const i = atual.indexOf(valor);
    if (i >= 0) atual.splice(i, 1);
    else atual.push(valor);
    setListaFiltro(campo, atual);
    if (i >= 0) {
      if (campo === 'card_code' && filtro.card_labels) delete filtro.card_labels[valor];
      if (campo === 'item_code' && filtro.item_labels) delete filtro.item_labels[valor];
    }
    sincronizarToolbar();
    if (opcoesCache) {
      MULTI_FILTERS.forEach((cfg) => {
        if (cfg.campo === campo) preencherMulti(cfg, opcoesCache[cfg.opcao] || []);
      });
    }
    carregar();
  }

  function toggleFiltroComLabel(campo, valor, labelCampo, labelValor) {
    if (!valor) return;
    const mapKey = campo === 'card_code' ? 'card_labels' : (campo === 'item_code' ? 'item_labels' : labelCampo);
    if (!filtroTem(campo, valor) && mapKey && labelValor) {
      filtro[mapKey] = filtro[mapKey] || {};
      filtro[mapKey][valor] = labelValor;
    }
    toggleFiltro(campo, valor);
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
    const appendList = (key, campo) => {
      valoresFiltro(campo).forEach((v) => params.append(key, v));
    };
    appendList('vendedor[]', 'vendedor');
    appendList('grupo_cliente[]', 'grupo_cliente');
    appendList('regiao[]', 'regiao');
    appendList('grupo_item[]', 'grupo_item');
    appendList('ano_mes[]', 'ano_mes');
    appendList('card_code[]', 'card_code');
    appendList('item_code[]', 'item_code');
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
    }
    if (opcoesCache) {
      MULTI_FILTERS.forEach((cfg) => preencherMulti(cfg, opcoesCache[cfg.opcao] || []));
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

    document.getElementById('kpiFaturamento').textContent = fmtMoeda(kpis.faturamento_liquido || 0);
    document.getElementById('kpiDevolucao').textContent = fmtMoeda(kpis.devolucoes || 0);
    document.getElementById('kpiTaxaDevolucao').textContent = fmtPct(kpis.taxa_devolucao || 0);
    document.getElementById('kpiTicket').textContent = fmtMoeda(kpis.ticket_medio || 0);
    document.getElementById('kpiFaturamentoDelta').textContent = (kpis.clientes_ativos || 0) + ' clientes ativos (parceiros com fatura)';
    document.getElementById('kpiDevolucaoDelta').textContent = (kpis.qtd_devolucoes || 0) + ' linhas de item em notas de devolução';
    document.getElementById('kpiTaxaDevolucaoDelta').textContent =
      (kpis.taxa_devolucao || 0) > 10 ? 'Acima da meta de 10%' : 'Dentro da meta de 10%';
    document.getElementById('kpiTicketDelta').textContent = 'Por cliente ativo';

    const setTxt = (id, txt) => {
      const el = document.getElementById(id);
      if (el) el.textContent = txt;
    };
    setTxt('kpiItensVendidos', fmtQtd(kpis.itens_vendidos || 0));
    setTxt('kpiDesconto', fmtMoeda(kpis.desconto || 0));
    setTxt('kpiPctDesconto', fmtPct(kpis.pct_desconto || 0));
    setTxt('kpiBonificacoes', fmtMoeda(kpis.valor_bonificacoes || 0));
    setTxt('kpiBonificacoesDelta', (kpis.qtd_bonificacoes || 0) + ' linhas de item');
    setTxt('kpiItensBonificados', fmtQtd(kpis.itens_bonificados || 0));
    setTxt('kpiPctBonificacoes', fmtPct(kpis.pct_bonificacoes || 0));
    setTxt('kpiBrindes', fmtMoeda(kpis.valor_brindes || 0));
    setTxt('kpiBrindesDelta', (kpis.qtd_brindes || 0) + ' linhas de item');
    setTxt('kpiItensBrindes', fmtQtd(kpis.itens_brindes || 0));
    setTxt('kpiPctBrindes', fmtPct(kpis.pct_brindes || 0));

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
          pointRadius: evolucao.map((x) => (filtroTem('ano_mes', x.label) ? 6 : 3)),
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
      '<span class="item' + (filtroAtivo('grupo_cliente') && !filtroTem('grupo_cliente', g.label) ? ' dim' : '') +
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
            corCategoria('grupo_cliente', g.label, PALETA_GRUPO[i % PALETA_GRUPO.length])
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

    const clientes = data.top_clientes || [];
    const clienteVals = clientes.map((c) => c.liquido);
    const clienteScale = adaptiveAxisMax(clienteVals);
    const maxClienteVisual = clienteScale.max || 1;
    const subEl = document.getElementById('subTopClientes');
    if (subEl) {
      subEl.textContent = clientes.length
        ? clientes.length + ' cliente(s) · ordenado por faturamento líquido · clique para marcar um ou mais'
        : 'Nenhum cliente no recorte';
    }
    document.getElementById('tblClientes').innerHTML = clientes.length
      ? clientes.map((info) => {
        const pct = Math.max(4, Math.min(100, Math.round(info.liquido / maxClienteVisual * 100)));
        const code = String(info.card_code || '').trim();
        const nome = String(info.cliente || '').trim() || '—';
        const parceiro = (code ? '<span class="cliente-code">' + escapeHtml(code) + '</span>' : '') +
          '<span class="cliente-nome">' + escapeHtml(nome) + '</span>';
        const sel = filtroTem('card_code', code) ? ' class="is-selected"' : '';
        const label = (code ? code + ' · ' : '') + nome;
        return '<tr' + sel + ' data-card-code="' + escapeHtml(code) + '" data-card-label="' + escapeHtml(label) + '">' +
          '<td class="name-cell" title="' + escapeHtml(label) + '">' + parceiro +
          '<div class="bar-mini"><span style="width:' + pct + '%"></span></div></td>' +
          '<td class="col-grupo" title="' + escapeHtml(info.grupo || '') + '">' + escapeHtml(info.grupo || '—') + '</td>' +
          '<td class="num col-num">' + fmtMoedaTabela(info.liquido) + '</td>' +
          '<td class="num col-num neg">' + (info.devolucao > 0 ? fmtMoedaTabela(-Math.abs(info.devolucao)) : '—') + '</td></tr>';
      }).join('')
      : '<tr><td colspan="4" style="text-align:center;color:var(--csd-ink-mute);padding:20px;">Sem resultados para os filtros aplicados</td></tr>';
    const foot = document.getElementById('tblClientesFoot');
    if (foot) {
      if (!clientes.length) {
        foot.innerHTML = '';
      } else {
        const totLiq = clientes.reduce((s, c) => s + Number(c.liquido || 0), 0);
        const totDev = clientes.reduce((s, c) => s + Number(c.devolucao || 0), 0);
        foot.innerHTML = '<tr><td colspan="2">Total</td>' +
          '<td class="num col-num">' + fmtMoedaTabela(totLiq) + '</td>' +
          '<td class="num col-num neg">' + (totDev > 0 ? fmtMoedaTabela(-Math.abs(totDev)) : '—') + '</td></tr>';
      }
    }

    const itens = data.top_itens || [];
    const itemVals = itens.map((c) => c.liquido);
    const itemScale = adaptiveAxisMax(itemVals);
    const maxItemVisual = itemScale.max || 1;
    const subItens = document.getElementById('subTopItens');
    if (subItens) {
      subItens.textContent = itens.length
        ? itens.length + ' item(ns) · ordenado por faturamento líquido · clique para marcar um ou mais'
        : 'Nenhum item no recorte';
    }
    const tblItens = document.getElementById('tblItens');
    if (tblItens) {
      tblItens.innerHTML = itens.length
        ? itens.map((info) => {
          const pct = Math.max(4, Math.min(100, Math.round(info.liquido / maxItemVisual * 100)));
          const code = String(info.item_code || '').trim();
          const nome = String(info.item_name || '').trim() || '—';
          const parceiro = (code ? '<span class="cliente-code">' + escapeHtml(code) + '</span>' : '') +
            '<span class="cliente-nome">' + escapeHtml(nome) + '</span>';
          const sel = filtroTem('item_code', code) ? ' class="is-selected"' : '';
          const label = (code ? code + ' · ' : '') + nome;
          return '<tr' + sel + ' data-item-code="' + escapeHtml(code) + '" data-item-label="' + escapeHtml(label) + '">' +
            '<td class="name-cell" title="' + escapeHtml(label) + '">' + parceiro +
            '<div class="bar-mini"><span style="width:' + pct + '%"></span></div></td>' +
            '<td class="num col-num col-qtd">' + fmtQtdItem(info.quantidade) + '</td>' +
            '<td class="num col-num">' + fmtMoedaTabela(info.liquido) + '</td>' +
            '<td class="num col-num neg">' + (info.devolucao > 0 ? fmtMoedaTabela(-Math.abs(info.devolucao)) : '—') + '</td></tr>';
        }).join('')
        : '<tr><td colspan="4" style="text-align:center;color:var(--csd-ink-mute);padding:20px;">Sem resultados para os filtros aplicados</td></tr>';
    }
    const footItens = document.getElementById('tblItensFoot');
    if (footItens) {
      if (!itens.length) {
        footItens.innerHTML = '';
      } else {
        const totQtd = itens.reduce((s, c) => s + Number(c.quantidade || 0), 0);
        const totLiq = itens.reduce((s, c) => s + Number(c.liquido || 0), 0);
        const totDev = itens.reduce((s, c) => s + Number(c.devolucao || 0), 0);
        footItens.innerHTML = '<tr><td>Total</td>' +
          '<td class="num col-num col-qtd">' + fmtQtdItem(totQtd) + '</td>' +
          '<td class="num col-num">' + fmtMoedaTabela(totLiq) + '</td>' +
          '<td class="num col-num neg">' + (totDev > 0 ? fmtMoedaTabela(-Math.abs(totDev)) : '—') + '</td></tr>';
      }
    }
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
  const tblClientes = document.getElementById('tblClientes');
  if (tblClientes) {
    tblClientes.addEventListener('click', (e) => {
      const tr = e.target.closest('tr');
      if (!tr) return;
      const code = tr.getAttribute('data-card-code');
      if (!code) return;
      toggleFiltroComLabel('card_code', code, 'card_labels', tr.getAttribute('data-card-label') || code);
    });
  }
  const tblItens = document.getElementById('tblItens');
  if (tblItens) {
    tblItens.addEventListener('click', (e) => {
      const tr = e.target.closest('tr');
      if (!tr) return;
      const code = tr.getAttribute('data-item-code');
      if (!code) return;
      toggleFiltroComLabel('item_code', code, 'item_labels', tr.getAttribute('data-item-label') || code);
    });
  }

  function initMultiFilters() {
    MULTI_FILTERS.forEach((cfg) => {
      const btn = document.getElementById(cfg.btn);
      const panel = document.getElementById(cfg.panel);
      const search = document.getElementById(cfg.search);
      if (!btn || !panel) return;
      btn.addEventListener('click', (ev) => {
        ev.stopPropagation();
        const open = panel.classList.contains('is-open');
        fecharPaineisMulti();
        if (!open) {
          panel.classList.add('is-open');
          btn.setAttribute('aria-expanded', 'true');
          posicionarPainelMulti(btn, panel);
          if (search) {
            search.value = '';
            if (opcoesCache) preencherMulti(cfg, opcoesCache[cfg.opcao] || []);
            search.focus();
          }
        }
      });
      panel.addEventListener('click', (ev) => ev.stopPropagation());
      if (search) {
        search.addEventListener('input', () => {
          if (opcoesCache) preencherMulti(cfg, opcoesCache[cfg.opcao] || []);
        });
      }
    });
  }
  initMultiFilters();
  document.getElementById('btnLimpar').addEventListener('click', () => {
    filtro = {
      periodo: '12',
      date_from: '',
      date_to: '',
      vendedor: [],
      grupo_cliente: [],
      regiao: [],
      grupo_item: [],
      ano_mes: [],
      card_code: [],
      card_labels: {},
      item_code: [],
      item_labels: {}
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
  const btnToggleFiltros = document.getElementById('btnToggleFiltros');
  if (btnToggleFiltros) {
    btnToggleFiltros.addEventListener('click', () => {
      const open = btnToggleFiltros.getAttribute('aria-expanded') !== 'true';
      aplicarEstadoFiltros(open);
    });
    let openPref = true;
    try {
      openPref = localStorage.getItem('crmSalesFiltrosOpen') !== '0';
    } catch (e) { /* ignore */ }
    aplicarEstadoFiltros(openPref);
  }

  function fecharKpiTips(exceto) {
    root.querySelectorAll('.csd-kpi-info').forEach((btn) => {
      const tip = document.getElementById(btn.getAttribute('aria-controls') || '');
      if (btn === excepto) return;
      btn.setAttribute('aria-expanded', 'false');
      if (tip) tip.classList.remove('is-open');
    });
  }
  root.querySelectorAll('.csd-kpi-info').forEach((btn) => {
    btn.addEventListener('click', (ev) => {
      ev.stopPropagation();
      const tip = document.getElementById(btn.getAttribute('aria-controls') || '');
      const open = btn.getAttribute('aria-expanded') === 'true';
      fecharKpiTips();
      if (!open && tip) {
        btn.setAttribute('aria-expanded', 'true');
        tip.classList.add('is-open');
      }
    });
  });
  root.querySelectorAll('.csd-kpi-tip').forEach((tip) => {
    tip.addEventListener('click', (ev) => ev.stopPropagation());
  });
  document.addEventListener('click', () => {
    fecharKpiTips();
    fecharPaineisMulti();
  });
  document.addEventListener('keydown', (ev) => {
    if (ev.key === 'Escape') {
      fecharKpiTips();
      fecharPaineisMulti();
    }
  });
  window.addEventListener('resize', () => fecharPaineisMulti());
  window.addEventListener('scroll', (ev) => {
    const t = ev.target;
    if (t && t.closest && t.closest('.csd-ms-panel')) return;
    fecharPaineisMulti();
  }, true);

  sincronizarToolbar();
  carregar();
})();
