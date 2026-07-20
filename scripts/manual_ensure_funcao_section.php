<?php

declare(strict_types=1);

/**
 * Garante a seção padrão <h2>Função no sistema</h2> no início de cada tópico do manual.
 *
 * Uso:
 *   php scripts/manual_ensure_funcao_section.php           # aplica
 *   php scripts/manual_ensure_funcao_section.php --dry-run  # só relatório
 */

$dryRun = in_array('--dry-run', $argv ?? [], true);
$root = dirname(__DIR__) . '/docs/manual/content';
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)
);

$updated = 0;
$skipped = 0;
$errors = 0;

foreach ($iterator as $file) {
    if (!$file->isFile() || strtolower($file->getExtension()) !== 'html') {
        continue;
    }
    $path = $file->getPathname();
    $html = file_get_contents($path);
    if ($html === false) {
        $errors++;
        continue;
    }

    if (preg_match('/<h2>\s*Função no sistema\s*<\/h2>/iu', $html)) {
        $skipped++;
        continue;
    }

    if (!preg_match('/<h1>(.*?)<\/h1>/isu', $html, $h1Match, PREG_OFFSET_CAPTURE)) {
        fwrite(STDERR, "Sem H1: {$path}\n");
        $errors++;
        continue;
    }

    $h1Full = $h1Match[0][0];
    $h1End = $h1Match[0][1] + strlen($h1Full);
    $title = trim(html_entity_decode(strip_tags($h1Match[1][0]), ENT_QUOTES | ENT_HTML5, 'UTF-8'));

    $afterH1 = substr($html, $h1End);
    $funcaoBody = null;
    $removeParagraph = null;

    if (preg_match('/^\s*(<p\b(?![^>]*class="help-note")[^>]*>.*?<\/p>)/isu', $afterH1, $pMatch)) {
        $candidate = $pMatch[1];
        $candidateText = trim(html_entity_decode(strip_tags($candidate), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        if ($candidateText !== '' && !preg_match('/^Quem acessa:/iu', $candidateText)) {
            $funcaoBody = $candidate;
            $removeParagraph = $candidate;
        }
    }

    if ($funcaoBody === null) {
        $safeTitle = htmlspecialchars($title !== '' ? $title : 'esta tela', ENT_QUOTES, 'UTF-8');
        $funcaoBody = '<p>Esta tela concentra a função de <strong>' . $safeTitle
            . '</strong> no sistema: use o fluxo abaixo para operar o recurso no dia a dia e consulte a visão geral do módulo quando precisar do contexto completo.</p>';
    }

    $section = "\n\n<h2>Função no sistema</h2>\n" . $funcaoBody . "\n";
    $newHtml = substr($html, 0, $h1End) . $section . substr($html, $h1End);

    if ($removeParagraph !== null) {
        // Remove a primeira ocorrência do parágrafo original após a nova seção (evita duplicar).
        $posSection = strpos($newHtml, $section);
        if ($posSection !== false) {
            $searchFrom = $posSection + strlen($section);
            $dupPos = strpos($newHtml, $removeParagraph, $searchFrom);
            if ($dupPos !== false) {
                $newHtml = substr($newHtml, 0, $dupPos) . substr($newHtml, $dupPos + strlen($removeParagraph));
                // Limpa linhas em branco extras
                $newHtml = preg_replace('/\n{3,}/', "\n\n", $newHtml) ?? $newHtml;
            }
        }
    }

    if ($dryRun) {
        echo "[DRY] {$path}\n";
    } else {
        if (file_put_contents($path, $newHtml) === false) {
            fwrite(STDERR, "Falha ao gravar: {$path}\n");
            $errors++;
            continue;
        }
        echo "[OK] {$path}\n";
    }
    $updated++;
}

echo "\nResumo: atualizados={$updated} ja_ok={$skipped} erros={$errors}"
    . ($dryRun ? " (dry-run)\n" : "\n");
exit($errors > 0 ? 1 : 0);
