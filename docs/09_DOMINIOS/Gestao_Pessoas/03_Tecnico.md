# Gestão de Pessoas — Documento técnico

## Estado técnico atual

- controllers de recrutamento em `app/adms/Controllers/rh`;
- views em `app/adms/Views/rh`;
- repositories globais em `app/adms/Models/Repository`;
- candidatos, vagas, candidaturas e entrevistas em tabelas `rh_*`;
- colaboradores e estrutura atual concentrados em `adms_users`;
- ACL geral baseada em páginas e níveis;
- autorização por objeto varia por controller;
- status de candidatura e candidato podem divergir;
- operações compostas nem sempre usam transação;
- não há suíte automatizada abrangente;
- histórico de candidatura em `rh_candidaturas_historico` (append-only), com dual-write nas transições;
- service `RhCandidaturaMovimentacaoService` para entrevista + pipeline na mesma transação.

## Direção arquitetural

- manter monólito modular;
- preservar rotas e namespaces até existir benefício real na mudança;
- introduzir services de aplicação para casos de uso;
- centralizar policies de domínio;
- tornar históricos imutáveis a fonte de transições;
- usar outbox para integração assíncrona;
- adotar storage privado e download por objeto;
- migrar identidade por Expand/Contract.

## Entidades conceituais

- Pessoa, Conta, Vínculo e Lotação;
- Requisição de Pessoal;
- Vaga;
- Candidato;
- Candidatura;
- Etapa e Histórico de Candidatura;
- Entrevista, Scorecard e Avaliação;
- Oferta e Pré-admissão;
- Onboarding/Offboarding e itens de checklist;
- Competência, Ciclo, Avaliação, PDI e Ação;
- Treinamento e requisito;
- Fato analítico e projeção.

Nomes físicos serão definidos somente na especificação de cada incremento.

## Autorização prioritária

Matrizes específicas devem cobrir:

- candidatos e anexos;
- vagas e candidaturas;
- entrevistas e avaliações;
- documentos de folha;
- solicitações;
- desempenho, feedback e PDI;
- treinamentos e certificados;
- exportações e indicadores.

## Eventos iniciais

Referência: [`EVENTOS_AUDITORIA.md`](../../07_EVENTOS/EVENTOS_AUDITORIA.md).

Talentos deverá especificar pelo menos abertura/encerramento de vaga,
movimentação de candidatura, realização de entrevista e aceite de oferta.

## Migração

Toda mudança estrutural deve:

1. expandir schema sem quebrar consumidores;
2. realizar backfill idempotente;
3. manter adapters ou dual read/write por período controlado;
4. medir divergências;
5. migrar consumidores;
6. tornar novo contrato obrigatório;
7. remover legado em release posterior.

## Riscos técnicos prioritários

- arquivos de currículo em área pública;
- exclusão/anonimização física inconsistente;
- ACL sem escopo de registro;
- duas fontes de status;
- ausência de histórico de candidatura (mitigado: Expand com `rh_candidaturas_historico`);
- `adms_users` como entidade híbrida;
- analytics sobre fontes divergentes;
- ausência de testes de regressão.

## Diagnósticos

- [Segurança e LGPD — Currículos (ATS)](SEG_CURRICULOS_DIAGNOSTICO.md)
- [Integridade — Pipeline ATS](SEG_PIPELINE_INTEGRIDADE.md)
- [Matriz de autorização — Talentos](MATRIZ_AUTORIZACAO_TALENTOS.md)
- [Catálogo de eventos — Talentos](CATALOGO_EVENTOS_TALENTOS.md)
- [Histórico imutável — Expand](HISTORICO_CANDIDATURA_EXPAND.md)
- [Etapas do pipeline — Expand](ETAPAS_PIPELINE_EXPAND.md)
- [Status geral — projeção](STATUS_PROCESSO_PROJECAO.md)
- [Requisição de pessoal — Expand](REQUISICAO_PESSOAL_EXPAND.md)

## Referências

- [Modelo de identidade](../../04_IDENTIDADE/MODELO_IDENTIDADE.md)
- [Modelo de autorização](../../05_AUTORIZACAO/MODELO_AUTORIZACAO.md)
- [Fontes de verdade](../../06_FONTES_VERDADE/FONTES_VERDADE.md)
- [ADR-0001](../../08_ADR/ADR-0001_MONOLITO_MODULAR.md)
- [ADR-0002](../../08_ADR/ADR-0002_PESSOA_E_CONTA.md)
