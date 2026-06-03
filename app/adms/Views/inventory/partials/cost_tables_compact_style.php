<?php if (!isset($this)) { exit; } ?>
<style>
  .inv-cost-compact-tables table {
    table-layout: fixed;
    width: 100%;
    font-size: 0.7rem;
    margin-bottom: 0;
  }
  .inv-cost-compact-tables thead th {
    font-size: 0.65rem;
    font-weight: 600;
    padding: 0.25rem 0.35rem;
    white-space: nowrap;
    vertical-align: middle;
  }
  .inv-cost-compact-tables tbody td {
    padding: 0.2rem 0.35rem;
    vertical-align: middle;
    line-height: 1.2;
  }
  .inv-cost-compact-tables .cell-truncate {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    max-width: 1px;
  }
  .inv-cost-compact-tables .cell-component-name {
    font-size: 0.8rem;
    font-weight: 600;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    display: block;
    line-height: 1.25;
  }
  .inv-cost-compact-tables .cell-op-title {
    font-size: 0.78rem;
    font-weight: 600;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    display: block;
    line-height: 1.25;
  }
  .inv-cost-compact-tables .cell-desc-sub {
    font-size: 0.65rem;
    color: #6c757d;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    display: block;
    max-width: 100%;
    line-height: 1.15;
  }
  .inv-cost-compact-tables .col-w-component { width: 50%; }
  .inv-cost-compact-tables .col-w-qty { width: 11%; }
  .inv-cost-compact-tables .col-w-cost { width: 11%; }
  .inv-cost-compact-tables .col-w-line { width: 12%; }
  .inv-cost-compact-tables .col-w-min { width: 8%; }
  .inv-cost-compact-tables .col-w-mo { width: 19%; font-size: 0.62rem; }
  .inv-cost-compact-tables .col-num {
    font-size: 0.65rem;
    padding-left: 0.2rem !important;
    padding-right: 0.2rem !important;
  }
  .inv-cost-compact-tables .card-header {
    padding-top: 0.45rem;
    padding-bottom: 0.45rem;
    font-size: 0.85rem;
  }
  .inv-cost-compact-tables .table-wrap {
    padding: 0.35rem 0.5rem 0.5rem;
  }
  .inv-cost-compact-tables code {
    font-size: 0.65rem;
  }
  .inv-cost-compact-tables .nav-tabs .nav-link {
    font-size: 0.8rem;
    padding: 0.35rem 0.65rem;
  }
  @media (max-width: 767.98px) {
    .inv-cost-compact-tables .nav-tabs {
      flex-wrap: nowrap;
      overflow-x: auto;
      -webkit-overflow-scrolling: touch;
      border-bottom: 1px solid #dee2e6;
    }
    .inv-cost-compact-tables .nav-tabs .nav-item {
      flex-shrink: 0;
    }
    .inv-cost-compact-tables .table-wrap {
      padding: 0;
      border: 0 !important;
    }
  }
  .inv-structure-mobile-card {
    font-size: 0.8rem;
  }
  .inv-structure-mobile-card .card-body {
    padding: 0.65rem 0.75rem;
  }
  .inv-structure-mobile-card .row-label {
    color: #6c757d;
    font-size: 0.72rem;
  }
  .inv-structure-mobile-total {
    font-size: 0.8rem;
    padding: 0.5rem 0.75rem;
    background: #f8f9fa;
    border-radius: 0.375rem;
  }
</style>
