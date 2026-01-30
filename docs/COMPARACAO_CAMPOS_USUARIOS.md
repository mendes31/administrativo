# Comparação: Campos de Cadastro vs Edição de Usuários

## Campos Presentes em AMBOS os Formulários

| Campo | Create | Update | Observações |
|-------|--------|--------|-------------|
| **name** (Nome) | ✅ col-md-12 | ✅ col-4 | Layout diferente |
| **email** (Email) | ✅ col-md-12 | ✅ col-4 | Layout diferente |
| **username** (Usuário) | ✅ col-md-12 | ✅ col-4 | Layout diferente |
| **cpf** (CPF) | ✅ col-md-6 | ✅ col-md-6 | ✅ Igual |
| **celular** (Celular) | ✅ col-md-6 | ✅ col-md-6 | ✅ Igual |
| **user_department_id** (Departamento) | ✅ col-md-6 | ✅ col-md-6 | ✅ Igual |
| **user_position_id** (Cargo) | ✅ col-md-6 | ✅ col-md-6 | ✅ Igual |
| **immediate_supervisor_id** (Supervisor Imediato) | ✅ col-md-6 | ✅ col-md-6 | ✅ Igual |
| **tentativas_login** (Tentativas de Login) | ✅ col-md-4 | ✅ col-md-4 | ✅ Igual (readonly) |
| **data_nascimento** (Data de Nascimento) | ✅ col-md-6 | ✅ col-md-4 | Layout diferente |
| **status** (Status) | ✅ col-md-3 | ✅ col-md-3 | ✅ Igual (switch) |
| **bloqueado** (Bloqueado) | ✅ col-md-3 | ✅ col-md-3 | ✅ Igual (switch) |
| **senha_nunca_expira** (Senha Nunca Expira) | ✅ col-md-3 | ✅ col-md-3 | ✅ Igual (switch) |
| **modificar_senha_proximo_logon** | ✅ col-md-3 | ✅ col-md-3 | ✅ Igual (switch) |

---

## Campos Presentes APENAS no CREATE (Cadastro)

| Campo | Motivo |
|-------|--------|
| **password** (Senha) | ✅ Faz sentido - só precisa definir senha no cadastro |
| **confirm_password** (Confirmar Senha) | ✅ Faz sentido - só precisa confirmar no cadastro |
| **image** (Imagem do Usuário) | ⚠️ **DIFERENÇA** - Presente no create, mas comentado no update |

---

## Campos Presentes APENAS no UPDATE (Edição)

| Campo | Motivo |
|-------|--------|
| **id** (ID - hidden) | ✅ Faz sentido - só existe na edição |
| **data_admissao** (Data de Admissão) | ⚠️ **DIFERENÇA** - Não está no create |
| **data_desligamento** (Data de Desligamento) | ⚠️ **DIFERENÇA** - Não está no create |
| **motivo_desligamento** (Motivo do Desligamento) | ⚠️ **DIFERENÇA** - Não está no create |

---

## Diferenças Identificadas

### 1. ⚠️ Campo "Imagem do Usuário" (image)

**CREATE:** Campo presente e funcional
```php
<div class="col-md-6">
    <label for="image" class="form-label">Imagem do Usuário</label>
    <input type="file" name="image" class="form-control" id="image" accept="image/*">
    <small class="form-text text-muted">Formatos permitidos: JPG, PNG, GIF. Tamanho máximo: 2MB.</small>
</div>
```

**UPDATE:** Campo está comentado
```php
<!-- <div class="col-md-6">
    <label for="image" class="form-label">Imagem do Usuário</label>
    ...
</div> -->
```

**Recomendação:** Descomentar e ativar o campo no UPDATE, ou remover do CREATE se não for necessário.

---

### 2. ⚠️ Campos de Data de Admissão/Desligamento

**CREATE:** Não possui
- `data_admissao`
- `data_desligamento`
- `motivo_desligamento`

**UPDATE:** Possui todos os três campos

**Recomendação:** Adicionar `data_admissao` no CREATE, pois é uma informação importante no momento do cadastro.

---

### 3. ⚠️ Layout Diferente nos Campos Principais

**CREATE:**
- `name`, `email`, `username`: `col-md-12` (largura total)

**UPDATE:**
- `name`, `email`, `username`: `col-4` (1/3 da largura cada)

**Recomendação:** Padronizar o layout. Sugestão: usar `col-md-4` em ambos para consistência.

---

## Resumo das Ações Necessárias

### ✅ Campos que estão corretos (diferentes por design):
- `password` e `confirm_password` - só no CREATE ✅
- `id` - só no UPDATE ✅

### ⚠️ Campos que precisam ser ajustados:

1. **`image` (Imagem do Usuário)**
   - **Ação:** Descomentar no UPDATE ou remover do CREATE
   - **Recomendação:** Manter em ambos

2. **`data_admissao` (Data de Admissão)**
   - **Ação:** Adicionar no CREATE
   - **Motivo:** Informação importante no momento do cadastro

3. **`data_desligamento` e `motivo_desligamento`**
   - **Ação:** Manter apenas no UPDATE (faz sentido)
   - **Status:** ✅ Correto como está

4. **Layout dos campos principais**
   - **Ação:** Padronizar `name`, `email`, `username` para `col-md-4` em ambos

---

## Recomendações Finais

1. ✅ **Manter diferentes:** `password`, `confirm_password` (só CREATE), `id` (só UPDATE)
2. ⚠️ **Adicionar no CREATE:** `data_admissao`
3. ⚠️ **Ativar no UPDATE:** Campo `image` (descomentar)
4. ⚠️ **Padronizar layout:** Usar `col-md-4` para `name`, `email`, `username` em ambos

