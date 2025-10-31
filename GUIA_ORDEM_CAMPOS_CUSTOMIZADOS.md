# 📋 GUIA: ORDEM DE EXIBIÇÃO DOS CAMPOS CUSTOMIZADOS

## 🎯 **ONDE OS CAMPOS APARECERÃO NO FORMULÁRIO?**

### **Localização: Seção Separada "Campos Customizáveis"**

Os campos customizados **NÃO** aparecem dentro de "Informações Básicas" ou "Classificação".

Eles aparecem em uma **seção própria** chamada **"Campos Customizáveis"**, localizada em uma posição específica do formulário.

---

## 📐 **ESTRUTURA COMPLETA DO FORMULÁRIO**

```
┌─────────────────────────────────────────────────────────┐
│           FORMULÁRIO DE CRIAÇÃO/EDIÇÃO                 │
├─────────────────────────────────────────────────────────┤
│                                                         │
│  ┌─────────────────────┐  ┌─────────────────────┐     │
│  │ Informações Básicas │  │   Classificação     │     │
│  │                     │  │                     │     │
│  │ • Código            │  │ • Tipo de Parceiro │     │
│  │ • Nome              │  │ • Responsável       │     │
│  │ • Email             │  │ • Departamento      │     │
│  │ • Telefone          │  │ • Prioridade        │     │
│  │ • Endereço          │  │ • Receita Estimada  │     │
│  │ • etc...            │  │                     │     │
│  └─────────────────────┘  └─────────────────────┘     │
│                                                         │
│  ┌─────────────────────────────────────────────────┐  │
│  │  📋 CAMPOS CUSTOMIZÁVEIS                        │  │
│  │  ────────────────────────────────────────────  │  │
│  │                                                  │  │
│  │  [Aqui aparecem SEUS campos customizados]       │  │
│  │                                                  │  │
│  │  Ordem determina a posição DENTRO desta seção!  │  │
│  │                                                  │  │
│  │  Exemplo:                                        │  │
│  │  • Aniversário (ordem: 10) ← aparece primeiro   │  │
│  │  • Tipo de Cliente (ordem: 20) ← aparece depois│  │
│  │  • Observações (ordem: 30) ← aparece por último │  │
│  │                                                  │  │
│  └─────────────────────────────────────────────────┘  │
│                                                         │
│  ┌─────────────────────────────────────────────────┐  │
│  │  [Salvar Parceiro]  [Cancelar]                  │  │
│  └─────────────────────────────────────────────────┘  │
│                                                         │
└─────────────────────────────────────────────────────────┘
```

---

## 🔢 **COMO FUNCIONA A ORDEM DE EXIBIÇÃO?**

### **IMPORTANTE:**
A ordem de exibição **NÃO** determina onde a seção "Campos Customizáveis" aparece no formulário (ela sempre aparece no mesmo lugar).

A ordem determina **APENAS** a posição dos campos **DENTRO** da seção "Campos Customizáveis".

---

## 📊 **EXEMPLO PRÁTICO**

### **Cenário: Você tem 3 campos customizados**

**Campo 1: "Aniversário"**
- Ordem: 10
- Aparece: **PRIMEIRO** na seção "Campos Customizáveis"

**Campo 2: "Tipo de Cliente"**
- Ordem: 20
- Aparece: **SEGUNDO** na seção "Campos Customizáveis"

**Campo 3: "Observações Especiais"**
- Ordem: 30
- Aparece: **TERCEIRO** na seção "Campos Customizáveis"

### **Resultado Visual:**

```
┌─────────────────────────────────────────┐
│ 📋 Campos Customizáveis                 │
├─────────────────────────────────────────┤
│                                         │
│ Aniversário: [____/____/____]          │ ← Ordem 10 (primeiro)
│                                         │
│ Tipo de Cliente: [Dropdown ▼]          │ ← Ordem 20 (segundo)
│                                         │
│ Observações Especiais: [________]      │ ← Ordem 30 (terceiro)
│              [________]                 │
│                                         │
└─────────────────────────────────────────┘
```

---

## 🎯 **COMO ESCOLHER A ORDEM?**

### **Regra Básica:**
- **Menor número** = aparece **primeiro** dentro da seção
- **Maior número** = aparece **depois** dentro da seção

### **Estratégia Recomendada:**

#### **1. Primeiro Campo (Sugestão: 10)**
```
Campo "Aniversário" → Ordem: 10
```
Se for o primeiro campo que você cria, use **10**.

#### **2. Campos Adicionais (Incrementar de 10 em 10)**
```
Campo 1: Aniversário → Ordem: 10
Campo 2: Tipo de Cliente → Ordem: 20
Campo 3: Observações → Ordem: 30
Campo 4: País de Atuação → Ordem: 40
```

#### **3. Inserir Campo no Meio**
```
Campos existentes:
- Campo A → Ordem: 10
- Campo B → Ordem: 20
- Campo C → Ordem: 30

Para inserir entre A e B:
- Novo Campo → Ordem: 15
```

#### **4. Reorganizar Depois**
Se precisar reorganizar, basta editar a ordem de cada campo:
- Edite o campo e altere o número da ordem
- Salve
- O campo será reposicionado automaticamente

---

## 📍 **POSIÇÃO FIXA DA SEÇÃO "CAMPOS CUSTOMIZÁVEIS"**

### **No Formulário de Parceiros:**
```
1. Informações Básicas (esquerda)
2. Classificação (direita)
3. 🆕 Campos Customizáveis (abaixo, largura total)
4. Botões Salvar/Cancelar
```

### **No Formulário de Oportunidades:**
```
1. Informações da Oportunidade (esquerda)
2. Controle (direita)
3. 🆕 Campos Customizáveis (abaixo, largura total)
4. Botões Salvar/Cancelar
```

**⚠️ A seção sempre aparece no mesmo lugar, independente da ordem dos campos!**

---

## 💡 **DICAS PRÁTICAS**

### **✅ RECOMENDAÇÕES:**

1. **Use múltiplos de 10** (10, 20, 30, 40...)
   - Facilita inserir campos entre existentes
   - Se precisar inserir entre 10 e 20, use 15

2. **Campos mais importantes = números menores**
   - Campos essenciais: 10, 20, 30
   - Campos secundários: 50, 60, 70

3. **Não precisa ser sequencial**
   - Pode usar 10, 15, 25, 30 (não precisa ser 10, 11, 12, 13)
   - Espaços facilitam reorganização futura

4. **Pode reorganizar depois**
   - Edite qualquer campo e altere a ordem
   - Não perde dados, apenas reorganiza visualmente

---

## 🔍 **COMO VER A ORDEM ATUAL?**

### **Na Listagem de Campos:**
Acesse: `CRM → Campos Customizáveis`

A tabela mostra a coluna **"Ordem"** com o número de cada campo.

### **Ao Criar Novo Campo:**
- O sistema mostra uma tabela com campos existentes
- Cada campo mostra sua ordem atual
- O sistema sugere automaticamente a próxima ordem disponível

---

## 📝 **RESUMO**

### **Onde aparecem?**
✅ Em uma seção separada chamada **"Campos Customizáveis"**
✅ Localizada **DEPOIS** de "Informações Básicas" e "Classificação"
✅ Localizada **ANTES** dos botões "Salvar/Cancelar"

### **O que a ordem controla?**
✅ A posição dos campos **DENTRO** da seção "Campos Customizáveis"
❌ **NÃO** controla onde a seção aparece no formulário (ela é fixa)

### **Como escolher a ordem?**
✅ Menor número = aparece primeiro na seção
✅ Maior número = aparece depois na seção
✅ Use múltiplos de 10 (10, 20, 30...) para facilitar inserções

---

**Última atualização:** 31/10/2025

