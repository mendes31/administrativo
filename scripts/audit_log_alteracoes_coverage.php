<?php

/**
 * Auditoria estática: cobertura de LogAlteracaoService::registrarAlteracao
 * nos repositórios e referências a partir dos controllers.
 *
 * Uso (na raiz do projeto):
 *   php scripts/audit_log_alteracoes_coverage.php
 *
 * Gera:
 *   scripts/output/log_alteracoes_repositories.csv
 *   scripts/output/log_alteracoes_controllers.csv
 */

declare(strict_types=1);

$projectRoot = dirname(__DIR__);
$repoDir = $projectRoot . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'adms' . DIRECTORY_SEPARATOR . 'Models' . DIRECTORY_SEPARATOR . 'Repository';
$ctrlDir = $projectRoot . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'adms' . DIRECTORY_SEPARATOR . 'Controllers';
$outDir = $projectRoot . DIRECTORY_SEPARATOR . 'scripts' . DIRECTORY_SEPARATOR . 'output';

if (!is_dir($outDir) && !mkdir($outDir, 0755, true) && !is_dir($outDir)) {
    fwrite(STDERR, "Não foi possível criar: {$outDir}\n");
    exit(1);
}

/** @return iterable<string> */
function iterPhpFiles(string $dir): iterable
{
    $it = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS)
    );
    foreach ($it as $file) {
        /** @var SplFileInfo $file */
        if (strtolower($file->getExtension()) !== 'php') {
            continue;
        }
        yield $file->getPathname();
    }
}

function hasMutatingSql(string $content): bool
{
    // Heurística: ignorar blocos de comentário longos é caro; suficiente para auditoria
    if (preg_match('/\bUPDATE\s+[`"]?[a-z0-9_]+[`"]?/i', $content)) {
        return true;
    }
    if (preg_match('/\bINSERT\s+INTO\s+[`"]?[a-z0-9_]+[`"]?/i', $content)) {
        return true;
    }
    if (preg_match('/\bDELETE\s+FROM\s+[`"]?[a-z0-9_]+[`"]?/i', $content)) {
        return true;
    }

    return false;
}

function hasRegistrarAlteracao(string $content): bool
{
    return str_contains($content, 'registrarAlteracao(');
}

/**
 * Repositórios em que NÃO se deve exigir LogAlteracaoService por segurança ou adequação:
 * - LogAlteracoes* / Logs*: pipeline do próprio log (risco de recursão ou ruído).
 * - LogAlteracoesDetalhesRepository: escrito apenas por LogAlteracaoService.
 * - LogJustificativasRepository: texto livre associado a logs; evitar cadeia log-sobre-log.
 * - LoginRepository: mutações muito frequentes (tentativas/bloqueio); risco de amplificação e dados sensíveis.
 * - AdmsSessionsRepository: contém session_id; alto volume; trilha em adms_log_acessos quando aplicável.
 * - AdmsSlowRequestProfileRepository: telemetria de performance, não auditoria de negócio.
 */
function repositoryExcludedFromMutatingGapAudit(string $class): bool
{
    static $extra = [
        'LogAlteracoesDetalhesRepository',
        'LogJustificativasRepository',
        'LoginRepository',
        'AdmsSessionsRepository',
        'AdmsSlowRequestProfileRepository',
    ];
    if (in_array($class, $extra, true)) {
        return true;
    }

    return (bool) preg_match('/^(Log(Alteracoes|Acessos)|Logs)Repository$/', $class);
}

/** @return list<string> */
function extractRepositoryNews(string $content): array
{
    $found = [];
    // new UsersRepository( — padrão mais comum nos controllers
    if (preg_match_all('/new\s+([A-Za-z0-9_]+Repository)\s*\(/', $content, $m1)) {
        foreach ($m1[1] as $name) {
            $found[$name] = $name;
        }
    }

    return array_values($found);
}

// --- Repositórios ---
/** @var array<string, array{path: string, rel: string, has_log: bool, has_mut: bool}> */
$repoMap = [];

foreach (iterPhpFiles($repoDir) as $absPath) {
    $rel = str_replace($projectRoot . DIRECTORY_SEPARATOR, '', $absPath);
    $rel = str_replace('\\', '/', $rel);
    $base = basename($absPath, '.php');
    $content = (string) file_get_contents($absPath);
    $hasLog = hasRegistrarAlteracao($content);
    $hasMut = hasMutatingSql($content);
    $repoMap[$base] = [
        'path' => $absPath,
        'rel' => $rel,
        'has_log' => $hasLog,
        'has_mut' => $hasMut,
    ];
}

$reposCsv = $outDir . DIRECTORY_SEPARATOR . 'log_alteracoes_repositories.csv';
$fh = fopen($reposCsv, 'wb');
if ($fh === false) {
    fwrite(STDERR, "Falha ao escrever {$reposCsv}\n");
    exit(1);
}
fwrite($fh, "repository_class;relative_path;has_registrar_alteracao;has_mutating_sql;gap_mutating_without_log;infra_log_tables\n");
foreach ($repoMap as $class => $info) {
    $infra = repositoryExcludedFromMutatingGapAudit($class);
    $gap = !$infra && $info['has_mut'] && !$info['has_log'] ? '1' : '0';
    $line = sprintf(
        "%s;%s;%s;%s;%s;%s\n",
        $class,
        $info['rel'],
        $info['has_log'] ? '1' : '0',
        $info['has_mut'] ? '1' : '0',
        $gap,
        $infra ? '1' : '0'
    );
    fwrite($fh, $line);
}
fclose($fh);

// --- Controladores ---
$ctrlCsv = $outDir . DIRECTORY_SEPARATOR . 'log_alteracoes_controllers.csv';
$fh2 = fopen($ctrlCsv, 'wb');
if ($fh2 === false) {
    fwrite(STDERR, "Falha ao escrever {$ctrlCsv}\n");
    exit(1);
}
fwrite($fh2, "relative_path;has_direct_registrar_alteracao;instantiated_repositories;repos_mutating_without_log;repos_unknown_class\n");

foreach (iterPhpFiles($ctrlDir) as $absPath) {
    if (str_contains($absPath, DIRECTORY_SEPARATOR . 'Services' . DIRECTORY_SEPARATOR)) {
        continue;
    }
    $rel = str_replace($projectRoot . DIRECTORY_SEPARATOR, '', $absPath);
    $rel = str_replace('\\', '/', $rel);
    $content = (string) file_get_contents($absPath);
    $directLog = str_contains($content, 'LogAlteracaoService::registrarAlteracao')
        || str_contains($content, '\\LogAlteracaoService::registrarAlteracao');

    $repos = extractRepositoryNews($content);
    $missing = [];
    $unknown = [];
    foreach ($repos as $rClass) {
        if (!isset($repoMap[$rClass])) {
            $unknown[] = $rClass;
            continue;
        }
        $isInfra = repositoryExcludedFromMutatingGapAudit($rClass);
        if (!$isInfra && $repoMap[$rClass]['has_mut'] && !$repoMap[$rClass]['has_log']) {
            $missing[] = $rClass;
        }
    }

    fwrite($fh2, sprintf(
        "%s;%s;%s;%s;%s\n",
        $rel,
        $directLog ? '1' : '0',
        implode('|', $repos),
        implode('|', $missing),
        implode('|', $unknown)
    ));
}
fclose($fh2);

echo "OK\n";
echo "Repositórios: {$reposCsv}\n";
echo "Controladores: {$ctrlCsv}\n";
