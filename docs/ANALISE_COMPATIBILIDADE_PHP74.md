# Análise: Compatibilidade com PHP 7.4

## 🔍 Problema Identificado

**Erro atual:**
```
Fatal error: Composer detected issues in your platform: 
Your Composer dependencies require a PHP version ">= 8.2.0". 
You are running 7.4.33.
```

**Causa:** O projeto foi desenvolvido com recursos do PHP 8.0+ e algumas dependências requerem PHP 8.2+.

---

## 📋 Recursos PHP 8.0+ Encontrados no Código

### 1. ⚠️ **`match()` Expression** (PHP 8.0+)

**Encontrado em:** 55 arquivos

**Exemplos:**
```php
// app/adms/Helpers/SendWhatsAppService.php (linha 58)
return match ($config['api_provider']) {
    'Evolution' => self::sendViaEvolution(...),
    'Twilio' => self::sendViaTwilio(...),
    'Meta' => self::sendViaMeta(...),
    default => ['success' => false, 'error' => '...']
};

// app/adms/Views/lgpd/consentimentos/list.php (linha 170)
$statusClass = match($consentimento['status']) {
    'Ativo' => 'success',
    'Revogado' => 'danger',
    default => 'secondary'
};
```

**Solução:** Substituir por `switch` ou `if/elseif`

**Arquivos principais:**
- `app/adms/Helpers/SendWhatsAppService.php` (crítico)
- `app/adms/Views/lgpd/consentimentos/list.php`
- `app/adms/Views/reports/list.php`
- `app/adms/Views/rooms/*.php` (vários)
- `app/adms/Views/performance/*.php` (vários)
- `app/adms/Views/portal/*.php` (vários)
- E mais 40+ arquivos

---

### 2. ⚠️ **`str_starts_with()`** (PHP 8.0+)

**Encontrado em:** 3 arquivos

**Exemplos:**
```php
// app/adms/Helpers/SendWhatsAppService.php (linha 49)
if (!str_starts_with($phoneNumber, '55')) {
    $phoneNumber = '55' . $phoneNumber;
}

// app/adms/Helpers/SendWhatsAppService.php (linha 95)
if (str_starts_with($config['api_url'], 'https://')) {
    // ...
}

// app/adms/Controllers/dashboards/GetFilterOptions.php (linha 19)
if (str_starts_with($trimmed, '"') && str_ends_with($trimmed, '"')) {
    // ...
}
```

**Solução:** Criar polyfill ou substituir por `strpos() === 0`

---

### 3. ⚠️ **`str_ends_with()`** (PHP 8.0+)

**Encontrado em:** 1 arquivo

**Exemplo:**
```php
// app/adms/Controllers/dashboards/GetFilterOptions.php (linha 19)
if (str_starts_with($trimmed, '"') && str_ends_with($trimmed, '"')) {
    // ...
}
```

**Solução:** Criar polyfill ou substituir por `substr() ===`

---

### 4. ⚠️ **`str_contains()`** (PHP 8.0+)

**Encontrado em:** 3 arquivos

**Exemplos:**
```php
// app/adms/Models/Repository/ResetPasswordRepository.php (linha 21)
$isEmail = str_contains($identificador, '@');

// app/adms/Controllers/dashboards/ExecuteDashboard.php (linha 469)
if (str_contains($identifier, '.')) {
    // ...
}

// app/adms/Controllers/dashboards/GetFilterOptions.php (linha 24)
if (str_contains($trimmed, '.')) {
    // ...
}
```

**Solução:** Criar polyfill ou substituir por `strpos() !== false`

---

### 5. ⚠️ **Type Hints Union Types** (PHP 8.0+)

**Encontrado em:** 290 arquivos (456 ocorrências)

**Exemplos:**
```php
// app/adms/Controllers/users/CreateUser.php
private array|string|null $data = null;

// app/adms/Models/Repository/UsersRepository.php
public function getUser(int $id): array|bool
```

**Solução:** Remover union types ou usar docblocks

**Nota:** Este é o mais crítico, mas pode ser ignorado se o PHP 7.4 não validar strict types. Precisamos testar.

---

### 6. ⚠️ **Dependências do Composer**

**Problema:** Algumas dependências requerem PHP 8.1+ ou 8.2+

**Verificações necessárias:**
- `monolog/monolog: ^3.9` - Verificar versão compatível
- `vlucas/phpdotenv: ^5.6` - Verificar versão compatível
- `phpoffice/phpspreadsheet: ^4.4` - Verificar versão compatível
- `mpdf/mpdf: ^8.2` - Verificar versão compatível
- `dompdf/dompdf: ^3.1` - Verificar versão compatível
- `phpunit/phpunit: ^12.2` (dev) - Requer PHP 8.2+

---

## 🔧 Soluções Necessárias

### Solução 1: Criar Polyfills (Já criado)

**Arquivo:** `app/adms/Helpers/Php74Compatibility.php`

**Conteúdo:**
```php
// Polyfills para str_starts_with, str_ends_with, str_contains
```

**Status:** ✅ Criado, mas precisa ser carregado no `index.php`

---

### Solução 2: Substituir `match()` por `switch`

**Arquivo crítico:** `app/adms/Helpers/SendWhatsAppService.php`

**Antes:**
```php
return match ($config['api_provider']) {
    'Evolution' => self::sendViaEvolution(...),
    'Twilio' => self::sendViaTwilio(...),
    'Meta' => self::sendViaMeta(...),
    default => ['success' => false, 'error' => '...']
};
```

**Depois:**
```php
switch ($config['api_provider']) {
    case 'Evolution':
        return self::sendViaEvolution($phoneNumber, $message, $config, $options);
    case 'Twilio':
        return self::sendViaTwilio($phoneNumber, $message, $config, $options);
    case 'Meta':
        return self::sendViaMeta($phoneNumber, $message, $config, $options);
    default:
        return ['success' => false, 'error' => 'Provedor desconhecido: ' . $config['api_provider']];
}
```

**Total:** ~55 arquivos precisam ser ajustados

---

### Solução 3: Ajustar `composer.json`

**Adicionar:**
```json
{
    "require": {
        "php": ">=7.4",
        ...
    },
    "config": {
        "platform": {
            "php": "7.4.33"
        }
    }
}
```

**Ajustar versões de dependências:**
- Verificar versões compatíveis com PHP 7.4
- Possivelmente downgrade de algumas dependências

---

### Solução 4: Carregar Polyfills no `index.php`

**Adicionar antes do autoload:**
```php
// Carregar polyfills para PHP 7.4 (antes do autoload)
require_once __DIR__ . '/app/adms/Helpers/Php74Compatibility.php';
```

---

## 📊 Resumo dos Ajustes Necessários

| Item | Quantidade | Prioridade | Complexidade |
|------|------------|------------|--------------|
| **match() → switch** | ~55 arquivos | 🔴 Alta | Média |
| **str_starts_with()** | 3 arquivos | 🟡 Média | Baixa (polyfill) |
| **str_ends_with()** | 1 arquivo | 🟡 Média | Baixa (polyfill) |
| **str_contains()** | 3 arquivos | 🟡 Média | Baixa (polyfill) |
| **Union Types** | 290 arquivos | 🟢 Baixa* | Alta |
| **composer.json** | 1 arquivo | 🔴 Alta | Baixa |
| **Dependências** | Várias | 🔴 Alta | Média |

*Union Types podem não causar erro fatal se não houver strict types

---

## 🎯 Plano de Ação Recomendado

### Fase 1: Preparação (Crítico)
1. ✅ Criar polyfills (já feito)
2. ⏳ Carregar polyfills no `index.php`
3. ⏳ Ajustar `composer.json` (adicionar `"php": ">=7.4"`)

### Fase 2: Arquivos Críticos
1. ⏳ `SendWhatsAppService.php` - Substituir `match()` e `str_starts_with()`
2. ⏳ `ResetPasswordRepository.php` - Substituir `str_contains()`
3. ⏳ `GetFilterOptions.php` - Substituir `str_starts_with()` e `str_ends_with()`
4. ⏳ `ExecuteDashboard.php` - Substituir `str_contains()`

### Fase 3: Views (Baixa Prioridade)
1. ⏳ Substituir `match()` em views (55 arquivos)
2. ⏳ Testar se union types causam erro

### Fase 4: Dependências
1. ⏳ Verificar e ajustar versões no `composer.json`
2. ⏳ Executar `composer update` com PHP 7.4
3. ⏳ Resolver conflitos de dependências

---

## ⚠️ Riscos e Considerações

### 1. Dependências Incompatíveis

**Problema:** Algumas dependências podem não ter versões compatíveis com PHP 7.4

**Exemplos:**
- `phpunit/phpunit: ^12.2` requer PHP 8.2+ (mas é dev, pode ignorar)
- `monolog/monolog: ^3.9` - Verificar se há versão 7.4 compatível
- `phpoffice/phpspreadsheet: ^4.4` - Verificar compatibilidade

**Solução:** Downgrade de versões ou encontrar alternativas

---

### 2. Union Types

**Problema:** 290 arquivos usam `array|string|null`

**Risco:** Pode não causar erro fatal se não houver `declare(strict_types=1)`

**Teste necessário:** Verificar se PHP 7.4 aceita (com warning) ou rejeita (fatal error)

**Solução se necessário:** Remover union types ou usar docblocks

---

### 3. Performance

**Impacto:** Polyfills podem ter pequeno impacto de performance

**Mitigação:** Usar funções nativas quando disponíveis (verificar versão PHP)

---

## 📝 Arquivos que Precisam de Ajuste

### Arquivos Críticos (Funcionalidade Core)

1. **`app/adms/Helpers/SendWhatsAppService.php`**
   - `match()` (linha 58)
   - `str_starts_with()` (linhas 49, 95)

2. **`app/adms/Models/Repository/ResetPasswordRepository.php`**
   - `str_contains()` (linha 21)

3. **`app/adms/Controllers/dashboards/GetFilterOptions.php`**
   - `str_starts_with()` (linha 19)
   - `str_ends_with()` (linha 19)
   - `str_contains()` (linha 24)

4. **`app/adms/Controllers/dashboards/ExecuteDashboard.php`**
   - `str_contains()` (linha 469)

5. **`app/adms/Helpers/CountryHelper.php`**
   - `str_starts_with()` (linha 115)

### Arquivos de Views (Menos Crítico)

- `app/adms/Views/lgpd/consentimentos/list.php`
- `app/adms/Views/reports/list.php`
- `app/adms/Views/rooms/*.php` (vários)
- `app/adms/Views/performance/*.php` (vários)
- `app/adms/Views/portal/*.php` (vários)
- E mais ~40 arquivos

---

## 🔄 Exemplo de Conversão

### Exemplo 1: `match()` → `switch`

**Antes:**
```php
$statusClass = match($consentimento['status']) {
    'Ativo' => 'success',
    'Revogado' => 'danger',
    default => 'secondary'
};
```

**Depois:**
```php
switch($consentimento['status']) {
    case 'Ativo':
        $statusClass = 'success';
        break;
    case 'Revogado':
        $statusClass = 'danger';
        break;
    default:
        $statusClass = 'secondary';
        break;
}
```

---

### Exemplo 2: `str_starts_with()` → Polyfill

**Antes:**
```php
if (!str_starts_with($phoneNumber, '55')) {
    $phoneNumber = '55' . $phoneNumber;
}
```

**Depois (com polyfill):**
```php
// Funciona automaticamente se polyfill estiver carregado
if (!str_starts_with($phoneNumber, '55')) {
    $phoneNumber = '55' . $phoneNumber;
}
```

**Ou (sem polyfill):**
```php
if (strpos($phoneNumber, '55') !== 0) {
    $phoneNumber = '55' . $phoneNumber;
}
```

---

### Exemplo 3: `str_contains()` → Polyfill

**Antes:**
```php
$isEmail = str_contains($identificador, '@');
```

**Depois (com polyfill):**
```php
// Funciona automaticamente se polyfill estiver carregado
$isEmail = str_contains($identificador, '@');
```

**Ou (sem polyfill):**
```php
$isEmail = strpos($identificador, '@') !== false;
```

---

## 📦 Ajustes no `composer.json`

### Versão Atual:
```json
{
    "require": {
        "monolog/monolog": "^3.9",
        "vlucas/phpdotenv": "^5.6",
        "robmorgan/phinx": "^0.16.6",
        "rakit/validation": "^1.4",
        "phpmailer/phpmailer": "^6.9",
        "phpoffice/phpspreadsheet": "^4.4",
        "mpdf/mpdf": "^8.2",
        "dompdf/dompdf": "^3.1"
    }
}
```

### Versão Ajustada (Sugestão):
```json
{
    "require": {
        "php": ">=7.4",
        "monolog/monolog": "^2.8",
        "vlucas/phpdotenv": "^5.4",
        "robmorgan/phinx": "^0.16.6",
        "rakit/validation": "^1.4",
        "phpmailer/phpmailer": "^6.6",
        "phpoffice/phpspreadsheet": "^1.29",
        "mpdf/mpdf": "^8.0",
        "dompdf/dompdf": "^2.0"
    },
    "config": {
        "platform": {
            "php": "7.4.33"
        }
    }
}
```

**⚠️ Nota:** Versões acima são sugestões. Precisam ser testadas.

---

## ✅ Checklist de Ajustes

### Preparação
- [ ] Criar polyfills (✅ Já criado)
- [ ] Carregar polyfills no `index.php`
- [ ] Ajustar `composer.json` (adicionar `"php": ">=7.4"`)

### Arquivos Críticos
- [ ] `SendWhatsAppService.php` - `match()` e `str_starts_with()`
- [ ] `ResetPasswordRepository.php` - `str_contains()`
- [ ] `GetFilterOptions.php` - `str_starts_with()`, `str_ends_with()`, `str_contains()`
- [ ] `ExecuteDashboard.php` - `str_contains()`
- [ ] `CountryHelper.php` - `str_starts_with()`

### Views (Opcional - pode funcionar mesmo com match)
- [ ] Substituir `match()` em views (55 arquivos)
- [ ] Ou testar se funciona mesmo com `match()` (pode dar erro de sintaxe)

### Dependências
- [ ] Verificar compatibilidade de cada dependência
- [ ] Ajustar versões no `composer.json`
- [ ] Executar `composer update` em ambiente PHP 7.4
- [ ] Resolver conflitos

### Testes
- [ ] Testar sistema completo em PHP 7.4
- [ ] Verificar se union types causam erro
- [ ] Testar funcionalidades críticas

---

## 🎯 Priorização

### 🔴 **ALTA PRIORIDADE** (Sistema não funciona sem)
1. Ajustar `composer.json`
2. Carregar polyfills
3. `SendWhatsAppService.php` (WhatsApp não funciona)
4. Verificar dependências críticas

### 🟡 **MÉDIA PRIORIDADE** (Funcionalidades específicas)
1. `ResetPasswordRepository.php` (Recuperação de senha)
2. `GetFilterOptions.php` (Dashboards)
3. `ExecuteDashboard.php` (Dashboards)

### 🟢 **BAIXA PRIORIDADE** (Pode funcionar mesmo com erro)
1. Views com `match()` (pode dar erro de sintaxe, mas não quebra tudo)
2. Union types (pode funcionar com warning)

---

## 📚 Referências

- **PHP 7.4:** Última versão da série 7.x
- **PHP 8.0:** Introduziu `match()`, `str_starts_with()`, `str_contains()`, union types
- **PHP 8.1:** Introduziu enums, readonly properties
- **PHP 8.2:** Introduziu readonly classes, disjunctive normal form types

---

## ⚠️ **IMPORTANTE**

**Antes de fazer os ajustes:**
1. Fazer backup completo do projeto
2. Testar em ambiente isolado primeiro
3. Verificar se a hospedagem realmente tem apenas PHP 7.4 (pode ter múltiplas versões)
4. Considerar pedir upgrade de PHP na hospedagem (mais fácil que downgrade do código)

---

## 💡 Alternativa: Upgrade de PHP na Hospedagem

**Vantagens:**
- ✅ Não precisa alterar código
- ✅ Melhor performance
- ✅ Mais seguro
- ✅ Suporte a recursos modernos

**Desvantagens:**
- ⚠️ Pode ter custo adicional
- ⚠️ Pode precisar de migração

**Recomendação:** Verificar se é possível atualizar PHP na hospedagem antes de fazer downgrade do código.


