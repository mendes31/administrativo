<?php

declare(strict_types=1);

/**
 * Manifestos fixos para re-deploy quando ficheiros ficaram no Git mas não no servidor.
 *
 * @return array<string, list<string>>
 */
function deployFeatureManifestMap(): array
{
    return [
        'database-schema' => [
            'app/adms/Controllers/databaseSchema/ListDatabaseTables.php',
            'app/adms/Controllers/databaseSchema/ViewDatabaseTable.php',
            'app/adms/Models/Repository/DatabaseSchemaRepository.php',
            'app/adms/Models/Services/DatabaseSchemaCacheService.php',
            'app/adms/Views/Services/LoadViewService.php',
            'app/adms/Views/databaseSchema/list.php',
            'app/adms/Views/databaseSchema/view.php',
            'app/adms/Views/layouts/schema.php',
            'app/adms/Views/partials/menu.php',
            'database/migrations/20260627120000_register_database_schema_pages.php',
        ],
        'institutional-user' => [
            'app/adms/Controllers/dashboard/Dashboard.php',
            'app/adms/Controllers/informativos/AcknowledgeInformativo.php',
            'app/adms/Controllers/informativos/ExportRelatorioInformativoExcel.php',
            'app/adms/Controllers/informativos/ExportRelatorioInformativoPdf.php',
            'app/adms/Controllers/informativos/RelatorioInformativo.php',
            'app/adms/Controllers/lgpd/LgpdConsentimentoLogin.php',
            'app/adms/Controllers/login/Login.php',
            'app/adms/Controllers/policies/AcknowledgePolicy.php',
            'app/adms/Controllers/policies/ExportRelatorioPolicyExcel.php',
            'app/adms/Controllers/policies/ExportRelatorioPolicyPdf.php',
            'app/adms/Controllers/policies/RelatorioPolicy.php',
            'app/adms/Controllers/portal/MyPayrollDocuments.php',
            'app/adms/Controllers/portal/PayrollImportBatchReport.php',
            'app/adms/Controllers/portal/SignPayrollDocument.php',
            'app/adms/Controllers/trainings/TrainingNotificationService.php',
            'app/adms/Controllers/users/ForcePasswordChange.php',
            'app/adms/Helpers/InformativoReadStatusHelper.php',
            'app/adms/Helpers/InstitutionalSystemUserHelper.php',
            'app/adms/Models/Repository/CompanyEventsRepository.php',
            'app/adms/Models/Repository/EmployeePayrollDocumentsRepository.php',
            'app/adms/Models/Repository/GamificationRankingExclusions.php',
            'app/adms/Models/Repository/InformativosRepository.php',
            'app/adms/Models/Repository/PoliciesRepository.php',
            'app/adms/Models/Repository/TrainingUsersRepository.php',
            'app/adms/Models/Repository/UsersRepository.php',
            'app/adms/Models/Services/SstPendenciasService.php',
            'app/adms/Models/Services/TimelineCelebrationsService.php',
            'app/adms/Views/informativos/view.php',
            'app/adms/Views/policies/view.php',
            'app/adms/Views/portal/my_payroll_documents.php',
        ],
    ];
}

/**
 * @return list<string>|null
 */
function deployFeatureManifestFiles(string $name): ?array
{
    $map = deployFeatureManifestMap();

    return $map[$name] ?? null;
}
