# Gestão de Pessoas — ADRs relacionados

## Vigentes

- [ADR-0001 — Manter monólito modular](../../08_ADR/ADR-0001_MONOLITO_MODULAR.md)
- [ADR-0003 — Storage privado e retenção de currículos](../../08_ADR/ADR-0003_STORAGE_PRIVADO_CURRICULOS.md)
- [ADR-0004 — Escopo ATS via ViewAll](../../08_ADR/ADR-0004_ESCOPO_VIEWALL_ATS.md)
- [ADR-0005 — Worker SMTP de entrevistas com outbox](../../08_ADR/ADR-0005_WORKER_SMTP_ENTREVISTAS.md)

## Propostos

- [ADR-0002 — Separar Pessoa e Conta](../../08_ADR/ADR-0002_PESSOA_E_CONTA.md)

## Aceitos neste domínio

- [ADR-0006 — Modelo físico Pessoa/Vínculo/Lotação](../../08_ADR/ADR-0006_MODELO_FISICO_IDENTIDADE.md)
- [ADR-0007 — Workflow de solicitações (delegação/escalação)](../../08_ADR/ADR-0007_WORKFLOW_SOLICITACOES.md)

## Decisões que ainda exigirão ADR

- fonte canônica de empresa e filial;
- estratégia de IDs e compatibilidade plena com `adms_users` (Contract);
- máquina de estados e histórico do recrutamento;
- fronteira entre Treinamentos e SST;
- separação de Departamento Pessoal;
- materialização de fatos para People Analytics.

ADRs serão adicionados ao índice global em `docs/08_ADR/README.md`. Este arquivo
apenas relaciona decisões que afetam o domínio.
