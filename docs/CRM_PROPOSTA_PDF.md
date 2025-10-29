# 📄 Proposta Comercial PDF - Guia de Personalização

## 🎨 Visão Geral

O módulo CRM gera propostas comerciais profissionais em PDF com layout corporativo moderno, completo com:

- ✅ **Capa Corporativa** com gradiente verde (identidade visual)
- ✅ **Dados do Cliente** organizados em cards
- ✅ **Valor em Destaque** com design premium (fundo dourado)
- ✅ **Tabela de Detalhes** com informações da oportunidade
- ✅ **Descrição Detalhada** formatada
- ✅ **Condições Comerciais** padrão
- ✅ **Termos e Condições** completos
- ✅ **Seção de Assinatura** bilateral
- ✅ **Rodapé Corporativo** com contatos

---

## ⚙️ Personalização (Variáveis de Ambiente)

Para personalizar as informações da sua empresa na proposta, adicione as seguintes variáveis no seu arquivo `.env`:

```env
# ============================================
# DADOS DA EMPRESA PARA PROPOSTA PDF
# ============================================

# Nome da Empresa (aparece no cabeçalho e rodapé)
COMPANY_NAME="Sua Empresa Ltda"

# E-mail de Contato da Empresa
COMPANY_EMAIL="contato@suaempresa.com.br"

# Telefone da Empresa
COMPANY_PHONE="(11) 3456-7890"

# Endereço Completo da Empresa
COMPANY_ADDRESS="Rua Exemplo, 123 - Centro - São Paulo/SP - CEP 01234-567"
```

### 📌 Valores Padrão

Se não configurar as variáveis, a proposta usará valores padrão:
- **Nome:** "Sua Empresa"
- **E-mail:** "contato@suaempresa.com.br"
- **Telefone:** "(00) 0000-0000"
- **Endereço:** "Endereço da Empresa"

---

## 🚀 Como Gerar a Proposta

### 1. **Via Interface Web**
1. Acesse `CRM > Oportunidades`
2. Clique em qualquer oportunidade para ver os detalhes
3. Clique no botão **"Proposta (PDF)"** (ícone azul)
4. O PDF será aberto em nova aba do navegador

### 2. **Via URL Direta**
```
https://seudominio.com/administrativo/crm-generate-proposal-pdf/{ID_OPORTUNIDADE}
```

---

## 📋 Estrutura do PDF

### **PÁGINA 1 - Capa e Informações**

#### 1. Cabeçalho Corporativo (Verde)
- Nome da empresa
- Tagline: "Excelência em Soluções Comerciais"
- Título: "PROPOSTA COMERCIAL"
- Metadata: Código, Emissão, Validade, Responsável

#### 2. Dados do Cliente
Card com informações do parceiro:
- Nome/Razão Social
- E-mail
- Telefone
- Celular

#### 3. Valor em Destaque
Box dourado premium com estrelas:
- Valor total da proposta em fonte grande
- Subtexto: "Valor sujeito a negociação comercial"

#### 4. Detalhes da Oportunidade
Tabela com:
- Título do Projeto
- Consultor Responsável
- Probabilidade de Fechamento (badge verde)
- Previsão de Fechamento (badge azul)

#### 5. Descrição Detalhada
Card com texto formatado da descrição da oportunidade.

---

### **PÁGINA 2 - Condições e Termos**

#### 6. Condições Comerciais
Tabela com:
- Forma de Pagamento
- Prazo de Entrega
- Validade da Proposta
- Impostos e Encargos
- Garantia

#### 7. Termos e Condições Gerais
Lista detalhada com 8 cláusulas:
- Validade
- Confidencialidade
- Aceite
- Alterações
- Faturamento
- Entrega
- Cancelamento
- Garantia

#### 8. Alert de Contato
Box azul com instruções para aceite.

#### 9. Seção de Assinatura
Box verde de aceite + duas áreas de assinatura:
- Empresa (com nome do responsável)
- Cliente

#### 10. Rodapé Corporativo
Fundo escuro com:
- Nome da empresa
- E-mail, telefone e endereço

---

## 🎨 Cores do Layout

| Elemento | Cor Principal | Cor Secundária |
|----------|---------------|----------------|
| Cabeçalho | `#1f6b45` | `#2E9263` → `#3daf78` (gradiente) |
| Cards/Ícones | `#2E9263` | `#1f6b45` (gradiente) |
| Valor Destaque | `#ffd700` (dourado) | `#ffed4e` (amarelo) |
| Badges Success | `#d4edda` (fundo) | `#155724` (texto) |
| Badges Info | `#d1ecf1` (fundo) | `#0c5460` (texto) |
| Rodapé | `#2c3e50` | - |

---

## 🔧 Personalizações Avançadas

### Alterar o Tagline da Empresa

Edite em `app/adms/Controllers/crm/CrmGenerateProposalPdf.php` (linha ~490):

```php
<div class="company-tagline">Excelência em Soluções Comerciais</div>
```

### Alterar Prazo de Validade Padrão

Edite em `app/adms/Controllers/crm/CrmGenerateProposalPdf.php` (linha ~44):

```php
$validUntil = date('d/m/Y', strtotime('+30 days')); // Altere +30 para +60, +90, etc.
```

### Adicionar Logo da Empresa

Edite o cabeçalho em `getProposalHTML()` e adicione antes do `company-name`:

```html
<div class="company-logo">
    <img src="caminho/para/logo.png" width="150" alt="Logo">
</div>
```

---

## 📊 Exemplo de Uso Completo

```env
# .env
COMPANY_NAME="TechSolutions Inovação Digital Ltda"
COMPANY_EMAIL="comercial@techsolutions.com.br"
COMPANY_PHONE="(11) 98765-4321"
COMPANY_ADDRESS="Av. Paulista, 1000 - Sala 501 - Bela Vista - São Paulo/SP - CEP 01310-100"
```

**Resultado:** PDF profissional com todos os dados da empresa preenchidos automaticamente! 🎉

---

## 🆘 Suporte

**Problemas comuns:**

### "Erro 003" ao gerar PDF
- ✅ **Solução:** Execute as seeds
```bash
vendor\bin\phinx seed:run -s AddAdmsPages -c database\phinx.php
vendor\bin\phinx seed:run -s AddAdmsGroupsPages -c database\phinx.php
```

### PDF sem dados da empresa
- ✅ **Solução:** Configure as variáveis no `.env`

### Layout quebrado
- ✅ **Solução:** Verifique se a biblioteca mPDF está instalada
```bash
composer require mpdf/mpdf
```

---

## 📝 Changelog

### Versão 2.0 (Atual) - Layout Premium
- ✅ Design corporativo moderno
- ✅ 2 páginas estruturadas
- ✅ Cabeçalho e rodapé profissionais
- ✅ Valor em destaque premium (dourado)
- ✅ Termos e condições completos
- ✅ Assinatura bilateral
- ✅ Cards com ícones
- ✅ Tabelas estilizadas
- ✅ Badges de status

### Versão 1.0 - MVP
- Layout simples em 1 página

---

**🎨 Design criado especialmente para combinar com a identidade visual do projeto (#2E9263)!**

