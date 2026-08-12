# ADR-0009 — Domínio Portaria / Controle de Acesso Físico

- Status: Aprovado
- Data: 2026-08-12
- Responsável: Arquitetura / Facilities / Segurança Patrimonial
- Módulos impactados: portaria, visitantes, termos de acesso, autorizações,
  movimentações, pontos de controle, notificações, ACL de páginas; consome
  identidade (`adms_users`), departamentos, filiais, LGPD Termos e (futuro) SST

## Contexto

Não existe no Sistema Administrativo fonte de verdade para visitas, termos de
ciência às dependências, autorizações de acesso físico nem entradas/saídas.
`ti_acessos` trata contas em sistemas; `adms_log_acessos` trata login no Portal;
inventário LGPD só classifica a finalidade — nenhum substitui a operação de
portaria. A empresa opera com duas unidades próximas em que o visitante, após
liberação em uma, pode circular no complexo; a presença no MVP é por período
(complexo), não por área/zona.

## Decisão

1. Criar o domínio **Portaria / Controle de Acesso Físico** (pasta e artefatos
   em `docs/09_DOMINIOS/Portaria/`), integrado ao monólito modular (ADR-0001).
2. Fontes de verdade do domínio (prefixo sugerido `portaria_*`):
   visitante, termo versionado + evidência de aceite, autorização de acesso,
   movimentação (entrada/saída), ponto de controle.
3. **Visitante não é `adms_users`.** Anfitrião e porteiro são `adms_user_id`.
4. Separação obrigatória: Visitante × Termo × Autorização × Movimentação.
   Autorização ≠ entrada; saída não encerra necessariamente a autorização.
5. **Presença MVP:** estado único DENTRO/FORA do **complexo** (perímetro das
   unidades tratadas como um). Controle por filial/área fica para fases
   posteriores; o modelo já grava `ponto_controle_id` em cada movimentação.
6. **Saída sempre permitida**, mesmo com autorização vencida (gera alerta de
   permanência excedida, não bloqueio).
7. **Entrada com visitante já DENTRO:** alertar e oferecer
   **regularizar saída faltante + nova entrada** (movimentações append-only;
   saída regularizada com motivo/flag e porteiro autenticado).
8. **Termo** de ciência às dependências:
   - **Conteúdo/versão** cadastrado em **LGPD → Termos** (`lgpd_termos`, tipo
     dedicado, ex.: `acesso_dependencias`), elaborado por Jurídico/DPO;
   - **Aceite/evidência** pertence à Portaria (`portaria_termo_aceites`),
     referenciando o `lgpd_termos.id` vigente;
   - validade **padrão 1 ano**, **configurável** no aceite (ou regra da versão);
   - não é assinado a cada entrada; formalização presencial no MVP;
   - link/OTP/QR na Fase 2; adendo por área: fora do MVP.
9. **Não agendado — notificação e SLA:** o sistema oferece o anfitrião via
   **push e/ou WhatsApp** com **links públicos Autorizar/Recusar** (token
   `decisao_token`, sem login); se não houver resposta, o **porteiro realiza
   ligação telefônica** ao anfitrião e registra o contato no histórico da
   autorização (não há autoaprovação silenciosa no MVP).
10. **Autenticação do porteiro:** credencial própria (`adms_users`); proibido
   usuário genérico “portaria” para atestação de conferência de identidade.
11. **Auditoria de visitantes:** listagem de visitantes com status do termo
    (vigente/vencido/ausente), histórico de aceites e vínculo a autorizações
    e movimentações.
12. Grupo ACL novo: **Portaria**. Páginas nascem sem concessão em massa.
13. Limites explícitos: não fundir com TI/Acessos, ACL do Portal, Patrimônio
    nem Estoque. Inventário LGPD não opera a visita; apenas o **cadastro do
    texto do termo** reutiliza `lgpd_termos`. Biometria/foto e hardware fora do
    MVP (ADR + jurídico próprios).

## Alternativas consideradas

- Extender `ti_acessos` — semântica de conta em sistema, não de visita física.
- Colocar sob Gestão de Pessoas / RH — visita externa não é vínculo empregatício;
  inflaria o mega-grupo ACL.
- Um único agregado “Visita” misturando termo, autorização e movimento — quebra
  multidias, almoço (saída+entrada) e validade do termo.
- Presença por área já no MVP — exige zonas operacionais e infla escopo sem
  catracas por zona.
- Duplicar editor de termos só na Portaria — rejeitado: Jurídico/DPO elabora e
  versiona em LGPD → Termos; Portaria consome a versão e guarda o aceite.
- Escalonamento automático para outro aprovador (só ADR-0007) — no MVP a
  contingência é ligação do porteiro; escalonamento formal pode evoluir depois.

## Consequências

### Positivas

- ownership e ACL claros para Facilities/portaria;
- modelo aguenta autorização multidia e múltiplas entradas/saídas;
- presença do complexo confiável com regularização auditável;
- termo com governança jurídica no módulo LGPD já existente;
- auditoria de visitantes/termos sem depender só da memória da portaria;
- caminho preparado para filial, áreas, prestadores/SST e hardware.

### Negativas e riscos

- depende de cadastro disciplinado de pontos e porteiros;
- presença global do complexo pode mascarar “em qual prédio” até haver controle
  por filial;
- redação, retenção e base legal do termo ainda passam por Jurídico/DPO antes
  de produção (o *onde cadastrar* já está definido);
- WhatsApp/push dependem de config e telefone/celular do anfitrião atualizados;
- ligação telefônica é processo operacional (sistema registra; não substitui a voz).
