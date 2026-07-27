# ADR-0007 — Workflow de solicitações com delegação e escalação

- Status: Aprovado
- Data: 2026-07-21
- Responsável: Arquitetura / Gestão de Pessoas (Portal)
- Módulos impactados: solicitações do colaborador, tipos de solicitação, ACL portal

## Contexto

O fluxo binário gestor→RH não tratava ausência do imediato nem distinção entre
aprovar e acompanhar a árvore (ex.: coordenador aprova; gerente de área acompanha).

## Decisão

1. Etapas por tipo (`immediate` / `hr`) com status legados preservados.
2. Delegação temporária com vigência antes da escalação.
3. Escalação por SLA para o nível acima; fallback RH.
4. Visão de equipe recursiva separada da autorização de ato.
5. Expand/Contract: sem remover colunas `manager_*` / `hr_*` neste passo.

## Alternativas consideradas

- Sempre enviar ao gestor da área (sobrecarrega Nathiele-like).
- Só RH na ausência (engessa a área).
- Motor BPM genérico (custo alto para o 1º incremento).

## Consequências

### Positivas

- deploy compatível com filas e status atuais;
- ausência coberta por delegação + SLA;
- gestora de área acompanha sem ser obrigada a aprovar tudo.

### Negativas e riscos

- depende de hierarquia (`immediate_supervisor_id`) cadastrada;
- escalação exige cron/CLI periódico;
- etapa RH com ACL via página <em>ApproveEmployeeRequestHR</em> (concedida automaticamente a quem já tem <em>PendingApprovals</em>);
- notificações in-app (sino + push) e e-mail ao entrar em cada etapa, escalar ou finalizar/rejeitar.
