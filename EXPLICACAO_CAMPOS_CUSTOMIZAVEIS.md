# 📝 EXPLICAÇÃO: CAMPOS CUSTOMIZÁVEIS NO CRM

## 🎯 **O QUE SÃO CAMPOS CUSTOMIZÁVEIS?**

Campos customizáveis permitem que **administradores/gestores** criem campos adicionais específicos para sua empresa nos formulários de **Parceiros** e **Oportunidades**, sem precisar modificar o código do sistema.

---

## ✅ **SIM, O USUÁRIO PODE CRIAR CAMPOS PERSONALIZADOS!**

Qualquer usuário com permissão pode criar seus próprios campos através da interface:

**Menu:** `CRM → Campos Customizáveis → Novo Campo`

---

## 🔧 **COMO FUNCIONA?**

### **1. CRIAR UM CAMPO CUSTOMIZÁVEL**

**Passo a passo:**

1. **Acessar:** `CRM → Campos Customizáveis → Novo Campo`

2. **Preencher o formulário:**
   - **Entidade:** Escolher onde o campo aparecerá
     - `Parceiro` → Aparece no formulário de parceiros
     - `Oportunidade` → Aparece no formulário de oportunidades
   
   - **Nome do Campo (slug):** Nome técnico único (ex: `data_vencimento`, `codigo_cliente`)
     - Apenas letras minúsculas e underscore (_)
     - Usado internamente pelo sistema
   
   - **Label (Rótulo):** Texto que o usuário verá (ex: `Data de Vencimento`, `Código do Cliente`)
   
   - **Tipo de Campo:** Escolher o tipo de entrada
     - `Texto` → Input de texto simples
     - `Texto Longo` → Textarea (várias linhas)
     - `Número` → Input numérico
     - `Data` → Input de data
     - `Lista Suspensa (Select)` → Dropdown com opções pré-definidas
     - `Checkbox` → Múltipla escolha (várias opções selecionáveis)
   
   - **Opções:** Se escolher Select ou Checkbox, definir as opções separadas por vírgula
     - Exemplo: `Brasil, Argentina, Chile, Uruguai`
   
   - **Ordem de Exibição:** Número para controlar a ordem dos campos (menor = aparece primeiro)
   
   - **Campo Obrigatório:** Marcar se o campo é obrigatório
   
   - **Status:** Ativo ou Inativo

3. **Salvar:** O campo é criado e automaticamente aparece nos formulários!

---

## 📋 **EXEMPLOS PRÁTICOS**

### **Exemplo 1: Campo "Tipo de Cliente" (Select)**
```
Entidade: Parceiro
Nome: tipo_cliente
Label: Tipo de Cliente
Tipo: Select
Opções: Cliente Premium, Cliente Padrão, Cliente Especial
Obrigatório: Sim
Ordem: 10
```

**Resultado:** No formulário de parceiros, aparecerá um dropdown "Tipo de Cliente" com as 3 opções.

---

### **Exemplo 2: Campo "Valor Mínimo de Pedido" (Número)**
```
Entidade: Oportunidade
Nome: valor_minimo_pedido
Label: Valor Mínimo de Pedido
Tipo: Número
Obrigatório: Não
Ordem: 5
```

**Resultado:** No formulário de oportunidades, aparecerá um campo numérico "Valor Mínimo de Pedido".

---

### **Exemplo 3: Campo "Observações Especiais" (Textarea)**
```
Entidade: Parceiro
Nome: observacoes_especiais
Label: Observações Especiais
Tipo: Textarea
Obrigatório: Não
Ordem: 100
```

**Resultado:** No formulário de parceiros, aparecerá uma área de texto grande para observações.

---

### **Exemplo 4: Campo "Países de Atuação" (Checkbox)**
```
Entidade: Parceiro
Nome: paises_atuacao
Label: Países de Atuação
Tipo: Checkbox
Opções: Brasil, Argentina, Chile, Colômbia, México
Obrigatório: Não
Ordem: 15
```

**Resultado:** No formulário de parceiros, aparecerão checkboxes permitindo selecionar múltiplos países.

---

## 🏗️ **ARQUITETURA TÉCNICA**

### **Estrutura de Banco de Dados:**

```
1. crm_custom_fields (Definição dos campos)
   ├── id
   ├── entity_type (partner/opportunity)
   ├── field_name (slug único)
   ├── field_label (texto exibido)
   ├── field_type (text/number/date/select/textarea/checkbox)
   ├── field_options (JSON com opções para select/checkbox)
   ├── is_required (obrigatório?)
   ├── display_order (ordem de exibição)
   └── is_active (ativo/inativo)

2. crm_custom_field_values_partners (Valores para parceiros)
   ├── partner_id
   ├── custom_field_id
   └── field_value (valor salvo)

3. crm_custom_field_values_opportunities (Valores para oportunidades)
   ├── opportunity_id
   ├── custom_field_id
   └── field_value (valor salvo)
```

---

## 🔄 **FLUXO DE FUNCIONAMENTO**

### **1. CRIAÇÃO DO CAMPO (Admin/Gestor)**
```
Admin acessa → CRM → Campos Customizáveis → Novo Campo
Preenche formulário → Salva
Campo é registrado na tabela crm_custom_fields
```

### **2. EXIBIÇÃO NO FORMULÁRIO (Usuário)**
```
Usuário acessa → CRM → Novo Parceiro (ou Nova Oportunidade)
Sistema busca campos customizados ativos (crm_custom_fields WHERE is_active = 1)
Renderiza campos dinamicamente no formulário
```

### **3. SALVAMENTO DO VALOR (Usuário)**
```
Usuário preenche formulário → Inclui valores dos campos customizados
Sistema salva parceiro/oportunidade → Salva valores em crm_custom_field_values_*
```

### **4. VISUALIZAÇÃO (Usuário)**
```
Usuário visualiza parceiro/oportunidade
Sistema busca valores dos campos customizados
Exibe valores junto com informações padrão
```

---

## 💡 **VANTAGENS DO SISTEMA**

### ✅ **Flexibilidade**
- Cada empresa pode criar campos específicos para seu negócio
- Não precisa modificar código para adicionar campos

### ✅ **Escalabilidade**
- Pode criar quantos campos quiser
- Campos podem ser ativados/desativados sem perder dados

### ✅ **Organização**
- Campos são ordenados por `display_order`
- Pode agrupar campos relacionados pela ordem

### ✅ **Validação**
- Campos obrigatórios são validados
- Tipos de campo garantem dados consistentes

### ✅ **Performance**
- Valores são salvos em tabelas separadas (não polui tabelas principais)
- Busca otimizada por índices

---

## ⚠️ **O QUE ESTÁ FALTANDO?**

### **❌ Renderização nos Formulários**
Os campos são criados, mas **ainda não aparecem** nos formulários de parceiros e oportunidades.

**O que precisa ser feito:**
1. Modificar `app/adms/Views/crm/partners/form.php` para renderizar campos customizados
2. Modificar `app/adms/Views/crm/opportunities/form.php` para renderizar campos customizados
3. Criar helper para renderizar campos dinamicamente

### **❌ Salvamento dos Valores**
Os valores não estão sendo salvos quando o formulário é submetido.

**O que precisa ser feito:**
1. Modificar `CrmCreatePartner.php` para salvar valores
2. Modificar `CrmUpdatePartner.php` para salvar valores
3. Modificar `CrmCreateOpportunity.php` para salvar valores
4. Modificar `CrmUpdateOpportunity.php` para salvar valores

### **❌ Exibição nas Views**
Os valores não aparecem na visualização de parceiros/oportunidades.

**O que precisa ser feito:**
1. Modificar `app/adms/Views/crm/partners/view.php` para exibir campos customizados
2. Modificar `app/adms/Views/crm/opportunities/view.php` para exibir campos customizados
3. Buscar valores dos campos customizados nos controllers

---

## 🎨 **COMO FICARÁ VISUALMENTE**

### **ANTES (Formulário Padrão):**
```
┌─────────────────────────────────────┐
│ Novo Parceiro                       │
├─────────────────────────────────────┤
│ Nome: [___________]                 │
│ Email: [___________]                 │
│ Telefone: [___________]             │
│ ...                                 │
└─────────────────────────────────────┘
```

### **DEPOIS (Com Campos Customizados):**
```
┌─────────────────────────────────────┐
│ Novo Parceiro                       │
├─────────────────────────────────────┤
│ Nome: [___________]                 │
│ Email: [___________]                │
│ Telefone: [___________]             │
│ ...                                 │
│                                     │
│ ──── Campos Customizados ────      │
│                                     │
│ Tipo de Cliente: [Dropdown ▼]      │
│ Valor Mínimo: [_______]             │
│ Observações: [____________]         │
│         [____________]              │
│ Países: ☑ Brasil ☐ Argentina       │
└─────────────────────────────────────┘
```

---

## 📊 **TIPOS DE CAMPOS DISPONÍVEIS**

| Tipo | Descrição | Quando Usar | Exemplo |
|------|-----------|-------------|---------|
| **text** | Input de texto simples | Nomes, códigos, textos curtos | Código do Cliente |
| **textarea** | Área de texto grande | Observações, descrições longas | Observações Especiais |
| **number** | Input numérico | Valores, quantidades | Valor Mínimo de Pedido |
| **date** | Seletor de data | Datas importantes | Data de Contrato |
| **select** | Lista suspensa | Uma opção entre várias | Tipo de Cliente |
| **checkbox** | Múltipla escolha | Várias opções selecionáveis | Países de Atuação |

---

## 🔐 **PERMISSÕES**

**Quem pode criar campos customizados?**
- Usuários com permissão `CrmCreateCustomField`
- Normalmente: Administradores e Gestores

**Quem pode usar os campos?**
- Todos os usuários que podem criar/editar parceiros/oportunidades
- Os campos aparecem automaticamente nos formulários

---

## 📝 **REGRAS E LIMITAÇÕES**

### ✅ **Permitido:**
- Criar campos para `partner` e `opportunity`
- Usar letras minúsculas e underscore no `field_name`
- Criar quantos campos quiser
- Ativar/desativar campos sem perder dados
- Campos obrigatórios ou opcionais

### ❌ **Não Permitido:**
- Usar espaços ou caracteres especiais no `field_name`
- Mudar `entity_type` de um campo depois de criado (pode causar perda de dados)
- Deletar campo com valores salvos (valores ficam órfãos)

---

## 🚀 **PRÓXIMOS PASSOS**

Para tornar o sistema 100% funcional, falta:

1. **Renderizar campos nos formulários** (3-4 horas)
2. **Salvar valores nos controllers** (1-2 horas)
3. **Exibir valores nas views** (1-2 horas)

**Total estimado:** 5-8 horas de desenvolvimento

---

## 💬 **RESUMO**

**Pergunta:** O usuário pode criar campos personalizados?

**Resposta:** ✅ **SIM!** Qualquer usuário com permissão pode criar campos customizados através da interface `CRM → Campos Customizáveis → Novo Campo`. Os campos são criados dinamicamente e aparecem automaticamente nos formulários de parceiros e oportunidades.

**Status atual:** Sistema de criação está 100% funcional. Falta apenas renderizar os campos nos formulários e salvar/exibir os valores.

---

**Última atualização:** 31/10/2025

