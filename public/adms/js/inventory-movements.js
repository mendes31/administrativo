(function() {
  function qs(sel, root) { return (root || document).querySelector(sel); }
  function qsa(sel, root) { return Array.from((root || document).querySelectorAll(sel)); }

  function baseUrl(path) {
    var adm = window.URL_ADM || (document.documentElement.getAttribute('data-url-adm') || '/');
    if (adm.slice(-1) !== '/') adm += '/';
    return adm + path;
  }

  // Global updater placeholder (will be assigned inside initItemsTable)
  window.INV_updateAvailability = window.INV_updateAvailability || function(){};

  function initPositionsSelects() {
    function bind(select, getTarget) {
        select.addEventListener('change', function() {
          var stockId = this.value;
          var positionSelect = getTarget(this);
          if (!positionSelect) return;
          positionSelect.innerHTML = '<option value="">Carregando...</option>';
          if (!stockId) { positionSelect.innerHTML = '<option value="">Selecione...</option>'; return; }
          fetch(baseUrl('inventory-ajax-positions?action=positions&stock_id=') + encodeURIComponent(stockId), {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
          }).then(function(r) { return r.json(); })
            .then(function(items) {
              var opts = '<option value="">Selecione...</option>' + (Array.isArray(items) ? items.map(function(p){
                return '<option value="' + p.id + '">' + (p.code ? (p.code + ' - ') : '') + (p.description || '') + '</option>';
              }).join('') : '');
              positionSelect.innerHTML = opts;
              if (typeof window.INV_updateAvailability === 'function') { window.INV_updateAvailability(); }
            }).catch(function(err){ console.error('positions fetch error', err); positionSelect.innerHTML = '<option value="">Selecione...</option>'; });
        });
    }
    qsa('[data-stock-select]').forEach(function(sel){
      bind(sel, function(s){ var target = s.getAttribute('data-target-position'); return target ? qs('#' + target) : null; });
    });
    qsa('[data-row-stock]').forEach(function(sel){
      bind(sel, function(s){
        var row = s.closest('.mov-item');
        // Quando trocar o estoque por linha, (re)carrega as posições disponíveis para o item selecionado
        var posSelect = row.querySelector('select[name$="[from_position_id]"]') || row.querySelector('select[name$="[to_position_id]"]');
        var itemSel = row.querySelector('select[name$="[inv_item_id]"]');
        if (posSelect) posSelect.innerHTML = '<option value="">Carregando...</option>';
        if (itemSel && itemSel.value) {
          fetch(baseUrl('inventory-ajax-positions?action=positionsAvailable&item_id=') + encodeURIComponent(itemSel.value) + '&stock_id=' + encodeURIComponent(s.value))
            .then(function(r){ return r.json(); })
            .then(function(list){
              var opts = '<option value="">Selecione...</option>' + (list || []).map(function(p){ var label = (p.code ? p.code + ' - ' : '') + (p.description || ''); return '<option value="' + p.id + '">' + label + ' (' + p.qty + ')' + '</option>'; }).join('');
              if (posSelect) posSelect.innerHTML = opts;
            }).catch(function(){ if (posSelect) posSelect.innerHTML = '<option value="">Selecione...</option>'; });
        } else {
          if (posSelect) posSelect.innerHTML = '<option value="">Selecione...</option>';
        }
        return posSelect;
      });
    });
  }

  function findHeaderOriginSelectors() {
    return {
      stock: qs('select[name="from_stock_id"]'),
      position: qs('select[name="from_position_id"]')
    };
  }

  function initItemsTable() {
    var container = qs('#mov-items');
    if (!container) return;
    var template = qs('#mov-item-row');
    var btnAdd = qs('#btn-add-item');
    var itemsData = window.INV_ITEMS || [];

    function renderOptions() {
      return itemsData.map(function(i){
        var label = (i.code ? i.code + ' - ' : '') + i.description;
        return '<option value="' + i.id + '" data-admin="' + i.admin_type + '">' + label + '</option>';
      }).join('');
    }

    function applyAdminFields(row) {
      var sel = row.querySelector('select[name$="[inv_item_id]"]');
      var admin = sel && sel.selectedOptions[0] ? sel.selectedOptions[0].getAttribute('data-admin') : 'none';
      var lot = row.querySelector('[data-field="batch_code"]');
      var exp = row.querySelector('[data-field="expiration_date"]');
      var serialWrap = row.querySelector('[data-serial-container]');
      if (admin === 'serial') {
        if (lot) { lot.required = false; lot.closest('.col-md-2').style.display = 'none'; }
        if (exp) { exp.required = false; exp.closest('.col-md-2').style.display = 'none'; }
        if (serialWrap) { serialWrap.style.display = ''; var t = serialWrap.querySelector('textarea'); if (t) t.required = true; }
      } else if (admin === 'lot') {
        if (lot) { lot.required = true; lot.closest('.col-md-2').style.display = ''; }
        if (exp) { exp.required = false; exp.closest('.col-md-2').style.display = ''; }
        if (serialWrap) { serialWrap.style.display = 'none'; var t2 = serialWrap.querySelector('textarea'); if (t2) t2.required = false; }
        preloadLotsForRow(row);
      } else {
        if (lot) { lot.required = false; lot.closest('.col-md-2').style.display = 'none'; }
        if (exp) { exp.required = false; exp.closest('.col-md-2').style.display = 'none'; }
        if (serialWrap) { serialWrap.style.display = 'none'; var t3 = serialWrap.querySelector('textarea'); if (t3) t3.required = false; }
      }
    }

    function renameRowFields(row, index) {
      var mappings = [
        ['select[name="items[][inv_item_id]"]', 'items[' + index + '][inv_item_id]'],
        ['input[name="items[][qty]"]', 'items[' + index + '][qty]'],
        ['input[name="items[][unit_cost]"]', 'items[' + index + '][unit_cost]'],
        ['select[name="items[][to_stock_id]"]', 'items[' + index + '][to_stock_id]'],
        ['select[name="items[][to_position_id]"]', 'items[' + index + '][to_position_id]'],
        ['input[name="items[][batch_code]"]', 'items[' + index + '][batch_code]'],
        ['input[name="items[][expiration_date]"]', 'items[' + index + '][expiration_date]'],
        ['textarea[name="items[][serial_list]"]', 'items[' + index + '][serial_list]']
      ];
      mappings.forEach(function(map){
        var el = row.querySelector(map[0]);
        if (el) el.setAttribute('name', map[1]);
      });
    }

    function preloadLotsForRow(row) {
      var selItem = row.querySelector('select[name$="[inv_item_id]"]');
      var lotInput = row.querySelector('input[name$="[batch_code]"]');
      if (!selItem || !lotInput || !selItem.value) return;
      // Preferir estoque/posição por linha (saída) e cair para cabeçalho (entrada)
      var rowFromStock = row.querySelector('select[name$="[from_stock_id]"]');
      var rowFromPos = row.querySelector('select[name$="[from_position_id]"]');
      var header = findHeaderOriginSelectors();
      var stockId = rowFromStock ? rowFromStock.value : (header.stock ? header.stock.value : '');
      var positionId = rowFromPos ? rowFromPos.value : (header.position ? header.position.value : '');
      if (!stockId) return;
      fetch(baseUrl('inventory-ajax-positions?action=getBalanceOptions&item_id=') + encodeURIComponent(selItem.value) + '&stock_id=' + encodeURIComponent(stockId) + '&position_id=' + encodeURIComponent(positionId))
        .then(function(r){ return r.json(); })
        .then(function(list) {
          var dlId = 'dl_lotes_' + Math.random().toString(36).slice(2);
          var dl = document.createElement('datalist');
          dl.id = dlId;
          dl.innerHTML = (Array.isArray(list) ? list : []).map(function(b){
            var label = (b.batch_code || '') + (b.expiration_date ? (' - ' + b.expiration_date) : '') + ' (' + (b.qty || 0) + ')';
            return '<option value="' + (b.batch_code || '') + '">' + label + '</option>';
          }).join('');
          document.body.appendChild(dl);
          lotInput.setAttribute('list', dlId);
        }).catch(function(){ /* ignore */ });
    }

    function addRow() {
      var clone = document.importNode(template.content, true);
      var row = clone.querySelector('.mov-item');
      var sel = row.querySelector('select[name="items[][inv_item_id]"]');
      sel.innerHTML = '<option value="">Selecione...</option>' + renderOptions();
      var index = container.querySelectorAll('.mov-item').length;
      renameRowFields(row, index);
      sel.addEventListener('change', function(){
        applyAdminFields(row);
        // Ao trocar o item, recarregar estoques com disponibilidade
        var itemId = this.value;
        var stockSelect = row.querySelector('select[name$="[from_stock_id]"]') || row.querySelector('select[name$="[to_stock_id]"]');
        if (stockSelect && itemId) {
          stockSelect.innerHTML = '<option value="">Carregando...</option>';
          fetch(baseUrl('inventory-ajax-positions?action=stocksAvailable&item_id=') + encodeURIComponent(itemId))
            .then(function(r){ return r.json(); })
            .then(function(list){
              var opts = '<option value="">Selecione...</option>' + (list || []).map(function(s){ return '<option value="' + s.id + '">' + s.name + ' (' + s.qty + ')</option>'; }).join('');
              stockSelect.innerHTML = opts;
              // Limpa posições
              var posSelect = row.querySelector('select[name$="[from_position_id]"]') || row.querySelector('select[name$="[to_position_id]"]');
              if (posSelect) posSelect.innerHTML = '<option value="">Selecione...</option>';
              updateAvailableQty(row);
            }).catch(function(){ stockSelect.innerHTML = '<option value="">Selecione...</option>'; });
        }
        updateAvailableQty(row);
      });
      // Bind de estoque por linha para carregar posições disponíveis
      var rowStock = row.querySelector('select[name$="[from_stock_id]"]') || row.querySelector('select[name$="[to_stock_id]"]');
      if (rowStock) {
        rowStock.addEventListener('change', function(){
          var posSelect = row.querySelector('select[name$="[from_position_id]"]') || row.querySelector('select[name$="[to_position_id]"]');
          if (posSelect) posSelect.innerHTML = '<option value="">Carregando...</option>';
          var itemSel = row.querySelector('select[name$="[inv_item_id]"]');
          if (itemSel && itemSel.value) {
            fetch(baseUrl('inventory-ajax-positions?action=positionsAvailable&item_id=') + encodeURIComponent(itemSel.value) + '&stock_id=' + encodeURIComponent(this.value))
              .then(function(r){ return r.json(); })
              .then(function(list){
                var opts = '<option value="">Selecione...</option>' + (list || []).map(function(p){ var label = (p.code ? p.code + ' - ' : '') + (p.description || ''); return '<option value="' + p.id + '">' + label + ' (' + p.qty + ')</option>'; }).join('');
                if (posSelect) posSelect.innerHTML = opts;
              }).catch(function(){ if (posSelect) posSelect.innerHTML = '<option value="">Selecione...</option>'; });
          } else {
            if (posSelect) posSelect.innerHTML = '<option value="">Selecione...</option>';
          }
        });
      }
      var qtyInput = row.querySelector('input[name$="[qty]"]');
      if (qtyInput) {
        // Exibir dica de disponibilidade somente nas telas de ENTRADA (linhas com to_stock_id)
        var isEntryRow = !!row.querySelector('select[name$="[to_stock_id]"]');
        if (isEntryRow) {
          var hint = document.createElement('small');
          hint.className = 'text-muted';
          hint.style.marginLeft = '6px';
          qtyInput.parentElement.appendChild(hint);
          qtyInput.addEventListener('focus', function(){ updateAvailableQty(row); });
        }
      }
      // Preencher estoques se item já vier selecionado (não usual)
      container.appendChild(clone);
      applyAdminFields(container.lastElementChild);
    }

    function updateAvailableQty(row) {
      var header = findHeaderOriginSelectors();
      var rowFromStock = row.querySelector('select[name$="[from_stock_id]"]');
      var rowFromPos = row.querySelector('select[name$="[from_position_id]"]');
      var stockId = rowFromStock ? rowFromStock.value : (header.stock ? header.stock.value : '');
      var positionId = rowFromPos ? rowFromPos.value : (header.position ? header.position.value : '');
      var itemSel = row.querySelector('select[name$="[inv_item_id]"]');
      var qtyInput = row.querySelector('input[name$="[qty]"]');
      if (!stockId || !itemSel || !itemSel.value) return;
      fetch(baseUrl('inventory-ajax-positions?action=getAvailable&item_id=') + encodeURIComponent(itemSel.value) + '&stock_id=' + encodeURIComponent(stockId) + '&position_id=' + encodeURIComponent(positionId))
        .then(function(r){ return r.json(); })
        .then(function(obj){
          var available = (obj && typeof obj.available !== 'undefined') ? obj.available : null;
          var admin = itemSel.selectedOptions[0] ? itemSel.selectedOptions[0].getAttribute('data-admin') : 'none';
          var hint = qtyInput && qtyInput.parentElement ? qtyInput.parentElement.querySelector('small.text-muted') : null;
          if (hint && available !== null) { hint.textContent = 'Disp.: ' + available; }
          if (admin === 'lot') { preloadLotsForRow(row); }
        }).catch(function(err){ console.error('available fetch error', err); });
    }

    function updateAllRowsAvailability() {
      qsa('#mov-items .mov-item').forEach(function(r){ updateAvailableQty(r); });
    }

    // Expor globalmente
    window.INV_updateAvailability = updateAllRowsAvailability;

    var header = findHeaderOriginSelectors();
    if (header.stock) header.stock.addEventListener('change', function(){ updateAllRowsAvailability(); });
    if (header.position) header.position.addEventListener('change', function(){ updateAllRowsAvailability(); });

    if (btnAdd) btnAdd.addEventListener('click', function(e){ e.preventDefault(); addRow(); });
    if (!container.children.length) { addRow(); }
  }

  document.addEventListener('DOMContentLoaded', function(){
    initPositionsSelects();
    initItemsTable();
  });
})();


