# Log de download de currículo — Expand

- Domínio: Gestão de Pessoas / Talentos.
- Data: 19/07/2026 (backend); UI 20/07/2026.
- Status: backend + **UI/exportação entregues**.

## Modelo

Tabela append-only `rh_candidato_anexo_access_logs`:

- gravada após autorização e arquivo legível;
- fontes: `authorized_controller` e `legacy_file_server`;
- sem FK CASCADE (evidência sobrevive à exclusão);
- falha de gravação não bloqueia o download;
- deduplicação de 60s no `serve-file` (Range/PDF).

## Migration

- `database/migrations/20260719231000_create_rh_candidato_anexo_access_logs.php`
- `database/migrations/20260720150000_register_rh_candidato_anexo_access_logs_pages.php`

## UI / ACL

- Listagem: `ListRhCandidatoAnexoAccessLogs` (`list-rh-candidato-anexo-access-logs`)
- Excel: `ExportRhCandidatoAnexoAccessLogsExcel`
- Menu: Administração → Logs → Log download currículos
- Concessão inicial: níveis que já possuem `ListLogAcessos` (DPO/auditoria)

## Fora deste incremento

- PDF de exportação;
- alerta/notificação em tempo real de download;
- retenção automatizada / purge do log.
