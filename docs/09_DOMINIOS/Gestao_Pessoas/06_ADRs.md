# Gestão de Pessoas — ADRs relacionados

## Vigentes

- [ADR-0001 — Manter monólito modular](../../08_ADR/ADR-0001_MONOLITO_MODULAR.md)

## Propostos

- [ADR-0002 — Separar Pessoa e Conta](../../08_ADR/ADR-0002_PESSOA_E_CONTA.md)

## Decisões que exigirão ADR

- modelo físico de Pessoa, Vínculo e Lotação;
- fonte canônica de empresa e filial;
- estratégia de IDs e compatibilidade com `adms_users`;
- máquina de estados e histórico do recrutamento;
- outbox e contrato de eventos;
- fronteira entre Treinamentos e SST;
- separação de Departamento Pessoal;
- retenção e armazenamento de currículos;
- política transversal de autorização;
- materialização de fatos para People Analytics.

ADRs serão adicionados ao índice global em `docs/08_ADR/README.md`. Este arquivo
apenas relaciona decisões que afetam o domínio.
