# Commits pendentes em ~10 lotes (deploy incremental).
# Uso: powershell -ExecutionPolicy Bypass -File scripts/commit_pending_batches.ps1
# O hook pre-commit audita só o que está no índice após stash do restante.

$ErrorActionPreference = 'Stop'
Set-Location (Join-Path $PSScriptRoot '..')

function Invoke-BatchCommit {
    param(
        [string[]]$Paths,
        [string]$Message
    )
    if ($Paths.Count -eq 0) { return }

    Write-Host "`n=== Commit: $Message ===" -ForegroundColor Cyan
    git reset HEAD --quiet 2>$null
    git add -- @Paths
    if (-not (git diff --cached --quiet)) {
        git stash push -u --keep-index -m "commit-batch-stash" 2>$null
        git commit -m $Message
        git stash pop 2>$null
        Write-Host "OK" -ForegroundColor Green
    } else {
        Write-Host "Nada para commitar neste lote." -ForegroundColor Yellow
    }
}

$batches = @(
    @{
        Message = "feat(database-schema): descrições, módulos e ordenação do catálogo`n`nInclui serviços de descrição/módulo, script de comentários MySQL e ordenação por módulo e tabela."
        Paths = @(
            'app/adms/Models/Repository/DatabaseSchemaRepository.php',
            'app/adms/Models/Services/DatabaseSchemaDescriptionService.php',
            'app/adms/Models/Services/DatabaseSchemaModuleResolver.php',
            'scripts/generate_database_schema_comments.php'
        )
    },
    @{
        Message = "feat(trainings): exclusão de treinamento na matriz de concluídos`n`nController, repositórios, view, migration e página do sistema."
        Paths = @(
            'app/adms/Controllers/trainings/DeleteCompletedTraining.php',
            'app/adms/Controllers/trainings/CompletedTrainingsMatrix.php',
            'app/adms/Models/Repository/TrainingApplicationsRepository.php',
            'app/adms/Models/Repository/TrainingUsersRepository.php',
            'app/adms/Models/Repository/TrainingsRepository.php',
            'app/adms/Views/trainings/completedTrainingsMatrix.php',
            'database/migrations/20260626140100_register_delete_completed_training_page.php'
        )
    },
    @{
        Message = "docs(manual): conteúdo administração e cadastro"
        Paths = @('docs/manual/content/administracao/', 'docs/manual/content/cadastro/')
    },
    @{
        Message = "docs(manual): conteúdo comunicação interna e CRM"
        Paths = @('docs/manual/content/comunicacao/', 'docs/manual/content/crm/')
    },
    @{
        Message = "docs(manual): conteúdo estoque e financeiro"
        Paths = @('docs/manual/content/estoque/', 'docs/manual/content/financeiro/')
    },
    @{
        Message = "docs(manual): conteúdo gestão de pessoas"
        Paths = @('docs/manual/content/gestao_pessoas/')
    },
    @{
        Message = "docs(manual): parceiros, LGPD e planejamento estratégico"
        Paths = @('docs/manual/content/parceiros/', 'docs/manual/content/lgpd/', 'docs/manual/content/planejamento/')
    },
    @{
        Message = "docs(manual): projetos, qualidade e relatórios"
        Paths = @('docs/manual/content/projetos/', 'docs/manual/content/qualidade/', 'docs/manual/content/relatorios/')
    },
    @{
        Message = "docs(manual): treinamentos RH, SAC, salas e SST"
        Paths = @('docs/manual/content/rh_treinamentos/', 'docs/manual/content/sac/', 'docs/manual/content/salas/', 'docs/manual/content/sst/')
    },
    @{
        Message = "chore(manual): scripts, manifestos, seed e hooks de auditoria`n`nGeradores do manual, manifests JSON, seed de páginas e pre-commit de cobertura."
        Paths = @(
            'docs/manual/README.md',
            'docs/manual/help-menu.json',
            'docs/manual/manifest.json',
            'docs/manual/page-topic-map.json',
            'scripts/generate_manual_manifest.php',
            'scripts/generate_manual_module_docs.php',
            'scripts/generate_manual_page_topics.php',
            'scripts/load_adms_menu_tree.php',
            'scripts/manual_aggregate_topic_map.php',
            'scripts/audit_manual_coverage.php',
            'scripts/fix_manual_page_titles.php',
            'scripts/manual_coverage_lib.php',
            'scripts/sync_help_menu_titles.php',
            'scripts/git-hooks/',
            'database/seeds/AddAdmsPages.php'
        )
    }
)

foreach ($batch in $batches) {
    Invoke-BatchCommit -Paths $batch.Paths -Message $batch.Message
}

Write-Host "`n--- Status final ---" -ForegroundColor Cyan
git status --short
git log -10 --oneline
