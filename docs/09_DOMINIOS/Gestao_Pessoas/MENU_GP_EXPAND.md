# Menu Gestão de Pessoas — Expand

- Domínio: Gestão de Pessoas / UX de navegação.
- Data: 20/07/2026.
- Status: Concluído (1º incremento) — reorganização do submenu em `menu.php`.

## Objetivo

Alinhar o menu lateral à estrutura-alvo do Executivo:

```text
Gestão de Pessoas
├── Organização
├── Talentos
├── Jornada do Colaborador
├── Desenvolvimento
├── Portal do Colaborador
└── People Analytics
```

## Regras deste incremento

- Apenas rearranjo de labels/níveis em `app/adms/Views/partials/menu.php`.
- Controllers e ACL (`adms_pages` / permissões) **não mudam**.
- **Filiais permanecem em Administração → Configurações** — cadastro transversal
  (empresa contratante, filtros multi-unidade, etc.), não exclusivo de GP.
- Itens de folha digital (importação/pendências/cron/tipos) ficam no submenu **Folha Digital (RH)** dentro de Portal.
- Solicitações e Chamados ficam sob **Portal do Colaborador**.
- Top-level **Cadastro** (Usuários, Cargos, Departamentos) permanece — evita quebrar hábitos financeiros/ACL.

## Mapeamento aplicado

| Grupo | Conteúdo |
|---|---|
| Organização | Políticas Internas; Categorias de Políticas |
| Talentos | Dashboard, Currículos, Vagas, Portal público, Requisições, Entrevistas |
| Jornada do Colaborador | Pessoas; Movimentações; Offboarding |
| Desenvolvimento | Ex-grupo Desempenho (ciclos, PDI, 9BOX, carreira, sucessão, etc.) |
| Portal do Colaborador | Portal, meus docs/EPI/SST, Solicitações, Chamados, Folha Digital (RH) |
| People Analytics | Dashboard, relatórios, Pulse/eNPS, planejamento de quadro |

## Fora deste incremento

- Mover Usuários/Cargos/Departamentos para dentro de Organização;
- Unificar Treinamentos (grupo 24) sob Desenvolvimento;
- Nested groups em `adms_groups_pages` (menu continua hardcoded em PHP).

## Arquivo

`app/adms/Views/partials/menu.php`
