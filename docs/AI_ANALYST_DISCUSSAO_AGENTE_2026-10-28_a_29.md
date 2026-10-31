# Discussão — Agente de IA / Tiarajuzinho × SAP (28–29/10/2026)

Documento de exportação da conversa no Cursor, para análise em outra IA.  
Escopo: arquitetura do agente inteligente, MCP, SAP Business One, relatórios e camada semântica.  
Não inclui tópicos anteriores (SLA Canal de Denúncias, filtro C00001, commits, etc.), exceto quando citados como contexto do estado atual do produto.

---

## Contexto do produto (já existente no Portal)

- Chat **Tiarajuzinho** com tools de relatório (`report.run` / Analista IA com tools fechadas).
- Relatórios dinâmicos com SQL (local + SAP B1), flag **Disponível no chat**, **nome da tool**, **descrição para a IA**, **exemplos de perguntas**.
- API de consulta ao SAP **não permite** `INSERT`/`DELETE` (somente leitura nessa via).
- Há catálogo técnico (`AiAnalyst/Catalog/data/*.json`) e fluxo conservador do Analista IA: plano → validação PHP → tools → narração (sem SQL livre da LLM).
- Menu relevante: **Relatórios → Relatórios Locais** (SQL personalizado) e listagem **Relatórios SAP (API)**; exemplos atuais em “Vendas por mês” / `[FILTRO] Parceiros` etc.

---

## 1) 28/10/2026 — Fluxo desejado (IA → MCP → SAP)

### Solicitação do usuário

Intenção: agente inteligente que, ao receber mensagem de solicitação, envia ao MCP; o MCP chama a API que conecta ao SAP e devolve o resultado.

Exemplo:

- Mensagem = *«Compare os 5 melhores clientes de cada ano.»*
- IA (ex.: Claude) interpreta, monta uma query e envia ao MCP.
- MCP envia à API SAP; retorno vai à IA, que analisa e responde ao usuário.

Pergunta: **esse fluxo faz sentido?**

### Resposta do assistente (resumo)

- O fluxo **faz sentido** como produto: `usuário → IA → MCP → API SAP → IA → usuário`.
- O ponto frágil é a IA **montar SQL livre** e mandar ao SAP.
- Recomendação alinhada ao Portal: IA interpreta e monta **plano/tools**; PHP valida; MCP/API executa só o **autorizado**; IA narra o resultado.
- Exemplo do mesmo pedido via plano: ranking de clientes, top 5, por ano → tool parametrizada / relatório homologado — sem SQL inventado.

---

## 2) 29/10/2026 — Inteligência real, sem query por cenário

### Solicitação do usuário

- Não quer criar queries para cada cenário nem “ensinar” frase a frase.
- Quer agente realmente inteligente, capaz de **montar queries** pela solicitação.
- Quer análises como sênior por área:
  - Vendas / comercial  
  - Compras  
  - Financeiro (DRE, fluxo de caixa, etc.)
- Observação: a API SAP já bloqueia delete/insert; o que validar são as **consultas**.
- Perguntas: indicar principais cláusulas `WHERE`? Ter uma consulta por cenário que traga muitos dados como exemplo?

### Resposta do assistente (resumo)

- Objetivo válido; SQL 100% livre da IA ainda é frágil (custo, dados errados, vazamento, escopo), mesmo sem escrita.
- Caminho proposto (**kit de construção controlado**):
  1. Catálogo semântico (tabelas, colunas, sinônimos)  
  2. IA monta **plano JSON** (domínio, métrica, dimensões, filtros, top N, períodos) — não SQL solto  
  3. PHP/MCP **compila** plano → `SELECT` com regras (views liberadas, WHERE de data/filial, LIMIT/TOP)  
  4. Validação antes de executar (allowlist, só SELECT, escopo do usuário)  
  5. IA sênior **narra** só com o resultado (persona por domínio)
- Sobre exemplos vs WHERE:

| Abordagem | Utilidade | Limitação |
|-----------|-----------|-----------|
| Consultas-exemplo por domínio (poucas, boas) | Few-shot de join/métrica | Não deve ser “a query do cenário” |
| WHERE obrigatórios | Segurança e performance | Não ensina análise |
| Allowlist views/colunas | Controle do SQL | Manutenção do catálogo |
| Persona sênior | Análise de negócio | Depois dos dados corretos |

- Combinação recomendada: 3–10 queries canônicas por domínio + WHERE mínimo + compilador de plano.
- “Ensino” deixa de ser frase→query e vira **domínio → catálogo → plano → SQL seguro**.

---

## 3) 29/10/2026 — Como outras ferramentas integram ao SAP B1

### Solicitação do usuário

Como outras ferramentas que integram ao SAP Business One trabalham?

### Resposta do assistente (resumo)

Padrão de mercado / orientação SAP — **camadas**, não IA livre no company DB:

1. **Escrita** → Service Layer / DI-API (nunca INSERT direto).  
2. **Leitura pesada** → views aprovadas, Semantic Layer (HANA) ou SQL read-only (às vezes réplica).  
3. **Camada semântica**: pergunta → objetos de negócio (cliente, venda líquida, período), não → SQL livre.  
4. **Service Layer SQLQueries** (B1 10+): SQL dinâmico **governado**, parametrizado, permissões.  
5. **AI agents comerciais**: mapa semântico curado → OData/query read-only → LLM analisa JSON; writes com aprovação via SL.  
6. Pesquisa NL2SQL (ex. HIT-SQL): LLM com dataset, avaliação e feedback — não “ligar Claude no HANA”.

| Abordagem | Uso típico |
|-----------|------------|
| SL + semantic layer | Add-ons / sample SAP |
| Views/SQL read-only + allowlist | Analytics / AI on-prem |
| SQL livre da IA | Quase ninguém em produção |
| Persona sênior na resposta | Depois dos dados certos |

Mercado **não** faz uma query por frase; faz **mapa por domínio** + composição.

---

## 4) 29/10/2026 — Variações de frase (“5 melhores clientes”)

### Solicitação do usuário

Se o humano escrever “5 melhores clientes” ou variações da mesma solicitação, o agente irá interpretar?

### Resposta do assistente (resumo)

- **Sim** — a IA mapeia variações para a **mesma intenção estruturada** (ex.: métrica faturamento, dimensão cliente, top_n=5, por ano).
- O sistema executa o mesmo template/binding.
- O que se “ensina” uma vez é o **conceito** (o que é cliente, o que é “melhor”), não cada formulação.
- Ambiguidades de negócio (“melhor” = líquido, margem ou ticket?) → pergunta ou regra padrão do domínio.

---

## 5) 29/10/2026 — Onde armazenar queries por domínio

### Solicitação do usuário

Como e onde armazenaria as queries para cada domínio?

### Resposta do assistente (resumo)

Não armazenar “uma query por frase”. Armazenar **pacotes por domínio**.

| Camada | Conteúdo | Onde (encaixe no Portal) |
|--------|----------|---------------------------|
| Metadados | Tabelas, colunas, sinônimos | Já: `AiAnalyst/Catalog/data/*.json` |
| Queries canônicas | SQL/view parametrizado (poucas/domínio) | Relatórios dinâmicos `chat_enabled` + binding |
| Mapa do domínio | Métricas, dimensões, filtros, persona | Novo: ex. `AiAnalyst/Domains/sales/domain.json` |
| Execução | ACL, filial | MCP/API + tools |

Exemplo de estrutura sugerida:

```text
AiAnalyst/Domains/
  sales/domain.json (+ templates opcionais)
  purchasing/domain.json
  finance/domain.json
```

`domain.json` aponta bindings (`vendas_mes`, `vendas_top_clientes`); SQL “oficial” no relatório (banco + ACL); JSON no git = significado.

Template exemplo: parâmetros `:date_from`, `:date_to`, `:top_n` — IA preenche; frases diferentes → mesmo molde.

Começar só com **vendas** (2–4 templates).

---

## 6) 29/10/2026 — Em qual menu salvar? Exemplos ≠ linguagem natural?

### Solicitação do usuário (com prints da UI)

- Em qual menu salvar as queries?
- Percebe que precisa passar **exemplos de perguntas** — isso não seria linguagem natural?

UI observada:
- **Relatórios SAP (API)** — cards (ex.: Vendas por mês, [FILTRO] Parceiros…).
- Edição de relatório: SQL personalizado; bloco **Tiarajuzinho (chat)** com disponível no chat, nome da tool (`vendas_por_mes`), descrição, exemplos (`vendas por mês`, `vendas por mes`, `venda mensal`).

### Resposta do assistente (resumo)

- Salvar em relatório com **SQL personalizado** + **Disponível no chat** (fluxo Relatórios Locais / builder), não tratar a listagem SAP API como “pasta de IA”.
- Campos críticos: SQL, tool name, ACL.
- Exemplos hoje = **atalhos de match** do modo legado, **não** NLU completa — por isso parece ensinar frase a frase.
- No desenho inteligente: exemplos são dicas opcionais; a IA interpreta variações → mesmo binding.
- Para “5 melhores clientes por ano”: outra **fonte canônica** (ex. `vendas_top_clientes`), não só exemplos em “Vendas por mês”.

---

## 7) 29/10/2026 — “Não faz sentido criar relatório por solicitação”

### Solicitação do usuário

É justamente isso que não faz sentido: ter que criar relatório para cada tipo de solicitação.

### Resposta do assistente (resumo)

- Concordância explícita: relatório por pergunta **não escala** para o objetivo do usuário.
- Isso é o **modo atual (legado)** do chat, não o agente inteligente desejado.

| | Hoje | Desejado |
|--|------|----------|
| Unidade | 1 pergunta ≈ 1 relatório | 1 domínio ≈ poucas bases + composição |
| Quem monta a consulta | Humano no builder | IA + compilador/validador |
| Exemplos | Quase obrigatórios | Opcionais |
| “5 melhores clientes” | Novo relatório | Mesma base + top_n + por ano |

- Próximo passo de produto: sair de “relatório por solicitação” → **consulta composta sobre mapa do domínio**.
- Enquanto isso não existir, o Portal só atende bem o que já tem relatório — limitação do desenho atual, não do objetivo.

---

## Posição consolidada (síntese para outra IA)

### Objetivo do usuário
Agente que interpreta linguagem natural (variações inclusive), monta consultas ao SAP via MCP/API, analisa como sênior por domínio (vendas, compras, financeiro), **sem** cadastrar um relatório/query por frase.

### O que o assistente recomendou
1. Fluxo: `NL → plano estruturado → validação → execução read-only (MCP/API SAP) → narração sênior`.  
2. Não: SQL cru gerado pela LLM sem allowlist/compilador.  
3. Sim: mapa semântico + poucas bases/templates parametrizados por domínio.  
4. Validação: só SELECT, WHERE mínimos, TOP/LIMIT, escopo ACL/filial.  
5. UI atual de “exemplos de perguntas” é ponte legada; não é a arquitetura final.  
6. Mercado B1 (Service Layer, semantic layer, views aprovadas) segue o mesmo espírito.

### Estado atual do Portal vs gap
- **Tem:** chat, relatórios SQL chat-enabled, API read-oriented, catálogo técnico, Analista IA conservador (tools fechadas).  
- **Não tem ainda (gap):** compilador de plano → SQL dinâmico seguro por domínio; personas sênior multi-domínio de ponta a ponta sem relatório por pergunta.

### Próximo passo sugerido na conversa
Definir contrato do **plano JSON** (começar por vendas) + compilador/validador SELECT reaproveitando catálogo e views já existentes (ex. `VW_CRM_VENDAS_LINHA`), em vez de proliferar relatórios no menu.

---

## Metadados

- Período da conversa exportada: **28/10/2026 – 29/10/2026**  
- Tema: agente de IA / integração SAP / MCP / arquitetura  
- Gerado a pedido do usuário para análise em outra IA  
- Projeto: Portal Administrativo Tiaraju (`administrativo`)
