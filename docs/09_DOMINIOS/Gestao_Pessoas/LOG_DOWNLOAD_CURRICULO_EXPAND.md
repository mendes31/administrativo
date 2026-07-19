# Log de download de currículo — Expand

- Domínio: Gestão de Pessoas / Talentos.
- Data: 19/07/2026.
- Status: registro backend entregue; UI/exportação pendente.

## Modelo

Tabela append-only `rh_candidato_anexo_access_logs`:

- gravada após autorização e arquivo legível;
- fontes: `authorized_controller` e `legacy_file_server`;
- sem FK CASCADE (evidência sobrevive à exclusão);
- falha de gravação não bloqueia o download;
- deduplicação de 60s no `serve-file` (Range/PDF).

## Migration

`database/migrations/20260719231000_create_rh_candidato_anexo_access_logs.php`

## Contract futuro

Tela/exportação para DPO/auditoria com permissão própria.
