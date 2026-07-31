# TI / Acessos — Documento funcional

## Escopo

### Incluído

- Catálogo de sistemas/aplicações/equipamentos com controle de usuário próprio.
- Vínculo N:N colaborador (`adms_users`) ↔ sistema, com status ativo/revogado.
- Visões na ficha do usuário, na ficha do sistema e no offboarding.
- Histórico de liberação e revogação (quem, quando, observação).

### Explicitamente excluído (Fase 1)

- Discovery/varredura de rede.
- ACL de páginas do Portal Administrativo.
- Inventário LGPD (dados), Estoque (`inv_*`), Patrimônio físico, SST.
- Matriz cargo × perfil, sync AD/M365, contratos e licenças.

## Atores

| Papel | Atuação |
|---|---|
| TI / administrador de acessos | Cadastra sistemas, libera e revoga acessos |
| RH / responsável de offboarding | Consulta mapa no desligamento; marca inativação quando autorizado |
| Gestor | Consome relatório/visão (fases posteriores) |

## Processos

### Cadastrar sistema

- Objetivo: registrar nome e contexto do sistema/equipamento.
- Entrada: nome, tipo (`embarcado|local|rede|saas|outro`), filial, localização,
  tag do equipamento (obrigatória se embarcado), fabricante/modelo/série (opc.),
  status. Código interno é sequencial automático (ex.: 00001).
- Regra: cada instalação física de sistema embarcado = um registro; tag única.
- Saída: registro em `ti_sistemas`.
- Evidência: log de alteração.

### Liberar acesso

- Objetivo: registrar que o colaborador possui login naquele sistema.
- Entrada: usuário, sistema, login externo (opcional), observação de perfil.
- Regra: no máximo um acesso **ativo** por par usuário+sistema.
- Saída: `ti_acessos` com `status=ativo`.

### Revogar acesso

- Objetivo: marcar que a conta foi inativada no sistema de destino.
- Entrada: id do acesso, ator, data, observação.
- Saída: `status=revogado`, `data_revogacao`, `revogado_por`.
- O Administrativo **não** integra automaticamente com o equipamento.

### Desligamento (consumo)

- Objetivo: listar acessos ativos e garantir revogação antes de fechar o item
  `revogar_acessos` do offboarding.
- Regra: item checklist só pode ir a `concluido` com zero acessos TI ativos;
  `dispensado` permanece permitido com observação.

## Capacidades

### Essenciais

- Manter catálogo de sistemas.
- Mapear acessos colaborador ↔ sistema.
- Revogar e auditar.
- Exibir mapa no offboarding.

### Recomendadas

- Matriz cargo → sistema.
- Alertas (desligado com acesso ativo; revisão vencida).
- Solicitação/aprovação de acesso.

### Avançadas

- Discovery de rede.
- Integrações AD/M365/APIs.

## Casos de uso

| ID | Ator | Fluxo | Resultado |
|---|---|---|---|
| UC-01 | TI | Cadastra IHM/CLP embarcado | Sistema ativo no catálogo |
| UC-02 | TI | Vincula colaborador ao sistema | Acesso ativo no mapa |
| UC-03 | TI/RH | No offboarding, marca cada acesso como inativado | Acessos revogados |
| UC-04 | RH | Tenta concluir `revogar_acessos` com ativos | Bloqueado com mensagem |

## Experiência

- Menu **TI / Acessos → Sistemas**.
- Aba **Acessos** na edição/visualização do usuário.
- Card na ficha do sistema com usuários.
- Card no offboarding com lista e ação de revogar.

## Critérios de aceite funcionais

1. Cadastrar sistema embarcado sem URL/rede.
2. Vincular e listar na ficha do usuário e do sistema.
3. Offboarding lista ativos e permite revogar.
4. Checklist `revogar_acessos` não conclui com acessos ativos.
5. Grupo ACL próprio; sem liberação em massa automática.
