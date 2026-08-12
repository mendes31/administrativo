# Portaria — Visão executiva

## Objetivo

Controlar visitas agendadas e não programadas às dependências: autorização,
termo de ciência, identificação na portaria, entradas/saídas auditáveis e
presença atual de pessoas externas no complexo.

## Situação atual

- Não há módulo de portaria/controle de acesso físico no Administrativo.
- Colaboradores existem em `adms_users`; visitantes físicos não.
- TI/Acessos cobre contas em sistemas; log de login cobre o Portal — outro
  problema.
- Operação: duas unidades próximas; após liberação em uma, o visitante pode
  circular no complexo; presença MVP é por período (DENTRO/FORA do complexo).
- Termo de ciência: texto versionado em **LGPD → Termos** (Jurídico/DPO);
  aceite e auditoria na Portaria.

## Benefícios esperados

- rastro claro de quem autorizou, quem entrou/saiu e com qual termo vigente;
- painel “quem está dentro agora” confiável para rotina e emergência futura;
- redução de atrito com termo anual (não assinar a cada visita);
- base para prestadores, SST, áreas e integração física depois.

## Capacidades

Ver [Catálogo de capacidades](../../03_DOMINIOS/CATALOGO_CAPACIDADES.md)
(secção Portaria) e o [documento funcional](02_Funcional.md).

## Prioridades

1. MVP operacional da portaria (essencial);
2. pré-formalização, OTP, QR, notificações e veículos (recomendado);
3. prestadores + SST (recomendado/avançado);
4. hardware (catracas/cancelas) e biometria (avançado; ADR + jurídico).

## Indicadores (MVP)

| Indicador | Fonte | Periodicidade |
|---|---|---|
| Visitantes presentes agora | movimentações abertas | Tempo real / painel |
| Permanências excedidas | saída após fim da autorização | Diário |
| Saídas regularizadas | flag `saida_regularizada` | Semanal |
| Não agendados × tempo até autorização | autorizações | Mensal |

## Roadmap resumido

- Fase 0: domínio, ADR, compliance, modelo.
- Fase 1: MVP Portaria.
- Fase 2: experiência (link, OTP, QR, veículos).
- Fase 3: prestadores / SST.
- Fase 4: integração física.
- Fase 5: analytics e modo emergência.

Detalhe: [04_Roadmap.md](04_Roadmap.md). Referência de origem:
plano de alinhamento *Controle de Acesso e Portaria* (artefato vivo).
