# Portaria — Documento técnico

## Arquitetura atual

Domínio novo. Não há Controllers, tabelas nem menu dedicados de portaria.

## Arquitetura-alvo

```text
app/adms/Controllers/portaria/
app/adms/Views/portaria/
app/adms/Models/Repository/Portaria*.php
app/adms/Models/Services/Portaria*.php
database/migrations/*_portaria_*.php
docs/manual/content/portaria/
```

Padrões a reutilizar (precedente, não as tabelas de outros domínios):

| Necessidade | Precedente |
|---|---|
| ACL / páginas | `adms_pages` + grupo novo **Portaria** |
| Anfitrião / porteiro | `adms_users` |
| Termo versionado (texto) | **`lgpd_termos`** (tipo dedicado, ex.: `acesso_dependencias`) |
| Aceite do visitante | tabela própria `portaria_termo_aceites` → FK `lgpd_termo_id` |
| Notificação anfitrião | `PushNotificationService` + `SendWhatsAppService` |
| Auditoria de alteração | `LogAlteracaoService` + eventos de movimentação append-only |
| Retenção futura | padrão Whistleblowing/Candidate retention |
| QR (Fase 2) | padrão `SstEquipamentoQrHelper` / mPDF |
| Portal público termo (Fase 2) | opcional; pode reutilizar publicação de termo LGPD |

## Modelo de dados (MVP — conceitual → físico na implementação)

Prefixo: `portaria_`.

### `portaria_pontos_controle`

Ponto físico de registro (ex.: Portaria Principal – Unidade A).

- `nome`, `codigo`, `adms_branch_id` (opcional, prepara filial), `ativo`, timestamps.

### `portaria_visitantes`

Pessoa externa (não é `adms_users`).

- identificação: nome, documento, telefone, empresa externa, observações;
- timestamps; sem biometria/foto no MVP.

### Texto do termo — `lgpd_termos` (domínio LGPD)

- Jurídico/DPO elabora e versiona em LGPD → Termos.
- Tipo dedicado (ex.: `acesso_dependencias` / `portaria`).
- Portaria **não** mantém cópia paralela do conteúdo.
- Nova versão no LGPD: Portaria passa a exigir aceite da versão ativa quando
  a política marcar necessidade de novo aceite.

### `portaria_termo_aceites`

Evidência de formalização por visitante (fonte de verdade do aceite).

- `visitante_id`, `lgpd_termo_id` (versão aceita);
- método (presencial/…), `porteiro_user_id`, confirmação de conferência de
  identidade, data/hora, dispositivo, OTP (Fase 2);
- `valido_ate` — **padrão = aceite + 1 ano**, **editável** na formalização;
- `revogado_em`, timestamps.

### `portaria_autorizacoes`

- `visitante_id`, `anfitriao_user_id`, destino/departamento, motivo;
- `data_inicio`, `data_fim`, `hora_inicio`, `hora_fim`, dias permitidos;
- tipo (único / múltiplas no dia / período / recorrente);
- status (`rascunho`, `aguardando`, `liberada`, `recusada`, `cancelada`, …);
- veículo/placa (opcional no MVP ou Fase 2);
- `escopo` MVP = complexo; campos de filial/área nullable para evolução;
- timestamps.

### `portaria_autorizacao_contatos` (ou eventos no histórico)

Registro das tentativas de contato com o anfitrião no não agendado:

- `autorizacao_id`, `canal` (`push` \| `whatsapp` \| `ligacao` \| `outro`);
- `resultado` (`enviado` \| `autorizou` \| `recusou` \| `nao_atendeu` \| …);
- `porteiro_user_id` (quando ligação), `ocorrido_em`, observações.

### `portaria_movimentacoes`

Append-only.

- `autorizacao_id` (nullable se política permitir), `visitante_id`;
- `tipo` (`entrada` \| `saida`);
- `ponto_controle_id`, `porteiro_user_id`;
- `ocorrido_em`;
- `regularizada` (bool), `motivo_regularizacao`, `movimentacao_entrada_id`
  (para saída que fecha uma entrada);
- alertas derivados (permanência excedida) como flag ou tabela auxiliar;
- timestamps / auditoria.

**Presença atual:** visitante está DENTRO se existe entrada sem saída
posterior correspondente (consulta, não campo denormalizado obrigatório;
pode haver cache/projeção depois).

### Vínculo porteiro ↔ ponto

- Preferência de turno: `ponto_controle_id` padrão no perfil/config do
  porteiro (detalhe na implementação); troca permitida se atuar em mais de um
  ponto.

## Autorização

Grupo ACL: **Portaria**.

Páginas típicas (nomes a fechar na migration):

| Uso | Controller (sugerido) |
|---|---|
| Painel | `PortariaPainel` |
| Visitantes + auditoria termos | `PortariaVisitantes*` |
| Autorizações / agendar | `PortariaAutorizacoes*` |
| Registrar E/S | `PortariaMovimentacoes*` |
| Pontos de controle | `PortariaPontos*` |
| Histórico / presença | `PortariaPresenca` |

Texto do termo: telas já existentes **LGPD → Termos** (`LgpdTermos*`).
Portaria só seleciona/consome a versão ativa do tipo configurado.

Páginas `public_page = 0` no MVP (operador autenticado). Portal do visitante
(Fase 2) poderá usar `public_page = 1` + token.

## Eventos e auditoria

- Toda movimentação é fato imutável (correção = nova movimentação/regularização).
- Atestação de identidade referencia `porteiro_user_id`.
- `LogAlteracaoService` em cadastros (visitante, autorização, termo).

## Segurança e LGPD

- Redação do termo: Jurídico/DPO em **LGPD → Termos**; retenção e base legal
  do tratamento de visitantes validados antes de produção.
- Aceites e movimentações: dados operacionais da Portaria (minimização; sem
  foto/biometria no MVP).
- Storage de anexos (se houver): privado, padrão RH.

## Integrações

- Consome: `adms_users`, departamentos, filiais, notificações.
- Não escreve em `ti_acessos` nem `adms_log_acessos`.
- Futuro: SST (prestadores), hardware.

## Decisão estrutural

[ADR-0009](../../08_ADR/ADR-0009_DOMINIO_PORTARIA.md).
