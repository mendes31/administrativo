# Gestão de Pessoas — Documento funcional

## Escopo

### Organização

Pessoa, vínculo, lotação, empresas, filiais, departamentos, cargos, equipes,
gestores, organograma e movimentações.

### Talentos

Requisição de pessoal, headcount, vagas, portal público, candidatos,
candidaturas, pipeline, entrevistas, scorecards, comunicação, banco de
talentos, oferta e pré-admissão.

### Jornada do Colaborador

Admissão, onboarding, período de experiência, movimentações, transferências,
promoções, afastamentos e offboarding.

### Desenvolvimento

Competências, desempenho, avaliações 180° e 360°, PDI, treinamentos, carreira,
sucessão e Nine Box.

### Portal do Colaborador

Solicitações, documentos, treinamentos, avaliações e acompanhamento das
jornadas autorizadas.

### People Analytics

Indicadores derivados de vínculos, recrutamento, jornada, desenvolvimento,
clima e integrações autorizadas.

## Jornada integrada alvo

```text
Requisição
  -> aprovação
  -> vaga
  -> candidatura
  -> seleção
  -> oferta
  -> pré-admissão
  -> vínculo
  -> onboarding
  -> desenvolvimento
  -> movimentação
  -> offboarding
```

## Regras funcionais fundamentais

- candidato pode participar de múltiplas vagas;
- candidatura, não candidato, controla o processo seletivo;
- toda transição relevante registra ator, data, origem e motivo;
- aprovação de vaga não cria vínculo;
- oferta aceita inicia pré-admissão, não admissão automática;
- pessoa pode existir sem conta;
- vínculo e lotação possuem vigência;
- acesso não é concedido ou removido implicitamente apenas pelo vínculo;
- avaliação de treinamento não é avaliação de desempenho;
- dados médicos permanecem sob SST;
- folha e obrigações legais permanecem sob DP;
- Canal de Denúncias não pertence a Gestão de Pessoas.

## Classificação inicial

### Essencial

- segurança e LGPD de currículos;
- autorização por objeto;
- etapas e histórico de candidatura;
- motivos estruturados;
- integridade e transações;
- testes dos fluxos críticos;
- requisição de pessoal;
- identidade, vínculo e lotação conceituais.

### Recomendado

- portal público;
- scorecards;
- templates e agenda;
- pré-admissão;
- onboarding e offboarding completos;
- indicadores operacionais.

### Avançado

- matching e triagem assistidos;
- recomendações de desenvolvimento;
- sucessão assistida;
- previsão de turnover;
- analytics preditivo.

## Processos a detalhar

Cada processo terá estados, transições, responsáveis, SLA, exceções, evidências
e matriz de autorização antes da implementação.
