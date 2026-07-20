<?php

declare(strict_types=1);

/**
 * Insere <h2>O que significam os termos</h2> em tópicos do manual que usam jargão
 * conhecido e ainda não têm a secção. Não cria secção vazia.
 *
 * Uso:
 *   php scripts/manual_ensure_termos_section.php --dry-run
 *   php scripts/manual_ensure_termos_section.php --only=gestao_pessoas
 *   php scripts/manual_ensure_termos_section.php --limit=90
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

/** @var list<array{id:string,label:string,pattern:string,definition:string}> */
$catalog = [
    [
        'id' => 'enps',
        'label' => 'eNPS (Employee Net Promoter Score)',
        'pattern' => '/\beNPS\b/iu',
        'definition' => 'Indicador de quanto o colaborador recomendaria a empresa como lugar para trabalhar, numa escala de <strong>0 a 10</strong>. Promotores (9–10), neutros (7–8), detratores (0–6); o score é <code>% promotores − % detratores</code>.',
    ],
    [
        'id' => 'pulse',
        'label' => 'Pulse (pesquisa pulso)',
        'pattern' => '/\bPulse\b/u',
        'definition' => 'Pesquisa <strong>rápida e periódica</strong> de clima, com poucas perguntas (Likert e/ou texto), para acompanhar temas pontuais sem o peso de uma pesquisa anual longa.',
    ],
    [
        'id' => 'pdi',
        'label' => 'PDI (Plano de Desenvolvimento Individual)',
        'pattern' => '/\bPDI\b/u',
        'definition' => 'Plano individual com objetivos, ações, prazos e evidências que traduz avaliação/calibração em desenvolvimento concreto no período.',
    ],
    [
        'id' => 'okr',
        'label' => 'OKR / Meta',
        'pattern' => '/\bOKRs?\b|\bmetas?\b.*\bOKR\b/iu',
        'definition' => 'Objetivo com resultado-chave ou indicador mensurável (meta) acompanhado no ciclo de desempenho.',
    ],
    [
        'id' => 'ninebox',
        'label' => 'Nine Box / 9BOX',
        'pattern' => '/\bNine\s*Box\b|\b9BOX\b|\b9\s*BOX\b/iu',
        'definition' => 'Matriz 3×3 que cruza <strong>desempenho</strong> e <strong>potencial</strong> para apoiar talent pool, sucessão e priorização de desenvolvimento.',
    ],
    [
        'id' => 'hipo',
        'label' => 'HiPo (alto potencial)',
        'pattern' => '/\bHiPo\b/u',
        'definition' => 'Colaborador identificado com alto potencial, tipicamente priorizado no talent pool e em planos de sucessão/desenvolvimento.',
    ],
    [
        'id' => 'calibracao',
        'label' => 'Calibração',
        'pattern' => '/\bcalibra(?:ção|cao|ções|coes|r|das?|dos?)\b/iu',
        'definition' => 'Sessão em que gestores alinham notas de desempenho/potencial entre áreas antes do fechamento do ciclo, reduzindo vieses entre equipes.',
    ],
    [
        'id' => 'ciclo',
        'label' => 'Ciclo de desempenho',
        'pattern' => '/\bciclo(?:s)? de desempenho\b|\bciclo(?:s)?\b(?=.*avalia)/iu',
        'definition' => 'Janela temporal que agrupa avaliações, metas, feedbacks e calibração sob a mesma governança de RH.',
    ],
    [
        'id' => 'talentpool',
        'label' => 'Talent pool',
        'pattern' => '/\btalent\s*pool\b/iu',
        'definition' => 'Banco interno de talentos nomeados (ex.: a partir da 9BOX) para oportunidades futuras, HiPo e sucessão.',
    ],
    [
        'id' => 'sucessao',
        'label' => 'Sucessão',
        'pattern' => '/\bsucess[aã]o\b|\bsucessor(?:es)?\b/iu',
        'definition' => 'Preparação e acompanhamento de sucessores para cargos críticos, com graus de prontidão (readiness).',
    ],
    [
        'id' => 'readiness',
        'label' => 'Readiness (prontidão)',
        'pattern' => '/\breadiness\b|\bprontid[aã]o\b/iu',
        'definition' => 'Grau de prontidão do sucessor para assumir o cargo crítico (ex.: pronto agora, em 1–2 anos).',
    ],
    [
        'id' => 'competencia',
        'label' => 'Competência',
        'pattern' => '/\bcompet[eê]ncias?\b/iu',
        'definition' => 'Conhecimento, habilidade ou comportamento avaliável, usado em avaliações, PDI e requisitos de cargo.',
    ],
    [
        'id' => 'lgpd',
        'label' => 'LGPD',
        'pattern' => '/\bLGPD\b/u',
        'definition' => 'Lei Geral de Proteção de Dados: regras para tratar dados pessoais com base legal, segurança e direitos do titular.',
    ],
    [
        'id' => 'epi',
        'label' => 'EPI',
        'pattern' => '/\bEPIs?\b/u',
        'definition' => 'Equipamento de Proteção Individual exigido para controlar riscos ocupacionais; no SST inclui fichas e entregas.',
    ],
    [
        'id' => 'aso',
        'label' => 'ASO',
        'pattern' => '/\bASOs?\b/u',
        'definition' => 'Atestado de Saúde Ocupacional emitido após exame médico do trabalho (admissional, periódico, etc.).',
    ],
    [
        'id' => 'ppp',
        'label' => 'PPP',
        'pattern' => '/\bPPP\b/u',
        'definition' => 'Perfil Profissiográfico Previdenciário: histórico de exposições e atividades para fins previdenciários/eSocial.',
    ],
    [
        'id' => 'cipa',
        'label' => 'CIPA',
        'pattern' => '/\bCIPA\b/u',
        'definition' => 'Comissão Interna de Prevenção de Acidentes (e assédio), com inspeções e atas no módulo SST.',
    ],
    [
        'id' => 'sla',
        'label' => 'SLA',
        'pattern' => '/\bSLAs?\b/u',
        'definition' => 'Service Level Agreement: prazo ou nível de serviço acordado (ex.: tempo de atendimento de chamado).',
    ],
    [
        'id' => 'okr_meta_label',
        'label' => 'OKR',
        'pattern' => '/\b\(OKRs?\)|\bOKRs?\b/u',
        'definition' => 'Objectives and Key Results: forma de estruturar metas com objetivos e resultados-chave mensuráveis.',
    ],
];

// Evitar duplicar OKR se já adicionado pelo padrão composto
$root = dirname(__DIR__) . '/docs/manual/content';
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)
);

$updated = 0;
$skippedOk = 0;
$skippedNoTerms = 0;
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

    if (preg_match('/<h2>\s*O que significam os termos\s*<\/h2>/iu', $html)) {
        $skippedOk++;
        continue;
    }

    $matched = [];
    $seenIds = [];
    foreach ($catalog as $term) {
        if (isset($seenIds[$term['id']])) {
            continue;
        }
        // Evitar falso positivo de "Pulse" em palavras compostas irrelevantes: já é word boundary
        if (!preg_match($term['pattern'], $html)) {
            continue;
        }
        // "competência" aparece em quase tudo de RH — só incluir se o título/função for sobre competências
        // ou se houver poucas ocorrências? Better: always include if matched for clarity on GP pages.
        // Skip generic "ciclo" false positives on unrelated pages without desempenho context
        if ($term['id'] === 'ciclo' && !preg_match('/desempenho|avalia|calibra|PDI|Nine|9BOX|meta|OKR/iu', $html)) {
            continue;
        }
        if ($term['id'] === 'competencia' && !preg_match('/<h1>[^<]*(compet|PDI|avalia|desempenho)/iu', $html)
            && !preg_match('/Função no sistema[\s\S]{0,400}compet/iu', $html)) {
            continue;
        }
        if ($term['id'] === 'okr' && isset($seenIds['okr_meta_label'])) {
            continue;
        }
        if ($term['id'] === 'okr_meta_label' && isset($seenIds['okr'])) {
            continue;
        }
        $seenIds[$term['id']] = true;
        $matched[] = $term;
    }

    if ($matched === []) {
        $skippedNoTerms++;
        continue;
    }

    $dl = "<h2>O que significam os termos</h2>\n<dl>\n";
    foreach ($matched as $term) {
        $dl .= '    <dt>' . $term['label'] . '</dt>' . "\n";
        $dl .= '    <dd>' . $term['definition'] . '</dd>' . "\n";
    }
    $dl .= "</dl>\n\n";

    // Inserir após "Quem acessa" se existir; senão após bloco Função; senão após H1+Função
    $insertAt = null;
    if (preg_match('/<p[^>]*>\s*<strong>\s*Quem acessa:\s*<\/strong>.*?<\/p>/isu', $html, $m, PREG_OFFSET_CAPTURE)) {
        $insertAt = $m[0][1] + strlen($m[0][0]);
    } elseif (preg_match('/<h2>\s*Função no sistema\s*<\/h2>[\s\S]*?<\/p>/iu', $html, $m, PREG_OFFSET_CAPTURE)) {
        $insertAt = $m[0][1] + strlen($m[0][0]);
    } elseif (preg_match('/<\/h1>/iu', $html, $m, PREG_OFFSET_CAPTURE)) {
        $insertAt = $m[0][1] + strlen($m[0][0]);
    } else {
        fwrite(STDERR, "Sem âncora de inserção: {$path}\n");
        $errors++;
        continue;
    }

    $newHtml = substr($html, 0, $insertAt) . "\n\n" . $dl . substr($html, $insertAt);
    $newHtml = preg_replace('/\n{3,}/', "\n\n", $newHtml) ?? $newHtml;

    if ($dryRun) {
        $ids = implode(',', array_column($matched, 'id'));
        echo "[DRY] {$rel} ← {$ids}\n";
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

echo "\nResumo: atualizados={$updated} ja_com_secao={$skippedOk} sem_jargon={$skippedNoTerms} erros={$errors}"
    . ($dryRun ? " (dry-run)\n" : "\n");
exit($errors > 0 ? 1 : 0);
