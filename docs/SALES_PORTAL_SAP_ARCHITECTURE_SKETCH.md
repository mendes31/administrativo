# Portal de Vendas (SAP B1) — Esboço arquitetural aderente ao projeto

Documento de referência para implementação futura. Padrões verificados no código em `app/adms/`, `database/migrations`, `database/seeds`, `routes/`, `app/adms/Views/partials/menu.php`, `ListLogAlteracoes`, `LogResumoService`, `button_log_alteracoes.php`.

### Decisão: máxima reutilização do ecossistema actual

- **Stack**: PHP (MVC existente), **sem** aplicação paralela em ASP.NET para este portal.
- **Reutilizar**: `LoadPageAdmAccessLevel`, `adms_pages` / seeds, `PageLayoutService`, partials (`head`, menu, alerts, botão log), `SapB1ServiceLayer` + extensão para entidades de vendas, `LogAlteracaoService` / `LogResumoService`, padrão CRM (controllers, views, repositories).
- **Benefício**: um só deploy, mesmas permissões e auditoria, menos duplicação de identidade e de integração SL.

---

## 1. Decisão de produto (menu)

- **Opção A — Menu raiz** `Portal de Vendas (SAP)`: novo bloco no array `$menu` em `menu.php`, **rótulos do submenu em ordem alfabética** (ex.: Acompanhamento de pedidos → Consulta de estoque → …).
- **Opção B — Dentro do CRM**: inserir um submenu **"Portal de vendas SAP"** dentro do item `CRM`, com as mesmas entradas, também **alfabéticas**.
- Em ambos: `permission` = nome da controller (ex.: `SalesPortalLaunchpad`), espelhando `adms_pages.controller`.
- **Destaque ativo**: manter o mesmo critério já usado — `menu` em `PageLayoutService::configurePageElements` com `controller_url` da página atual (ex.: `sales-portal-launchpad` para a home do portal).

---

## 2. Nomenclatura e pastas

| Elemento | Padrão do projeto | Proposta |
|----------|-------------------|----------|
| Namespace controllers | `App\adms\Controllers\{pasta}\` | `App\adms\Controllers\salesPortal\` |
| Pasta física | `app/adms/Controllers/salesPortal/` (camelCase, cf. `crm`, `companyEvents`) | Igual |
| Nome da classe | PascalCase = ficheiro | `SalesPortalLaunchpad`, `SalesPortalListQuotations`, … |
| `adms_pages.directory` | Igual ao segmento da pasta | `salesPortal` |
| `controller_url` | kebab-case | `sales-portal-launchpad`, `sales-portal-list-quotations`, … |
| Repositories | `app/adms/Models/Repository/` | `SalesPortalUserLinksRepository`, … |
| Views | `app/adms/Views/sales_portal/` ou `salesPortal/` | Preferir **snake_case de pasta** só se o projeto já misturar — hoje CRM usa `Views/crm/`; usar **`Views/salesPortal/`** alinhado a `companyEvents` (camelCase) |

**Roteador legado** (`routes/LoadPageAdm.php`): acrescentar cada controller em `$listPgPrivate` e `salesPortal` em `$listDirectory` (igual CRM).

**Service Layer**: reutilizar e estender `App\adms\Models\Services\SapB1ServiceLayer` (login, cookies, `makeRequest` hoje privado — extrair métodos públicos por domínio ou criar `SapB1SalesDocumentsService` que encapsule POST/PATCH em `Orders`, `Quotations`, etc., sem duplicar login).

---

## 3. Migrations (somente DDL, nomes únicos)

Antes de criar ficheiro, listar `database/migrations/` e escolher timestamp **posterior** ao último existente (ex.: `YYYYMMDDHHMMSS`).

### 3.1 `20XXXXXXXXXXXX_create_sales_portal_user_links.php` (exemplo de nome final a ajustar)

- **Tabela** `sales_portal_user_links` (prefixo sem `adms_` alinhado a `crm_`).
- Colunas sugeridas:
  - `id` PK AI UNSIGNED
  - `adms_user_id` UNSIGNED, **UNIQUE**, FK → `adms_users.id` (`ON DELETE CASCADE` ou `RESTRICT` conforme política; preferir RESTRICT + soft validation)
  - `sap_card_code` VARCHAR(15) ou tamanho usado no B1, NOT NULL após validação na app
  - `default_whscode` VARCHAR(8) NULL (depósito padrão B1, se aplicável)
  - `active` TINYINT(1) DEFAULT 1
  - `created_by`, `updated_by` UNSIGNED NULL, FK users
  - `created_at`, `updated_at` TIMESTAMP
- **Índices**: `UNIQUE(adms_user_id)`, índice em `sap_card_code`, `active`.
- **Segurança**: `hasTable` + early return no `change()`; sem dados sensíveis em migration; comentários Phinx em colunas.

### 3.2 Migrations adicionais (fases)

- Tabelas de **rascunho local** (opcional): `sales_portal_draft_documents` apenas se a UX exigir persistir antes do POST no B1 — cada uma em migration própria com FKs e índices explícitos.
- **Não** colocar lógica de negócio ou seeds em migrations.

---

## 4. Seeds

### 4.1 `database/seeds/AddAdmsGroupsPages.php`

- Inserir na lista `$grupos` o registo **`Portal de Vendas (SAP)`** (nome estável), na posição que o time usar para **ordenação documental**; o ID real será auto-increment.

### 4.2 `database/seeds/AddAdmsPages.php`

1. Em `$groupsToEnsure`, incluir `['name' => 'Portal de Vendas (SAP)', 'obs' => '…']`.
2. No bloco que resolve IDs reais (como `Gestão de Pessoas`, `Comunicação Social`), adicionar:
   - `fetchRow` por `name = 'Portal de Vendas (SAP)'` → `$portalVendasSapGroupId`.
3. No `foreach ($pages as $page)`, acrescentar ramo **placeholder** (ex.: `elseif ($groupId == 40) { $groupId = $portalVendasSapGroupId; }`) — usar um ID numérico **não colidente** com os já mapeados (28, 31, 36, 37, 39, 0); documentar no comentário ao lado das páginas novas.
4. **Novas linhas** no array `$pages` (uma por rota privada), todas com `adms_packages_page_id` => 1, `public_page` => 0, `page_status` => 1, `default_page` => 0 (exceto endpoints JSON com `default_page` => 1 se seguirem padrão timeline/gamification).

**Lista mínima de páginas (exemplo — ajustar nomes finais):**

| name (PT) | controller | controller_url | default_page |
|-------------|------------|------------------|----------------|
| Portal de Vendas — Início | SalesPortalLaunchpad | sales-portal-launchpad | 0 |
| Portal de Vendas — Cotações | SalesPortalListQuotations | sales-portal-list-quotations | 0 |
| Portal de Vendas — Nova cotação | SalesPortalCreateQuotation | sales-portal-create-quotation | 0 |
| Portal de Vendas — Ver cotação | SalesPortalViewQuotation | sales-portal-view-quotation | 0 |
| Portal de Vendas — Pedidos | SalesPortalListOrders | sales-portal-list-orders | 0 |
| … | … | … | … |

5. **ACL inicial**: replicar o padrão do fim da seed (ex.: `INSERT IGNORE` a partir de uma página “irmã” já liberada para o mesmo perfil comercial), ou aplicar bloco `UPDATE` fechando permissões no `adms_groups_page_id` do portal — **igual** LGPD/Logs/Reserva de Salas, para não expor páginas novas por engano.

---

## 5. Repositories

- `SalesPortalUserLinksRepository`: `getByUserId`, `getById`, `insert`, `update`, `listForAdmin` (se houver tela de cadastro), validação de `sap_card_code` (trim, comprimento, charset).
- Toda escrita que altere dados persistidos: chamar `LogAlteracaoService::registrarAlteracao('sales_portal_user_links', $id, …)` em **insert/update/delete**, como em CRM/outros repositórios.
- Serviços SAP: classes em `Models/Services/` que **não** substituem repositories para dados locais; apenas orquestram `SapB1ServiceLayer`.

---

## 6. Controllers

- Espelhar `CrmViewPartner`: validar ID, redirecionar com `$_SESSION['msg']`, carregar dados, `LogResumoService::getResumo` nas telas de **detalhe** de registo local, `PageLayoutService` com `title_head`, `menu` = `controller_url` da listagem ou launchpad, `buttonPermission` = lista de controllers de ações na barra (ex.: `['SalesPortalUpdateUserLink', …]`).
- **CSRF**: onde houver POST, usar o mesmo helper/padrão já usado no CRM (`CSRFHelper` ou equivalente do módulo).
- **Permissões**: não contornar `LoadPageAdmAccessLevel`; páginas devem existir em `adms_pages`.

---

## 7. Views e UI (botões, responsivo, PWA)

- Layout: `container-fluid px-4`, `card shadow-sm`, `row` / `col-12 col-md-*`, breadcrumbs como em `crm/partners/view.php`.
- **Botões**: `btn btn-primary`, `btn-outline-*`, ícones Font Awesome (`fas`, `fab`), `btn-sm` onde o projeto já compacta; grupos `hstack gap-2` / `mb-2 me-2` como no CRM.
- **Launchpad (Fiori-like)**: grelha `row row-cols-1 row-cols-sm-2 row-cols-lg-4 g-3` com `card h-100 text-decoration-none` + ícone + título; não alterar `head.php` global sem necessidade — já existe `viewport` para mobile.
- **PWA**: se o projeto tiver manifest em outro módulo, reutilizar; caso contrário, evitar prometer PWA neste doc até haver `manifest.json` e ícones no padrão do repo.

---

## 8. Log de alterações (obrigatório)

| Ficheiro / peça | Ação |
|-----------------|------|
| `LogAlteracaoService` | Chamadas nos writes do `SalesPortalUserLinksRepository` (e outras tabelas `sales_portal_*`). |
| `ListLogAlteracoes::getLinkRegistro` | Novos `case 'sales_portal_user_links':` → `return URL_ADM . 'sales-portal-view-user-link/' . $objetoId;` (ajustar URL à página real de visualização). |
| Views de visualização | Definir `$this->data['log_resumo'] = LogResumoService::getResumo('sales_portal_user_links', $id, $returnUrl);` |
| Partial | `include ... button_log_alteracoes.php` com `$log_resumo` e `$log_btn_class` (igual `crm/partners/view.php`). |

---

## 9. `PageLayoutService` (menu dinâmico, se aplicável)

- Se o portal tiver entradas no menu gerado por `PageLayoutService` (como outros módulos), adicionar secção com **labels alfabéticos** e mesma estrutura `permission` / `icon` que `CRM`.

---

## 10. `app/adms/Views/partials/menu.php`

- Novo item de menu raiz **ou** submenu CRM:
  - Ordenar labels **alfabeticamente** em cada nível.
  - `permission` alinhada às controllers.
  - `id` único (ex.: `sales-portal-sap`).

---

## 11. Ordem de implementação sugerida

1. Migration `sales_portal_user_links` + repository + log + tela admin “vínculo usuário ↔ CardCode” (valida stack MVC).
2. Seed grupo + páginas + ACL fechada + `LoadPageAdm` + menu.
3. `ListLogAlteracoes` + partial nas views.
4. Estender `SapB1ServiceLayer` (ou serviço novo) para leituras: itens, estoque, PN.
5. Telas launchpad + listagens leitura (histórico / financeiro consultivo).
6. Fluxos de escrita B1 (cotação/pedido) com tratamento de erro SL e mensagens ao utilizador.

---

## 12. Checklist de conformidade

- [ ] Timestamp de migration único e `hasTable` / FKs seguras  
- [ ] `AddAdmsGroupsPages` + `groupsToEnsure` + placeholder de grupo em `AddAdmsPages`  
- [ ] Cada rota em `AddAdmsPages` e em `LoadPageAdm.php`  
- [ ] `directory` = nome da pasta em `Controllers/`  
- [ ] `controller` PascalCase = nome da classe  
- [ ] Permissões e `default_page` alinhados à política do grupo  
- [ ] Menu alfabético + `menu` correto nas páginas  
- [ ] Botões e cards responsivos (`col-12`, grelha)  
- [ ] `LogAlteracaoService` + `LogResumoService` + partial + `getLinkRegistro`  

---

## 13. Referências rápidas no repositório

- CRM controller + log: `app/adms/Controllers/crm/CrmViewPartner.php`, `app/adms/Views/crm/partners/view.php`
- Partial botão log: `app/adms/Views/partials/button_log_alteracoes.php`
- Mapeamento log: `app/adms/Controllers/logs/ListLogAlteracoes.php` (`crm_*` cases)
- Seed páginas + grupo dinâmico: `database/seeds/AddAdmsPages.php` (linhas ~882–944)
- Migration CRM exemplo: `database/migrations/20251028100000_create_crm_partners.php`
- Service Layer: `app/adms/Models/Services/SapB1ServiceLayer.php`
