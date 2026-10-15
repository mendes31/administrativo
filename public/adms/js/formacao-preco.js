(function () {
  "use strict";

  function parseDec(raw) {
    if (raw === null || raw === undefined) return null;
    var s = String(raw).trim().replace(/\u00a0/g, "").replace(/\s/g, "");
    if (s === "") return null;
    if (s.indexOf(",") >= 0 && s.indexOf(".") >= 0) {
      s = s.replace(/\./g, "").replace(",", ".");
    } else {
      s = s.replace(",", ".");
    }
    var n = parseFloat(s);
    return isFinite(n) ? n : null;
  }

  function parsePct(raw) {
    var n = parseDec(raw);
    return n === null ? null : n / 100;
  }

  function fmtMoney(v) {
    if (v === null || v === undefined || !isFinite(v)) return "";
    return v.toFixed(2).replace(".", ",");
  }

  function fmtPct(v) {
    if (v === null || v === undefined || !isFinite(v)) return "";
    return (v * 100).toFixed(2).replace(".", ",");
  }

  function impostoEfetivo(icms, pis, cofins) {
    return icms + (1 - icms) * (pis + cofins);
  }

  function tetoPb(imp, frete, com) {
    return 1 - imp - frete - com;
  }

  function tetoRl(imp, frete, com) {
    var base = 1 - imp;
    if (base <= 0) return 0;
    return 1 - (frete + com) / base;
  }

  function tetoUso(teto) {
    return Math.max(0, teto - 0.0001);
  }

  function fmtTetoLabel(teto) {
    return (Math.floor(tetoUso(teto) * 10000) / 100).toFixed(2).replace(".", ",") + "%";
  }

  function precoMeta(metodo, custo, imp, frete, com, meta) {
    var den;
    if (metodo === "pb") {
      den = 1 - imp - frete - com - meta;
      return den > 0 ? custo / den : null;
    }
    if (metodo === "rl") {
      den = (1 - imp) * (1 - meta) - frete - com;
      return den > 0 ? custo / den : null;
    }
    den = 1 - imp - frete - com;
    return den > 0 ? (custo * (1 + meta)) / den : null;
  }

  function calcMetodo(metodo, inRow, precoInf) {
    var custo = inRow.custo;
    var imp = inRow.imposto;
    var meta = inRow["meta_" + metodo];
    var emptyMeta = meta === null;
    var emptyPreco = precoInf === null;
    var status;
    if (!emptyMeta && !emptyPreco) status = "VERIFICAR: informe % OU preço";
    else if (emptyMeta && emptyPreco) status = "INFORMAR % OU PREÇO";
    else if (!emptyPreco) status = "OK - preço informado";
    else status = "OK - preço calculado";

    var t = null;
    if (precoInf !== null) {
      t = precoInf > 0 ? precoInf : null;
    } else if (meta !== null) {
      t = precoMeta(metodo, custo, imp, inRow.frete, inRow.comissao, meta);
      if (t === null) {
        if (metodo === "mk") status = "INVÁLIDO: imposto + frete + comissão ≥ 100%";
        else {
          var teto = metodo === "rl"
            ? tetoRl(imp, inRow.frete, inRow.comissao)
            : tetoPb(imp, inRow.frete, inRow.comissao);
          status = "INVÁLIDO: use até " + fmtTetoLabel(teto) + " nesta linha";
        }
      }
    }

    if (t === null) {
      return { status: status, preco_vendido: null, preco_tabela: null, impostos_r: null, receita_liquida: null, lucro_unitario: null, margem_pb: null, margem_rl: null, markup: null };
    }

    var icmsR = t * inRow.icms;
    var basePis = t - icmsR;
    var impostos = icmsR + basePis * inRow.pis + basePis * inRow.cofins;
    var rl = t - impostos;
    var lucro = rl - custo - t * inRow.frete - t * inRow.comissao;
    var tabela = inRow.desconto >= 1 ? null : t / (1 - inRow.desconto);
    return {
      status: status,
      preco_vendido: t,
      preco_tabela: tabela,
      impostos_r: impostos,
      receita_liquida: rl,
      lucro_unitario: lucro,
      margem_pb: t > 0 ? lucro / t : null,
      margem_rl: rl > 0 ? lucro / rl : null,
      markup: custo > 0 ? lucro / custo : null
    };
  }

  function val(row, namePart) {
    var el = row.querySelector('[name$="[' + namePart + ']"]');
    return el ? el.value : "";
  }

  function recalcRow(row) {
    var icms = parsePct(val(row, "icms")) || 0;
    var pis = parsePct(val(row, "pis")) || 0;
    var cofins = parsePct(val(row, "cofins")) || 0;
    var cvar = parseDec(val(row, "custo_variavel")) || 0;
    var cfix = parseDec(val(row, "custo_fixo")) || 0;
    var inRow = {
      icms: icms,
      pis: pis,
      cofins: cofins,
      custo: cvar + cfix,
      imposto: impostoEfetivo(icms, pis, cofins),
      frete: parsePct(val(row, "frete")) || 0,
      comissao: parsePct(val(row, "comissao")) || 0,
      desconto: parsePct(val(row, "desconto")) || 0,
      meta_pb: parsePct(val(row, "meta_pb")),
      meta_rl: parsePct(val(row, "meta_rl")),
      meta_mk: parsePct(val(row, "meta_mk"))
    };
    var precoInf = parseDec(val(row, "preco_informado"));
    var pb = calcMetodo("pb", inRow, precoInf);
    var rl = calcMetodo("rl", inRow, precoInf);
    var mk = calcMetodo("mk", inRow, precoInf);

    function setOut(key, value, isPct) {
      row.querySelectorAll('.fp-out[data-k="' + key + '"]').forEach(function (el) {
        if (key === "status" || key === "validacao" || /\.status$/.test(key)) {
          el.textContent = value || "";
          el.classList.toggle("fp-status-bad", String(value || "").indexOf("INVÁLIDO") === 0 || String(value || "").indexOf("VERIFICAR") === 0 || String(value || "").indexOf("INFORMAR") === 0);
          return;
        }
        el.textContent = value === null || value === undefined ? "" : (isPct ? fmtPct(value) : fmtMoney(value));
      });
    }

    setOut("imposto", inRow.imposto, true);
    setOut("custo", inRow.custo, false);
    ["pb", "rl", "mk"].forEach(function (m) {
      var r = m === "pb" ? pb : m === "rl" ? rl : mk;
      setOut(m + ".status", r.status, false);
      setOut(m + ".preco_vendido", r.preco_vendido, false);
      setOut(m + ".preco_tabela", r.preco_tabela, false);
      setOut(m + ".impostos_r", r.impostos_r, false);
      setOut(m + ".receita_liquida", r.receita_liquida, false);
      setOut(m + ".lucro_unitario", r.lucro_unitario, false);
      setOut(m + ".margem_pb", r.margem_pb, true);
      setOut(m + ".margem_rl", r.margem_rl, true);
      setOut(m + ".markup", r.markup, true);
    });
  }

  function recalcAll() {
    document.querySelectorAll("#fp-grid tbody .fp-row").forEach(recalcRow);
  }

  var TABS = { produtos: 1, pb: 1, rl: 1, mk: 1, cmp: 1, exemplo: 1, expl: 1 };
  var storageKey = "fp-tab:" + location.pathname;
  var currentTab = "produtos";

  function parseTab(raw) {
    var t = String(raw || "").replace(/^#/, "").replace(/^fp-/, "");
    return TABS[t] ? t : null;
  }

  function persistTab(tab) {
    currentTab = tab;
    try { sessionStorage.setItem(storageKey, tab); } catch (e) {}
    var next = location.pathname + location.search + "#fp-" + tab;
    if (history.replaceState) {
      history.replaceState(null, "", next);
    } else {
      location.hash = "fp-" + tab;
    }
  }

  function readSavedTab() {
    var fromHash = parseTab(location.hash);
    if (fromHash) return fromHash;
    try { return parseTab(sessionStorage.getItem(storageKey)) || "produtos"; } catch (e) { return "produtos"; }
  }

  function showTab(tab) {
    currentTab = tab;
    document.querySelectorAll("#fp-tabs [data-fp-tab]").forEach(function (btn) {
      btn.classList.toggle("active", btn.getAttribute("data-fp-tab") === tab);
    });
    var special = tab === "exemplo" || tab === "expl";
    document.querySelectorAll("[data-fp-pane]").forEach(function (pane) {
      var id = pane.getAttribute("data-fp-pane");
      if (id === "grid") {
        pane.classList.toggle("d-none", special);
        return;
      }
      if (id === "exemplo" || id === "expl") {
        pane.classList.toggle("d-none", id !== tab);
        return;
      }
      pane.classList.toggle("d-none", id !== tab);
    });
    if (special) return;
    var mobile = window.matchMedia("(max-width: 767.98px)").matches;
    document.querySelectorAll("#fp-grid [data-fp-g]").forEach(function (el) {
      var g = el.getAttribute("data-fp-g") || "";
      var parts = g.split(/\s+/);
      var show = parts.indexOf("always") >= 0 || parts.indexOf(tab) >= 0;
      if (!show) {
        el.style.display = "none";
        return;
      }
      el.style.display = mobile && el.tagName === "TD" ? "block" : "";
    });
  }

  function nextIndex() {
    var max = -1;
    document.querySelectorAll('#fp-grid [name^="itens["]').forEach(function (el) {
      var m = String(el.name).match(/^itens\[(\d+)]/);
      if (m) max = Math.max(max, parseInt(m[1], 10));
    });
    return max + 1;
  }

  function reindexRow(row, idx) {
    row.querySelectorAll("[name]").forEach(function (el) {
      el.name = String(el.name).replace(/itens\[\d+]/, "itens[" + idx + "]");
    });
    var num = row.querySelector(".fp-idx");
    if (num) num.textContent = String(idx + 1);
  }

  function bindRow(row) {
    row.querySelectorAll(".fp-in").forEach(function (el) {
      el.addEventListener("input", function () {
        if (el.classList.contains("fp-preco-inf")) {
          row.querySelectorAll(".fp-preco-inf").forEach(function (other) {
            if (other !== el) other.value = el.value;
          });
        }
        recalcRow(row);
      });
    });
    var del = row.querySelector(".fp-del");
    if (del) {
      del.addEventListener("click", function () {
        var tbody = row.parentNode;
        if (tbody.querySelectorAll(".fp-row").length <= 1) {
          row.querySelectorAll("input").forEach(function (inp) {
            if (inp.name && inp.name.indexOf("[produto]") >= 0) inp.value = "";
            else if (inp.classList.contains("fp-in")) inp.value = "";
          });
          recalcRow(row);
          return;
        }
        row.remove();
      });
    }
  }

  function defaultsFromHeader() {
    return {
      icms: document.getElementById("icms_padrao") ? document.getElementById("icms_padrao").value : "12,00",
      pis: document.getElementById("pis_padrao") ? document.getElementById("pis_padrao").value : "1,65",
      cofins: document.getElementById("cofins_padrao") ? document.getElementById("cofins_padrao").value : "7,60"
    };
  }

  function addRow(preset) {
    var tbody = document.querySelector("#fp-grid tbody");
    if (!tbody) return;
    var proto = tbody.querySelector(".fp-row");
    if (!proto) return;
    var clone = proto.cloneNode(true);
    clone.querySelectorAll("input").forEach(function (inp) { inp.value = ""; });
    clone.querySelectorAll(".fp-out").forEach(function (el) { el.textContent = ""; });
    var d = defaultsFromHeader();
    var map = {
      icms: d.icms,
      pis: d.pis,
      cofins: d.cofins,
      frete: "0,00",
      comissao: "0,00",
      desconto: "0,00"
    };
    if (preset) {
      Object.keys(preset).forEach(function (k) { map[k] = preset[k]; });
    }
    Object.keys(map).forEach(function (k) {
      var el = clone.querySelector('[name$="[' + k + ']"]');
      if (el) el.value = map[k];
    });
    var idx = nextIndex();
    reindexRow(clone, idx);
    tbody.appendChild(clone);
    bindRow(clone);
    recalcRow(clone);
    showTab(currentTab);
  }

  function updateSandbox() {
    var metaEl = document.getElementById("sb-meta");
    if (!metaEl) return;
    var meta = (+metaEl.value) / 100;
    var frete = (+document.getElementById("sb-frete").value) / 100;
    var com = (+document.getElementById("sb-comissao").value) / 100;
    var desc = (+document.getElementById("sb-desconto").value) / 100;
    document.getElementById("sb-meta-l").textContent = metaEl.value + "%";
    document.getElementById("sb-frete-l").textContent = document.getElementById("sb-frete").value + "%";
    document.getElementById("sb-comissao-l").textContent = document.getElementById("sb-comissao").value + "%";
    document.getElementById("sb-desconto-l").textContent = document.getElementById("sb-desconto").value + "%";
    var CUSTO = 28.69, IMP = 0.2014;

    function box(id, T) {
      var el = document.getElementById(id);
      var price = el.querySelector(".sb-price");
      var extra = el.querySelector(".sb-extra");
      if (T === null || !isFinite(T) || T <= 0) {
        price.textContent = "INVÁLIDO";
        extra.textContent = "reduza a meta, o frete ou a comissão";
        return;
      }
      var lucro = T * (1 - IMP) - CUSTO - T * frete - T * com;
      var tabela = desc < 1 ? T / (1 - desc) : null;
      var rl = T * (1 - IMP);
      var rlReal = rl > 0 ? lucro / rl : null;
      var pbReal = T > 0 ? lucro / T : null;
      var mkReal = CUSTO > 0 ? lucro / CUSTO : null;
      price.textContent = "R$ " + fmtMoney(T);
      extra.textContent = "lucro R$ " + fmtMoney(lucro)
        + " · PB real " + fmtPct(pbReal) + "%"
        + " · RL real " + fmtPct(rlReal) + "%"
        + " · Mk real " + fmtPct(mkReal) + "%"
        + (tabela ? " · tabela R$ " + fmtMoney(tabela) : "");
    }

    var denPb = 1 - IMP - frete - com - meta;
    box("sb-box-pb", denPb > 0 ? CUSTO / denPb : null);
    var denRl = (1 - IMP) * (1 - meta) - frete - com;
    box("sb-box-rl", denRl > 0 ? CUSTO / denRl : null);
    var denMk = 1 - IMP - frete - com;
    box("sb-box-mk", denMk > 0 ? (CUSTO * (1 + meta)) / denMk : null);

    document.getElementById("sb-teto").textContent =
      "Teto teórico: Preço Bruto " + fmtPct(Math.max(tetoPb(IMP, frete, com), 0)) +
      "%  ·  Receita Líquida " + fmtPct(Math.max(tetoRl(IMP, frete, com), 0)) +
      "%  ·  Markup: sem teto.";
  }

  document.querySelectorAll("#fp-tabs [data-fp-tab]").forEach(function (btn) {
    btn.addEventListener("click", function () {
      persistTab(btn.getAttribute("data-fp-tab"));
      showTab(currentTab);
    });
  });
  window.addEventListener("hashchange", function () {
    var t = parseTab(location.hash);
    if (t && t !== currentTab) {
      persistTab(t);
      showTab(t);
    }
  });

  document.querySelectorAll("#fp-grid tbody .fp-row").forEach(bindRow);
  var addBtn = document.getElementById("fp-add-row");
  if (addBtn) addBtn.addEventListener("click", function () { addRow(null); });
  var exBtn = document.getElementById("fp-add-exemplo");
  if (exBtn) {
    exBtn.addEventListener("click", function () {
      addRow({
        produto: "Probiotico Saúde Vaginal x 30 sticks",
        custo_variavel: "19,42",
        custo_fixo: "9,27",
        meta_pb: "20,70",
        meta_rl: "25,93",
        meta_mk: "35,00",
        frete: "0,00",
        comissao: "0,00",
        desconto: "0,00"
      });
    });
  }

  ["sb-meta", "sb-frete", "sb-comissao", "sb-desconto"].forEach(function (id) {
    var el = document.getElementById(id);
    if (el) el.addEventListener("input", updateSandbox);
  });

  persistTab(readSavedTab());
  showTab(currentTab);
  recalcAll();
  updateSandbox();
  if (window.matchMedia) {
    window.matchMedia("(max-width: 767.98px)").addEventListener("change", function () {
      showTab(currentTab);
    });
  }
})();
