# 📚 Funcionamento do Sistema de Competências

## 🎯 Visão Geral

O sistema de competências permite:
1. **Cadastrar competências** com seus níveis de proficiência
2. **Vincular competências aos cargos** definindo o nível requerido
3. **Avaliar colaboradores** comparando seu nível atual com o requerido

---

## 🔄 Fluxo Completo do Sistema

### **ETAPA 1: Cadastrar Competências**

**Onde:** `Gestão de Pessoas → Desempenho → Competências → Nova Competência`

**O que fazer:**
1. Preencher:
   - **Nome** (ex: "Linguagem de programação C")
   - **Tipo** (Técnica, Comportamental, Liderança)
   - **Categoria** (ex: "TI")
   - **Descrição** (opcional)

2. **Definir Níveis de Proficiência (1-5):**
   - **Nível 1 (Iniciante):** Ex: "Conhecimento básico, precisa de supervisão constante"
   - **Nível 2 (Básico):** Ex: "Conhecimento básico, pode trabalhar com supervisão ocasional"
   - **Nível 3 (Intermediário):** Ex: "Conhecimento sólido, trabalha de forma independente"
   - **Nível 4 (Avançado):** Ex: "Conhecimento avançado, pode orientar outros"
   - **Nível 5 (Especialista):** Ex: "Conhecimento especializado, referência na área"

**Resultado:** A competência fica disponível para uso na matriz.

---

### **ETAPA 2: Vincular Competências aos Cargos** ⭐ **AQUI ACONTECE A VINCULAÇÃO**

**Onde:** `Gestão de Pessoas → Desempenho → Matriz de Competências`

**Como funciona:**

1. **Acesse a Matriz de Competências:**
   - Menu: `Gestão de Pessoas → Desempenho → Matriz de Competências`
   - Ou botão "Matriz" na listagem de competências

2. **Visualize a Tabela:**
   ```
   ┌─────────────┬──────────────┬──────────────┬──────────────┐
   │   Cargo     │ Competência A │ Competência B │ Competência C │
   ├─────────────┼──────────────┼──────────────┼──────────────┤
   │ Desenvolvedor│  [Nível]     │  [Nível]     │  [Nível]     │
   │   PHP       │  [Obrig.]    │  [Obrig.]    │  [Obrig.]    │
   ├─────────────┼──────────────┼──────────────┼──────────────┤
   │ Analista    │  [Nível]     │  [Nível]     │  [Nível]     │
   │   de TI     │  [Obrig.]    │  [Obrig.]    │  [Obrig.]    │
   └─────────────┴──────────────┴──────────────┴──────────────┘
   ```

3. **Para cada célula (Cargo × Competência):**
   - **Selecione o Nível Requerido (1-5):**
     - Dropdown com opções: `-` (sem vínculo), `1`, `2`, `3`, `4`, `5`
     - Se escolher `-` ou `0`, a competência NÃO é requerida para aquele cargo
     - Se escolher `1` a `5`, define o nível mínimo necessário
   
   - **Marque como "Obrigatória" (opcional):**
     - Checkbox "Obrig." indica que a competência é essencial para o cargo
     - Competências obrigatórias aparecem primeiro nas listagens

4. **Salve a Matriz:**
   - Clique em "Salvar Matriz"
   - O sistema cria/atualiza os registros na tabela `adms_competency_matrix`

**O que acontece no banco de dados:**
```sql
-- Registro criado/atualizado na tabela adms_competency_matrix
INSERT INTO adms_competency_matrix 
  (position_id, competency_id, required_level, is_mandatory)
VALUES 
  (5, 1, 4, true)  -- Cargo ID 5, Competência ID 1, Nível 4, Obrigatória
```

---

### **ETAPA 3: Usar na Avaliação de Desempenho**

**Onde:** `Gestão de Pessoas → Desempenho → Avaliações de Desempenho`

**Como funciona:**
1. Ao criar uma avaliação, o sistema busca as competências do cargo do colaborador
2. Compara o nível atual do colaborador com o nível requerido
3. Identifica gaps (diferenças) para criar planos de desenvolvimento

---

## 📋 Estrutura de Dados

### **Tabela: `adms_competencies`**
Armazena as competências e seus níveis de proficiência:
- `id` - ID da competência
- `name` - Nome (ex: "Linguagem de programação C")
- `competency_type` - Tipo (technical, behavioral, leadership)
- `category` - Categoria (ex: "TI")
- `level_1_description` até `level_5_description` - Descrições dos níveis

### **Tabela: `adms_competency_matrix`** ⭐ **TABELA DE VINCULAÇÃO**
Armazena a vinculação entre cargos e competências:
- `position_id` - ID do cargo
- `competency_id` - ID da competência
- `required_level` - Nível requerido (1-5)
- `is_mandatory` - Se é obrigatória (true/false)

**Exemplo de registro:**
```
position_id = 5 (Desenvolvedor PHP)
competency_id = 1 (Linguagem de programação C)
required_level = 4 (Avançado)
is_mandatory = true (Obrigatória)
```

**Significado:** O cargo "Desenvolvedor PHP" REQUER a competência "Linguagem de programação C" no nível 4 (Avançado), e ela é obrigatória.

---

## 🔍 Listagem de Competências

**Onde:** `Gestão de Pessoas → Desempenho → Competências`

**O que mostra:**
- **ID** - Identificador
- **Nome** - Nome da competência
- **Tipo** - Badge colorido (Técnica, Comportamental, Liderança)
- **Categoria** - Categoria da competência
- **Níveis** - Indicador visual mostrando quais níveis (1-5) estão definidos
  - Badges coloridos: Níveis definidos aparecem coloridos, não definidos aparecem cinza
  - Contador: Ex: "(3/5)" significa que 3 dos 5 níveis estão definidos
- **Descrição** - Preview da descrição
- **Ações** - Visualizar, Editar, Apagar

**Filtros disponíveis:**
- Por Tipo (Técnica, Comportamental, Liderança)
- Busca por Nome ou Descrição

---

## 🎯 Resumo: Onde Vincular Competência ao Cargo?

### **RESPOSTA DIRETA:**

**📍 Local:** `Gestão de Pessoas → Desempenho → Matriz de Competências`

**🔧 Como fazer:**
1. Acesse a página da Matriz de Competências
2. Encontre a interseção entre o **Cargo** (linha) e a **Competência** (coluna)
3. Na célula:
   - Selecione o **Nível Requerido** (1-5) no dropdown
   - Marque **"Obrig."** se a competência for essencial
4. Clique em **"Salvar Matriz"**

**💾 Onde fica salvo:**
- Tabela: `adms_competency_matrix`
- Campos: `position_id`, `competency_id`, `required_level`, `is_mandatory`

---

## 📊 Exemplo Prático

### Cenário: Vincular "Linguagem de programação C" ao cargo "Desenvolvedor PHP"

1. **Cadastrar a Competência:**
   - Nome: "Linguagem de programação C"
   - Tipo: Técnica
   - Definir níveis 1-5 com descrições

2. **Vincular na Matriz:**
   - Linha: "Desenvolvedor PHP"
   - Coluna: "Linguagem de programação C"
   - Selecionar: Nível 4 (Avançado)
   - Marcar: Obrigatória ✓
   - Salvar

3. **Resultado:**
   - Qualquer colaborador no cargo "Desenvolvedor PHP" deve ter nível 4 em "Linguagem de programação C"
   - Isso pode ser usado em avaliações de desempenho
   - Gaps podem ser identificados automaticamente

---

## 🔗 Relacionamentos

```
adms_competencies (Competências)
    ↓
adms_competency_matrix (VINCULAÇÃO) ← AQUI ACONTECE A VINCULAÇÃO
    ↓
adms_positions (Cargos)
```

**Fluxo:**
1. Competência criada → `adms_competencies`
2. Vinculação criada → `adms_competency_matrix` (liga cargo + competência + nível)
3. Usado em avaliações → Compara nível atual vs requerido

---

## ✅ Checklist de Uso

- [ ] 1. Cadastrar competências com níveis de proficiência
- [ ] 2. Acessar Matriz de Competências
- [ ] 3. Para cada cargo, definir quais competências são necessárias
- [ ] 4. Definir o nível requerido (1-5) para cada competência em cada cargo
- [ ] 5. Marcar competências obrigatórias
- [ ] 6. Salvar a matriz
- [ ] 7. Usar nas avaliações de desempenho

---

**Última atualização:** 05/12/2025

