# Requisição de pessoal — Expand Fase 1

- Domínio: Gestão de Pessoas / Talentos.
- Data: 19/07/2026.
- Status: fluxo mínimo entregue (criar → aprovar/rejeitar → converter em vaga).

## Modelo

Tabela `rh_personnel_requests` + `rh_vagas.personnel_request_id` (nullable, único).

Estados: `pending_approval` → `approved` | `rejected` → `converted`.

## Regras

- Sem autoaprovação.
- Conversão só se `approved` e ainda sem vaga; transação com `FOR UPDATE`.
- Vaga gerada inicia como `pausada` para completar detalhes.
- Módulo de solicitações do portal (`adms_employee_requests`) **não** é a fonte canônica.

## Telas

- `rh-personnel-requests` — listagem
- create / view / approve / reject / convert

## Migration

`database/migrations/20260719160000_create_rh_personnel_requests.php`
