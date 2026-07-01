# Queries SAP — Sincronização de Itens e Estruturas

Documento de referência das consultas SQL enviadas à **API SAP** (`SapReportApiService`) durante a sincronização em **Listar Itens de Estoque**.

> **Filtro de grupos:** quase todas as consultas em `OITM` incluem `AND T0."ItmsGrpCod" IN (...)` com os códigos resolvidos a partir dos prefixos permitidos (`100`, `1000`, `200`, `300`, `400`, `600`, `700`) na tabela `OITB`.

---

## 1. Catálogo de itens (sync principal)

### 1.1 Grupos de itens (uma vez por execução)

```sql
SELECT "ItmsGrpCod", "ItmsGrpNam" FROM OITB
```

Usado para mapear `ItmsGrpCod` → nome da categoria local.

### 1.2 Resolver grupos permitidos (uma vez por execução)

Mesma query acima; filtra em PHP os grupos cujo nome começa com os prefixos permitidos.

### 1.3 Estimar total do catálogo (barra de progresso)

```sql
SELECT COUNT(*) AS "cnt"
FROM OITM T0
WHERE 1=1
  AND T0."ItmsGrpCod" IN (/* códigos OITB permitidos */)
```

### 1.4 Varredura em lotes (principal)

Executada repetidamente com paginação por `ItemCode` (keyset). Lote padrão: **50 itens** (reduz automaticamente se a API falhar).

**Todos os campos do item vêm na mesma consulta.** Campos de usuário (UDF) vazios usam `COALESCE` para não derrubar a API:

```sql
SELECT T0."ItemCode", T0."ItemName", T0."InvntryUom", T0."validFor", T0."ItmsGrpCod",
       T0."AvgPrice",
       COALESCE(TO_NVARCHAR(T0."UpdateDate"), '') AS "UpdateDate",
       COALESCE(T0."U_FormaFarma", '') AS "U_FormaFarma",
       COALESCE(T0."U_LinhaProduto", '') AS "U_LinhaProduto",
       COALESCE(T0."U_beas_ver", '') AS "U_beas_ver",
       COALESCE(T0."MinOrdrQty", 0) AS "MinOrdrQty",
       COALESCE(T0."MinLevel", 0) AS "MinLevel",
       COALESCE(T0."MaxLevel", 0) AS "MaxLevel",
       COALESCE(T0."ManBtchNum", '') AS "ManBtchNum",
       COALESCE(T0."ManSerNum", '') AS "ManSerNum"
FROM OITM T0
WHERE 1=1
  AND T0."ItmsGrpCod" IN (/* códigos permitidos */)
  AND T0."ItemCode" > '{ultimo_codigo_do_lote_anterior}'   -- omitido no 1º lote
ORDER BY T0."ItemCode"
LIMIT 50
```

**Itens sem UDF:** `U_FormaFarma`, `U_LinhaProduto` e `U_beas_ver` retornam string vazia — a sync continua normalmente.

**Itens com UDF, flags ou UpdateDate vazios (NULL):** no SAP Query Manager o campo aparece em branco. A API 5007 pode retornar HTTP 500 ao ler NULL “cru” — o SELECT usa `COALESCE` / `COALESCE(TO_NVARCHAR(UpdateDate), '')` em todos os campos textuais opcionais. No PHP, `NULL`, string vazia, espaços e literal `"null"` são normalizados antes de comparar ou gravar; a sync continua com valor vazio/`NULL` local.

**Itens com registro corrompido no SAP** (valores inválidos, não apenas NULL): o sistema reduz o lote; se o erro persistir em um único item, tenta **fallback por grupos de colunas**. Só registra falha se nem o fallback retornar dados.

**Comparação local × SAP:** sempre pelo **código ERP** (`erp_code` / `ItemCode`), nunca pela descrição. Campos conferidos incluem lote padrão (`MinOrdrQty`), administrar por (`ManBtchNum`/`ManSerNum`), estoque mín./máx., custos, UDF e demais atributos do hash.

### 1.4.1 Log de falhas por execução

Cada item ignorado ou com falha de estrutura gera uma linha em:

`logs/sap_sync_failures_{run_id}.log`

Formato:

```text
[2026-06-29 10:15:02] items | 10500142 | A API SAP não retornou o cadastro completo do item.
```

No banco (`inv_inventory_sap_sync_runs.error_log`), o mesmo conteúdo é espelhado em JSON após o prefixo `failures:` (pode coexistir com `checkpoint:` em sync parcial).

Consulta útil:

```sql
SELECT id, rows_failed, result_message, error_log, started_at, finished_at
FROM inv_inventory_sap_sync_runs
ORDER BY id DESC
LIMIT 5;
```

### 1.5 Item unitário (filtro por Cód. ERP na tela)

Mesma lista de colunas da seção 1.4, com `WHERE T0."ItemCode" = '{ItemCode}'` e `LIMIT 1`.

### 1.6 Sonda de forma farmacêutica (legado — não usada na varredura em lote)

Até uma query por código de forma conhecido (`1`, `2`, `3`, …):

```sql
SELECT "ItemCode" FROM OITM
WHERE "ItemCode" = '{ItemCode}'
  AND "U_FormaFarma" = '{codigo_forma}'
```

### 1.7 Sonda de linha de produção (fallback)

```sql
SELECT "ItemCode" FROM OITM
WHERE "ItemCode" = '{ItemCode}'
  AND "U_LinhaProduto" = 'P'   -- ou 'T'
```

### 1.8 Item unitário (filtro por Cód. ERP na tela)

```sql
SELECT T0."ItemCode", T0."ItemName", T0."InvntryUom", T0."validFor", T0."ItmsGrpCod",
       T0."AvgPrice", T0."UpdateDate"
FROM OITM T0
WHERE T0."ItemCode" = '{ItemCode}'
  AND T0."ItmsGrpCod" IN (/* códigos permitidos */)
LIMIT 1
```

### 1.9 Catálogo completo para purge (sync **Completa** — só `ItemCode`)

Lotes de 100:

```sql
SELECT T0."ItemCode"
FROM OITM T0
WHERE 1=1
  AND T0."ItmsGrpCod" IN (/* códigos permitidos */)
  AND T0."ItemCode" > '{ultimo}'
ORDER BY T0."ItemCode"
LIMIT 100
```

### 1.10 Formas farmacêuticas (UFD1)

```sql
SELECT "Descr" FROM UFD1
WHERE "TableID" = 'OITM'
  AND "FieldID" = 99
  AND "FldValue" = '{codigo}'
```

---

## 2. Estruturas (BOM + rota BEAS)

Por item elegível: **2 queries** (materiais + rota).

### 2.1 Lista de materiais (BOM)

```sql
SELECT
  S."POS_ID" AS "pos_id",
  S."ART1_ID" AS "codigo",
  S."DESCRIPTION" AS "descricao",
  S."INPUT_QTY" AS "quantidade",
  S."MENGE_VERBRAUCH" AS "menge_verbrauch",
  S."INPUT_UNIT" AS "unidade_medida",
  T2."ItemName" AS "component_item_name",
  T2."InvntryUom" AS "component_uom",
  TO_DECIMAL(COALESCE(LC."LastCost", W."AvgPrice", T2."AvgPrice"), 19, 4) AS "component_avg_price",
  T3."ItmsGrpNam" AS "component_group_name"
FROM BEAS_STL S
INNER JOIN BEAS_ITEM_VERSION V ON S."ItemCode" = V."StlItemCode"
INNER JOIN OITM I ON V."ItemCode" = I."ItemCode"
  AND V."Version" = COALESCE(NULLIF(TRIM(I."U_beas_ver"), ''), (SELECT MAX(VX."Version") FROM BEAS_ITEM_VERSION VX WHERE VX."ItemCode" = I."ItemCode"))
  AND I."ItemCode" = '{codigo_erp_pai}'
LEFT JOIN OITM T2 ON T2."ItemCode" = S."ART1_ID"
LEFT JOIN OITB T3 ON T3."ItmsGrpCod" = T2."ItmsGrpCod"
LEFT JOIN OITW W ON W."ItemCode" = S."ART1_ID"
  AND W."WhsCode" = CASE
    WHEN T2."DfltWH" = 'TJQP' THEN 'TJQR'
    WHEN T2."DfltWH" = 'APQP' THEN 'APQR'
    ELSE T2."DfltWH"
  END
LEFT JOIN (
  SELECT X."ItemCode", X."LastCost"
  FROM (
    SELECT N."ItemCode",
      CASE WHEN N."InQty" <> 0 THEN N."TransValue" / N."InQty" END AS "LastCost",
      ROW_NUMBER() OVER (PARTITION BY N."ItemCode" ORDER BY N."DocDate" DESC, N."TransNum" DESC) AS "RN"
    FROM OINM N
    WHERE N."InQty" > 0
  ) X
  WHERE X."RN" = 1
) LC ON LC."ItemCode" = S."ART1_ID"
WHERE UPPER(S."DESCRIPTION") NOT LIKE '%GERADOR DE LOTE%'
```

### 2.2 Rota (operações BEAS)

```sql
SELECT
  A."POS_ID" AS "pos_id",
  CAST(COALESCE(A."MASTER_POS_ID", 0) AS INTEGER) AS "master_pos_id",
  A."AG_ID" AS "codigo",
  A."BEZ" AS "descricao",
  A."APLATZ_ID" AS "recurso",
  A."THAPLATZ" AS "tempo_th",
  A."TNAPLATZ" AS "tempo_tn",
  A."TEAPLATZ" AS "tempo_te"
FROM BEAS_APL A
INNER JOIN BEAS_ITEM_VERSION V ON A."ItemCode" = V."RoutingId"
INNER JOIN OITM I ON V."ItemCode" = I."ItemCode"
  AND V."Version" = I."U_beas_ver"
  AND I."ItemCode" = '{codigo_erp_pai}'
```

> **Nota:** não exige mais `validFor = 'Y'` — itens inativos no SAP também importam estrutura. A versão BEAS usa `U_beas_ver` ou, se vazia, a versão mais recente em `BEAS_ITEM_VERSION`.

---

## 3. Onde otimizar no SAP/HANA

| Consulta | Impacto | Sugestões |
|----------|---------|-----------|
| **1.4 Lote OITM** | Alto (milhares de linhas) | Índice em `OITM("ItemCode")`, filtro `ItmsGrpCod`; evitar funções na `WHERE` |
| **1.5 SELECT com UDF** | Médio (PA/PI) | Se possível, habilitar UDF no SELECT da API — elimina sondas 1.6/1.7 |
| **2.1 BOM + OINM** | Alto por estrutura | Subquery `OINM` com `ROW_NUMBER` é pesada; considerar view materializada ou custo em `OITW` apenas |
| **2.1 JOIN BEAS_*** | Médio | Índices em `BEAS_STL.ItemCode`, `BEAS_ITEM_VERSION.ItemCode` + `Version` |
| **1.3 COUNT** | Baixo (1×) | Pode cachear no SAP se catálogo for estável |

---

## 4. Melhorias já aplicadas no PHP (2026-06)

1. **Progresso:** contador da barra reinicia a cada **passagem** de retomada automática; exibe `Passagem N — X / total`.
2. **Retomada:** sync completa com checkpoint parcial continua do último código, sem revarrer do zero.
3. **Incremental:** pré-filtro com dados do lote leve — itens sem mudança **não** disparam consulta completa.
4. **MP/EMB:** usa só o lote leve (sem query extra por item).
5. **UDF unificado:** cache se a API rejeita `U_FormaFarma`/`U_LinhaProduto` no SELECT.
6. **Lote:** 75 itens (antes 50); pausa entre lotes 0,8 s (antes 1,2 s).

---

## 5. Código-fonte

| Arquivo | Responsabilidade |
|---------|------------------|
| `app/adms/Models/Services/InventorySapSyncService.php` | Montagem e execução das queries |
| `app/adms/Models/Services/SapReportApiService.php` | HTTP → API SAP |
| `app/adms/Models/Repository/inventory/InvInventorySapSyncRunsRepository.php` | Payload de progresso |

Para inspecionar a SQL exata em runtime, habilite log da API SAP ou adicione temporariamente em `executeSapQueryWithRetry()`:

```php
GenerateLog::generateLog('debug', 'SAP SQL', ['sql' => $sql]);
```
