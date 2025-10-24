<?php
/**
 * Template PDF PADRÃO TIARAJU - FORMULÁRIO EM BRANCO
 * Uma página única com cabeçalho, campos, questões e histórico
 */

$nomeEmpresa = "Sistema Administrativo";
$codDocumento = "AVAL-" . str_pad($model['id'], 4, '0', STR_PAD_LEFT);
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

        /* CABEÇALHO COM LOGOS */
        .header-section {
            margin-bottom: 15px;
            width: 100%;
        }
        .header-layout {
            width: 100%;
            white-space: nowrap;
            font-size: 0; /* Remove espaço entre inline-blocks */
        }
        .logo-area {
            display: inline-block;
            width: 13%; /* Reduzido para aproximar logos */
            text-align: center;
            vertical-align: top;
            padding-top: 3px; /* Reduzido */
            font-size: 10pt;
            white-space: normal;
        }
        .box-area {
            display: inline-block;
            width: 74%; /* Aumentado para compensar */
            vertical-align: top;
            font-size: 10pt;
            white-space: normal;
        }
        .logo-img {
            max-width: 65px; /* Reduzido */
            max-height: 45px; /* Reduzido */
            height: auto;
        }
        .logo-tagline {
            font-size: 6pt; /* Reduzido */
            margin-top: 2px; /* Reduzido */
            line-height: 1.05;
        }
        .header-box {
            border: 2px solid #000;
            padding: 3px 5px; /* Reduzido */
            text-align: center;
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

        /* CAMPOS DE PREENCHIMENTO */
        .form-fields {
            margin: 12px 0 15px 0;
        }
        .field-row {
            margin: 7px 0;
        }
        .field-label {
            font-weight: bold;
            display: inline;
        }
        .field-underline {
            display: inline-block;
            border-bottom: 1px solid #000;
            width: 450px;
            margin-left: 5px;
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
    <!-- CABEÇALHO COM LOGOS FORA DA CAIXA -->
    <div class="header-section">
        <div class="header-layout">
            <!-- LOGO ESQUERDA -->
            <div class="logo-area">
                <?php if (file_exists($logoPath)): ?>
                    <img src="file://<?= $logoPath ?>" class="logo-img" alt="Logo Tiaraju">
                    <div class="logo-tagline">Natural é se<br>sentir bem</div>
                <?php endif; ?>
            </div>

            <!-- CAIXA CENTRAL -->
            <div class="box-area">
                <div class="header-box">
                    <div class="header-title"><?= $codDocumento ?></div>
                    <div class="header-line"></div>
                    <div class="header-subtitle">ID: <?= $model['id'] ?></div>
                    <div class="header-line"></div>
                    <div class="header-description"><?= strtoupper(htmlspecialchars($model['titulo'])) ?></div>
                </div>
            </div>

            <!-- LOGO DIREITA -->
            <div class="logo-area">
                <?php if (file_exists($logoPath)): ?>
                    <img src="file://<?= $logoPath ?>" class="logo-img" alt="Logo Tiaraju">
                    <div class="logo-tagline">Natural é se<br>sentir bem</div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- CAMPOS DE PREENCHIMENTO -->
    <div class="form-fields">
        <div class="field-row">
            <span class="field-label">Nome:</span>
            <span class="field-underline"></span>
        </div>
        <div class="field-row">
            <span class="field-label">Data:</span>
            <span class="field-underline"></span>
        </div>
        <div class="field-row">
            <span class="field-label">Código e Versão do Documento:</span>
            <span class="field-underline"></span>
        </div>
        <div class="field-row">
            <span class="field-label">Nota:</span>
            <span class="field-underline"></span>
        </div>
    </div>

    <!-- QUESTÕES -->
    <?php foreach ($questions as $index => $questao): 
        $numero = $index + 1;
        $pontos = number_format($questao['pontos'] ?? 1.00, 2, ',', '.');
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
                ?>
                    <div class="opcao-item">( ) <?= htmlspecialchars($alt) ?></div>
                <?php endforeach; ?>
            </div>
        <?php elseif ($questao['tipo'] === 'verdadeiro_falso'): ?>
            <div class="opcoes-list">
                <div class="opcao-item">( ) Verdadeiro.</div>
                <div class="opcao-item">( ) Falso.</div>
            </div>
        <?php else: ?>
            <div style="margin: 8px 0; border: 1px solid #999; min-height: 35px; background: #fff;"></div>
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
                    <td class="versao-col">1</td>
                    <td>Emissão inicial</td>
                </tr>
                <tr>
                    <td class="versao-col"><?= date('Y-m-d') ?></td>
                    <td>Formulário impresso para avaliação (Total: <?= $totalPontos ?> pontos | Nota mínima: <?= number_format($model['nota_minima_aprovacao'] ?? 7.00, 2, ',', '.') ?>)</td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- RODAPÉ -->
    <div class="footer">
        Página 1 de 1
    </div>

</body>
</html>
