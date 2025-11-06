# 🔍 Filtros Dinâmicos para Dashboards

## ✅ Problema Resolvido

### Antes:
- ❌ Filtros eram campos de **texto livre** (usuário digitava manualmente)
- ❌ Filtros **não funcionavam** (SQL não tinha WHERE)
- ❌ **Difícil de usar** - usuário precisava saber exatamente o nome

### Agora:
- ✅ Filtros são **dropdowns/selects** com opções carregadas do banco
- ✅ Filtros **funcionam corretamente** com aplicação automática de WHERE
- ✅ **Fácil de usar** - basta selecionar da lista

---

## 🚀 O que foi Implementado

### 1. **API de Opções de Filtros** (`GetFilterOptions.php`)

Nova API que:
- Busca **valores distintos** do campo de filtro
- Retorna lista de opções únicas
- Cache automático (1000 opções max)
- Ordenação alfabética

**Endpoint:**
```
GET /get-filter-options?dashboard_id={ID}&field={NOME_DO_CAMPO}
```

**Resposta:**
```json
{
  "success": true,
  "options": [
    {"value": "João Silva", "label": "João Silva"},
    {"value": "Maria Santos", "label": "Maria Santos"},
    ...
  ],
  "count": 15
}
```

---

### 2. **View com Dropdowns Dinâmicos** (`view.php`)

A view do dashboard agora:
- Renderiza **selects** em vez de inputs de texto
- Carrega opções via **AJAX** ao abrir a página
- Mostra "Carregando..." enquanto busca os dados
- Opção "Todos" para não filtrar

**Tipos de Filtro:**
- `year`: Dropdown com últimos 5 anos
- `month`: Dropdown com 12 meses
- `text`: **Dropdown dinâmico** com valores únicos do banco
- `number`: Dropdown dinâmico com valores únicos

---

### 3. **Aplicação de Filtros com WHERE** (`ExecuteDashboard.php`)

Novo método `applyFiltersWithWhere()` que:
- **Wrap a query original** em subquery
- Adiciona `WHERE` com os filtros selecionados
- Suporta múltiplos filtros (AND)
- Escape seguro de valores
- Suporte a YEAR() e MONTH() automático

**Exemplo:**
```sql
-- Query original:
SELECT * FROM OINV ...

-- Com filtro aplicado:
SELECT * FROM (
    SELECT * FROM OINV ...
) AS filtered_data
WHERE "nomeVendedor" = 'João Silva'
AND YEAR("DataCriação") = 2025
```

---

## 📊 Dashboard de Vendas - RMVendas

O dashboard de exemplo agora tem **4 filtros totalmente funcionais**:

### Filtro 1: **Vendedor**
- Tipo: Dropdown
- Carrega: Todos os vendedores únicos do relatório
- Campo: `nomeVendedor`

### Filtro 2: **Grupo de Parceiro**
- Tipo: Dropdown
- Carrega: Todos os grupos de parceiros únicos
- Campo: `nomeGrupoPN`

### Filtro 3: **Ano**
- Tipo: Dropdown
- Opções: 2025, 2024, 2023, 2022, 2021
- Campo: `YEAR(DataCriação)`

### Filtro 4: **Mês**
- Tipo: Dropdown
- Opções: Janeiro...Dezembro
- Campo: `MONTH(DataCriação)`

---

## 🧪 Como Testar

### 1. Acessar o Dashboard
```
Menu → Relatórios → Meus Dashboards → "Dashboard de Vendas - RMVendas"
```

### 2. Verificar Filtros
- **Vendedor**: Deve mostrar lista de vendedores (ex: "João Silva", "Maria Santos", etc.)
- **Grupo de Parceiro**: Deve mostrar lista de grupos (ex: "Clientes Premium", "Clientes Bronze", etc.)
- **Ano**: Deve mostrar 2025, 2024, 2023, 2022, 2021
- **Mês**: Deve mostrar Janeiro...Dezembro

### 3. Aplicar Filtro
```
1. Selecionar: Vendedor = "João Silva"
2. Selecionar: Ano = 2025
3. Clicar em: "Consultar"
4. Aguardar: Carregamento dos dados
5. Verificar: KPIs e gráficos atualizados apenas com dados de João Silva em 2025
```

### 4. Testar Múltiplos Filtros
```
1. Vendedor: João Silva
2. Grupo de Parceiro: Clientes Premium
3. Ano: 2025
4. Mês: 11 (Novembro)
5. Consultar
6. Ver: Apenas vendas de João Silva para Clientes Premium em Nov/2025
```

### 5. Verificar Console do Navegador
```javascript
// Você deve ver mensagens como:
✅ 15 opções carregadas para nomeVendedor
✅ 8 opções carregadas para nomeGrupoPN
```

---

## 🔧 Arquivos Modificados/Criados

### Criados:
- `app/adms/Controllers/dashboards/GetFilterOptions.php` - API de opções
- `database/seeds/AddGetFilterOptionsPage.php` - Seeder da página
- `docs/FILTROS_DINAMICOS_DASHBOARD.md` - Esta documentação

### Modificados:
- `app/adms/Views/dashboards/view.php` - Dropdowns + AJAX
- `app/adms/Controllers/dashboards/ExecuteDashboard.php` - WHERE com filtros
- `app/adms/Controllers/Services/PageLayoutService.php` - Registro do controller

---

## 🎨 Como Funciona (Fluxo)

### Carregamento da Página:

```
1. Usuário acessa dashboard
        ↓
2. View renderiza selects com "Carregando..."
        ↓
3. JavaScript faz AJAX para cada filtro:
   GET /get-filter-options?dashboard_id=5&field=nomeVendedor
        ↓
4. GetFilterOptions.php:
   - Valida acesso
   - Busca DISTINCT do campo na query
   - Retorna JSON com opções
        ↓
5. JavaScript popula os selects
        ↓
6. Usuário vê dropdowns preenchidos
```

### Aplicação de Filtros:

```
1. Usuário seleciona filtros e clica "Consultar"
        ↓
2. JavaScript envia FormData via POST:
   filters[nomeVendedor] = "João Silva"
   filters[YEAR(DataCriação)] = 2025
        ↓
3. ExecuteDashboard.php:
   - Recebe filtros
   - Chama applyFiltersWithWhere()
   - Wrap query original
   - Adiciona WHERE com filtros
        ↓
4. SQL resultante:
   SELECT * FROM (... query original ...) AS filtered_data
   WHERE "nomeVendedor" = 'João Silva' AND YEAR("DataCriação") = 2025
        ↓
5. Executa query filtrada
        ↓
6. Calcula medidas, KPIs, gráficos
        ↓
7. Retorna JSON
        ↓
8. JavaScript atualiza interface
```

---

## 🐛 Resolução de Problemas

### Problema: "Carregando..." não muda

**Causa:** Erro ao buscar opções

**Solução:**
1. Abrir console do navegador (F12)
2. Ver mensagens de erro
3. Verificar se a página `GetFilterOptions` está registrada:
   ```sql
   SELECT * FROM adms_pages WHERE controller_url = 'get-filter-options';
   ```

---

### Problema: Filtro não aplica

**Causa:** Nome do campo incorreto ou tipo errado

**Solução:**
1. Verificar o nome exato do campo no relatório
2. Conferir se o tipo está correto (text, year, month, number)
3. Ver logs do PHP:
   ```
   tail -f /path/to/error.log
   ```
4. Procurar mensagem:
   ```
   🔍 SQL com filtros aplicados: SELECT * FROM ...
   ```

---

### Problema: Nenhuma opção aparece

**Causa:** Campo não existe na query ou todos os valores são NULL

**Solução:**
1. Verificar se o campo existe:
   - Executar o relatório base
   - Confirmar que o campo aparece nos resultados
2. Verificar se há dados:
   ```sql
   SELECT DISTINCT "nomeVendedor" FROM (...query...) WHERE "nomeVendedor" IS NOT NULL;
   ```

---

## 💡 Criando Novos Dashboards com Filtros Dinâmicos

### Ao criar um novo dashboard:

1. **Adicionar Filtro:**
   ```
   Clicar: "Adicionar Filtro"
   Campo: nomeCliente (nome exato do campo na SQL)
   Rótulo: Cliente
   Tipo: text (será dropdown automático)
   Variável: (pode deixar vazio)
   ```

2. **O sistema automaticamente:**
   - Cria um dropdown
   - Busca valores únicos do campo
   - Aplica WHERE ao consultar

3. **Tipos de Filtro:**
   - `text`: Dropdown dinâmico (strings)
   - `number`: Dropdown dinâmico (números)
   - `year`: Dropdown últimos 5 anos
   - `month`: Dropdown 1-12 meses

---

## 🎉 Benefícios

### Para o Usuário:
- ✅ **Mais fácil** - Selecionar em vez de digitar
- ✅ **Sem erros** - Não pode digitar errado
- ✅ **Mais rápido** - Ver todas as opções de uma vez
- ✅ **Descoberta** - Ver quais valores existem

### Para o Sistema:
- ✅ **Performance** - WHERE otimizado
- ✅ **Segurança** - Escape automático de SQL injection
- ✅ **Manutenibilidade** - Código limpo e organizado
- ✅ **Escalável** - Funciona com qualquer relatório

---

## 📚 Próximos Passos

### Possíveis Melhorias:
- [ ] Cache das opções (evitar buscar toda vez)
- [ ] Busca/filtro dentro do dropdown (para muitas opções)
- [ ] Filtros dependentes (ex: Estado → Cidade)
- [ ] Salvar filtros favoritos
- [ ] Aplicar filtros automaticamente ao abrir

---

**🎊 Filtros dinâmicos implementados com sucesso!** 🚀

**Teste agora:**
```
Menu → Relatórios → Meus Dashboards → Dashboard de Vendas - RMVendas
```

