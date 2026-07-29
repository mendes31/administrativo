# Modelo de autorização

## Princípio

ACL de página responde se uma conta pode acessar uma capacidade geral.
Autorização de domínio responde se ela pode executar uma ação sobre um recurso
específico.

```text
ator + ação + recurso + relação + escopo + sensibilidade + contexto
```

Ausência de regra autorizadora resulta em negação.

## Acesso total vs ACL de página

Dois atores têm **acesso full** às capacidades de página e **não dependem** da
matriz ACL (`adms_access_levels_pages`) para entrar em telas privadas:

| Ator | Como identifica | Efeito |
|------|-----------------|--------|
| **Super Administrador** | nível `adms_access_levels.id = 1` | bypass ACL de página |
| **Super usuário** | flag no cadastro (`adms_users` / sessão) | mesmo efeito prático |

Implementação: `UserAccessHelper::hasFullSystemAccess()`.

Demais níveis **sempre** passam pela ACL. Mesmo com acesso full, autorização
por objeto e segregações legais/médicas continuam aplicáveis (mínimo privilégio
no domínio).

## Nascimento de páginas (`adms_pages`)

Regra operacional obrigatória (Plano Diretor + este modelo):

1. **`public_page = 1`** — acessível sem autenticação/ACL de nível (ex.: portal
   público). Não confundir com liberar a todos os níveis autenticados.
2. **`default_page = 1`** — página padrão do sistema para níveis autenticados,
   conforme o mecanismo já existente de páginas default.
3. **Demais páginas** (`public_page = 0` e `default_page = 0`) — **nascem sem
   `permission = 1`** em qualquer nível de acesso.

Consequências:

- migrations/seeds **registram** a página em `adms_pages`; **não** concedem ACL
  em massa (`grantPageToLevels`, cópia a partir de outra página, etc.), salvo
  ADR explícito;
- liberação aos cargos necessários é feita **no sistema** (matriz de permissões
  do nível), pelo administrador;
- Super Administrador / Super usuário já acessam sem essa liberação.

## Camadas

1. **Autenticação:** identifica ator e força necessária;
2. **Página/endpoint:** controla acesso à capacidade;
3. **Policy de domínio:** decide ação no recurso;
4. **Escopo da consulta:** limita registros no repository;
5. **Proteção da resposta:** limita campos, anexos e exportações.

Endpoints AJAX, jobs, crons, APIs e downloads aplicam a mesma policy das telas.

## Ações padronizadas

- `list`
- `view`
- `create`
- `update`
- `transition`
- `approve`
- `download`
- `manage_access`
- `delete`
- `restore`
- `audit`

Domínios podem adicionar ações específicas. Verbos genéricos como `manage`
devem ser evitados quando agruparem riscos diferentes.

## Papéis, relações e contexto

Papéis não bastam. Exemplos:

- recrutador designado atua em vagas atribuídas;
- gestor da vaga avalia candidaturas daquela vaga;
- entrevistador acessa somente dados necessários;
- colaborador visualiza seus documentos;
- RH atua dentro do escopo definido;
- acesso técnico não concede automaticamente dados médicos ou denúncias.

## Matriz obrigatória por domínio

| Recurso | Ação | Papéis/relações | Escopo | Condições | Campos protegidos | Auditoria |
|---|---|---|---|---|---|---|
| Exemplo | visualizar | titular ou autorizado | próprio/atribuído | estado permitido | dados sensíveis | sim/não |

## Requisitos

- policies independem de HTML;
- controllers não duplicam regras complexas;
- consultas recebem escopo autorizado;
- downloads autorizam pelo registro, não pelo caminho;
- atores técnicos são identificáveis;
- negações não revelam recurso sensível;
- políticas possuem testes positivos e negativos;
- Super Administrador e Super usuário não passam pela ACL de página, mas **não**
  ignoram automaticamente segregação legal ou médica no domínio;
- páginas novas não públicas e não default nascem sem permissão nos níveis;
  concessão só via matriz no sistema (ou ADR);
- ao criar página nova, analisar se o grupo ACL existente ainda é adequado ou se
  deve nascer um grupo novo (ver Plano Diretor e
  [PLANO_SEPARACAO_GRUPOS_PAGINAS.md](PLANO_SEPARACAO_GRUPOS_PAGINAS.md)).

## Grupos de páginas

`adms_groups_pages` organiza a matriz de permissões (não substitui o menu).

- Preferir grupos por **capacidade/módulo** alinhados ao menu (ex.: Talentos,
  Portal/Solicitações), não um único “catch-all” de domínio.
- Meta operacional: ~20–50 páginas por grupo; acima de ~80 revisar cisão.
- Novas páginas: **sempre** decidir grupo existente vs novo grupo antes do
  insert em `adms_pages` (gate do Plano Diretor).

## Prioridades

1. currículos e anexos — **matriz Talentos publicada**;
2. candidatos, vagas, candidaturas e entrevistas — **matriz Talentos publicada**;
3. documentos de folha;
4. solicitações do colaborador;
5. desempenho, feedbacks e PDI;
6. treinamentos e certificados;
7. SST e dados médicos;
8. Canal de Denúncias;
9. exportações e analytics.

Matriz detalhada vigente: [`docs/09_DOMINIOS/Gestao_Pessoas/MATRIZ_AUTORIZACAO_TALENTOS.md`](../09_DOMINIOS/Gestao_Pessoas/MATRIZ_AUTORIZACAO_TALENTOS.md).

## Critério de saída

- ações padronizadas publicadas;
- matrizes dos recursos críticos preenchidas;
- exceções do roteador catalogadas;
- policies e escopos com estratégia de testes;
- backlog de lacunas priorizado.
