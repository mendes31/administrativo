<?php

declare(strict_types=1);

/**
 * Padrões de exclusão compartilhados entre deploy FTP e generate_ftp_deploy_state.php.
 * Mantém paridade com .github/workflows/deploy.yml (bloco exclude).
 *
 * @return list<string> Prefixos/caminhos relativos excluídos do deploy
 */
function deployExcludePrefixes(): array
{
    return [
        '.git/',
        '.github/',
        'node_modules/',
        '.vscode/',
        'vendor/',
        'lib/',
        'storage/sst/epi_fichas/',
        'storage/sst/attachments/',
        'storage/lgpd/consentimentos/',
        'storage/private/payroll/',
        'storage/cache/',
        'storage/logs/',
        'logs/',
        'app/storage/cache/',
        'app/storage/logs/',
    ];
}

/**
 * @return list<string> Nomes de ficheiros na raiz excluídos do deploy
 */
function deployExcludeRootFiles(): array
{
    return [
        '.gitignore',
        '.DS_Store',
        'LICENSE.txt',
        '.env',
        '.ftp-deploy-sync-state.json',
    ];
}

function deployPathExcluded(string $relPath): bool
{
    $relPath = str_replace('\\', '/', $relPath);

    if ($relPath === '') {
        return true;
    }

    if (in_array(basename($relPath), deployExcludeRootFiles(), true)) {
        return true;
    }

    if (str_ends_with($relPath, '.log')) {
        return true;
    }

    foreach (deployExcludePrefixes() as $prefix) {
        if ($relPath === rtrim($prefix, '/')) {
            return true;
        }
        if (str_starts_with($relPath, $prefix)) {
            if ($relPath === 'storage/private/payroll/.gitkeep') {
                return false;
            }

            return true;
        }
    }

    return false;
}

/**
 * Conteúdo multiline para SamKirkland/FTP-Deploy-Action (exclude:).
 */
function deployExcludeYamlBlock(): string
{
    $lines = [
        '.git/',
        '.git/**',
        '.github/',
        '.github/**',
        '.gitignore',
        '.DS_Store',
        'LICENSE.txt',
        'node_modules/',
        'node_modules/**',
        '.vscode/',
        '.vscode/**',
        '**/*.log',
        'vendor/',
        'vendor/**',
        'lib/',
        'lib/**',
        'storage/sst/epi_fichas/**',
        'storage/sst/attachments/**',
        'storage/lgpd/consentimentos/**',
        'storage/private/payroll/**',
        '!storage/private/payroll/.gitkeep',
        'storage/cache/**',
        'storage/logs/**',
        'logs/**',
        'app/storage/cache/**',
        'app/storage/logs/**',
    ];

    return implode("\n", $lines);
}
