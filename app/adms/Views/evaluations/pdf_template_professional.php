<?php
/**
 * Template PDF PADRÃO TIARAJU - RESULTADO COM RESPOSTAS
 * Uma página única com cabeçalho, dados, questões respondidas e histórico
 */

$nomeEmpresa = "Sistema Administrativo";
$codDocumento = "AVAL-" . str_pad($model['id'], 4, '0', STR_PAD_LEFT);
$docCodigo = "DOC-AVAL-" . str_pad($attempt['id'], 6, '0', STR_PAD_LEFT);
$totalPontos = array_sum(array_column($questions, 'pontos'));
// Caminho absoluto do logo para MPDF
$logoPath = realpath(dirname(__DIR__, 3) . '/public/adms/image/logo/logo.png');
if (!$logoPath) {
    $logoPath = getcwd() . '/public/adms/image/logo/logo.png';
}
$logoPath = str_replace('\\', '/', $logoPath); // Normalizar para MPDF
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        @page {
            margin: 12mm 15mm 12mm 15mm;
        }
        body {
            font-family: "Arial", sans-serif;
            font-size: 10pt;
            color: #000;
            line-height: 1.35;
        }

        /* CABEÇALHO COM LOGO DENTRO DA CAIXA */
        .header-section {
            margin-bottom: 15px;
            width: 100%;
            text-align: center;
        }
        .header-layout {
            width: 70%;
            margin: 0 auto;
        }
        .header-box {
            border: 2px solid #000;
            padding: 8px 10px;
            text-align: center;
            position: relative;
        }
        .header-logo-top {
            text-align: center;
            margin-bottom: 8px;
        }
        .logo-img {
            max-width: 100px;
            max-height: 60px;
            height: auto;
        }
        .logo-tagline {
            font-size: 7pt;
            margin-top: 3px;
            line-height: 1.1;
            color: #2d5f2e;
        }
        .header-title {
            font-size: 10.5pt; /* Reduzido */
            font-weight: bold;
            margin: 1px 0; /* Reduzido */
            padding: 1px 0;
        }
        .header-line {
            border-top: 1px solid #000;
            margin: 0;
            height: 1px;
        }
        .header-subtitle {
            font-size: 9pt; /* Reduzido */
            font-weight: bold;
            margin: 1px 0; /* Reduzido */
            padding: 1px 0;
        }
        .header-description {
            font-size: 7.5pt; /* Reduzido */
            margin: 1px 0; /* Reduzido */
            padding: 1px 0;
            line-height: 1.15;
        }

        /* CAMPOS PREENCHIDOS */
        .info-fields {
            margin: 12px 0 15px 0;
            padding: 10px;
            border: 1px solid #999;
            background: #f5f5f5;
        }
        .info-row {
            margin: 6px 0;
            font-size: 9.5pt;
        }
        .info-label {
            font-weight: bold;
            display: inline-block;
            width: 180px;
        }
        .info-value {
            display: inline;
        }

        /* BOX DE RESULTADO */
        .resultado-box {
            border: 3px solid <?= $statusCor ?>;
            background-color: <?= $statusCorClara ?>;
            padding: 12px;
            margin: 15px 0;
            text-align: center;
        }
        .resultado-status {
            font-size: 20pt;
            font-weight: bold;
            color: <?= $statusCor ?>;
            margin: 0 0 10px 0;
            letter-spacing: 3px;
        }
        .metricas {
            margin-top: 10px;
        }
        .metricas table {
            width: 100%;
        }
        .metricas td {
            text-align: center;
            padding: 5px;
        }
        .metrica-valor {
            font-size: 16pt;
            font-weight: bold;
            color: <?= $statusCor ?>;
            display: block;
        }
        .metrica-label {
            font-size: 7.5pt;
            color: #333;
            display: block;
            margin-top: 2px;
            text-transform: uppercase;
        }

        /* QUESTÕES */
        .questao-container {
            margin: 14px 0;
            page-break-inside: avoid;
        }
        .questao-titulo {
            font-weight: bold;
            margin-bottom: 6px;
        }
        .questao-texto {
            margin-bottom: 8px;
            line-height: 1.4;
        }
        .opcoes-list {
            margin-left: 20px;
        }
        .opcao-item {
            margin: 5px 0;
            padding: 3px 6px;
        }
        .opcao-correta {
            background-color: #d4edda;
            font-weight: bold;
        }
        .opcao-selecionada-correta {
            background-color: #cce5ff;
            font-weight: bold;
        }
        .opcao-selecionada-incorreta {
            background-color: #f8d7da;
        }
        .questao-resultado {
            margin-top: 8px;
            padding: 5px;
            text-align: right;
            font-weight: bold;
            font-size: 9pt;
            border-top: 1px dotted #999;
        }
        .resultado-correto {
            color: #28a745;
        }
        .resultado-incorreto {
            color: #dc3545;
        }

        /* HISTÓRICO */
        .historico-section {
            margin-top: 25px;
            page-break-inside: avoid;
        }
        .historico-titulo {
            font-weight: bold;
            font-size: 10.5pt;
            margin-bottom: 8px;
            text-transform: uppercase;
            text-align: center;
        }
        .historico-table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #000;
        }
        .historico-table th {
            background-color: #d9d9d9;
            border: 1px solid #000;
            padding: 6px;
            font-weight: bold;
            text-align: center;
        }
        .historico-table td {
            border: 1px solid #000;
            padding: 6px;
        }
        .versao-col {
            width: 20%;
            text-align: center;
        }

        /* ASSINATURA */
        .assinatura-section {
            margin-top: 30px;
            page-break-inside: avoid;
        }
        .assinatura-info {
            margin-bottom: 10px;
            font-size: 9pt;
        }
        .assinatura-linha {
            border-top: 1.5px solid #000;
            margin-top: 45px;
            padding-top: 5px;
            text-align: center;
            font-size: 9pt;
        }

        /* RODAPÉ */
        .footer {
            margin-top: 15px;
            text-align: right;
            font-size: 8.5pt;
            color: #666;
        }
    </style>
</head>
<body>
    <!-- CABEÇALHO COM LOGO DENTRO DA CAIXA -->
    <div class="header-section">
        <div class="header-layout">
            <div class="header-box">
                <!-- LOGO NO TOPO DA CAIXA -->
                <div class="header-logo-top">
                    <?php if (file_exists($logoPath)): ?>
                        <img src="file://<?= $logoPath ?>" class="logo-img" alt="Logo Tiaraju">
                        <div class="logo-tagline">Natural é se sentir bem</div>
                    <?php endif; ?>
                </div>
                
                <!-- INFORMAÇÕES DO DOCUMENTO -->
                <div class="header-line"></div>
                <div class="header-title"><?= $codDocumento ?></div>
                <div class="header-line"></div>
                <div class="header-subtitle"><?= $docCodigo ?></div>
                <div class="header-line"></div>
                <div class="header-description"><?= strtoupper(htmlspecialchars($model['titulo'])) ?></div>
            </div>
        </div>
    </div>

    <!-- CAMPOS PREENCHIDOS -->
    <div class="info-fields">
        <div class="info-row">
            <span class="info-label">Nome:</span>
            <span class="info-value"><?= htmlspecialchars($user['name']) ?></span>
        </div>
        <div class="info-row">
            <span class="info-label">Data:</span>
            <span class="info-value"><?= $dataAvaliacao ?></span>
        </div>
        <div class="info-row">
            <span class="info-label">Código do Documento:</span>
            <span class="info-value"><?= $docCodigo ?></span>
        </div>
        <div class="info-row">
            <span class="info-label">Nota:</span>
            <span class="info-value" style="font-weight: bold; color: <?= $statusCor ?>;">
                <?= number_format($attempt['nota_obtida'], 2, ',', '.') ?>
            </span>
        </div>
    </div>

    <!-- RESULTADO -->
    <div class="resultado-box">
        <div class="resultado-status"><?= $statusTexto ?></div>
        <div class="metricas">
            <table>
                <tr>
                    <td>
                        <span class="metrica-valor"><?= number_format($attempt['nota_obtida'], 2, ',', '.') ?></span>
                        <span class="metrica-label">Nota Final</span>
                    </td>
                    <td>
                        <span class="metrica-valor"><?= $attempt['questoes_corretas'] ?>/<?= $attempt['total_questoes'] ?></span>
                        <span class="metrica-label">Quest. Corretas</span>
                    </td>
                    <td>
                        <span class="metrica-valor"><?= number_format($attempt['percentual'], 1, ',', '.') ?>%</span>
                        <span class="metrica-label">Aproveitamento</span>
                    </td>
                    <td>
                        <span class="metrica-valor"><?= number_format($model['nota_minima_aprovacao'] ?? 7.00, 2, ',', '.') ?></span>
                        <span class="metrica-label">Nota Mínima</span>
                    </td>
                </tr>
            </table>
        </div>
    </div>

    <!-- QUESTÕES E RESPOSTAS -->
    <?php foreach ($questions as $index => $questao): 
        $numero = $index + 1;
        $pontos = number_format($questao['pontos'] ?? 1.00, 2, ',', '.');
        
        // Buscar resposta por questao_id
        $respostaData = null;
        foreach ($respostas as $r) {
            if ($r['questao_id'] == $questao['id']) {
                $respostaData = $r;
                break;
            }
        }
        
        $respostaUsuario = $respostaData['resposta'] ?? null;
        $correta = !empty($respostaData['correta']);
        $pontosObtidos = $respostaData['pontos_obtidos'] ?? 0;
    ?>
    <div class="questao-container">
        <div class="questao-titulo">
            Questão <?= $numero ?>. (<?= $pontos ?> pontos)
        </div>
        <div class="questao-texto">
            <?= nl2br(htmlspecialchars($questao['pergunta'])) ?>
        </div>

        <?php if ($questao['tipo'] === 'multipla_escolha' && !empty($questao['opcoes'])): 
            $alternativas = explode("\n", $questao['opcoes']);
        ?>
            <div class="opcoes-list">
                <?php foreach ($alternativas as $alt): 
                    $alt = trim($alt);
                    if (empty($alt)) continue;
                    
                    $ehCorreta = ($alt === $questao['resposta_correta']);
                    $foiSelecionada = ($alt === $respostaUsuario);
                    
                    $classOpcao = 'opcao-item';
                    $marcador = '( )';
                    
                    if ($foiSelecionada && $correta) {
                        $classOpcao .= ' opcao-selecionada-correta';
                        $marcador = '(✓)';
                    } elseif ($foiSelecionada && !$correta) {
                        $classOpcao .= ' opcao-selecionada-incorreta';
                        $marcador = '(✗)';
                    } elseif (!$foiSelecionada && $ehCorreta) {
                        $classOpcao .= ' opcao-correta';
                        $marcador = '(✓)';
                    }
                ?>
                    <div class="<?= $classOpcao ?>">
                        <?= $marcador ?> <?= htmlspecialchars($alt) ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php elseif ($questao['tipo'] === 'verdadeiro_falso'): ?>
            <div class="opcoes-list">
                <?php
                foreach (['Verdadeiro', 'Falso'] as $opcao):
                    $ehCorreta = ($opcao === $questao['resposta_correta']);
                    $foiSelecionada = ($opcao === $respostaUsuario);
                    
                    $classOpcao = 'opcao-item';
                    $marcador = '( )';
                    
                    if ($foiSelecionada && $correta) {
                        $classOpcao .= ' opcao-selecionada-correta';
                        $marcador = '(✓)';
                    } elseif ($foiSelecionada && !$correta) {
                        $classOpcao .= ' opcao-selecionada-incorreta';
                        $marcador = '(✗)';
                    } elseif (!$foiSelecionada && $ehCorreta) {
                        $classOpcao .= ' opcao-correta';
                        $marcador = '(✓)';
                    }
                ?>
                    <div class="<?= $classOpcao ?>">
                        <?= $marcador ?> <?= $opcao ?>.
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div style="margin: 8px 20px; padding: 8px; border: 1px solid #999; background: <?= $correta ? '#d4edda' : '#f8d7da' ?>;">
                <strong>Resposta:</strong> <?= htmlspecialchars($respostaUsuario ?? 'Não respondida') ?>
            </div>
            <?php if (!empty($questao['resposta_correta'])): ?>
                <div style="margin: 5px 20px; padding: 8px; border: 1px solid #999; background: #d4edda;">
                    <strong>Gabarito:</strong> <?= htmlspecialchars($questao['resposta_correta']) ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <div class="questao-resultado <?= $correta ? 'resultado-correto' : 'resultado-incorreto' ?>">
            <?= $correta ? '✓ CORRETO' : '✗ INCORRETO' ?> -
            <?= number_format($pontosObtidos ?? 0, 2, ',', '.') ?> / <?= number_format($questao['pontos'] ?? 1.00, 2, ',', '.') ?> pts
        </div>

        <?php if (!empty($questao['explicacao'])): ?>
            <div style="margin: 8px 20px; padding: 8px; background: #fff3cd; border-left: 3px solid #ffc107; font-size: 9pt;">
                <strong>Explicação:</strong> <?= nl2br(htmlspecialchars($questao['explicacao'])) ?>
            </div>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>

    <!-- HISTÓRICO DE ALTERAÇÕES -->
    <div class="historico-section">
        <div class="historico-titulo">Histórico de Alterações</div>
        <table class="historico-table">
            <thead>
                <tr>
                    <th class="versao-col">Versão</th>
                    <th>Descrição</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="versao-col">1.0</td>
                    <td>Emissão inicial do formulário</td>
                </tr>
                <tr>
                    <td class="versao-col"><?= date('Y-m-d') ?></td>
                    <td>Resultado da Tentativa #<?= $attempt['tentativa_numero'] ?> - <?= $statusTexto ?> - Nota: <?= number_format($attempt['nota_obtida'], 2, ',', '.') ?></td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- ASSINATURA -->
    <div class="assinatura-section">
        <div class="assinatura-info">
            <strong>Colaborador:</strong> <?= htmlspecialchars($user['name']) ?><br>
            <strong>Data de Impressão:</strong> <?= $dataImpressao ?>
        </div>
        <div class="assinatura-linha">
            <?= htmlspecialchars($user['name']) ?><br>
            <span style="font-size: 8pt; color: #666;">Assinatura do Colaborador</span>
        </div>
    </div>

    <!-- RODAPÉ -->
    <div class="footer">
        Este documento é válido como comprovante oficial de realização da avaliação.<br>
        Gerado automaticamente em <?= $dataImpressao ?> - Página 1 de 1
    </div>

</body>
</html>
