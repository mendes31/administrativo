# Especificação de Custeio Fabril — Planilha Tiaraju × Sistema Administrativo

**Versão:** 1.0  
**Data:** 2026-06-06  
**Fontes analisadas (local, confidencial):**
- `Tutorial Planilha Custeio Fabril.pptx` (20 slides, People Strategy, Jun/2026)
- `Planilha de Custeio Tiaraju 202605.xlsx` (16 abas funcionais)

**Objetivo deste documento:** descrever com precisão como a planilha calcula custos e rateios, mapear o que o módulo de estoque já faz, listar lacunas e propor um caminho de implementação para precificação com resultados comparáveis à planilha.

---

## 1. Visão geral

### 1.1 O que a planilha faz

A planilha é um **custeio fabril de absorção** com **138+ SKUs em colunas**, consolidando:

| Bloco | Sigla | Conteúdo |
|-------|-------|----------|
| Custo variável | **CVAR** | MPs, embalagens (MAEs), MO direta, energia direta |
| Custo fixo rateado | **CFIX** | Folha indireta, despesas administrativas, energia HVAC/comum, CQ/P&D/DA |
| Custo pleno | **Preço produzido** | CVAR + CFIX (R$/SKU) |
| Rentabilidade | **Margem** | Preço venda líquido vs custo pleno |

A aba principal é **CUSTEIO FABRIL** (estudo). As demais abas alimentam parâmetros e critérios de rateio.

### 1.2 O que o sistema faz hoje

O módulo `InventoryCostService` calcula **custo variável operacional** por item:

- **BOM** (materiais × custo médio × scrap%)
- **Rota** (tempo/lote × Σ custos/min de MO cadastrada + MO SAP + equipamentos)
- **Rateio lote → SKU** via `standard_batch_size` (simulação)
- **Cenários** de ajuste % (materiais, rota, global)
- Persistência de simulações e exportação PDF

**Não calcula:** custo fixo rateado, eficiência de produção, energia por potência instalada, margem vs preço de venda, visão multi-SKU.

### 1.3 Equação-alvo (planilha)

Para cada SKU `p`:

```
CVAR_MP_p  = Σ (qtd_MP_i,p × preço_MP_i) / eficiência_p
CVAR_MAE_p = Σ (qtd_MAE_j,p × preço_MAE_j)          [sem eficiência na planilha]
CVAR_MO_p  = custo MO direta (HH × custo/h ou equivalente na rota)
CVAR_EE_p  = consumo energia direta (HM × kW × tarifa)
CVAR_p     = CVAR_MP_p + CVAR_MAE_p + CVAR_MO_p + CVAR_EE_p + outros CVAR

CFIX_p     = Σ_k ( despesa_fixa_k × critério_rateio_k,p )

CUSTO_pleno_p = CVAR_p + CFIX_p

MARGEM_p   = (preço_venda_líquido_p - CUSTO_pleno_p) / CUSTO_pleno_p    [linha 14 — markup]
```

> **Convenção da planilha (linha 14):** o rótulo “MARGEM” é, na prática, **markup** (margem sobre o custo), não margem % sobre o preço. Fórmula: `(preço - custo) / custo`. Detalhes e exemplos numéricos na seção **5.2**.

---

## 2. Arquitetura da planilha

### 2.1 Abas e papéis

| Aba | Função | Alimenta |
|-----|--------|----------|
| **FORMPROD** | Formulação + rota por produto (MP, MAE, operações, tempos, colaboradores) | CVAR MP/MAE, HH, HM |
| **Pasta 1 - BASE** | Período, códigos SAP, linha produtiva, terceiro/Tiaraju | Metadados SKU |
| **Pasta 2 - CM e Prob** | Classificação cápsula mole / probiótico | Rateio energia HVAC (crit. 8) |
| **Pasta 3 - QTDADES. PRODUZIDAS** | Unidades produzidas no período | Critério rateio 1 |
| **Pasta 4 - TAMANHO LOTES** | Lote teórico fixo vs média praticada | Lote adotado (linha 26) |
| **Pasta 5 - EFICIENCIAS** | Eficiência por SKU ou por linha produtiva | Linha 1094 |
| **Pasta 6 - CUSTOS MP E MAES** | Preço médio em estoque (SAP) | Coluna E, linhas 31–1087 |
| **Pasta 7 - ENERGIA ELÉTRICA** | Potência instalada (kW) por equipamento/atividade | HM × kW, HVAC, comum |
| **Pasta 8 - DRE Balancete** | Gastos com pessoal + despesas administrativas | Pool de CFIX |
| **Pasta 9 - DIST RH** | Folha por área funcional + % distribuição | Rateio folha → áreas |
| **Pasta 10 - COMPLEXANALIT** | Complexidade analítica por SKU | Critérios 4 e 6 |
| **Pasta 11 - PREÇOS DE VENDA** | Preço líquido (sem impostos/comerciais) | Linha 5, margem |
| **CUSTEIO FABRIL** | Motor de cálculo e critérios de rateio | Resultado final |
| **DASHBOARD / GRAFICO RESUMO** | Pareto, margens, faturamento | Análise gerencial |

### 2.2 Sequência de preenchimento (PPT slide 15)

1. Dados básicos (Pastas 1–5)
2. Formulações e preços MP/MAE (FORMPROD + Pasta 6)
3. Roteiros e energia (FORMPROD + Pasta 7)
4. Dados financeiros (Pasta 8 → linhas 2320–2417)
5. Folha de pagamento (Pasta 9 → colunas D, linhas 2336–2356)
6. Complexidade analítica (Pasta 10 → linha 2307+)
7. Resultados automáticos

---

## 3. Custo variável (CVAR) — detalhamento

### 3.1 Matérias-primas (CVAR MP)

**Origem:** FORMPROD linhas 31–346 + preços Pasta 6 coluna E.

**Fórmula planilha (linha 8):**
```
CVAR_MP_SKU = Custo_Variável_Total_MP / Eficiência_Produção
```

Onde o numerador é a soma, para cada MP `i` usada no SKU `p`:
```
custo_i,p = quantidade_i,p × preço_unitário_i
```

- **Quantidade:** por coluna de produto na FORMPROD (unidade alinhada: KG, g, UN).
- **Preço:** preço médio real em estoque (SAP), não preço de última compra isolado.
- **Eficiência (linha 1094):** fator 0–1 (ex.: 0,9125 cápsula dura Tiaraju; 0,915 cápsula mole). Perdas de processo **além** do scrap de formulação são absorvidas aqui.

**Sistema atual (`InventoryCostService::computeMaterialLine`):**
```
effective_qty = qty × (1 + scrap% / 100)
line_cost     = effective_qty × component_cost
```
- Agrupa por categoria (Matéria Prima, Embalagens, etc.).
- **Não aplica eficiência global.**
- Scrap é por componente, não eficiência de linha.

**Gap:** eficiência por SKU/linha; separação explícita CVAR MP vs CVAR MAE nos totais.

---

### 3.2 Embalagens (CVAR MAE)

**Origem:** FORMPROD linhas 349–1087.

**Fórmula:**
```
CVAR_MAE_SKU = Σ (qtd_MAE_j,p × preço_MAE_j)
```

- **Sem divisão por eficiência** na planilha (eficiência impacta só MP).
- Linha consolidada 2299 / 2513.

**Sistema atual:** MAEs entram na BOM com mesma fórmula de MP (scrap + custo médio). Funcionalmente próximo, mas **sem totalizador CVAR MAE** separado no relatório.

---

### 3.3 Mão de obra direta (HH)

**Origem:** FORMPROD linhas 1098–1143 (operações × colaboradores × tempo).

**Conceitos (PPT slides 7, 15):**
- **Tempo por lote** por atividade (horas).
- **Colaboradores envolvidos** por atividade (cargos × quantidade).
- **HH por SKU** = Σ (tempo_etapa_h × qtd_colaboradores × nº_lotes_produzidos_no_período) — usado como **driver do rateio 2** para CFIX, e também compõe custo variável de MO quando aplicável.

**Determinação (linha 1096):** “Homens hora a partir operações por carga/lote”.

**Sistema atual:**
```
HH_linha = (tempo_minutos / 60) × qtd_pessoas_MO_cadastrada
custo_MO = tempo_minutos × Σ (qtd × R$/min)   [MO cadastrada]
custo_MO_SAP = tempo_minutos × Σ recursos tipo LABOR
```

- HH é **exibido** na rota (view/update).
- MO SAP e MO cadastrada usam **R$/min** cadastrado, não necessariamente folha/hora da planilha.
- HH **não alimenta rateio** de CFIX.

**Gap:** custo/h de MO alinhado à folha; HH agregado por período; uso de HH como driver de rateio 2.

---

### 3.4 Horas-máquina (HM)

**Origem:** FORMPROD linhas 1150–1184.

**Conceito:**
```
HM_p = Σ (tempo_máquina_etapa_h × nº_lotes_p)
```

**Usos na planilha:**
- **Critério de rateio 3** (% horas-máquina) — custos fixos de área comum de produção.
- Base para **consumo de energia direta** (HM × potência instalada).

**Sistema atual:** tempo de operação existe; equipamentos têm R$/min. **HM não é calculado como driver** nem ligado a potência kW de forma automática.

**Gap:** HM explícito; ligação com Pasta 7.

---

### 3.5 Energia elétrica variável

**Origem:** Pasta 7 + linhas 1189–1231 na aba CUSTEIO FABRIL.

**Estrutura (PPT slide 14):**

| Tipo | Descrição | Rateio |
|------|-----------|--------|
| **Direto** | Equipamentos na rota do produto | HM × kW × tarifa → CVAR |
| **Área comum** | Comum a todas as linhas | Despesa EE adm. redistribuída → critério 3 (HM) |
| **HVAC** | Climatização | Consumo anual HVAC → critério 8 (CM/Prob) |

**Linhas-chave:**
- 1190: Consumo anual HVAC (período estudado)
- 1230: Critério 7 — consumo EE por linha produtiva
- 1233: Critério 8 — HVAC por CM/Prob

**Classificação energética (linha 21):** cada SKU é `CAPSULA MOLE`, `PROBIÓTICO`, `OUTRO` etc. para rateio HVAC.

**Sistema atual:**
- `inv_production_resources` já possui campo `power_kw` (migration existente).
- Custo de energia na rota = **R$/min manual** no recurso (`energy_cost_per_min`).
- **Não calcula** kWh = HM × kW.
- **Não separa** HVAC / comum / direto.

**Gap:** motor de energia por potência; tarifa kWh; classificação CM/Prob; rateios 7 e 8.

---

### 3.6 Totais CVAR na planilha

| Linha | Campo | Exemplo (40500058 THERMO CAPS) |
|-------|-------|--------------------------------|
| 1554 / 2299 | CVAR MP (com eficiência) | ~3,33 |
| 2299 / 2513 | CVAR MAE | ~2,17 |
| 2300 / 2514 | **CVAR total** | ~5,50 |
| 7 | CVAR calculado (checagem) | ~5,50 |

**Sistema atual:** `material_cost` + `operations_cost` por SKU ≈ CVAR operacional, **sem** eficiência e **sem** separar MP/MAE nos cards principais.

---

## 4. Custo fixo (CFIX) — detalhamento

### 4.1 Pool de despesas fixas

**Origem:** Pasta 8 (DRE/Balancete) → linhas 2320–2417.

**Blocos principais:**

1. **Gastos com pessoal (linhas 2320–2332, col. C)**  
   Remuneração, encargos, benefícios etc. do DRE.

2. **Distribuição por área (linhas 2336–2356, col. D)**  
   Pasta 9: % da folha alocada a Produção, CQ, P&D, Administrativo, etc.

3. **Despesas gerais administrativas (linhas 2358–2417, col. C)**  
   Contas do DRE (análises/pesquisas, energia elétrica adm., etc.).

4. **Redistribuições especiais (PPT slide 11):**
   - Despesas de análises/pesquisas → CQ + Desenv. Analítico + P&D (proporcional à folha dessas áreas).
   - Despesas de energia elétrica adm. → HVAC + Produção área comum + Direto.

**Linha 10 / 2302 — CFIX por SKU:**
```
CFIX_p = Despesas_Administrativas_Rateadas_p
```

Exemplo THERMO CAPS (40500058): CVAR ~5,50 + CFIX ~14,31 ≈ **custo pleno ~19,81** vs preço venda ~24,93 → markup **0,258** (= 25,8% sobre o custo); ver seção 5.2.

### 4.2 Os 8 critérios de rateio

Cada despesa fixa (ou parcela) é ligada a **um ou mais critérios**. O percentual do SKU `p` no critério `k` é:

```
critério_k,p = driver_k,p / Σ driver_k,all_SKUs
```

| Crit. | Nome (planilha) | Driver | Linha ref. |
|-------|-----------------|--------|------------|
| **1** | % unidades produzidas | Qty produzida SKU / total | 23, 2535 |
| **2** | % HH colaboradores | HH do SKU / Σ HH | 1146, 2536 |
| **3** | % Horas-máquina | HM do SKU / Σ HM | 1187, 2537 |
| **4** | Complexidade análises | Peso complexidade / Σ pesos | 2309, 2538 |
| **5** | Nº matérias-primas | Qtd MPs distintas / Σ | 1089, 2539 |
| **6** | Complexidade × nº análises | Fator × qtd análises | 2312, 2540 |
| **7** | Consumo EE por linha | kWh linha produtiva | 1230, 2541 |
| **8** | HVAC CM/Prob | Regra especial CM vs Prob vs Outro | 1233 |

**Fatores de complexidade (Pasta 10 / slide 13):**
- Baixa: 2  
- Média: 5  
- Alta: 8  
- Subníveis (baixa-baixa: 1, baixa-alta: 3, etc.)

### 4.3 CFIX no sistema

**Status:** inexistente. Nenhuma tabela de período fiscal, DRE, pool de despesas ou matriz de rateio.

---

## 5. Precificação e indicadores

### 5.1 Preço de venda (linha 5)

- Origem: Pasta 11 — último preço **líquido** (sem impostos e sem despesas comerciais).
- Usado na **margem** (linha 14).

### 5.2 Margem (linha 14) — convenção e exemplos

A planilha chama o indicador de **“MARGEM”**, mas a fórmula usada na linha 14 é **markup** (ganho proporcional ao **custo**), validada contra os SKUs da aba CUSTEIO FABRIL:

```
MARGEM_planilha_p = (preço_venda_líquido_p - CUSTO_pleno_p) / CUSTO_pleno_p
```

Equivalente em Excel (coluna do produto): `=(F5-F12)/F12` (ajustar colunas conforme o SKU).

#### Três indicadores distintos (não confundir)

| Nome | Fórmula | Pergunta que responde |
|------|---------|------------------------|
| **Markup** (planilha) | `(preço - custo) / custo` | Quanto lucro há **em cima do custo**? |
| **Margem %** (comercial) | `(preço - custo) / preço` | Quanto do **preço de venda** é lucro? |
| **Razão preço/custo** | `preço / custo` | O preço é **quantas vezes** o custo? |

Relação entre markup e razão: `razão = 1 + markup` (ex.: markup 2,02 → preço ≈ 3,02× o custo).

#### Exemplo A — THERMO CAPS (40500058)

| Campo | Valor |
|-------|-------|
| Preço venda líquido (linha 5) | R$ 24,93 |
| Custo pleno (linha 12) | R$ 19,81 |
| Linha 14 (planilha) | **0,258** |

| Indicador | Cálculo | Resultado |
|-----------|---------|-----------|
| **Markup (planilha)** | (24,93 − 19,81) ÷ 19,81 | **0,258** (25,8%) ✓ |
| Margem % | (24,93 − 19,81) ÷ 24,93 | 20,5% |
| Razão preço/custo | 24,93 ÷ 19,81 | 1,26× |

#### Exemplo B — PROBIÓTICO PRODUO (40500052)

| Campo | Valor |
|-------|-------|
| Preço | R$ 57,75 |
| Custo pleno | R$ 19,11 |
| Linha 14 (planilha) | **2,02** |

| Indicador | Cálculo | Resultado |
|-----------|---------|-----------|
| **Markup (planilha)** | (57,75 − 19,11) ÷ 19,11 | **2,02** (202% sobre o custo) ✓ |
| Margem % | (57,75 − 19,11) ÷ 57,75 | 66,9% |
| Razão preço/custo | 57,75 ÷ 19,11 | 3,02× |

#### Exemplo C — Prejuízo (valores negativos na linha 14)

Se custo = R$ 26,06 e preço = R$ 14,48:

- Markup = (14,48 − 26,06) ÷ 26,06 = **−44%** (vende abaixo do custo)
- Margem % = (14,48 − 26,06) ÷ 14,48 = **−80%**

#### Implicação para o sistema

- Para **bater com a planilha**, o sistema deve calcular e exibir **markup** na linha equivalente à 14.
- Opcional (recomendado na UI): exibir também **margem %** e **razão preço/custo** como colunas auxiliares, com rótulos explícitos para evitar ambiguidade (“Markup (planilha)”, “Margem s/ preço”, etc.).

### 5.3 Dashboard

- Faturamento = preço × qty produzida  
- Pareto produto e faturamento  
- CMP, CMAE, CVAR, CFIX, margem por SKU  
- Filtros por linha produtiva  

**Sistema:** simulação por item; sem dashboard multi-SKU.

---

## 6. Dados mestres por SKU (planilha)

| Campo | Linha | Sistema |
|-------|-------|---------|
| Código SAP | 17 | `inv_items.erp_code` ✓ |
| Descrição | 18 | `description` ✓ |
| Terceiro / Tiaraju | 19 | ✗ |
| Linha produtiva | 20 | ✗ (parcial via categoria) |
| CM / Prob / Outro (energia) | 21 | ✗ |
| Qty produzida período | 22–23 | ✗ |
| Lote teórico | 24 | `standard_batch_size` (simulação) parcial |
| Qty média por rodada | 25 | ✗ |
| Lote adotado | 26 | parcial (simulação) |
| Lotes produzidos (2025) | 27 | ✗ |
| Eficiência produção | 1094 | ✗ |
| Complexidade análise | 2307 | ✗ |
| Preço venda líquido | 5 | ✗ |

---

## 7. Mapeamento sistema atual → planilha

### 7.1 Tabelas e serviços existentes

| Entidade | Tabela / serviço | Equivalente planilha |
|----------|------------------|-------------------|
| Item | `inv_items` | Pasta 1 / colunas produto |
| BOM | `inv_item_bom` | FORMPROD materiais |
| Rota | `inv_item_operations` + linhas MO/recursos | FORMPROD rota |
| Papéis MO | `inv_labor_roles` + `inv_item_operation_labor_lines` | Colaboradores por operação |
| Recursos | `inv_production_resources` (+ `power_kw`) | Pasta 7 equipamentos |
| Custo cálculo | `InventoryCostService` | CVAR parcial |
| Simulação | `inv_cost_simulations`, `simulate.php` | Cenários (não margem) |
| Sync SAP | estrutura SAP | FORMPROD / preços Pasta 6 |

### 7.2 Fórmulas atuais (`InventoryCostService`)

**Material:**
```php
effective_qty = qty × (1 + scrap% / 100)
line_cost_batch = effective_qty × unit_cost
line_cost_sku = line_cost_batch / batch_size
```

**Operação:**
```php
time_minutes = time_value × (60 se MIN, 1 se H)
cost_per_min = MO_cad/min + MO_SAP/min + equip/min
line_cost_batch = time_minutes × cost_per_min
line_cost_sku = line_cost_batch / batch_size
```

**Simulação:**
```php
sim_material = material_cost × (1 + mat_adj%)
sim_operations = operations_cost × (1 + op_adj%)
sim_total = (sim_material + sim_operations) × (1 + global_adj%)
```

### 7.3 O que já está alinhado

- Estrutura BOM + rota por item  
- Tempo por lote com unidade MIN/H  
- MO SAP vs MO cadastrada vs equipamentos  
- Subtotal/lote e rateio SKU via tamanho do lote  
- Scrap por componente  
- Agrupamento de materiais por categoria  
- Simulação, salvar, PDF  
- Campo `power_kw` em recursos (ainda não usado no cálculo)

---

## 8. Lacunas consolidadas (checklist)

### 8.1 Variável

- [ ] Eficiência de produção por SKU ou linha (÷ eficiência no CVAR MP)
- [ ] Totais explícitos CVAR MP e CVAR MAE
- [ ] HM (horas-máquina) calculado e armazenado
- [ ] Energia por potência: kWh = HM × kW × tarifa
- [ ] Separação energia direta / comum / HVAC
- [ ] Classificação SKU: linha produtiva, CM/Prob/Outro
- [ ] Tabela `inv_cost_production_batches` + sync SAP/CSV **global** (todos os lotes, sem vínculo a período)
- [ ] Agregação qty/lotes por produto **filtrada pelo período** na simulação/custeio (critério rateio 1)
- [ ] Depósitos **TJQP** e **APQP** na sync; `warehouse_code` gravado em cada lote; filtro por depósito na simulação (um, vários ou todos)
- [ ] Lote adotado: teórico fixo vs média praticada (regra Pasta 4)

### 8.2 Fixo

- [ ] Período de custeio (ex.: Jan–Out 2025, Jan–Fev 2026)
- [ ] Importação/cadastro DRE (Pasta 8)
- [ ] Pool gastos com pessoal (linhas 2320–2332)
- [ ] Distribuição folha por área (Pasta 9)
- [ ] Pool despesas administrativas (linhas 2358–2417)
- [ ] Redistribuições (análises → CQ/P&D/DA; EE adm. → HVAC/comum/direto)
- [ ] Motor dos 8 critérios de rateio
- [ ] Cadastro “qual critério” para cada conta despesa
- [ ] Regras especiais CFIX=0 e 50% CFIX (linhas 2520–2522)

### 8.3 Precificação e gestão

- [ ] Preço de venda líquido por SKU (Pasta 11)
- [ ] Custo pleno = CVAR + CFIX
- [ ] Markup (convenção planilha, linha 14) + margem % auxiliar na UI
- [ ] Dashboard multi-SKU (Pareto, ranking margem)
- [ ] Comparação período a período

### 8.4 Integração e governança

- [ ] Importação em massa FORMPROD / preços SAP
- [ ] Versionamento de cenário (“fechamento trimestral”)
- [ ] Auditoria de alterações em parâmetros globais
- [ ] Validação automática vs planilha (SKU piloto)

---

## 9. Modelo de dados proposto (visão alvo)

> Esboço para discussão — não implementado.

### 9.1 Período e parâmetros globais

```
inv_cost_periods
  id, name, date_from, date_to, status (draft|closed), kwh_tariff, notes
  created_at, updated_at

inv_cost_period_items
  id, inv_cost_period_id, inv_item_id
  -- parâmetros manuais / importados (não derivados dos lotes):
  batch_size_theoretical, batch_size_adopted
  efficiency_pct, production_line, energy_class (CM|PROB|OTHER)
  complexity_level, analysis_count, sale_price_net
  -- totais calculados a partir de inv_cost_production_batches (ver 9.5):
  qty_produced_cache, batches_produced_cache, share_criterion_1_cache
```

### 9.5 Produção — lotes sincronizados (SAP) e agregação por produto

**Decisão de desenho (duas camadas independentes):**

| Camada | Escopo | Quando |
|--------|--------|--------|
| **Cadastro de lotes** | Todos os lotes produzidos, histórico completo | Sync SAP/CSV/manual — **sem filtro de período** |
| **Período de custeio** | Janela analítica (`date_from` / `date_to`) | Cálculos, simulações, rateios, DRE |

A sync grava **cada lote/entrada de produção** em tabela global de detalhe, **incluindo o depósito de entrada** (`warehouse_code`). O período **não** é FK dos lotes: na simulação ou no fechamento de custeio, o sistema **filtra por `production_date`** dentro do intervalo do período e, opcionalmente, **por depósito(s)**. Não duplicar qty em cadastro manual se já vier do SAP.

**Depósitos no escopo de produção (Tiaraju):**

| Código SAP | Uso |
|------------|-----|
| `TJQP` | Produção / quarentena principal |
| `APQP` | Produção alternativa (mesma regra de entrada `BaseType = 59`) |

A sync importa lotes de **ambos** os depósitos. O filtro por depósito é **só na agregação** (simulação, conferência, rateio) — nunca exclui dados do cadastro global.

```
inv_cost_production_warehouses       -- cadastro de depósitos elegíveis (config)
  id, code, name, active, include_in_sync (default true)
  -- seed: TJQP, APQP

inv_cost_production_batches          -- cadastro global (sem inv_cost_period_id)
  id
  inv_item_id                 -- FK inv_items (match por erp_code = ItemCode), nullable se não cadastrado
  erp_code                    -- ItemCode SAP (redundante para auditoria)
  item_description            -- ItemName no momento da importação
  series_remark               -- NNM1.Remark (série / classificação)
  production_date             -- OBTN.InDate (data admissão lote — validar vs DocDate) — índice
  doc_date                    -- IBT1.DocDate
  goods_receipt_doc_num       -- IBT1.BaseNum ("Entrada de Mercadoria")
  production_order_num        -- OWOR.DocNum via BaseEntry, se disponível na query
  base_type                   -- IBT1.BaseType (59 = entrada de produção)
  base_entry                  -- IBT1.BaseEntry (id interno OP/documento base)
  batch_number                -- OBTN.DistNumber
  mnf_date, exp_date          -- opcional
  warehouse_code              -- IBT1.WhsCode — depósito onde entrou (TJQP, APQP, …) — índice
  warehouse_name              -- OWHS.WhsName (snapshot na importação, opcional)
  quantity                    -- IBT1.Quantity
  source (SAP|CSV|MANUAL)
  sap_sync_run_id             -- última execução que inseriu/atualizou a linha
  imported_at, updated_at, imported_by

inv_cost_production_sync_runs        -- log da sync (também sem vínculo a período)
  id, source, sync_mode (full|incremental)
  warehouse_codes_synced      -- JSON ex.: ["TJQP","APQP"]
  filter_from_date            -- opcional: só para incremental (InDate >=)
  rows_inserted, rows_updated, rows_skipped, status, error_log
  started_at, finished_at
```

**Agregação por produto no período (calculada na leitura, não na sync):**

```sql
-- Filtros aplicados só no cálculo / simulação
WHERE production_date BETWEEN :period_date_from AND :period_date_to
  AND (
    :warehouse_codes IS NULL          -- "Todos" os depósitos
    OR warehouse_code IN (:warehouse_codes)  -- ex.: ['TJQP'] ou ['TJQP','APQP']
  )

-- Por item no período (e depósitos selecionados)
qty_produced      = SUM(quantity) GROUP BY inv_item_id, erp_code
batches_produced  = COUNT(DISTINCT batch_number)  -- ou COUNT(DISTINCT base_entry)
entries_count     = COUNT(DISTINCT goods_receipt_doc_num)

-- Opcional: breakdown por depósito na UI de conferência
qty_by_warehouse  = SUM(quantity) GROUP BY erp_code, warehouse_code

-- Critério de rateio 1 (base = total do filtro ativo: período + depósitos)
share_criterion_1 = qty_produced_item / SUM(qty_produced) OVER (filtro_ativo)
```

**Query SAP de referência (detalhe por lote — sync global)** — base Tiaraju:

```sql
SELECT
    T1."BaseNum"   AS goods_receipt_doc_num,
    T0."InDate"    AS production_date,
    T1."DocDate",
    T1."CreateDate",
    T3."Remark"    AS series_remark,
    T0."ItemCode"  AS erp_code,
    T2."ItemName"  AS item_description,
    T1."Quantity"  AS quantity,
    T0."DistNumber" AS batch_number,
    T0."MnfDate", T0."ExpDate",
    T1."BaseType", T1."BaseEntry",
    T1."WhsCode"   AS warehouse_code,
    W."WhsName"    AS warehouse_name
FROM "OBTN" T0
INNER JOIN "IBT1" T1 ON T0."ItemCode" = T1."ItemCode" AND T0."DistNumber" = T1."BatchNum"
INNER JOIN "OITM" T2 ON T0."ItemCode" = T2."ItemCode"
INNER JOIN "NNM1" T3 ON T2."Series" = T3."Series"
LEFT JOIN "OWHS" W ON T1."WhsCode" = W."WhsCode"
WHERE T1."ItemCode" LIKE '4%'
  AND T1."WhsCode" IN ('TJQP', 'APQP')
  AND T1."BaseType" = 59
  -- incremental opcional: AND T0."InDate" >= :last_sync_date
```

**Fluxo operacional:**

1. **Sincronizar lotes** (ação independente) → `InventorySapProductionSyncService` faz upsert em `inv_cost_production_batches` com **todos** os lotes dos depósitos ativos (`TJQP`, `APQP`), gravando **onde entrou** em cada linha.
2. Tela **Produção**: listagem global com filtros opcionais por data e depósito; coluna `warehouse_code`; drill-down por lote.
3. Cadastrar **período de custeio** (`Jan–Abr/2026`) — só define a janela analítica.
4. Tela **Produção do período** / conferência: filtro `production_date` + depósito(s) → subtotal por `erp_code` (e opcionalmente por depósito) + total geral.
5. Na **simulação** (`simulate.php`): seletor de **período** + **depósito(s)**:
   - opção **Todos** (padrão sugerido para rateio consolidado);
   - ou multiselect **TJQP** / **APQP** (ex.: conferir só quarentena principal).
6. Sistema agrega qty por produto no filtro ativo e exibe % rateio 1.
7. Ao salvar simulação: persistir snapshot dos totais **e** `warehouse_codes` usados no filtro.

**UI simulação — filtro de depósitos:**

```
[ Período: Jan–Abr/2026 ▼ ]

Produção no período (base rateio 1):
( ) Todos os depósitos
( ) Selecionar: [x] TJQP  [x] APQP

| SKU        | Qty (filtro) | % rateio 1 |
| 40500058   | 12.400       | 18,2%      |
```

**Regras de sync:**

| Regra | Comportamento sugerido |
|-------|------------------------|
| Escopo da sync | Todos os lotes dos depósitos `include_in_sync`; período **não** entra na importação |
| Depósitos | `TJQP` + `APQP` por padrão; novos depósitos via `inv_cost_production_warehouses` |
| Reimportar / incremental | Upsert por chave natural; modo incremental com `InDate >= última sync` |
| Item SAP sem cadastro local | Gravar linha com `inv_item_id` NULL + alerta na UI |
| Chave de unicidade | `(base_entry, batch_number, erp_code, goods_receipt_doc_num, warehouse_code)` |
| Mesmo lote, dois depósitos | Duas linhas distintas (entrada em depósitos diferentes) |
| OP na query | Enriquecer com `JOIN OWOR` em `BaseEntry` quando `BaseType` = produção |
| Múltiplos períodos | Mesmo lote entra em qualquer período cuja janela contenha `production_date` |

**Serviços sugeridos:**

- `InventorySapProductionSyncService::syncFull(?array $warehouseCodes = null)` — default: depósitos ativos no cadastro
- `InvCostProductionAggregationService::aggregateByPeriod(int $periodId, ?array $warehouseCodes = null): array`
  - `null` ou `[]` = **todos** os depósitos no período
  - `['TJQP']` ou `['TJQP','APQP']` = filtro explícito

Retorno por item: `erp_code`, `description`, `qty_produced`, `batches_count`, `share_criterion_1`, `inv_item_id`, `qty_by_warehouse` (opcional).

### 9.2 Despesas fixas

```
inv_cost_expense_pools
  id, inv_cost_period_id, source (DRE|PAYROLL|MANUAL)
  account_code, description, amount, area, redistribution_group

inv_cost_allocation_rules
  id, expense_pool_id, criterion (1..8), weight_pct

inv_cost_criterion_drivers  -- materializado por período/SKU
  id, period_id, item_id, criterion, driver_value, share_pct
```

### 9.3 Resultado

```
inv_cost_results
  id, period_id, item_id
  cvar_mp, cvar_mae, cvar_mo, cvar_ee, cvar_total
  cfix_total, full_cost, sale_price, margin_pct
  breakdown_json
```

### 9.4 Energia

```
inv_production_resources.power_kw  -- já existe
inv_cost_energy_consumption       -- HVAC anual, comum, por linha
```

---

## 10. Arquitetura de cálculo proposta

```
┌─────────────────────────────────────────────────────────────┐
│                    inv_cost_period (trimestre)               │
└─────────────────────────────────────────────────────────────┘
         │                    │                    │
         ▼                    ▼                    ▼
   ┌──────────┐        ┌──────────┐        ┌──────────────┐
   │ BOM+Rota │        │ DRE+RH   │        │ Prod+Efic.   │
   │ (item)   │        │ (global) │        │ (por SKU)    │
   └────┬─────┘        └────┬─────┘        └──────┬───────┘
        │                   │                      │
        ▼                   ▼                      ▼
   CVAR_MP/MAE         Pool CFIX              Drivers 1–8
   MO, EE direta       por conta              (HH,HM,qty...)
        │                   │                      │
        └─────────┬─────────┴──────────┬───────────┘
                  ▼                    ▼
            CVAR_total            CFIX_rateado
                  │                    │
                  └────────┬───────────┘
                           ▼
                    Custo pleno / Margem
```

**Serviço sugerido:** `InventoryFullCostService` (ou evolução de `InventoryCostService`) com métodos:

1. `calculateVariableCost($itemId, $periodId)` → CVAR*
2. `calculateFixedCostAllocation($itemId, $periodId)` → CFIX
3. `calculateFullCost($itemId, $periodId)` → pleno + margem
4. `calculatePortfolio($periodId)` → dashboard

---

## 11. Fases de implementação sugeridas

### Fase 0 — Validação (1–2 semanas)

- Escolher **3 SKUs piloto** (ex.: 40500058 THERMO CAPS, um CM, um Probiótico).
- Replicar entradas da planilha no sistema manualmente.
- Documentar divergências % por linha (CVAR MP, MAE, MO, CFIX, markup).

### Fase 1 — CVAR alinhado (4–6 semanas)

| # | Entrega | Impacto |
|---|---------|---------|
| 1.1 | Eficiência por SKU/linha na simulação | CVAR MP |
| 1.2 | Relatório CVAR MP / CVAR MAE separados | Visibilidade |
| 1.3 | HM e HH consolidados por item/período | Base rateios |
| 1.4 | Energia: kWh = HM × power_kw × tarifa | CVAR EE |
| 1.5 | Metadados: linha produtiva, CM/Prob | Rateio 8 |
| 1.6 | Tabela global de lotes + sync SAP (sem período) + agregação filtrada por período | Rateio 1 |
| 1.7 | Seletor de período + filtro depósito (TJQP/APQP/todos) na simulação | Rateio + CFIX futuro |

**Resultado:** CVAR mais próximo da planilha; ainda sem CFIX.

### Fase 2 — CFIX e rateios (8–12 semanas)

| # | Entrega | Impacto |
|---|---------|---------|
| 2.1 | Cadastro período + import DRE (CSV) | Pool despesas |
| 2.2 | Pasta 9: distribuição RH | Folha por área |
| 2.3 | Motor critérios 1–8 | CFIX por SKU |
| 2.4 | Redistribuições EE e análises | Fidelidade planilha |
| 2.5 | CFIX no simulate + PDF | Custo pleno |

**Resultado:** Preço produzido comparável à planilha.

### Fase 3 — Precificação e dashboard (4–6 semanas)

| # | Entrega |
|---|---------|
| 3.1 | Preço venda líquido por SKU |
| 3.2 | Markup (planilha) + margem % opcional |
| 3.3 | Dashboard portfolio (Pareto, filtros) |
| 3.4 | Export Excel reconciliação |

---

## 12. Critérios de aceite (reconciliação)

Para cada SKU piloto, no mesmo período e parâmetros:

| Métrica | Tolerância sugerida |
|---------|---------------------|
| CVAR MP | ± 1% |
| CVAR MAE | ± 1% |
| CVAR total | ± 2% |
| CFIX | ± 3% (após Fase 2) |
| Custo pleno | ± 3% |
| Markup (linha 14) | ± 0,5 p.p. (em valor decimal, ex. 0,258) |

Processo:
1. Exportar planilha → CSV por SKU piloto.
2. Rodar `calculateFullCost` no sistema.
3. Relatório diff campo a campo.
4. Ajustar fórmula ou mapear exceção documentada.

---

## 13. Riscos e decisões em aberto

| # | Questão | Opções |
|---|---------|--------|
| D1 | ~~Margem: qual fórmula?~~ **Resolvido:** planilha usa **markup** `(preço-custo)/custo`. Sistema: mesmo para reconciliação; margem % como coluna extra na UI |
| D2 | Eficiência: por SKU ou só por linha? | Planilha permite ambos (Pasta 5) |
| D3 | Período de custeio: trimestral fixo? | Alinhar ao fechamento contábil |
| D4 | DRE: import manual CSV ou integração ERP? | Fase 2 começa com CSV |
| D5 | CFIX para SKU sem produção no período | Planilha inclui na base 1? |
| D6 | Manter simulação % separada do custeio oficial? | Simulação = what-if; período = oficial |
| D7 | `power_kw` já no banco — tarifa kWh onde cadastrar? | Período global vs por mês |
| D8 | ~~Sync de lotes vinculada ao período?~~ **Resolvido:** cadastro **global**; período só filtra em cálculo/simulação |
| D9 | ~~Quais depósitos na produção?~~ **Resolvido:** sync **TJQP + APQP**; `warehouse_code` em cada lote; filtro na simulação (um, vários ou todos) |

---

## 14. Referência rápida — linhas CUSTEIO FABRIL

| Linha | Conteúdo |
|-------|----------|
| 5 | Preço venda líquido |
| 7 | CVAR calculado (checagem) |
| 8 | CVAR MP ÷ eficiência |
| 9 | CVAR MAE |
| 10 | CFIX |
| 12 | Preço produto produzido (CVAR+CFIX) |
| 14 | Markup — `(preço - custo) / custo` (rótulo “MARGEM” na planilha) |
| 17–27 | Dados básicos SKU |
| 31–1087 | Formulações MP/MAE |
| 1094 | Eficiência |
| 1096–1143 | HH / colaboradores |
| 1150–1184 | HM |
| 1146 | Critério rateio 2 (HH) |
| 1187 | Critério rateio 3 (HM) |
| 1189–1231 | Energia |
| 1230 | Critério rateio 7 (EE linha) |
| 1233 | Critério rateio 8 (HVAC CM/Prob) |
| 1554 | Total CVAR MP |
| 2299–2300 | CVAR MAE / CVAR |
| 2307–2312 | Complexidade análises |
| 2309 | Critério rateio 4 |
| 2320–2417 | Pools CFIX (pessoal + adm.) |
| 2511–2518 | Resumo preço, CVAR, CFIX, margem |

---

## 15. Próximo passo recomendado

Antes de codificar CFIX (maior esforço), recomenda-se **Fase 0 + Fase 1.0 (lotes globais + período analítico)**:

1. **`inv_cost_production_batches`** + sync SAP global (TJQP e APQP, upsert, `warehouse_code` em cada linha).  
2. **`inv_cost_periods`** — janela só para cálculo; conferir totais filtrados vs planilha Pasta 3.  
3. Simulação: período + filtro depósito (todos / TJQP / APQP) → qty agregada e % rateio 1.  
4. Validar 3 SKUs piloto (CVAR variável + qty produzida).  
5. Depois: eficiência, CVAR MP/MAE separados, DRE e CFIX.

---

*Documento gerado a partir de análise local dos arquivos de custeio. Não substitui validação contábil das fórmulas célula a célula na planilha viva.*
