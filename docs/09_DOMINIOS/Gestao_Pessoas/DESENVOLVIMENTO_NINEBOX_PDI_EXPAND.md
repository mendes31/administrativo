# Matching Nine Box → PDI — Expand Fase 5 (1º incremento)

- Domínio: Gestão de Pessoas / Desenvolvimento.
- Data: 20/07/2026.
- Status: 1º incremento entregue (rascunho a partir do quadrante).

## Objetivo

Permitir, na **Matriz 9BOX** com ciclo selecionado, criar um **PDI em rascunho**
já pré-preenchido com título/descrição/orientação do quadrante (1–9), vinculado
ao colaborador e ao ciclo.

## Comportamento

1. Botão **Criar PDI** por colaborador na célula (ACL `CreatePdiPlan`).
2. Service `NineBoxPdiMatchService` monta payload (período = datas do ciclo;
   gestor = `immediate_supervisor_id` quando existir).
3. Se já existir PDI `draft`/`active` do mesmo user+ciclo → abre o existente
   (não duplica).
4. Após criar/reutilizar → redireciona para `view-pdi-plan/{id}`.
5. Na matriz, se já houver PDI aberto → link **Ver PDI** (`ViewPdiPlan`).

## Fora deste incremento

- geração automática de ações/competências;
- matching em massa (todos os boxes);
- IA;
- coluna `nine_box` obrigatória em `adms_pdi_plans`;
- sync com calibração lock.

## Arquivos

- `NineBoxPdiMatchService`, `NineBoxMatrix` (POST `create_pdi`),
  `PdiPlansRepository::findOpenByUserAndCycle` / `getOpenMapByCycle`,
  view `nine_box_matrix.php`, manual `nine-box-matrix.html`.
