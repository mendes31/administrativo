# Modelo de autorização

## Princípio

ACL de página responde se uma conta pode acessar uma capacidade geral.
Autorização de domínio responde se ela pode executar uma ação sobre um recurso
específico.

```text
ator + ação + recurso + relação + escopo + sensibilidade + contexto
```

Ausência de regra autorizadora resulta em negação.

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
- superusuário não ignora automaticamente segregação legal ou médica.

## Prioridades

1. currículos e anexos;
2. candidatos, vagas, candidaturas e entrevistas;
3. documentos de folha;
4. solicitações do colaborador;
5. desempenho, feedbacks e PDI;
6. treinamentos e certificados;
7. SST e dados médicos;
8. Canal de Denúncias;
9. exportações e analytics.

## Critério de saída

- ações padronizadas publicadas;
- matrizes dos recursos críticos preenchidas;
- exceções do roteador catalogadas;
- policies e escopos com estratégia de testes;
- backlog de lacunas priorizado.
