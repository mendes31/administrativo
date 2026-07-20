# Avaliações em massa — Expand Fase 5 (paridade de mercado)

- Domínio: Gestão de Pessoas / Desenvolvimento.
- Data: 20/07/2026.
- Status: 1º incremento — gerar avaliações em lote a partir do ciclo.

## Objetivo

A partir de um ciclo `draft|open`, gerar avaliações em `adms_performance_reviews`
(status `draft`) para um conjunto de colaboradores, com opção de pular
duplicatas (mesmo colaborador + ciclo + tipo).

## Regras

- Ciclo obrigatório e não `closed` (`PerformanceCycleService::assertMayLink`).
- Tipos: `90`, `180`, `360`, `annual`.
- Escopos: `all_active` | `department` (exige `department_id`) | `manager`
  (exige `manager_id`; sem full access, só o próprio gestor).
- Avaliador: `supervisor` (usa `immediate_supervisor_id`; fallback = ator) ou
  `fixed` (exige `reviewer_id`).
- Período da review = `period_start` / `period_end` do ciclo.
- `skip_existing` (default true): não cria se já existir review com o mesmo
  `(employee_id, performance_cycle_id, review_type)`.
- Sem full access: escopo `all_active`/`department` restringe aos subordinados
  do ator; `manager` só permite `manager_id` = ator.

## Modelo

Nenhuma tabela nova — reusa `adms_performance_reviews`.

## UI / ACL

- Controller: `BulkCreatePerformanceReviews` / `bulk-create-performance-reviews`
- Atalho na view do ciclo: “Gerar avaliações”
- ACL espelhada de `ListPerformanceReviews` (fallback ciclos)
- Menu: sem item novo; `related_routes` do ciclo inclui a rota

## Fora deste incremento

- 360° multiavaliador automático;
- templates de competências em massa;
- notificações aos avaliadores;
- import CSV;
- feedback contínuo global;
- calibração avançada;
- matching Nine Box → PDI.

## Migration

`database/migrations/20260720250000_register_bulk_create_performance_reviews.php`

## Dependências

[DESENVOLVIMENTO_CICLOS_EXPAND.md](DESENVOLVIMENTO_CICLOS_EXPAND.md),
[DESENVOLVIMENTO_AVALIACOES_CICLO_EXPAND.md](DESENVOLVIMENTO_AVALIACOES_CICLO_EXPAND.md)
