# Portaria — Roadmap

## Fase 0 — Arquitetura, segurança e compliance (atual)

- [x] Análise de domínio e compatibilidade com o monólito
- [x] Decisões operacionais: presença por complexo; ponto + porteiro;
      login próprio; regularização de saída faltante
- [x] Não agendado: push/WhatsApp + ligação do porteiro (sem autoaprovação)
- [x] Termo: texto em LGPD → Termos; aceite na Portaria; validade padrão 1 ano
      configurável; lista de visitantes para auditoria
- [x] ADR-0009 (Aprovado) + mapa de domínios + artefatos iniciais
- [x] Aprovação do ADR-0009
- [ ] Cadastro do termo por Jurídico/DPO em LGPD → Termos (tipo `acesso_dependencias`)
- [ ] Confirmar retenção/base legal com DPO antes de produção
- [x] Migration + grupo ACL + páginas base (sem ACL em massa)

## Fase 1 — MVP Portaria

- [x] Schema + grupo ACL + páginas + menu + controllers/views base
- [ ] Liberar ACL nos níveis de Portaria/Segurança (manual no sistema)
- [ ] Uso operacional: pontos, visitantes, autorizações, E/S
- [ ] Notificar anfitrião (push/WhatsApp) e registrar ligação
- [ ] Termo LGPD tipo `acesso_dependencias` + formalização presencial na ficha
- [ ] Auditoria de termos na lista de visitantes (refinar aceite na UI)
- [x] Manuais base (F1)
- [ ] Aceite operacional com porteiros

## Fase 2 — Experiência

- Pré-formalização (link), OTP, QR
- Notificações ao anfitrião
- Veículos; alertas refinados; recorrência
- (Opcional) portal público do termo no padrão gateway

## Fase 3 — Prestadores

- Empresa prestadora, documentos, ASO/NRs, áreas/horários
- Integração com SST; bloqueio por requisito vencido

## Fase 4 — Integração física

- Catracas, leitores, cancelas, tags
- Biometria somente com ADR + parecer jurídico

## Fase 5 — Analytics e emergência

- Dashboards, ocupação, exceções
- Modo emergência (lista presentes + evacuado/não localizado)
- Presença por filial/área quando a operação exigir
