# Mapa de domínios

## Plataforma Administrativa

```text
Sistema Administrativo
├── Administração do Sistema
├── Gestão de Pessoas
├── Departamento Pessoal
├── SST
├── Estoque
├── Compras
├── Custos
├── Produção
├── Qualidade
├── Financeiro
├── Patrimônio
├── TI / Acessos
├── Comunicação
├── Canal de Denúncias
└── Analytics Corporativo
```

CRM, SAC, Parceiros, Projetos, Planejamento Estratégico e Reserva de Salas
permanecem capacidades existentes e serão posicionados definitivamente em seus
diagnósticos.

## Responsabilidades preliminares

### Administração do Sistema

Contas, autenticação, sessões, páginas, rotas, ACL, configurações, notificações,
arquivos privados, auditoria técnica e serviços transversais.

### Gestão de Pessoas

- Organização;
- Talentos;
- Jornada do Colaborador;
- Desenvolvimento;
- Portal do Colaborador;
- People Analytics.

### Departamento Pessoal

Folha, férias, ponto, benefícios, admissão e rescisão legal e obrigações
trabalhistas. Compartilha Pessoa e Vínculo sem compartilhar acesso irrestrito.

### SST

GHE, riscos, exames, EPIs, acidentes e saúde ocupacional. Dados médicos possuem
política própria.

### Estoque

Itens, saldos, movimentações, armazenagem, inventário e rastreabilidade.

### Compras

Solicitações, cotações, aprovações, pedidos, recebimentos e fornecedores.

### Custos

Centros de custo, critérios de rateio, custeio fabril, simulações e análise de
variações.

### Produção

Ordens, apontamentos, consumo, rendimento, perdas e integração com estoque.

### Qualidade

Documentos controlados, revisões, não conformidades, ações e evidências.

### Financeiro

Contas a pagar e receber, bancos, movimentos, plano de contas, orçamento e
relatórios.

### Patrimônio

Ativos, responsáveis, localização, movimentações, manutenção, inventário e
baixa.

### TI / Acessos

Catálogo de sistemas e equipamentos com controle de usuário próprio (incluindo
embarcados fora da rede) e mapa colaborador ↔ acesso, consumido no offboarding.
Não substitui a ACL de páginas do Portal, o inventário LGPD de dados, o Estoque
nem o cadastro patrimonial de hardware.

### Comunicação

Informativos, comunicados, timeline, eventos e canais corporativos.

### Canal de Denúncias

Relatos, protocolo, comitês, investigação, evidências, criptografia e retenção.
Não herda acesso genérico de RH ou Administração.

### Analytics Corporativo

Catálogo de indicadores e projeções interdomínios. Consome fatos e não altera
fontes operacionais.

## Regras de dependência

1. cada dado operacional possui domínio proprietário;
2. outros domínios consomem contrato, serviço de leitura ou projeção;
3. dados sensíveis preservam a política do domínio de origem;
4. Comunicação entrega mensagens, mas não decide fatos de outros domínios;
5. Analytics calcula indicadores, mas não corrige silenciosamente suas fontes;
6. compartilhamento de banco não autoriza acesso direto indiscriminado.

## Ordem inicial de diagnóstico

1. Gestão de Pessoas;
2. SST;
3. Estoque, Custos e Produção;
4. Qualidade;
5. Compras;
6. Financeiro;
7. Comunicação;
8. Canal de Denúncias;
9. Patrimônio;
10. TI / Acessos;
11. Analytics Corporativo.

Mudanças nessa ordem devem considerar risco e prioridade empresarial.
