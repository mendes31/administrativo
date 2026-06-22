<?php

declare(strict_types=1);

/**
 * Ficheiros críticos verificados após deploy (hash SHA-256 + marcadores opcionais).
 *
 * @return list<array{path: string, must_contain?: list<string>}>
 */
function deployCriticalManifest(): array
{
    return [
        [
            'path' => 'routes/LoadPageAdm.php',
            'must_contain' => ['TrainingComplianceDashboard'],
        ],
        [
            'path' => 'app/adms/Controllers/trainings/TrainingComplianceDashboard.php',
            'must_contain' => ['function index(): void', 'countComplianceObrigacoes'],
        ],
        [
            'path' => 'app/adms/Controllers/Services/PageLayoutService.php',
            'must_contain' => ['TrainingComplianceDashboard'],
        ],
        [
            'path' => 'app/adms/Models/Repository/TrainingUsersRepository.php',
            'must_contain' => ['countComplianceObrigacoes', 'getComplianceStatsByDepartmentPaginated'],
        ],
        [
            'path' => 'app/adms/Models/Repository/TrainingsRepository.php',
            'must_contain' => ['countActiveDistinctCodigos'],
        ],
        [
            'path' => 'app/adms/Views/trainings/complianceDashboard.php',
        ],
        [
            'path' => 'app/adms/Views/trainings/partials/complianceDashboardCharts.php',
        ],
        [
            'path' => 'app/adms/Views/trainings/partials/complianceDashboardSections.php',
        ],
        [
            'path' => 'app/adms/Views/partials/menu.php',
            'must_contain' => ['Dashboard de Necessidades', 'training-compliance-dashboard'],
        ],
        [
            'path' => 'scripts/generate_ftp_deploy_state.php',
        ],
        [
            'path' => 'scripts/verify_production_deploy.php',
        ],
        [
            'path' => 'database/migrations/20260622140000_register_training_compliance_dashboard_page.php',
        ],
    ];
}
