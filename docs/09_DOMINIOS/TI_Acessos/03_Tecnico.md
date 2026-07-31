# TI / Acessos — Documento técnico

## Arquitetura atual

Domínio novo. Não havia Controllers, tabelas nem menu dedicados.

## Arquitetura-alvo

```text
app/adms/Controllers/ti/
app/adms/Views/ti/
app/adms/Models/Repository/TiSistemaRepository.php
app/adms/Models/Repository/TiAcessoRepository.php
app/adms/Models/Services/TiAcessoService.php
```

Offboarding (GP/Jornada) consome leitura/revogação via service; não é dono do
dado.

## Modelo de dados

### `ti_sistemas`

Fonte de verdade do catálogo. Campos: `codigo`, `nome`, `descricao`, `tipo`,
`localizacao`, `observacoes`, `status`, `created_by_user_id`, timestamps.

### `ti_acessos`

Fonte de verdade do mapa. Campos: `adms_user_id`, `ti_sistema_id`,
`login_externo`, `perfil_obs`, `status` (`ativo|revogado`), datas e atores de
liberação/revogação, `observacoes`.

Invariante: no máximo um registro **ativo** por (`adms_user_id`, `ti_sistema_id`).

Sujeito do acesso: `adms_user_id` (fachada operacional; ADR-0002).

## Autorização

Grupo ACL: **TI - Sistemas e Acessos**.

| Página | Controller | Uso |
|---|---|---|
| Listar sistemas | `TiSistemas` | Catálogo |
| Cadastrar | `TiSistemasCreate` | Alta |
| Editar | `TiSistemasUpdate` | Alteração |
| Visualizar | `TiSistemasView` | Detalhe + usuários |
| Liberar acesso | `TiAcessosCreate` | Vínculo |
| Revogar acesso | `TiAcessosRevoke` | Inativação no mapa |

Páginas nascem sem permissão em massa (exceto Super Admin / super usuário).

## Eventos e auditoria

- `LogAlteracaoService` em create/update de sistema e liberar/revogar acesso.
- Payload mínimo: entidade, id, antes/depois, ator.

## Integrações

- **Offboarding**: leitura de ativos + revogação; regra de conclusão do item
  `revogar_acessos`.
- Sem integração técnica com equipamentos embarcados (confirmação manual).

## Segurança e LGPD

- Finalidade: governança operacional de contas em sistemas corporativos.
- Dados: identificadores de colaborador e login externo (não senhas).
- Retenção: histórico de revogação preservado para auditoria.
- Não armazenar senhas dos sistemas de destino.

## Migração

Expand: criar tabelas e páginas novas. Sem backfill. Rollback: drop tabelas e
remover páginas/grupo.

## Riscos técnicos

- Cadastro incompleto reduz valor do offboarding (mitigar com processo TI).
- Confusão com ACL do Portal (mitigar na UI e no manual).

## Referências

- Funcional e roadmap deste domínio.
- [ADR-0008](../../08_ADR/ADR-0008_DOMINIO_TI_ACESSOS.md).
- Offboarding Expand: `Gestao_Pessoas/OFFBOARDING_EXPAND.md`.
