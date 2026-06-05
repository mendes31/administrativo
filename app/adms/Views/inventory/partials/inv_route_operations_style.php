<?php if (!isset($this)) { exit; } ?>
<style>
  .inv-view-sub-toggle {
    color: #6c757d !important;
    font-weight: 600;
    font-size: .78rem;
    text-transform: uppercase;
  }
  .inv-view-sub-toggle:hover { color: #198754 !important; }
  .inv-view-sub-toggle .inv-chevron {
    transition: transform .2s ease;
    font-size: .7rem;
  }
  .inv-view-sub-toggle.collapsed .inv-chevron { transform: rotate(-90deg); }

  .inv-route-op-card .card-header { padding: .5rem .75rem; }
  .inv-route-op-card .card-body { padding: .5rem .75rem; }

  .inv-route-op-card code.inv-erp-code,
  .inv-route-op-card .inv-op-code-display {
    color: #b02a37;
  }

  .inv-route-lines-align {
    width: 100%;
    container-type: inline-size;
  }
  .inv-route-lines-align .collapse {
    padding-left: 0;
    padding-right: 0;
  }

  .inv-route-sub-table {
    table-layout: fixed;
    width: 100%;
    box-sizing: border-box;
  }
  .inv-route-sub-table thead th {
    font-size: inherit;
    font-weight: 600;
    vertical-align: middle;
    white-space: nowrap;
  }
  .inv-route-sub-table tbody td {
    vertical-align: middle;
  }
  .inv-route-sub-table .inv-cell-num,
  .inv-route-sub-table thead th.inv-cell-num {
    text-align: left;
  }
  .inv-route-sub-table .inv-cell-tag {
    text-align: center;
    vertical-align: middle;
  }
  .inv-route-sub-table .inv-cell-act {
    text-align: center;
    vertical-align: middle;
    width: 2rem;
    padding-left: .15rem;
    padding-right: .15rem;
  }

  .inv-route-lines-align .inv-route-sub-table col:nth-child(1) { width: 28cqi; }
  .inv-route-lines-align .inv-route-sub-table col:nth-child(2) { width: 7cqi; }
  .inv-route-lines-align .inv-route-sub-table col:nth-child(3) { width: 9cqi; }
  .inv-route-lines-align .inv-route-sub-table col:nth-child(4) { width: 10cqi; }
  .inv-route-lines-align .inv-route-sub-table col:nth-child(5) { width: 10cqi; }
  .inv-route-lines-align .inv-route-sub-table col:nth-child(6) { width: 11cqi; }
  .inv-route-lines-align .inv-route-sub-table col:nth-child(7) { width: 11cqi; }
  .inv-route-lines-align .inv-route-sub-table col:nth-child(8) { width: 7cqi; }
  .inv-route-lines-align .inv-route-sub-table td:first-child,
  .inv-route-lines-align .inv-route-sub-table th:first-child {
    overflow: hidden;
  }

  /* —— Edição: mesmos blocos da view, campos discretos —— */
  .inv-route-edit .inv-seq-badge-input {
    background: transparent;
    border: 0;
    color: #fff;
    width: 2.75rem;
    padding: 0;
    margin: 0;
    text-align: center;
    font-size: inherit;
    font-weight: inherit;
    line-height: inherit;
    -moz-appearance: textfield;
    appearance: textfield;
  }
  .inv-route-edit .inv-seq-badge-input::-webkit-outer-spin-button,
  .inv-route-edit .inv-seq-badge-input::-webkit-inner-spin-button {
    -webkit-appearance: none;
    margin: 0;
  }
  .inv-route-edit .inv-seq-badge-input:focus {
    outline: 1px solid rgba(255, 255, 255, .45);
    border-radius: .15rem;
  }

  .inv-route-edit .inv-op-select-inline {
    display: inline-block;
    width: auto;
    max-width: min(28rem, calc(100vw - 8rem));
    border: 0;
    border-bottom: 1px dashed transparent;
    background: transparent;
    font-weight: 700;
    padding: 0 .1rem;
    font-size: inherit;
    line-height: inherit;
    vertical-align: baseline;
    cursor: pointer;
  }
  .inv-route-edit .inv-op-select-inline:hover {
    border-bottom-color: #adb5bd;
  }
  .inv-route-edit .inv-op-select-inline:focus {
    border-bottom-color: #198754;
    box-shadow: none;
  }

  .inv-route-edit .inv-op-remove-btn {
    font-size: .72rem;
    padding: 0;
    vertical-align: middle;
    text-decoration: none;
    opacity: .65;
  }
  .inv-route-edit .inv-op-remove-btn:hover { opacity: 1; }

  .inv-route-edit .inv-metric-inline,
  .inv-route-edit .inv-metric-inline-select {
    display: inline-block;
    border: 0;
    border-bottom: 1px dashed #ced4da;
    background: transparent;
    padding: 0 2px;
    font-weight: 700;
    color: #212529;
    width: auto;
    min-width: 3.25rem;
    max-width: 5.5rem;
    font-size: inherit;
    height: auto;
    min-height: 0;
    line-height: inherit;
    vertical-align: baseline;
  }
  .inv-route-edit .inv-metric-inline-select {
    font-weight: 400;
    max-width: 6.5rem;
    padding-right: 1.25rem;
  }
  .inv-route-edit .inv-metric-inline:focus,
  .inv-route-edit .inv-metric-inline-select:focus {
    border-bottom-color: #198754;
    box-shadow: none;
    outline: none;
  }

  .inv-route-edit .inv-notes-inline {
    display: inline-block;
    width: auto;
    min-width: 10rem;
    max-width: calc(100% - 6rem);
    border: 0;
    border-bottom: 1px dashed #ced4da;
    background: transparent;
    padding: 0 2px;
    font-size: inherit;
    height: auto;
    min-height: 0;
    line-height: inherit;
    vertical-align: baseline;
  }
  .inv-route-edit .inv-notes-inline:focus {
    border-bottom-color: #198754;
    box-shadow: none;
    outline: none;
  }

  .inv-route-edit .inv-route-sub-table .form-control-sm,
  .inv-route-edit .inv-route-sub-table .form-select-sm {
    width: 100%;
    max-width: 100%;
    min-width: 0;
    box-sizing: border-box;
    border-color: transparent;
    border-bottom-color: #e9ecef;
    padding-left: 0;
    padding-right: 0;
    background: transparent;
  }
  .inv-route-edit .inv-route-sub-table .form-control-sm:focus,
  .inv-route-edit .inv-route-sub-table .form-select-sm:focus {
    border-color: #86b7fe;
    box-shadow: none;
    background: #fff;
  }
  .inv-route-edit .inv-route-sub-table .inv-cell-num .form-control-sm {
    text-align: left;
    font-weight: 600;
  }
  .inv-route-edit .inv-cell-readonly {
    display: block;
    text-align: left;
    font-variant-numeric: tabular-nums;
  }
  .inv-route-edit .inv-row-remove {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 1.5rem;
    height: 1.5rem;
    padding: 0;
    border: 0;
    border-radius: .2rem;
    color: #adb5bd;
    background: transparent;
    line-height: 1;
    text-decoration: none;
  }
  .inv-route-edit .inv-row-remove:hover,
  .inv-route-edit .inv-row-remove:focus {
    color: #dc3545;
    background: rgba(220, 53, 69, .08);
  }

  .inv-route-edit .inv-add-row-link {
    font-size: .78rem;
    font-weight: 600;
    color: #6c757d !important;
    text-decoration: none;
    text-transform: uppercase;
  }
  .inv-route-edit .inv-add-row-link:hover {
    color: #198754 !important;
  }

  .inv-route-edit .inv-route-sub-block {
    margin-bottom: .5rem;
  }
  .inv-route-edit .inv-route-sub-block:last-child {
    margin-bottom: 0;
  }
</style>
