# Portaria — Documento funcional

## Escopo

**Inclui:** visitantes externos; termo de ciência às dependências (versionado);
autorizações de visita (agendada e não programada); pontos de controle;
movimentações entrada/saída; presença no complexo; conferência de identidade
pelo porteiro; regularização de saída faltante; painel e histórico.

**Exclui (MVP):** biometria/foto; controle por área/zona; controle rígido por
filial; catracas/cancelas; adendo de termo por área BPF; crachá físico
integrado; motoristas/entregas como tipo completo.

## Atores

| Papel | Sistema | Pode |
|---|---|---|
| Colaborador / anfitrião | `adms_users` | Agendar visita; autorizar/recusar não programado (quando permitido) |
| Porteiro | `adms_users` (login próprio) | Consultar, cadastrar não programado, notificar anfitrião (push/WhatsApp), registrar ligação, conferir documento, E/S, regularizar saída |
| Supervisor / Segurança | nível ACL | Exceções; apoio quando anfitrião inacessível |
| Visitante | não é usuário do portal | Formalizar termo (presencial no MVP; link depois) |
| DPO / Jurídico | LGPD → Termos | Elabora/redige e versiona o texto do termo de acesso às dependências |

## Conceitos independentes

1. **Visitante** — pessoa externa.
2. **Termo** — ciência/aceite; texto versionado em LGPD → Termos; validade
   própria (padrão 1 ano, **configurável**).
3. **Autorização** — quando, onde (complexo no MVP), sob responsabilidade de quem.
4. **Movimentação** — cada entrada ou saída efetivamente realizada.

## Processos

### P1 — Agendamento (colaborador)

- Entrada: dados do visitante, motivo, período, horários, anfitrião.
- Saída: autorização ` Liberada` (ou equivalente) para o período.
- Uma autorização cobre vários dias (ex.: 17–21/08, 07:30–18:00, seg–sex);
  não cria N visitas independentes.

### P2 — Chegada e entrada (portaria)

1. Localizar autorização / cadastrar não programado.
2. Conferir documento oficial com foto (porteiro autenticado atesta).
3. Verificar termo vigente; formalizar se necessário (presencial).
4. Registrar **entrada** (ponto + porteiro + data/hora).
5. Estado → **DENTRO**.

### P3 — Saída

- Sempre permitida, mesmo com autorização vencida.
- Se fora da janela: registrar + alerta de permanência excedida.
- Estado → **FORA**.

### P4 — Entrada com visitante já DENTRO (regularização)

1. Sistema **alerta**: há entrada aberta (quando/onde/quem).
2. Opções: cancelar **ou** **registrar saída faltante e liberar nova entrada**.
3. Saída regularizada: append-only, flag/motivo `saida_regularizada`, ponto e
   porteiro atuais, vínculo à entrada aberta; data/hora padrão = agora
   (ajustável se a UI permitir).
4. Em seguida grava a nova entrada. Não sobrescrever movimentações antigas.

### P5 — Não agendado

```text
Chegada → cadastro → escolhe anfitrião
  → sistema notifica anfitrião (push e/ou WhatsApp)
  → anfitrião autoriza/recusa no portal
  → se não responde: porteiro liga para o anfitrião e registra o contato
  → com autorização: termo → identidade → entrada
```

- Notificação pelo sistema: **push** e/ou **WhatsApp** (canais disponíveis /
  preferência do anfitrião; telefone/celular cadastrado).
- Sem resposta: **não há autoaprovação**. O porteiro **liga** ao anfitrião,
  obtém autorização verbal se a política operacional permitir e **registra** no
  histórico (data/hora, porteiro, resultado: autorizou / recusou / não
  atendeu / retornar depois).
- Escalonamento formal a outro aprovador (gestor de área) fica como evolução;
  no MVP a contingência é a ligação.

### P6 — Termo

- Texto elaborado por Jurídico/DPO e cadastrado em **LGPD → Termos**
  (tipo dedicado, ex.: `acesso_dependencias`), versionado como os demais termos.
- Portaria **consome** a versão ativa e grava o **aceite** do visitante com
  evidências (não duplica o editor de conteúdo).
- Desacoplado do check-in; validade **padrão 1 ano**, com **opção de definir**
  outra validade no aceite (ou regra associada à versão).
- Nova versão relevante pode exigir novo aceite.
- MVP: formalização presencial + conferência + evidências (versão/`lgpd_termo_id`,
  hash se aplicável, porteiro, data/hora, dispositivo quando possível).
- Foto/biometria: fora do MVP.

### P7 — Lista de visitantes e auditoria de termos

- Tela de **visitantes**: busca por nome, documento, empresa, telefone.
- Em cada visitante: status do termo (vigente / vencido / sem aceite),
  validade, versão aceita, histórico de aceites/revogações.
- Acesso às autorizações e movimentações vinculadas (auditoria operacional).
- Perfis: portaria e quem tiver ACL de auditoria/consulta.

## Regras de negócio (fechadas)

| ID | Regra |
|---|---|
| R1 | Autorização ≠ entrada; saída ≠ fim da autorização. |
| R2 | Presença MVP = DENTRO/FORA do **complexo** (unidades no perímetro). |
| R3 | Liberou em um ponto → pode circular; qualquer ponto registra saída. |
| R4 | Movimentação grava `ponto_controle_id` + `porteiro_user_id`. |
| R5 | Saída nunca bloqueada por vencimento da autorização. |
| R6 | Entrada duplicada: alertar + opção de regularizar saída faltante. |
| R7 | Porteiro com login próprio; sem usuário genérico para atestação. |
| R8 | Texto do termo em `lgpd_termos`; aceite em Portaria; não assinar a cada entrada. |
| R9 | Validade do aceite: padrão **1 ano**, **configurável**. |
| R10 | Não agendado: notificar anfitrião (push/WhatsApp) com links públicos Autorizar/Recusar (sem login); sem resposta → porteiro liga e registra. |
| R11 | Lista de visitantes com auditoria de termos/aceites. |
| R12 | Controle por filial/área e adendo de termo por zona: pós-MVP. |

## Capacidades

### Essenciais (MVP)

- Cadastro de visitante e ponto de controle.
- Autorização por período / janela diária / multidias.
- Não programados + push/WhatsApp ao anfitrião + registro de ligação.
- Termo via LGPD → Termos + formalização presencial + validade configurável.
- Lista de visitantes com status/histórico de termos (auditoria).
- Entrada/saída múltiplas; presença; regularização; painel; histórico; ACL.

### Recomendadas

- Link + OTP; QR; veículos; recorrência; alertas refinados; escalonamento formal.

### Avançadas

- Prestadores + SST; áreas; modo emergência; catracas; biometria (ADR próprio).

## Experiência (MVP)

- Painel Portaria: aguardados hoje, presentes, pendências, permanência excedida,
  autorizações aguardando resposta.
- **Visitantes:** listagem + ficha com termos, aceites, autorizações e movimentos.
- Busca: nome, documento, empresa, anfitrião, protocolo (QR depois).
- Colaborador: minhas visitas / agendar / autorizar não programado.
- Telas autenticadas (ACL). Quiosque/tablet do termo: Fase 2.

## Critérios de aceite funcionais (MVP)

Ver também [05_Testes.md](05_Testes.md): agendamento multidia; janela diária;
múltiplas E/S; não agendado; termo vigente; conferência; saída com vencimento;
entrada duplicada com regularização; presença confiável; ACL por perfil.
