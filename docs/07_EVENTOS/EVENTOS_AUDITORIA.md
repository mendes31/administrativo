# Eventos e auditoria

## Conceitos

- **Evento de domínio:** fato relevante já ocorrido;
- **Evento de integração:** contrato público e versionado do fato;
- **Comando:** solicitação para executar ação;
- **Auditoria:** evidência de ação, tentativa, decisão ou acesso;
- **Notificação:** mensagem causada por um fato;
- **Outbox:** evento gravado na mesma transação da alteração.

Eventos usam nomes no passado: `VinculoEncerrado`, não `EncerrarVinculo`.

## Campos do catálogo

| Campo | Finalidade |
|---|---|
| Evento | Nome estável |
| Produtor | Domínio proprietário |
| Consumidores | Domínios autorizados |
| Payload | Dados mínimos |
| Versão | Contrato |
| LGPD | Classificação de privacidade |
| Idempotência | Chave para evitar repetição |
| Correlação | Jornada entre operações |
| Origem | Tela, API, job ou integração |
| Criticidade | Crítica, alta, normal ou informativa |

## Criticidade

- **Crítica:** falha bloqueia processo essencial ou gera risco legal,
  financeiro, de segurança ou integridade;
- **Alta:** exige retry e alerta;
- **Normal:** processamento recuperável;
- **Informativa:** histórico ou telemetria sem efeito imediato.

## Catálogo inicial

| Evento | Produtor | Consumidores previstos | Criticidade | LGPD |
|---|---|---|---|---|
| `PessoaCriada` | Organização | Conta, DP, SST | Alta | Pessoal |
| `PessoaAlterada` | Organização | Autorizados | Normal | Pessoal |
| `VinculoCriado` | Organização/DP | Jornada, SST, Treinamentos, Analytics | Crítica | Pessoal |
| `VinculoEncerrado` | Organização/DP | Acessos, SST, Jornada, Analytics | Crítica | Pessoal |
| `LotacaoAlterada` | Organização | SST, Treinamentos, Autorização, Analytics | Alta | Pessoal |
| `RequisicaoPessoalAprovada` | Talentos | Vagas, Planejamento | Alta | Interna |
| `VagaAberta` | Talentos | Portal, Comunicação, Analytics | Normal | Interna |
| `VagaEncerrada` | Talentos | Portal, Analytics | Normal | Interna |
| `CandidaturaMovimentada` | Talentos | Comunicação, Analytics | Normal | Pessoal |
| `EntrevistaRealizada` | Talentos | Processo seletivo, Analytics | Normal | Pessoal |
| `OfertaAceita` | Talentos | Pré-admissão, Organização | Crítica | Pessoal |
| `TreinamentoConcluido` | Desenvolvimento | SST, Gestor, Analytics | Alta | Pessoal |
| `AvaliacaoFinalizada` | Desenvolvimento | PDI, Analytics | Alta | Pessoal |
| `DocumentoAssinado` | DP | Auditoria, Portal | Crítica | Restrita |
| `ExameVencido` | SST | Responsáveis autorizados | Alta | Sensível |
| `EpiEntregue` | SST | Perfil ocupacional, Auditoria | Alta | Pessoal |

Eventos tornam-se vigentes somente no documento técnico do produtor.

Catálogo detalhado de Talentos (contratos e lacunas de emissão):
[`docs/09_DOMINIOS/Gestao_Pessoas/CATALOGO_EVENTOS_TALENTOS.md`](../09_DOMINIOS/Gestao_Pessoas/CATALOGO_EVENTOS_TALENTOS.md).

## Regras de payload

- incluir somente o necessário;
- preferir identificadores a cópias;
- não incluir currículo, laudo, denúncia, senha, token ou documento;
- indicar finalidade dos dados pessoais;
- versionar mudanças incompatíveis.

## Processamento

1. concluir alteração e outbox na mesma transação;
2. publicar após commit;
3. consumidor verificar idempotência;
4. registrar tentativas e resultado;
5. alertar conforme criticidade;
6. aplicar retenção e anonimização.

## Auditoria mínima

Conforme o risco, registrar ator, ação, recurso, data, origem, estado anterior e
posterior permitido, justificativa, correlação e resultado. Logs não devem
reter credenciais, tokens ou dados pessoais completos sem necessidade.

Auditoria, evento e notificação são responsabilidades distintas.
