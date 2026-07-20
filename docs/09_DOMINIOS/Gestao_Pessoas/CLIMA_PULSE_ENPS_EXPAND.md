# Clima / Pulse / eNPS — Expand Fase 6 (1º incremento)

- Domínio: Gestão de Pessoas / People Analytics.
- Data: 20/07/2026.
- Status: 1º incremento — campanhas pulse/eNPS + resposta + resumo eNPS.

## Objetivo

Permitir ao RH abrir **campanhas** de pesquisa rápida (pulse ou eNPS), coletar
respostas dos colaboradores e ver o **score eNPS** agregado
((promotores − detratores) / total × 100).

## Regras

- Campanha: `name`, `campaign_type` (`enps|pulse`), `status`
  (`draft|open|closed`), período opcional, `anonymous` (bool), `created_by`.
- Tipo `enps`: ao criar, gera 1 pergunta NPS (0–10) automaticamente.
- Tipo `pulse`: RH pode adicionar perguntas `nps|likert|text` na view.
- Resposta: só campanha `open`; 1 resposta por usuário/pergunta (se não anônimo).
- Se `anonymous`: guarda `user_id` NULL (sem reidentificação neste incremento).
- eNPS: scores 9–10 promotor, 7–8 passivo, 0–6 detrator.
- Fechar campanha (`closed`) impede novas respostas.

## Modelo

- `adms_pulse_campaigns`
- `adms_pulse_questions`
- `adms_pulse_responses`

## UI / ACL

- `ListPulseCampaigns`, `CreatePulseCampaign`, `ViewPulseCampaign`,
  `UpdatePulseCampaign`, `RespondPulseCampaign`
- Menu People Analytics → Pesquisas (Pulse/eNPS)
- Manual com **Função no sistema**

## Fora deste incremento

- clima multi-dimensão / fatores;
- planos de ação pós-pesquisa;
- dashboard histórico rico;
- lembretes/notificações;
- segmentação avançada por cargo além do filtro de listagem.

## Migration

`database/migrations/20260720280000_create_adms_pulse_enps_tables.php`

## Dependências

Fase 5 de Desenvolvimento concluída no núcleo; matching Nine Box→PDI permanece adiado.
