<?php if (!isset($this)) { exit; } ?>
<style>
  .cons-route-toolbar {
    position: sticky;
    top: 0;
    z-index: 2;
    background: rgba(255, 255, 255, .96);
    backdrop-filter: blur(4px);
    border: 1px solid #dee2e6;
    border-radius: .5rem;
    padding: .65rem .85rem;
    margin-bottom: 1rem;
  }

  .cons-route-op.inv-route-op-card {
    border-left: 3px solid #0d6efd;
    border-radius: .5rem;
    overflow: hidden;
  }
  .cons-route-op.cons-route-op--alt {
    border-left-color: #6f42c1;
  }
  .cons-route-op.cons-route-op--alt .card-header {
    background: linear-gradient(90deg, rgba(111, 66, 193, .12) 0%, rgba(111, 66, 193, .03) 100%);
  }
  .cons-route-op .cons-route-op-header {
    cursor: pointer;
    user-select: none;
    border-bottom: 1px solid rgba(0, 0, 0, .06);
  }
  .cons-route-op .cons-route-op-header .cons-op-chevron {
    transition: transform .2s ease;
    font-size: .75rem;
  }
  .cons-route-op .cons-route-op-header.collapsed .cons-op-chevron {
    transform: rotate(-90deg);
  }
  .cons-route-op .card-header {
    background: linear-gradient(90deg, rgba(13, 110, 253, .1) 0%, rgba(13, 110, 253, .03) 100%);
  }
  .cons-route-op-inner {
    background: #fff;
    border-bottom: 1px dashed #dee2e6;
  }
  .cons-route-op-lines {
    padding-top: .75rem;
  }

  .cons-route-op .cons-op-select-inline {
    display: inline-block;
    width: auto;
    max-width: min(22rem, calc(100vw - 10rem));
    border: 0;
    border-bottom: 1px dashed transparent;
    background: transparent;
    font-weight: 700;
    padding: 0 .1rem;
    font-size: inherit;
    cursor: pointer;
  }
  .cons-route-op .cons-op-select-inline:hover {
    border-bottom-color: #adb5bd;
  }
  .cons-route-op .cons-op-select-inline:focus {
    border-bottom-color: #0d6efd;
    box-shadow: none;
  }

  .cons-op-time-bar {
    display: flex;
    flex-wrap: wrap;
    align-items: flex-end;
    gap: .75rem 1.25rem;
    padding: .55rem .75rem;
    background: rgba(255, 255, 255, .75);
    border: 1px solid rgba(13, 110, 253, .2);
    border-radius: .375rem;
  }

  .cons-op-time-group {
    flex: 0 0 auto;
  }

  .cons-op-time-label,
  .cons-op-notes-label {
    display: block;
    font-size: .7rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: .02em;
    color: #0d6efd;
    margin-bottom: .2rem;
    white-space: nowrap;
  }

  .cons-op-notes-label {
    color: #6c757d;
  }

  .cons-op-time-controls {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: .4rem .5rem;
  }

  .cons-route-op .cons-op-time-input {
    width: 6.5rem;
    min-width: 6.5rem;
    font-weight: 600;
    text-align: right;
  }

  .cons-route-op .cons-op-time-unit {
    width: 6.75rem;
    min-width: 6.75rem;
    flex-shrink: 0;
  }

  .cons-op-time-result {
    font-size: .8125rem;
    white-space: nowrap;
    padding-left: .15rem;
  }

  .cons-op-notes-group {
    flex: 1 1 14rem;
    min-width: 12rem;
  }

  .cons-route-op .cons-op-notes-input {
    width: 100%;
    min-width: 0;
  }

  @media (max-width: 575.98px) {
    .cons-op-time-bar {
      flex-direction: column;
      align-items: stretch;
    }
    .cons-op-notes-group {
      min-width: 0;
    }
  }

  .cons-section-panel {
    background: #f8f9fa;
    border: 1px solid #e9ecef;
    border-radius: .375rem;
    padding: .5rem .65rem;
    height: 100%;
  }

  .cons-section-empty {
    border: 1px dashed #ced4da;
    border-radius: .375rem;
    padding: 1rem .75rem;
    text-align: center;
    background: #fff;
  }

  .cons-section-empty .cons-empty-icon {
    font-size: 1.25rem;
    color: #adb5bd;
    margin-bottom: .35rem;
  }

  .cons-resource-count,
  .cons-labor-count {
    font-size: .65rem;
    vertical-align: middle;
  }

  .cons-route-op .cons-route-sub-table {
    table-layout: fixed;
    width: 100%;
  }
  .cons-route-op .cons-route-sub-table col.cons-col-item { width: auto; }
  .cons-route-op .cons-route-sub-table col.cons-col-qty { width: 3.5rem; }
  .cons-route-op .cons-route-sub-table col.cons-col-time { width: 4.75rem; }
  .cons-route-op .cons-route-sub-table col.cons-col-ref { width: 5.25rem; }
  .cons-route-op .cons-route-sub-table col.cons-col-cost-a { width: 5rem; }
  .cons-route-op .cons-route-sub-table col.cons-col-cost-b { width: 5rem; }
  .cons-route-op .cons-route-sub-table col.cons-col-sub-min { width: 5rem; }
  .cons-route-op .cons-route-sub-table col.cons-col-sub-lote { width: 5.75rem; }
  .cons-route-op .cons-route-sub-table col.cons-col-act { width: 2rem; }

  .cons-route-op .cons-route-sub-table thead th {
    font-size: .72rem;
    font-weight: 600;
    white-space: nowrap;
    padding: .35rem .3rem;
    vertical-align: bottom;
    line-height: 1.15;
  }
  .cons-route-op .cons-route-sub-table tbody td {
    padding: .3rem .3rem;
    vertical-align: middle;
  }
  .cons-route-op .cons-route-sub-table .inv-cell-readonly {
    display: block;
    font-size: .8125rem;
    white-space: nowrap;
  }

  .cons-cost-hint {
    line-height: 1.35;
    margin-bottom: .5rem;
  }

  .cons-route-sub-table .form-control-sm,
  .cons-route-sub-table .form-select-sm {
    width: 100%;
    min-width: 0;
    font-size: .8125rem;
  }

  .cons-route-op .cons-route-driver-table col.cons-col-qty { width: 4rem; }
  .cons-route-op .cons-route-driver-table col.cons-col-time { width: 4.75rem; }
  .cons-route-op .cons-route-driver-table col.cons-col-ref { width: 5.25rem; }
  .cons-route-op .cons-route-driver-table col.cons-col-act { width: 2rem; }

  .cons-route-op .cons-resource-line-time,
  .cons-route-op .cons-labor-line-time {
    text-align: right;
    font-size: .8125rem;
  }
  .cons-route-op .cons-resource-line-time::placeholder,
  .cons-route-op .cons-labor-line-time::placeholder {
    font-style: italic;
    opacity: .55;
  }

  .cons-route-grand-total {
    border-top: 2px solid #dee2e6;
    padding-top: .75rem;
    margin-top: .5rem;
  }

  @media (max-width: 991.98px) {
    .cons-route-toolbar {
      position: static;
    }
  }
</style>
