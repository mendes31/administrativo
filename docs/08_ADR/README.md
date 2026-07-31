# Architecture Decision Records

ADRs registram decisões estruturais e não substituem regras funcionais.

## Quando criar

- limite de domínio;
- fonte de verdade;
- identidade ou autorização;
- contrato transversal de evento ou integração;
- mudança incompatível;
- tecnologia estrutural;
- exceção aos princípios.

## Estados

- Proposto
- Aprovado
- Rejeitado
- Substituído
- Obsoleto

ADRs aprovados não são reescritos para mudar a decisão. Uma nova decisão cria
outro ADR e referencia o anterior.

## Índice

| ADR | Decisão | Estado |
|---|---|---|
| [ADR-0001](ADR-0001_MONOLITO_MODULAR.md) | Manter monólito modular | Aprovado |
| [ADR-0002](ADR-0002_PESSOA_E_CONTA.md) | Separar Pessoa e Conta | Aceito com condicionantes |
| [ADR-0003](ADR-0003_STORAGE_PRIVADO_CURRICULOS.md) | Storage privado e retenção de currículos | Aprovado |
| [ADR-0004](ADR-0004_ESCOPO_VIEWALL_ATS.md) | Escopo ATS via ViewAll (Expand/Contract) | Aprovado |
| [ADR-0005](ADR-0005_WORKER_SMTP_ENTREVISTAS.md) | Worker SMTP de entrevistas com outbox | Aprovado |
| [ADR-0006](ADR-0006_MODELO_FISICO_IDENTIDADE.md) | Modelo físico Pessoa/Vínculo/Lotação (Expand) | Aceito |
| [ADR-0007](ADR-0007_WORKFLOW_SOLICITACOES.md) | Workflow de solicitações (delegação/escalação) | Aprovado |
| [ADR-0008](ADR-0008_DOMINIO_TI_ACESSOS.md) | Domínio TI / Acessos (mapa sistemas↔colaborador) | Aprovado |

Use [TEMPLATE.md](TEMPLATE.md) para novos registros.
