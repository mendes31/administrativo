# Portal de Vendas (SAP B1) — Esboço arquitetural aderente ao projeto

Documento de referência para implementação futura. Padrões verificados no código em `app/adms/`, `database/migrations`, `database/seeds`, `routes/`, `app/adms/Views/partials/menu.php`, `ListLogAlteracoes`, `LogResumoService`, `button_log_alteracoes.php`.

### Decisão: máxima reutilização do ecossistema actual

- **Stack**: PHP (MVC existente), **sem** aplicação paralela em ASP.NET para este portal.
- **Reutilizar**: `LoadPageAdmAccessLevel`, `adms_pages` / seeds, `PageLayoutService`, partials (`head`, menu, alerts, botão log), `SapB1ServiceLayer` + extensão para entidades de vendas, `LogAlteracaoService` / `LogResumoService`, padrão CRM (controllers, views, repositories).
- **Benefício**: um só deploy, mesmas permissões e auditoria, menos duplicação de identidade e de integração SL.

**Fase 1 (implementada):** launchpad `SalesPortalLaunchpad`, grupo e página em seeds, menu, `LoadPageAdm`, `PageLayoutService`. **Política de acesso:** quem tem permissão no módulo **Portal de Vendas (SAP)** pode operar para **qualquer** parceiro no B1; o `CardCode` vem da escolha no ecrã ou do payload enviado à Service Layer, **sem** tabela local de vínculo utilizador ↔ cliente.

### O que é o `CardCode` no SAP B1

- No **SAP Business One**, cada **cliente** (parceiro de negócio do tipo cliente) tem um código único **`CardCode`**. Cotações, pedidos e documentos de vendas referem esse código.
- O **administrativo** mantém utilizadores próprios (`adms_users`) com permissões no PHP. A permissão ao módulo do portal **não** restringe a um único PN: em cada fluxo, o utilizador (ou o formulário) indica **para qual** cliente se actua; o backend valida permissão de módulo e chama a SL com o `CardCode` escolhido (ou listado).

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
| Repositories | `app/adms/Models/Repository/` | Serviços/repositórios por domínio (ex.: rascunhos locais se existirem), **não** mapeamento fixo user ↔ PN |
| Views | `app/adms/Views/sales_portal/` ou `salesPortal/` | Preferir **snake_case de pasta** só se o projeto já misturar — hoje CRM usa `Views/crm/`; usar **`Views/salesPortal/`** alinhado a `companyEvents` (camelCase) |

**Roteador legado** (`routes/LoadPageAdm.php`): acrescentar cada controller em `$listPgPrivate` e `salesPortal` em `$listDirectory` (igual CRM).

**Service Layer**: reutilizar e estender `App\adms\Models\Services\SapB1ServiceLayer` (login, cookies, `makeRequest` hoje privado — extrair métodos públicos por domínio ou criar `SapB1SalesDocumentsService` que encapsule POST/PATCH em `Orders`, `Quotations`, etc., sem duplicar login).

---

## 3. Migrations (somente DDL, nomes únicos)

Antes de criar ficheiro, listar `database/migrations/` e escolher timestamp **posterior** ao último existente (ex.: `YYYYMMDDHHMMSS`).

### 3.1 Tabela `sales_portal_user_links` (removida)

- Existiu uma migration inicial que criava `sales_portal_user_links`; foi **descontinuada** em favor da regra «permissão de módulo = todos os parceiros».
- A migration `RemoveSalesPortalUserPartnerLinks` remove a tabela (se existir), apaga as páginas CRUD associadas em `adms_pages` e as linhas em `adms_access_levels_pages`.
- **Não** voltar a introduzir vínculo 1:1 utilizador ↔ `CardCode` sem decisão de produto explícita.

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

- Dados mestres de clientes e documentos ficam no **B1**; repositórios PHP servem apenas para **cache opcional**, **rascunhos** ou **metadados** do administrativo, com o mesmo padrão de auditoria (`LogAlteracaoService`) quando houver tabelas `sales_portal_*` locais.
- **Não** usar repositório para impor «CardCode do utilizador logado»; a UI ou o corpo do pedido escolhe o PN dentro do âmbito permitido pelo módulo.
- Serviços SAP: classes em `Models/Services/` orquestram `SapB1ServiceLayer` sem duplicar login.

---

## 6. Controllers

- Espelhar `CrmViewPartner` onde fizer sentido: validar inputs, redirecionar com `$_SESSION['msg']`, `PageLayoutService` com `title_head`, `menu` = `controller_url` da listagem ou launchpad, `buttonPermission` = controllers de ações **da mesma página** (listar/editar/apagar documento ou rascunho).
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
| `LogAlteracaoService` | Chamadas nos writes de qualquer tabela `sales_portal_*` local (rascunhos, etc.). |
| `ListLogAlteracoes::getLinkRegistro` | Um `case` por tabela com URL de visualização no administrativo. |
| Views de visualização | `LogResumoService::getResumo('<tabela>', $id, $returnUrl)` quando aplicável. |
| Partial | `button_log_alteracoes.php` como no CRM. |

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

1. Seed grupo + páginas + ACL + `LoadPageAdm` + menu + launchpad (valida stack MVC).
2. Estender `SapB1ServiceLayer` (ou serviço novo) para leituras: PN, itens, estoque, documentos.
3. Telas com **seleção de parceiro** (lista/search `CardCode`) e fluxos de escrita B1 (`Orders`, `Quotations`, …) com tratamento de erro SL.
4. `ListLogAlteracoes` + partials nas views que persistirem dados locais.

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
- Remoção do vínculo user–PN: `database/migrations/20260513150000_remove_sales_portal_user_partner_links.php`
