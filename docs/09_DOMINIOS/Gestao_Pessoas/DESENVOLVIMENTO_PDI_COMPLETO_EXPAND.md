# PDI completo — Expand Fase 5 (paridade de mercado)

- Domínio: Gestão de Pessoas / Desenvolvimento.
- Data: 20/07/2026.
- Status: 2º incremento PDI — metas, feedbacks, aprovação e progresso.
- Referência de mercado: Feedz / MarQ / LG (`MODULO_PDI_PROPOSAL.md`).

## Objetivo

Completar o PDI operacional para uso de ponta a ponta no ciclo de
desenvolvimento: metas mensuráveis, feedback contínuo no plano, aprovação
do gestor e leitura de progresso (ações + metas).

## Regras

- Metas (`adms_pdi_goals`): título obrigatório; status
  `pending|in_progress|achieved|failed`; progresso via valores ou status.
- Feedbacks (`adms_pdi_feedbacks`): texto obrigatório; tipo
  `general|action|milestone|final`; `given_by` = usuário logado;
  `given_to` = colaborador do plano (ou gestor, se o autor for o colaborador).
- Aprovação: de `draft` → `active` preenche `approved_by` / `approved_at`;
  apenas quem tem `UpdatePdiPlan` (mesmo ACL do 1º incremento).
- Progresso do plano (view): % médio de ações + % de metas alcançadas
  (informativo; não altera status automaticamente).

## Modelo

Sem migration de schema se `adms_pdi_goals` / `adms_pdi_feedbacks` e campos
`approved_*` já existirem (`20251203000000_create_adms_pdi_tables.php`).

## UI / ACL

- Extensão de `ViewPdiPlan`: seções Metas e Feedbacks + botão Aprovar.
- Sem páginas ACL novas neste incremento.
- Manual `list-pdi-plans` atualizado.

## Fora deste incremento

- Dashboard/relatórios analíticos (Fase 6);
- MyPdiPlans dedicado;
- sync automático treinamento → ação;
- carreira / sucessão / matching Nine Box→PDI;
- delete hard de plano.

## Dependências

[DESENVOLVIMENTO_PDI_EXPAND.md](DESENVOLVIMENTO_PDI_EXPAND.md)
