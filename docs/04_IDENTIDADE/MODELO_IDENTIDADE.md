# Modelo de identidade

## Status

Modelo conceitual inicial. Não autoriza migrations. A implantação exige ADR
aprovado e estratégia Expand/Contract.

## Regra de negócio reconhecida

Todo colaborador ativo que utiliza o Portal precisa de uma conta de acesso, pois
comunicações, documentos, solicitações e chamados dependem de autenticação. Essa
regra é legítima e será preservada.

```text
Colaborador ativo (Portal) ── 1:1 ── Conta de acesso
```

Manter conta para colaboradores não é o problema. O problema é a tabela acumular
responsabilidades distintas na mesma entidade.

## Problema atual

`adms_users` centraliza corretamente o acesso dos colaboradores, mas acumula, na
mesma linha, identidade da pessoa, credenciais e acesso, vínculo empregatício,
cargo e lotação, estrutura organizacional, dados trabalhistas e preferências do
portal. Essa concentração — e não a existência das contas — dificulta histórico,
vigência, autorização, recontratações, integrações, privacidade e analytics.

## Modelo conceitual

```text
Pessoa
  ├── zero ou mais Contas
  └── zero ou mais Vínculos

Vínculo
  ├── Pessoa
  ├── Empresa
  └── uma ou mais Lotações ao longo do tempo

Lotação
  ├── Filial
  ├── Departamento
  ├── Cargo
  ├── Gestor
  ├── Equipe
  ├── Centro de custo
  └── vigência
```

## Responsabilidades

| Conceito | Responsabilidade | Implementação atual |
|---|---|---|
| Pessoa | Nome, CPF, nascimento, contatos e identidade civil | `adms_users` |
| Conta de acesso | Login, senha, MFA, bloqueio, último acesso e perfil técnico | `adms_users` |
| Vínculo | Matrícula, empresa, admissão, desligamento e tipo de contrato | `adms_users` + histórico de emprego |
| Lotação | Cargo, departamento, filial, gestor, equipe e vigência | `adms_users` (estado atual) |
| Permissões | Páginas, ações, papéis e escopo dos dados | ACL e cadastros relacionados |

O conceito Cargo é implementado por `adms_positions`, que permanece a fonte de
verdade atual (ver [Fontes de verdade](../06_FONTES_VERDADE/FONTES_VERDADE.md)).

## Regra operacional versus cardinalidade física

A obrigatoriedade da conta é uma regra de domínio, não uma limitação física
irreversível do banco:

> Para colaboradores ativos que utilizam o Portal, a existência de uma conta
> ativa é obrigatória.

O modelo deve continuar permitindo cenários que não seguem 1:1:

- candidato sem conta interna;
- pré-admitido sem acesso liberado;
- afastado com acesso suspenso;
- ex-colaborador com vínculo encerrado e conta bloqueada;
- prestador ou consultor com conta e sem vínculo empregatício;
- conta técnica de integração, sem pessoa;
- colaborador com mais de um vínculo ao longo do tempo.

## Invariantes iniciais

- conta humana referencia no máximo uma pessoa;
- vínculo pertence a exatamente uma pessoa e empresa;
- vínculo possui uma ou mais lotações com vigência, sem sobreposição incompatível;
- inativar conta não encerra vínculo; criar vínculo não concede acesso
  automaticamente;
- desligamento preserva histórico e evidências;
- recontratação reutiliza pessoa e pode reativar conta, mas cria novo vínculo;
- alterações organizacionais registram vigência e motivo;
- candidatos não são unificados automaticamente por nome, CPF ou e-mail.

## Estratégia de compatibilidade proposta

1. classificar cada FK para `adms_users` como conta, pessoa, vínculo ou ator;
2. criar estruturas novas sem remover colunas;
3. preservar IDs quando reduzir risco;
4. realizar backfill idempotente;
5. manter `UsersRepository` ou fachada equivalente durante a transição;
6. migrar leituras por domínio;
7. medir divergências;
8. tornar novas referências obrigatórias após estabilização;
9. remover legado somente em versão posterior.

## Candidato

`rh_candidatos` continua sendo identidade própria de Talentos. A associação
com Pessoa ocorre somente em conversão ou reconciliação explícita, auditável e
reversível.

## Decisões pendentes

- chave estável de Pessoa;
- múltiplos vínculos simultâneos;
- gestor funcional e administrativo;
- posição prevista versus cargo exercido;
- empresa e filial canônicas;
- terceiros e prestadores;
- dependentes e contatos de emergência;
- política de retenção após vínculo.
