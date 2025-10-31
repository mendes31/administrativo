# Validação de Vínculos de Treinamentos - Correções Implementadas

## Problema Identificado

O sistema estava criando vínculos de treinamentos mesmo quando:
1. **Usuários estavam inativos** - após reativação ou troca de cargo, vínculos eram recriados
2. **Treinamentos estavam inativos** - após reativação, vínculos eram recriados para usuários mesmo que o treinamento estivesse inativo
3. **Usuários inativos apareciam na listagem** - a query não filtrava usuários inativos
4. **Treinamentos inativos apareciam na listagem** - a query não filtrava treinamentos inativos

## Soluções Implementadas

### 1. Filtro na Query de Listagem (`getTrainingStatusByUser`)

**Arquivo**: `app/adms/Models/Repository/TrainingUsersRepository.php`

**Mudança**: Adicionado filtro para excluir usuários inativos e treinamentos inativos na query SQL:

```sql
INNER JOIN adms_users u ON u.id = tu.adms_user_id AND u.status = "Ativo"
INNER JOIN adms_trainings t ON t.id = tu.adms_training_id AND t.ativo = 1
```

**Resultado**: Usuários inativos e treinamentos inativos não aparecem mais na listagem de status de treinamentos.

### 2. Validação em `recreateLinksForUser`

**Arquivo**: `app/adms/Models/Repository/TrainingUsersRepository.php`

**Mudança**: Adicionada verificação antes de recriar vínculos:
- Verifica se o usuário está ativo
- Verifica se cada treinamento está ativo antes de criar vínculo

**Resultado**: Vínculos não são recriados para usuários inativos ou treinamentos inativos.

### 3. Validação em `recreateLinksForReactivatedUser`

**Arquivo**: `app/adms/Controllers/trainings/TrainingMatrixService.php`

**Mudança**: Adicionada verificação:
- Verifica se o usuário está ativo antes de processar
- Verifica se cada treinamento está ativo antes de criar vínculo

**Resultado**: Quando um usuário é reativado, apenas treinamentos ativos são vinculados.

### 4. Validação em `recreateLinksForReactivatedTraining`

**Arquivo**: `app/adms/Controllers/trainings/TrainingMatrixService.php`

**Mudança**: Adicionada verificação:
- Verifica se o treinamento está ativo antes de processar
- Verifica se cada usuário está ativo antes de criar vínculo

**Resultado**: Quando um treinamento é reativado, apenas usuários ativos recebem vínculos.

### 5. Validação em `checkAndFixUserLinks` (Troca de Cargo)

**Arquivo**: `app/adms/Controllers/trainings/TrainingMatrixService.php`

**Mudança**: Adicionada verificação:
- Verifica se o usuário está ativo antes de processar
- Verifica se cada treinamento está ativo antes de criar vínculo

**Resultado**: Quando há troca de cargo, apenas treinamentos ativos são vinculados ao usuário ativo.

### 6. Validação em `recreateLinksForTraining`

**Arquivo**: `app/adms/Models/Repository/TrainingUsersRepository.php`

**Mudança**: Adicionada verificação:
- Verifica se o treinamento está ativo antes de processar
- Verifica se cada usuário está ativo antes de criar vínculo

**Resultado**: Apenas usuários ativos recebem vínculos de treinamentos ativos.

### 7. Validação em `updateMatrixForUser`

**Arquivo**: `app/adms/Controllers/trainings/TrainingMatrixService.php`

**Mudança**: Adicionada verificação:
- Verifica se o usuário está ativo antes de processar
- Verifica se cada treinamento está ativo antes de criar vínculo

**Resultado**: A matriz é atualizada apenas para usuários e treinamentos ativos.

### 8. Método de Limpeza de Vínculos Órfãos

**Arquivo**: `app/adms/Models/Repository/TrainingUsersRepository.php`

**Novo Método**: `cleanupOrphanLinks()`

**Funcionalidade**: Remove vínculos órfãos (usuários inativos ou treinamentos inativos), mantendo apenas vínculos concluídos.

**Uso**: Este método pode ser executado periodicamente para manter a integridade dos dados.

```php
$trainingUsersRepo = new TrainingUsersRepository();
$results = $trainingUsersRepo->cleanupOrphanLinks();
// Retorna: ['removed_inactive_users' => X, 'removed_inactive_trainings' => Y, 'total_removed' => Z]
```

## Regras de Troca de Cargo

### Comportamento Atual

Quando um usuário troca de cargo:

1. **Vínculos individuais** são convertidos para vínculos por cargo (se o treinamento for obrigatório para o novo cargo)
2. **Novos vínculos** são criados apenas para treinamentos obrigatórios do novo cargo que o usuário ainda não possui
3. **Vínculos antigos** que não são mais obrigatórios são removidos (exceto concluídos)
4. **Validações aplicadas**:
   - Usuário deve estar ativo
   - Treinamento deve estar ativo
   - Apenas treinamentos obrigatórios para o novo cargo são vinculados

### Exemplo

**Cenário**: Usuário "João" troca de cargo de "Analista" para "Gerente"

**Treinamentos obrigatórios para "Analista"**:
- Treinamento A (individual)
- Treinamento B (por cargo)

**Treinamentos obrigatórios para "Gerente"**:
- Treinamento B (por cargo)
- Treinamento C (novo)

**Resultado**:
- Treinamento A: Mantido como individual (se já tinha) ou removido (se era por cargo)
- Treinamento B: Mantido (continua obrigatório)
- Treinamento C: Criado novo vínculo

## Como Executar Limpeza de Vínculos Órfãos

### Opção 1: Via Código PHP

```php
use App\adms\Models\Repository\TrainingUsersRepository;

$trainingUsersRepo = new TrainingUsersRepository();
$results = $trainingUsersRepo->cleanupOrphanLinks();

echo "Vínculos removidos:\n";
echo "- Usuários inativos: " . $results['removed_inactive_users'] . "\n";
echo "- Treinamentos inativos: " . $results['removed_inactive_trainings'] . "\n";
echo "- Total: " . $results['total_removed'] . "\n";
```

### Opção 2: Via Script CLI (Recomendado)

Crie um script em `scripts/cleanup_orphan_links.php`:

```php
<?php
require __DIR__ . '/../vendor/autoload.php';

use App\adms\Models\Repository\TrainingUsersRepository;
use App\adms\Helpers\EnvLoader;

EnvLoader::load();

$trainingUsersRepo = new TrainingUsersRepository();
$results = $trainingUsersRepo->cleanupOrphanLinks();

echo "Limpeza concluída!\n";
echo "Vínculos removidos:\n";
echo "- Usuários inativos: " . $results['removed_inactive_users'] . "\n";
echo "- Treinamentos inativos: " . $results['removed_inactive_trainings'] . "\n";
echo "- Total: " . $results['total_removed'] . "\n";
```

Execute:
```bash
php scripts/cleanup_orphan_links.php
```

## Impacto das Mudanças

### Positivo
- ✅ Usuários inativos não aparecem mais na listagem
- ✅ Treinamentos inativos não aparecem mais na listagem
- ✅ Vínculos não são recriados para usuários/treinamentos inativos
- ✅ Sistema mais consistente e previsível
- ✅ Melhor performance (menos dados processados)

### Observações
- ⚠️ Vínculos concluídos são mantidos mesmo se usuário/treinamento estiver inativo (histórico preservado)
- ⚠️ Ao reativar usuário ou treinamento, vínculos são recriados automaticamente apenas para entidades ativas
- ⚠️ Execute a limpeza de vínculos órfãos periodicamente para manter dados limpos

## Testes Recomendados

1. **Inativar usuário** → Verificar se vínculos são removidos e não aparecem na listagem
2. **Reativar usuário** → Verificar se apenas treinamentos ativos são vinculados
3. **Inativar treinamento** → Verificar se vínculos são removidos e não aparecem na listagem
4. **Reativar treinamento** → Verificar se apenas usuários ativos recebem vínculos
5. **Trocar cargo de usuário** → Verificar se apenas treinamentos ativos do novo cargo são vinculados
6. **Executar limpeza de órfãos** → Verificar se vínculos órfãos são removidos corretamente

## Manutenção Futura

1. **Cron Job**: Configure um cron job para executar `cleanupOrphanLinks()` periodicamente (ex: diariamente)
2. **Monitoramento**: Monitore os logs para verificar quantos vínculos órfãos são removidos
3. **Alertas**: Configure alertas se muitos vínculos órfãos forem encontrados (pode indicar problema no processo de inativação)

