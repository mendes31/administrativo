# Feedback contínuo global — Expand Fase 5 (paridade de mercado)

- Domínio: Gestão de Pessoas / Desenvolvimento.
- Data: 20/07/2026.
- Status: 1º incremento — hardening do CRUD `adms_performance_feedbacks`.

## Objetivo

Tornar o canal de **feedback contínuo fora do PDI** utilizável de forma
confiável: destinatários corretos para gestores, busca funcional, regras
claras de **público/privado** e **anonimato**, com `PerformanceFeedbackService`.

## Regras

- Tipos: `general|performance|recognition|improvement`.
- Texto obrigatório; `given_by` = ator logado (sem impersonação).
- Destinatário: full access = ativos; gestor = apenas subordinados
  (`immediate_supervisor_id`).
- Visibilidade:
  - privado: autor, destinatário, full access;
  - público (`is_public`): o acima + gestor imediato do destinatário.
- Anônimo (`is_anonymous`): oculta o nome do autor na UI para quem não é
  autor nem full access.
- Listagem: filtros + `search` (texto e nomes); mesmo escopo de visibilidade.
- View/Update: gate via service (sem acesso → redirect).

## Modelo

Reusa `adms_performance_feedbacks` (sem coluna nova neste incremento).

## UI / ACL

- Controllers existentes: List/Create/View/Update/DeletePerformanceFeedback
- Migration: grants idempotentes + bump cache de menu
- Menu Desempenho → Feedbacks (já existente)

## Fora deste incremento

- unificar com `adms_pdi_feedbacks`;
- notificações;
- feed peer-to-peer livre;
- vínculo obrigatório a ciclo;
- calibração avançada;
- matching Nine Box → PDI.

## Migration

`database/migrations/20260720260000_expand_performance_feedback_continuous.php`

## Dependências

CRUD legado de feedbacks; [DESENVOLVIMENTO_PDI_COMPLETO_EXPAND.md](DESENVOLVIMENTO_PDI_COMPLETO_EXPAND.md)
(feedback no plano permanece separado).
