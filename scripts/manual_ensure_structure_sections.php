<?php

declare(strict_types=1);

/**
 * Completa secções obrigatórias do manual: Fluxo, Campos e parâmetros, Problemas comuns.
 * Não altera páginas que já têm a secção. Não força "Termos" (só com jargão).
 *
 * Uso:
 *   php scripts/manual_ensure_structure_sections.php --dry-run
 *   php scripts/manual_ensure_structure_sections.php --limit=90
 *   php scripts/manual_ensure_structure_sections.php --only=gestao_pessoas
 */

$dryRun = in_array('--dry-run', $argv ?? [], true);
$only = null;
$limit = null;
foreach ($argv ?? [] as $arg) {
    if (str_starts_with($arg, '--only=')) {
        $only = trim(substr($arg, 7), "/\\");
    }
    if (str_starts_with($arg, '--limit=')) {
        $limit = max(1, (int) substr($arg, 8));
    }
}

$reFluxo = '/<h2>\s*(Fluxo principal|Fluxo|Passo a passo)[^<]*<\/h2>/iu';
$reCampos = '/<h2>\s*(Campos|Campos e (telas|parâmetros|parametros)|Parâmetros|Parametros)[^<]*<\/h2>/iu';
$reProblemas = '/<h2>\s*Problemas comuns\s*<\/h2>/iu';

$root = dirname(__DIR__) . '/docs/manual/content';
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)
);

$updated = 0;
$skipped = 0;
$errors = 0;

foreach ($iterator as $file) {
    if ($limit !== null && $updated >= $limit) {
        break;
    }
    if (!$file->isFile() || strtolower($file->getExtension()) !== 'html') {
        continue;
    }
    $path = $file->getPathname();
    $rel = str_replace('\\', '/', substr($path, strlen($root) + 1));
    if ($only !== null && !str_starts_with($rel, $only . '/') && $rel !== $only) {
        continue;
    }

    $html = file_get_contents($path);
    if ($html === false) {
        $errors++;
        continue;
    }

    if (!preg_match('/<h1>(.*?)<\/h1>/isu', $html, $h1Match)) {
        $errors++;
        continue;
    }
    $title = trim(html_entity_decode(strip_tags($h1Match[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    $safeTitle = htmlspecialchars($title !== '' ? $title : 'esta tela', ENT_QUOTES, 'UTF-8');
    $base = strtolower(basename($rel, '.html'));
    $isOverview = (bool) preg_match('/^(gp-|adm-|cad-|com-|crm-|est-|fin-|sst-|sac-|rel-|pe-|proj-|rh-trein-|salas-|lgpd-)/', $base)
        || str_contains($base, 'visao-geral')
        || str_contains($base, 'visão-geral');
    $isList = str_starts_with($base, 'list-') || str_contains($base, '-list');
    $isCreate = str_starts_with($base, 'create-');
    $isUpdate = str_starts_with($base, 'update-') || str_starts_with($base, 'edit-');
    $isView = str_starts_with($base, 'view-');

    $needFluxo = !preg_match($reFluxo, $html);
    $needCampos = !preg_match($reCampos, $html);
    $needProblemas = !preg_match($reProblemas, $html);

    if (!$needFluxo && !$needCampos && !$needProblemas) {
        $skipped++;
        continue;
    }

    $blocks = [];

    if ($needFluxo) {
        if ($isOverview) {
            $blocks['fluxo'] = "<h2>Fluxo principal</h2>\n<ol>\n"
                . "    <li>Abra este tópico pela central de ajuda (F1) ou pelo menu do módulo.</li>\n"
                . "    <li>Identifique a área ou tela relacionada ao que precisa fazer.</li>\n"
                . "    <li>Siga o link ou o caminho de menu indicado para a tela operacional.</li>\n"
                . "    <li>Na tela de destino, use o F1 novamente para o passo a passo detalhado.</li>\n"
                . "</ol>\n";
        } elseif ($isList) {
            $blocks['fluxo'] = "<h2>Fluxo principal</h2>\n<ol>\n"
                . "    <li>Acesse a listagem de <strong>{$safeTitle}</strong> pelo menu.</li>\n"
                . "    <li>Aplique filtros, se disponíveis, e localize o registro.</li>\n"
                . "    <li>Use Visualizar, Editar ou Nova conforme sua permissão.</li>\n"
                . "    <li>Confirme o resultado na listagem ou na tela de detalhe.</li>\n"
                . "</ol>\n";
        } elseif ($isCreate) {
            $blocks['fluxo'] = "<h2>Fluxo principal</h2>\n<ol>\n"
                . "    <li>Abra <strong>{$safeTitle}</strong> a partir da listagem (Nova/Criar).</li>\n"
                . "    <li>Preencha os campos obrigatórios.</li>\n"
                . "    <li>Salve e confira a mensagem de sucesso.</li>\n"
                . "    <li>Se necessário, continue na visualização ou volte à lista.</li>\n"
                . "</ol>\n";
        } elseif ($isUpdate) {
            $blocks['fluxo'] = "<h2>Fluxo principal</h2>\n<ol>\n"
                . "    <li>Abra o registro na listagem e escolha Editar.</li>\n"
                . "    <li>Altere os campos necessários em <strong>{$safeTitle}</strong>.</li>\n"
                . "    <li>Salve e valide as alterações na visualização ou na lista.</li>\n"
                . "</ol>\n";
        } elseif ($isView) {
            $blocks['fluxo'] = "<h2>Fluxo principal</h2>\n<ol>\n"
                . "    <li>Na listagem, abra o registro desejado.</li>\n"
                . "    <li>Revise os dados exibidos em <strong>{$safeTitle}</strong>.</li>\n"
                . "    <li>Use as ações disponíveis (editar, imprimir, histórico) conforme permissão.</li>\n"
                . "</ol>\n";
        } else {
            $blocks['fluxo'] = "<h2>Fluxo principal</h2>\n<ol>\n"
                . "    <li>Acesse <strong>{$safeTitle}</strong> pelo menu ou atalho F1.</li>\n"
                . "    <li>Identifique a ação principal da tela e os filtros ou formulários disponíveis.</li>\n"
                . "    <li>Execute a operação e confira o resultado ou mensagem do sistema.</li>\n"
                . "    <li>Em caso de dúvida, consulte Problemas comuns abaixo.</li>\n"
                . "</ol>\n";
        }
    }

    if ($needCampos) {
        if ($isOverview) {
            $blocks['campos'] = "<h2>Campos e parâmetros</h2>\n"
                . "<div class=\"help-field\"><strong>Mapa do módulo</strong> Links e resumos das telas cobertas por esta visão geral.</div>\n"
                . "<div class=\"help-field\"><strong>Permissões</strong> Cada tela operacional exige a página correspondente no nível de acesso.</div>\n"
                . "<div class=\"help-field\"><strong>Ajuda contextual (F1)</strong> Em cada tela de destino, o F1 abre o tópico detalhado daquela função.</div>\n";
        } elseif ($isList) {
            $blocks['campos'] = "<h2>Campos e parâmetros</h2>\n"
                . "<div class=\"help-field\"><strong>Filtros</strong> Critérios no topo da listagem (status, período, unidade, busca textual, conforme a tela).</div>\n"
                . "<div class=\"help-field\"><strong>Colunas</strong> Dados principais do registro para localizar e comparar itens.</div>\n"
                . "<div class=\"help-field\"><strong>Ações</strong> Nova, Visualizar, Editar, Excluir ou atalhos específicos — só aparecem com permissão.</div>\n"
                . "<div class=\"help-field\"><strong>Paginação</strong> Navegação entre páginas quando há muitos registros.</div>\n";
        } elseif ($isCreate || $isUpdate) {
            $blocks['campos'] = "<h2>Campos e parâmetros</h2>\n"
                . "<div class=\"help-field\"><strong>Campos obrigatórios</strong> Identificados no formulário; o salvamento exige o preenchimento completo.</div>\n"
                . "<div class=\"help-field\"><strong>Campos opcionais</strong> Complementam o cadastro sem bloquear a gravação.</div>\n"
                . "<div class=\"help-field\"><strong>Seletores</strong> Listas (departamento, colaborador, status, etc.) limitadas ao que você pode ver.</div>\n"
                . "<div class=\"help-field\"><strong>Salvar / Cancelar</strong> Grava alterações ou devolve à listagem sem salvar.</div>\n";
        } elseif ($isView) {
            $blocks['campos'] = "<h2>Campos e parâmetros</h2>\n"
                . "<div class=\"help-field\"><strong>Cabeçalho</strong> Identificação do registro (código, nome, status).</div>\n"
                . "<div class=\"help-field\"><strong>Blocos de dados</strong> Seções com atributos, histórico ou vínculos relacionados.</div>\n"
                . "<div class=\"help-field\"><strong>Ações</strong> Editar e demais botões conforme permissão e estado do registro.</div>\n";
        } else {
            $blocks['campos'] = "<h2>Campos e parâmetros</h2>\n"
                . "<div class=\"help-field\"><strong>Filtros ou formulário</strong> Controles principais para consultar ou alterar dados nesta tela.</div>\n"
                . "<div class=\"help-field\"><strong>Resultado / painel</strong> Área onde o sistema mostra listagens, totais ou detalhe do registro.</div>\n"
                . "<div class=\"help-field\"><strong>Ações</strong> Botões e links disponíveis conforme sua permissão.</div>\n";
        }
    }

    if ($needProblemas) {
        $blocks['problemas'] = "<h2>Problemas comuns</h2>\n<dl>\n"
            . "    <dt>Não vejo o menu ou a tela</dt>\n"
            . "    <dd>Confirme a permissão da página no nível de acesso e relogue se a migration/menu acabou de ser aplicada.</dd>\n"
            . "    <dt>Não consigo salvar ou a ação não aparece</dt>\n"
            . "    <dd>Falta permissão de criar/editar, ou o registro está em status que bloqueia a operação.</dd>\n"
            . "    <dt>Lista vazia ou sem dados esperados</dt>\n"
            . "    <dd>Revise filtros, período e escopo (filial/departamento); pode não haver registros no critério atual.</dd>\n"
            . "    <dt>F1 abre outro tópico</dt>\n"
            . "    <dd>Use a busca na central de ajuda pelo título <strong>{$safeTitle}</strong> ou o menu do módulo.</dd>\n"
            . "</dl>\n";
    }

    // Ordem de inserção no documento:
    // - Fluxo: antes de Campos, Problemas, ou no fim antes do último conteúdo útil
    // - Campos: antes de Problemas; se não houver Problemas, após Fluxo
    // - Problemas: no final (antes de permissões soltas se houver)

    $newHtml = $html;
    $changed = false;

    if (isset($blocks['fluxo'])) {
        $pos = findInsertBefore($newHtml, [
            '/<h2>\s*(Campos|Campos e)/iu',
            '/<h2>\s*Problemas comuns\s*<\/h2>/iu',
            '/<h2>\s*Permiss/iu',
        ]);
        if ($pos === null) {
            $pos = strlen($newHtml);
        }
        $newHtml = substr($newHtml, 0, $pos) . "\n" . $blocks['fluxo'] . "\n" . substr($newHtml, $pos);
        $changed = true;
    }

    if (isset($blocks['campos'])) {
        $pos = findInsertBefore($newHtml, [
            '/<h2>\s*Problemas comuns\s*<\/h2>/iu',
            '/<h2>\s*Permiss/iu',
        ]);
        if ($pos === null) {
            // após fluxo se existir
            if (preg_match($reFluxo, $newHtml, $m, PREG_OFFSET_CAPTURE)) {
                $pos = endOfSectionAfterH2($newHtml, (int) $m[0][1]);
            } else {
                $pos = strlen($newHtml);
            }
        }
        $newHtml = substr($newHtml, 0, $pos) . "\n" . $blocks['campos'] . "\n" . substr($newHtml, $pos);
        $changed = true;
    }

    if (isset($blocks['problemas'])) {
        $pos = findInsertBefore($newHtml, [
            '/<p>\s*<strong>\s*Permiss/iu',
            '/<h2>\s*Permiss/iu',
        ]);
        if ($pos === null) {
            $pos = strlen($newHtml);
        }
        $newHtml = substr($newHtml, 0, $pos) . "\n" . $blocks['problemas'] . "\n" . substr($newHtml, $pos);
        $changed = true;
    }

    if (!$changed) {
        $skipped++;
        continue;
    }

    $newHtml = preg_replace('/\n{3,}/', "\n\n", $newHtml) ?? $newHtml;

    if ($dryRun) {
        $needs = [];
        if ($needFluxo) {
            $needs[] = 'fluxo';
        }
        if ($needCampos) {
            $needs[] = 'campos';
        }
        if ($needProblemas) {
            $needs[] = 'problemas';
        }
        echo '[DRY] ' . $rel . ' ← ' . implode(',', $needs) . "\n";
    } else {
        if (file_put_contents($path, $newHtml) === false) {
            fwrite(STDERR, "Falha ao gravar: {$path}\n");
            $errors++;
            continue;
        }
        echo "[OK] {$rel}\n";
    }
    $updated++;
}

echo "\nResumo: atualizados={$updated} ja_completos={$skipped} erros={$errors}"
    . ($dryRun ? " (dry-run)\n" : "\n");
exit($errors > 0 ? 1 : 0);

/**
 * @param list<string> $patterns
 */
function findInsertBefore(string $html, array $patterns): ?int
{
    $best = null;
    foreach ($patterns as $re) {
        if (preg_match($re, $html, $m, PREG_OFFSET_CAPTURE)) {
            $pos = (int) $m[0][1];
            if ($best === null || $pos < $best) {
                $best = $pos;
            }
        }
    }
    return $best;
}

function endOfSectionAfterH2(string $html, int $h2Pos): int
{
    $after = substr($html, $h2Pos + 1);
    if (preg_match('/<h2\b/i', $after, $m, PREG_OFFSET_CAPTURE)) {
        return $h2Pos + 1 + (int) $m[0][1];
    }
    return strlen($html);
}
