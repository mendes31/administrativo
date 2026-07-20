# Menu Gestão de Pessoas — Expand

- Domínio: Gestão de Pessoas / UX de navegação.
- Data: 20/07/2026.
- Status: Concluído (1º incremento) — reorganização do submenu em `menu.php`.

## Objetivo

Alinhar o menu lateral à estrutura-alvo, separando **self-service**,
**operações de RH/gestor** e **parametrização**.

```text
Gestão de Pessoas
├── Organização             (parametrização)
├── Talentos
├── Jornada do Colaborador
├── Desenvolvimento
├── Portal do Colaborador   (self-service)
├── Solicitações (RH)       (aprovações gestor/RH)
├── Folha Digital (RH)      (operações RH)
└── People Analytics
```

## Regras deste incremento

- Apenas rearranjo de labels/níveis em `app/adms/Views/partials/menu.php`.
- Controllers e ACL (`adms_pages` / permissões) **não mudam**.
- **Filiais** em Administração → Configurações (cadastro transversal).
- **Organização (parametrização):** políticas, categorias, tipos de solicitação,
  tipos de documento RH, cron de lembretes.
- **Portal (self-service):** Início, meus docs/EPI/SST, Minhas Solicitações, Meus Chamados.
- **Solicitações (RH):** Aprovações Pendentes (fora do Portal).
- **Folha Digital (RH):** importação + pendências de ciência.
- Link do painel = **Início** (evita “Portal dentro de Portal”).
- Top-level **Cadastro** permanece.

## Mapeamento aplicado

| Grupo | Conteúdo |
|---|---|
| Organização | Políticas; Categorias; Tipos de Solicitação; Tipos de documento (RH); Cron lembretes folha |
| Talentos | Dashboard, Currículos, Vagas, Portal público CAPTCHA, Requisições, Entrevistas |
| Jornada do Colaborador | Pessoas; Movimentações; Offboarding |
| Desenvolvimento | Ex-grupo Desempenho |
| Portal do Colaborador | Início; meus docs/EPI/SST; Minhas Solicitações; Meus Chamados |
| Solicitações (RH) | Aprovações Pendentes |
| Folha Digital (RH) | Importar PDFs; Pendências de ciência |
| People Analytics | Dashboard, relatórios, Pulse/eNPS, planejamento de quadro |

## Fora deste incremento

- Mover Usuários/Cargos/Departamentos para dentro de Organização;
- Unificar Treinamentos (grupo 24) sob Desenvolvimento;
- Nested groups em `adms_groups_pages` (menu continua hardcoded em PHP).

## Arquivo

`app/adms/Views/partials/menu.php`
