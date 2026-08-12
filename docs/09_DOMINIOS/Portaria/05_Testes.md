# Portaria — Testes

## Fase 0 / gate

- [ ] ADR-0009 revisado (limites vs TI/Acessos e GP)
- [ ] Grupo ACL Portaria não empilha em RH/LGPD/TI
- [ ] Lacunas jurídicas listadas antes de produção

## Aceite MVP (quando implementado)

| # | Cenário | Resultado esperado |
|---|---|---|
| 1 | Agendar visita multidia com janela horária | Uma autorização; N movimentações possíveis |
| 2 | Entrada e saída no mesmo dia (almoço) | Duas entradas e duas saídas; mesma autorização |
| 3 | Não agendado + push/WhatsApp ao anfitrião | Anfitrião recebe; status aguardando |
| 3b | Sem resposta + ligação do porteiro | Contato registrado; autoriza/recusa sem autoaprovação |
| 4 | Termo vigente (validade configurada) | Não exige novo aceite dentro do prazo |
| 5 | Nova versão LGPD que exige novo aceite | Bloqueia entrada até formalizar |
| 5b | Aceite com validade ≠ 1 ano | `valido_ate` respeitado na entrada |
| 5c | Lista de visitantes / auditoria | Exibe status do termo e histórico de aceites |
| 6 | Autorização vencida + saída | Saída registrada + alerta permanência excedida |
| 7 | Entrada com já DENTRO | Alerta; cancelar OU saída regularizada + nova entrada |
| 8 | Liberou no ponto A; saída no ponto B | Status FORA; histórico com ambos os pontos |
| 9 | Usuário genérico “portaria” | Não deve atestar conferência (só login nominal) |
| 10 | ACL: colaborador vs porteiro | Escopos distintos |
| 11 | Presença “agora” | Só quem tem entrada sem saída correspondente |
| 12 | Manual F1 | Tópicos completos no padrão do Plano Diretor |

## Regressão transversal

- [ ] `ti_acessos` / login Portal inalterados em comportamento
- [ ] Sem pages ACL concedidas em massa na migration
