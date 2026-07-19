# Gestão de Pessoas — Estratégia de testes

## Objetivo

Criar proteção proporcional ao risco antes das expansões funcionais.

## Baseline automatizado

Em 19/07/2026 foi criada a primeira suíte PHPUnit sem conexão ao banco:

- validação obrigatória de usuário e senha;
- proteção da URL de retorno do login;
- geração, reutilização e consumo de token CSRF;
- identificação de Super Administrador e superusuário;
- contrato de dados organizacionais carregados no login;
- persistência de sessão antes do redirecionamento;
- sessão única e heartbeat;
- separação entre página pública e ACL autenticada;
- Portal vinculado ao colaborador autenticado.

Resultado inicial: 26 testes e 52 asserções aprovados.

Em 19/07/2026 foram adicionados (e depois atualizados) testes de
caracterização do fluxo de currículos (`CurriculoStorageContractTest`) e
testes unitários de `RhCandidatoAnexoService`. Ver
[diagnóstico de currículos](SEG_CURRICULOS_DIAGNOSTICO.md).

Correções iniciais da Fase 0 (caminho físico LGPD, auth em currículos,
validação de upload, exclusão sem GET, `.htaccess`, anonimização ampliada)
estão cobertas por esses testes.

Essa suíte caracteriza o funcionamento atual; não altera autenticação, sessão,
ACL, banco ou Portal.

## Pirâmide inicial

### Unidade

- cálculo de status projetado;
- transições permitidas;
- regras de retenção;
- scorecards e indicadores;
- policies de autorização;
- idempotência de consumidores.

### Integração

- repositories e constraints;
- transações de pipeline;
- storage, download e exclusão;
- outbox;
- backfills;
- filtros de escopo.

### Fluxo

- candidato e vaga;
- movimentação no pipeline;
- entrevista e decisão;
- requisição e aprovação;
- oferta e pré-admissão;
- vínculo e onboarding;
- documentos privados.

## Casos obrigatórios de autorização

Para cada ação:

- permite o papel e escopo corretos;
- nega papel incorreto;
- nega registro fora do escopo;
- nega estado incompatível;
- não revela recurso sensível;
- registra auditoria quando exigido.

## Segurança e LGPD

- MIME falso e extensão enganosa;
- arquivo acima do limite;
- path traversal;
- download sem autorização;
- CSRF;
- retenção vencida;
- anonimização completa;
- exclusão física verificada;
- logs sem credenciais ou PII excessiva.

## Migrations

- base vazia;
- base legada representativa;
- backfill repetível;
- compatibilidade entre versões;
- verificação de divergências;
- rollback operacional documentado.

## Critério mínimo da Fase 0

- configuração PHPUnit versionada — concluída;
- factories/fixtures mínimas;
- testes de autorização e uploads;
- testes de retenção;
- testes de pipeline e entrevistas;
- execução automatizada antes do deploy ou merge;
- teste de regressão para cada risco crítico corrigido.
