# ADR-0012 — SST: eSocial e PPP como rascunho interno

- Status: Aprovado
- Data: 2026-08-30
- Responsável: Arquitetura / SST
- Módulos impactados: fila eSocial SST, PPP, conformidade, treinamentos, acidentes

## Contexto

A fila `adms_sst_esocial_eventos` gera JSON simplificado (layout S-1.2) e
permite “marcar enviado”. O serviço montava **S-2240 a partir de entrega de
EPI** e **S-2245 a partir de aplicação de treinamento**. Isso não é o modelo
oficial: S-2240 descreve condições ambientais e agentes nocivos (Tabela 24);
EPI é evidência. Os eventos SST vigentes são S-2210, S-2220, S-2221 e S-2240.
S-2245 não entra no desenho oficial atual. O PPP gravava eficácia genérica
(“conforme entregas”) e técnica qualitativa placeholder.

Transmitir ou emitir esses artefatos como oficiais cria risco regulatório.
Implementar XML/XSD S-1.3 e mensageria agora atrasaria o piloto operacional
(matriz, EPI, treinamentos, pendências).

## Decisão

1. A fila eSocial e o PPP são **rascunho interno, não oficiais**. A UI
   declara isso de forma explícita (alerta + selo). O PDF do PPP leva
   marca d’água “NÃO OFICIAL”.
2. **Bloquear** geração de S-2240 (origem entrega de EPI) e S-2245.
   `SstEsocialPolicy` é a trava única; POST direto também recusa.
3. Permitir geração de **S-2210 e S-2220** só como JSON de conferência,
   com `oficial: false`. Não há XML, XSD, certificado nem envio ao governo.
4. “Marcar enviado” passa a **conferência interna** e não se aplica a
   eventos bloqueados. Layout alvo futuro: **S-1.3**, com S-2240 nascendo
   de condições ambientais — outro ADR.
5. Evento e PPP **não são fonte de verdade**. Fonte: cadastros SST e GP
   (pessoa, cargo, setor, riscos, EPI, ASO, treinamento).

## Alternativas consideradas

- Implementar agora transmissor XML S-1.3: rejeitada (escopo P2/P3).
- Remover a fila e o PPP do menu: rejeitada; conferência interna ainda é útil
  se o rótulo for honesto.
- Corrigir S-2240 para nascer de riscos sem Tabela 24 completa: rejeitada;
  geraria falsa conformidade.

## Consequências

### Positivas

- Impede uso da fila/PPP como canal legal durante o piloto.
- Documenta o modelo correto para evolução futura.

### Negativas e riscos

- Eventos S-2240/S-2245 já gravados ficam visíveis como legado, sem regenerar.
- Equipe SST precisa de processo externo para eSocial/PPP oficiais até haver
  gerador conforme.
