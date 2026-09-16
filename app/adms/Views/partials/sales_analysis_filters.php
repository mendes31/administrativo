<?php
/** @var array $this->data */
$an = $this->data['analytics'] ?? [];
$filters = $this->data['filters'] ?? [];
$opcoes = $an['filtros_opcoes'] ?? [];
$selfUrl = htmlspecialchars((string) ($this->data['self_url'] ?? ''), ENT_QUOTES, 'UTF-8');
$chave = (string) ($an['periodo']['chave'] ?? ($filters['periodo'] ?? '12'));
$resolvedFrom = (string) ($an['periodo']['date_from'] ?? '');
$resolvedTo = (string) ($an['periodo']['date_to'] ?? '');
$dateFrom = $chave === 'personalizado'
    ? (string) ($filters['date_from'] ?? $resolvedFrom)
    : $resolvedFrom;
$dateTo = $chave === 'personalizado'
    ? (string) ($filters['date_to'] ?? $resolvedTo)
    : $resolvedTo;
$sel = static function (mixed $raw): array {
    if ($raw === null || $raw === '') {
        return [];
    }
    if (!is_array($raw)) {
        $raw = [$raw];
    }
    $out = [];
    foreach ($raw as $item) {
        if ($item === null || $item === '') {
            continue;
        }
        $out[] = (string) $item;
    }
    return $out;
};
$rotuloLista = static function (array $selected, string $vazio): string {
    $n = count($selected);
    if ($n === 0) {
        return $vazio;
    }
    if ($n === 1) {
        return $selected[0];
    }
    return $n . ' selecionados';
};
$vendedoresSel = $sel($filters['vendedor'] ?? null);
$gruposSel = $sel($filters['grupo_cliente'] ?? null);
$regioesSel = $sel($filters['regiao'] ?? null);
$hiddenDims = [
    'grupo_item' => 'Grupo de item',
    'ano_mes' => 'Mês',
    'card_code' => 'Cliente',
    'item_code' => 'Item',
];
$periodos = [
    'mes_atual' => 'Mês atual',
    'mes_anterior' => 'Mês anterior',
    '3' => 'Últimos 3 meses',
    '6' => 'Últimos 6 meses',
    '12' => 'Últimos 12 meses',
    '24' => 'Últimos 24 meses',
    '36' => 'Últimos 36 meses',
    'ano_atual' => 'Ano atual (YTD)',
    'ano_anterior' => 'Ano anterior',
    'personalizado' => 'Personalizado…',
];
$campos = [
    [
        'name' => 'vendedor[]',
        'label' => 'Vendedor',
        'empty' => 'Todos',
        'search' => 'Buscar vendedor',
        'opcoes' => $opcoes['vendedores'] ?? [],
        'sel' => $vendedoresSel,
    ],
    [
        'name' => 'grupo_cliente[]',
        'label' => 'Grupo',
        'empty' => 'Todos',
        'search' => 'Buscar grupo',
        'opcoes' => $opcoes['grupos_cliente'] ?? [],
        'sel' => $gruposSel,
    ],
    [
        'name' => 'regiao[]',
        'label' => 'Região',
        'empty' => 'Todas',
        'search' => 'Buscar região',
        'opcoes' => $opcoes['regioes'] ?? [],
        'sel' => $regioesSel,
    ],
];
$chips = [];
foreach ($campos as $campo) {
    foreach ($campo['sel'] as $valor) {
        $chips[] = ['name' => $campo['name'], 'label' => $campo['label'], 'value' => $valor];
    }
}
foreach ($hiddenDims as $dim => $lab) {
    foreach ($sel($filters[$dim] ?? null) as $valor) {
        $chips[] = ['name' => $dim . '[]', 'label' => $lab, 'value' => $valor];
    }
}
?>
<form class="csd-toolbar" id="csdAnForm" method="get" action="<?= $selfUrl ?>">
  <div class="csd-toolbar-fields">
    <label>Período
      <select name="periodo" id="fPeriodoAn">
        <?php foreach ($periodos as $val => $lab): ?>
          <option value="<?= htmlspecialchars((string) $val, ENT_QUOTES, 'UTF-8') ?>"<?= $chave === (string) $val ? ' selected' : '' ?>><?= htmlspecialchars($lab, ENT_QUOTES, 'UTF-8') ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <div class="csd-custom-dates<?= $chave === 'personalizado' ? ' is-visible' : '' ?>" id="csdAnDates" aria-hidden="<?= $chave === 'personalizado' ? 'false' : 'true' ?>">
      <label>Data início
        <input type="date" name="date_from" id="fDateFromAn" value="<?= htmlspecialchars($dateFrom, ENT_QUOTES, 'UTF-8') ?>" data-resolved="<?= htmlspecialchars($resolvedFrom, ENT_QUOTES, 'UTF-8') ?>"<?= $chave === 'personalizado' ? '' : ' disabled' ?>>
      </label>
      <label>Data fim
        <input type="date" name="date_to" id="fDateToAn" value="<?= htmlspecialchars($dateTo, ENT_QUOTES, 'UTF-8') ?>" data-resolved="<?= htmlspecialchars($resolvedTo, ENT_QUOTES, 'UTF-8') ?>"<?= $chave === 'personalizado' ? '' : ' disabled' ?>>
      </label>
    </div>
    <?php foreach ($campos as $campo): ?>
      <?php
      $btnTxt = $rotuloLista($campo['sel'], $campo['empty']);
      $hasVal = $campo['sel'] !== [];
      ?>
      <div class="csd-field">
        <span><?= htmlspecialchars($campo['label'], ENT_QUOTES, 'UTF-8') ?></span>
        <div class="csd-ms" data-empty="<?= htmlspecialchars($campo['empty'], ENT_QUOTES, 'UTF-8') ?>">
          <button type="button" class="csd-ms-btn<?= $hasVal ? ' has-value' : '' ?>" aria-haspopup="listbox" aria-expanded="false" title="<?= htmlspecialchars($hasVal ? implode(', ', $campo['sel']) : $campo['empty'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($btnTxt, ENT_QUOTES, 'UTF-8') ?></button>
          <div class="csd-ms-panel">
            <input type="search" placeholder="<?= htmlspecialchars($campo['search'], ENT_QUOTES, 'UTF-8') ?>" autocomplete="off">
            <div class="csd-ms-list">
              <?php if (($campo['opcoes'] ?? []) === []): ?>
                <div class="csd-ms-empty">Nenhuma opção</div>
              <?php endif; ?>
              <?php foreach ($campo['opcoes'] as $opt): ?>
                <?php $v = (string) $opt; ?>
                <label class="csd-ms-opt">
                  <input type="checkbox" name="<?= htmlspecialchars($campo['name'], ENT_QUOTES, 'UTF-8') ?>" value="<?= htmlspecialchars($v, ENT_QUOTES, 'UTF-8') ?>"<?= in_array($v, $campo['sel'], true) ? ' checked' : '' ?>>
                  <span><?= htmlspecialchars($v, ENT_QUOTES, 'UTF-8') ?></span>
                </label>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
  <?php foreach ($hiddenDims as $dim => $lab): ?>
    <?php foreach ($sel($filters[$dim] ?? null) as $hv): ?>
      <input type="hidden" name="<?= htmlspecialchars($dim, ENT_QUOTES, 'UTF-8') ?>[]" value="<?= htmlspecialchars($hv, ENT_QUOTES, 'UTF-8') ?>">
    <?php endforeach; ?>
  <?php endforeach; ?>
  <div class="csd-toolbar-actions">
    <a class="csd-btn-clear" href="<?= $selfUrl ?>">Limpar</a>
    <button type="submit" class="csd-btn-primary">Atualizar</button>
  </div>
</form>
<?php if ($chips !== []): ?>
  <div class="csd-chips" id="csdAnChips">
    <?php foreach ($chips as $chip): ?>
      <span class="csd-chip">
        <?= htmlspecialchars($chip['label'] . ': ' . $chip['value'], ENT_QUOTES, 'UTF-8') ?>
        <button type="button" data-chip-name="<?= htmlspecialchars($chip['name'], ENT_QUOTES, 'UTF-8') ?>" data-chip-value="<?= htmlspecialchars($chip['value'], ENT_QUOTES, 'UTF-8') ?>" aria-label="Remover filtro">×</button>
      </span>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
<script>
(function () {
  const form = document.getElementById('csdAnForm');
  if (!form) return;
  const periodo = document.getElementById('fPeriodoAn');
  const dates = document.getElementById('csdAnDates');
  const fromEl = document.getElementById('fDateFromAn');
  const toEl = document.getElementById('fDateToAn');

  function toggleDates() {
    const show = periodo.value === 'personalizado';
    dates.classList.toggle('is-visible', show);
    dates.setAttribute('aria-hidden', show ? 'false' : 'true');
    fromEl.disabled = !show;
    toEl.disabled = !show;
    if (show) {
      if (!fromEl.value) fromEl.value = fromEl.getAttribute('data-resolved') || '';
      if (!toEl.value) toEl.value = toEl.getAttribute('data-resolved') || '';
    }
  }

  function checkedOf(ms) {
    return Array.from(ms.querySelectorAll('input[type="checkbox"]:checked')).map((el) => el.value);
  }

  function syncMs(ms) {
    const btn = ms.querySelector('.csd-ms-btn');
    const empty = ms.getAttribute('data-empty') || 'Todos';
    const vals = checkedOf(ms);
    btn.textContent = vals.length === 0 ? empty : (vals.length === 1 ? vals[0] : vals.length + ' selecionados');
    btn.title = vals.length ? vals.join(', ') : empty;
    btn.classList.toggle('has-value', vals.length > 0);
  }

  function closePanels(except) {
    form.querySelectorAll('.csd-ms-panel.is-open').forEach((panel) => {
      if (panel === except) return;
      panel.classList.remove('is-open');
      const btn = panel.parentElement && panel.parentElement.querySelector('.csd-ms-btn');
      if (btn) btn.setAttribute('aria-expanded', 'false');
    });
  }

  function placePanel(btn, panel) {
    const r = btn.getBoundingClientRect();
    const width = Math.max(r.width, 260);
    let left = r.left;
    if (left + width > window.innerWidth - 8) left = Math.max(8, window.innerWidth - width - 8);
    panel.style.left = left + 'px';
    panel.style.top = (r.bottom + 4) + 'px';
    panel.style.minWidth = width + 'px';
  }

  form.querySelectorAll('.csd-ms').forEach((ms) => {
    const btn = ms.querySelector('.csd-ms-btn');
    const panel = ms.querySelector('.csd-ms-panel');
    const search = ms.querySelector('input[type="search"]');
    btn.addEventListener('click', (ev) => {
      ev.preventDefault();
      const open = !panel.classList.contains('is-open');
      closePanels(open ? panel : null);
      panel.classList.toggle('is-open', open);
      btn.setAttribute('aria-expanded', open ? 'true' : 'false');
      if (open) {
        placePanel(btn, panel);
        if (search) search.focus();
      }
    });
    ms.querySelectorAll('input[type="checkbox"]').forEach((cb) => {
      cb.addEventListener('change', () => syncMs(ms));
    });
    if (search) {
      search.addEventListener('input', () => {
        const q = search.value.trim().toLowerCase();
        ms.querySelectorAll('.csd-ms-opt').forEach((opt) => {
          const txt = (opt.textContent || '').toLowerCase();
          opt.style.display = !q || txt.indexOf(q) >= 0 ? '' : 'none';
        });
      });
    }
    syncMs(ms);
  });

  document.addEventListener('click', (ev) => {
    if (!ev.target.closest || !ev.target.closest('.csd-ms')) closePanels();
  });
  document.addEventListener('keydown', (ev) => {
    if (ev.key === 'Escape') closePanels();
  });
  window.addEventListener('resize', () => closePanels());
  window.addEventListener('scroll', () => closePanels(), true);

  periodo.addEventListener('change', toggleDates);
  toggleDates();

  document.querySelectorAll('#csdAnChips [data-chip-name]').forEach((btn) => {
    btn.addEventListener('click', () => {
      const name = btn.getAttribute('data-chip-name');
      const value = btn.getAttribute('data-chip-value');
      const match = Array.from(form.querySelectorAll('input'))
        .find((el) => el.name === name && el.value === value);
      if (match) {
        if (match.type === 'checkbox') match.checked = false;
        else match.remove();
      }
      form.submit();
    });
  });
})();
</script>
