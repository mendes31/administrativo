# 📋 Importação de Níveis de Acesso com Permissões

## 🎯 Visão Geral

O sistema de importação de níveis de acesso foi atualizado para incluir **permissões predefinidas** ao criar novos níveis. **Níveis existentes terão suas permissões atualizadas** (mantendo as atuais + adicionando as novas).

## 📊 Formato do CSV

### Estrutura das Colunas:
```csv
name;permissions
Líder de Equipe;1,2,3,4,5,8,29,30,31,32,35,36,37,38
Analista RH;1,2,4,29,30,31,35,36,37
```

### Colunas:
- **`name`**: Nome do nível de acesso
- **`permissions`**: IDs das páginas permitidas (separados por vírgula)

## 🔄 Comportamento da Importação

### **Níveis Novos:**
- ✅ **Criados** com as permissões especificadas no CSV
- ✅ **Dashboard (ID 1)** sempre incluído automaticamente
- ✅ **Páginas não especificadas** recebem permission = 0 (negado)

### **Níveis Existentes:**
- ✅ **Permissões substituídas completamente** pelas novas do CSV
- ✅ **Não mescla** permissões antigas e novas
- ✅ **Páginas não especificadas** recebem permission = 0 (negado)

### **Permissão Total (ALL):**
- ✅ **Use "ALL"** para dar permissão total a um nível
- ✅ **Equivale** a permission = 1 para todas as páginas

### **Exemplo de Atualização:**
```csv
# Primeira importação
Analista RH;1,2,4,29,30,31,35,36,37

# Segunda importação (mesmo nível)
Analista RH;1,2,4,29,30,31,35,36,37,13,14,15
```

**Resultado:** Analista RH terá permissões `1,2,4,29,30,31,35,36,37,13,14,15` (apenas as especificadas, resto = 0)

## 🔍 Mapeamento de IDs das Páginas

### Grupo 1: Dashboard
- **ID 1** = Dashboard (sempre incluído automaticamente)

### Grupo 2: Usuários
- **ID 2** = Listar Usuários
- **ID 3** = Cadastrar Usuário
- **ID 4** = Visualizar Usuário
- **ID 5** = Editar Usuário
- **ID 6** = Editar Senha do Usuário
- **ID 7** = Apagar Usuário
- **ID 8** = Troca Obrigatória de Senha
- **ID 9** = Remover Imagem do Usuário
- **ID 10** = Perfil do Usuário
- **ID 11** = Editar Senha via Perfil
- **ID 12** = Importar Usuários

### Grupo 3: Níveis de Acesso
- **ID 13** = Cadastrar Nível de Acesso
- **ID 14** = Listar Níveis de Acesso
- **ID 15** = Visualizar Nível de Acesso
- **ID 16** = Editar Nível de Acesso
- **ID 17** = Apagar Nível de Acesso
- **ID 18** = Importar Níveis de Acesso

### Grupo 4: Pacotes de Páginas
- **ID 19** = Cadastrar Pacote de Páginas
- **ID 20** = Listar Pacotes de Páginas
- **ID 21** = Visualizar Pacote de Páginas
- **ID 22** = Editar Pacote de Páginas
- **ID 23** = Apagar Pacote de Páginas

### Grupo 5: Grupos de Páginas
- **ID 24** = Cadastrar Grupo de Páginas
- **ID 25** = Listar Grupos de Páginas
- **ID 26** = Visualizar Grupo de Páginas
- **ID 27** = Editar Grupo de Páginas
- **ID 28** = Apagar Grupo de Páginas

### Grupo 8: Departamentos
- **ID 29** = Cadastrar Departamento
- **ID 30** = Listar Departamentos
- **ID 31** = Visualizar Departamento
- **ID 32** = Editar Departamento
- **ID 33** = Apagar Departamento
- **ID 34** = Importar Departamentos

### Grupo 10: Cargos
- **ID 35** = Cadastrar Cargo
- **ID 36** = Listar Cargos
- **ID 37** = Visualizar Cargo
- **ID 38** = Editar Cargo
- **ID 39** = Apagar Cargo
- **ID 40** = Importar Cargos

## 💡 Exemplos de Perfis

### Super Administrador (Nível 1)
```csv
Super Administrador;ALL
```
**Permissões:** Todas as páginas do sistema (permission = 1)

### Líder de Equipe
```csv
Líder de Equipe;1,2,3,4,5,8,29,30,31,32,35,36,37,38
```
**Permissões:** Dashboard + Usuários + Departamentos + Cargos

### Analista RH
```csv
Analista RH;1,2,4,29,30,31,35,36,37
```
**Permissões:** Dashboard + Visualizar Usuários + Departamentos + Cargos

### Gerente Administrativo
```csv
Gerente Administrativo;1,2,3,4,5,6,8,29,30,31,32,33,35,36,37,38,39
```
**Permissões:** Dashboard + Usuários + Departamentos + Cargos (com exclusão)

### Coordenador
```csv
Coordenador;1,2,3,4,5,8,13,14,15,16,29,30,31,32,35,36,37,38
```
**Permissões:** Dashboard + Usuários + Níveis de Acesso + Departamentos + Cargos

### Diretor (Administrador Completo)
```csv
Diretor;1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25,26,27,28,29,30,31,32,33,34,35,36,37,38,39,40
```
**Permissões:** Todas as páginas do sistema

## ⚠️ Regras Importantes

1. **ID 1 (Dashboard) é sempre incluído automaticamente** - não precisa colocar no CSV
2. **Use vírgulas para separar múltiplos IDs** (sem espaços)
3. **Use ponto e vírgula (;) para separar colunas**
4. **IDs devem ser números válidos** existentes no banco
5. **Ordem dos IDs não importa** - o sistema organiza automaticamente
6. **Níveis existentes são atualizados** - permissões antigas + novas
7. **Permissões duplicadas são automaticamente removidas**

## 🚀 Como Usar

1. **Baixe o template** clicando em "Baixar Template"
2. **Personalize** as permissões conforme sua necessidade
3. **Salve como UTF-8** para manter acentos
4. **Importe** no sistema
5. **Verifique o relatório** de importação

## 🔄 Re-importação

### **Primeira importação:**
```csv
Analista RH;1,2,4,29,30,31,35,36,37
```
**Resultado:** Nível criado com 9 permissões

### **Segunda importação (mesmo arquivo):**
```csv
Analista RH;1,2,4,29,30,31,35,36,37,13,14,15
```
**Resultado:** Permissões atualizadas: 9 → 12 (mantém as antigas + adiciona as novas)

## 🔧 Solução de Problemas

### Erro: "IDs inválidos"
- Verifique se os IDs existem no banco de dados
- Use apenas números maiores que 0

### Erro: "Formato inválido"
- Use ponto e vírgula (;) para separar colunas
- Use vírgula (,) para separar IDs
- Verifique se o arquivo está salvo como CSV

### Permissões não atualizadas
- Verifique se o nome do nível está exatamente igual
- O sistema diferencia maiúsculas/minúsculas

## 📞 Suporte

Para dúvidas ou problemas, consulte a documentação do sistema ou entre em contato com o administrador.
