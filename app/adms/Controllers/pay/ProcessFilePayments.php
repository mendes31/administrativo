<?php

// namespace App\adms\Controllers\pay;

// /** @var array|string|null $data Dados que devem ser enviados para a VIEW */
// private array|string|null $arquivo = null;


// class ProcessFile

use App\adms\Models\Repository\PaymentsRepository;
use PhpOffice\PhpSpreadsheet\IOFactory;

$arquivo = $_FILES['arquivo'];

$camposObrigatorios = [
    'num_doc',
    'card_code_fornecedor',
    'due_date',
    'installment_number'
];

$importados = 0;
$ignorados = 0;
$atualizados = 0;
$erros = [];

function validarCabecalho($cabecalho, $camposObrigatorios) {
    $faltando = [];
    foreach ($camposObrigatorios as $campo) {
        if (!in_array($campo, $cabecalho)) {
            $faltando[] = $campo;
        }
    }
    return $faltando;
}

function converterData($data) {
    // Se já estiver no formato YYYY-MM-DD, retorna igual
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $data)) {
        return $data;
    }
    // DD-MM-YYYY ou D-M-YYYY
    if (preg_match('/^(\d{1,2})-(\d{1,2})-(\d{4})$/', $data, $m)) {
        if (checkdate($m[2], $m[1], $m[3])) {
            return sprintf('%04d-%02d-%02d', $m[3], $m[2], $m[1]);
        }
    }
    // DD/MM/YYYY ou D/M/YYYY
    if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $data, $m)) {
        if (checkdate($m[2], $m[1], $m[3])) {
            return sprintf('%04d-%02d-%02d', $m[3], $m[2], $m[1]);
        }
    }
    // MM/DD/YYYY ou M/D/YYYY (caso americano)
    if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $data, $m)) {
        if (checkdate($m[1], $m[2], $m[3])) {
            return sprintf('%04d-%02d-%02d', $m[3], $m[1], $m[2]);
        }
    }
    // Se não for possível converter, retorna vazio
    return '';
}

function normalizarData($data) {
    return $data ? date('Y-m-d', strtotime($data)) : '';
}

function normalizarValor($valor) {
    // Remove pontos (milhar) e troca vírgula por ponto (decimal)
    $valor = str_replace('.', '', $valor); // remove separador de milhar
    $valor = str_replace(',', '.', $valor); // troca vírgula por ponto
    return number_format((float)$valor, 2, '.', '');
}

// Lista de feriados nacionais, RS e Santo Ângelo (formato 'm-d')
function getFeriados($ano) {
    return [
        // Nacionais
        "$ano-01-01", // Confraternização Universal
        "$ano-04-21", // Tiradentes
        "$ano-05-01", // Dia do Trabalho
        "$ano-09-07", // Independência
        "$ano-10-12", // N. Sra. Aparecida
        "$ano-11-02", // Finados
        "$ano-11-15", // Proclamação da República
        "$ano-12-25", // Natal
        // Móveis (exemplo: Corpus Christi, Carnaval, Paixão de Cristo)
        date('Y-m-d', easter_date($ano) - 47*24*60*60), // Carnaval
        date('Y-m-d', easter_date($ano) - 2*24*60*60),  // Sexta-feira Santa
        date('Y-m-d', easter_date($ano) + 60*24*60*60), // Corpus Christi
        // RS
        "$ano-09-20", // Revolução Farroupilha
        // Santo Ângelo
        "$ano-03-22", // Aniversário Santo Ângelo
    ];
}

// Função para calcular o próximo dia útil
function proximoDiaUtil($data) {
    $timestamp = strtotime($data);
    if (!$timestamp) {
        // Data inválida, retorna a própria data ou vazio
        return $data;
    }
    $ano = date('Y', $timestamp);
    if ($ano < 1970) {
        // Ano inválido para easter_date, retorna a própria data ou vazio
        return $data;
    }
    $feriados = getFeriados($ano);
    $dataTeste = $data;
    while (in_array($dataTeste, $feriados) || date('N', strtotime($dataTeste)) >= 6) {
        $dataTeste = date('Y-m-d', strtotime($dataTeste . ' +1 day'));
    }
    return $dataTeste;
}

function gerenciarParcelas($num_doc, $card_code_fornecedor, $novasParcelas) {
    $paymentCreate = new PaymentsRepository();
    
    // Buscar todas as parcelas existentes do documento
    $parcelasExistentes = $paymentCreate->buscarParcelasDocumento($num_doc, $card_code_fornecedor);
    $totalParcelasExistentes = count($parcelasExistentes);
    $totalNovasParcelas = count($novasParcelas);
    
    // Se o número de parcelas diminuiu
    if ($totalNovasParcelas < $totalParcelasExistentes) {
        // Deletar parcelas excedentes
        for ($i = $totalNovasParcelas + 1; $i <= $totalParcelasExistentes; $i++) {
            $paymentCreate->deletarParcela($num_doc, $card_code_fornecedor, $i);
        }
    }
    
    // Se o número de parcelas aumentou
    if ($totalNovasParcelas > $totalParcelasExistentes) {
        // Calcular o valor total das parcelas existentes
        $valorTotal = 0;
        foreach ($parcelasExistentes as $parcela) {
            $valorTotal += $parcela['value'];
        }
        
        // Calcular o valor médio para as novas parcelas
        $valorMedio = $valorTotal / $totalNovasParcelas;
        
        // Adicionar novas parcelas
        for ($i = $totalParcelasExistentes + 1; $i <= $totalNovasParcelas; $i++) {
            $novaParcela = [
                'num_doc' => $num_doc,
                'card_code_fornecedor' => $card_code_fornecedor,
                'installment_number' => $i,
                'value' => number_format($valorMedio, 2, '.', ''),
                'due_date' => $novasParcelas[$i-1]['due_date'],
                'expected_date' => proximoDiaUtil($novasParcelas[$i-1]['due_date']),
                'pay_method_id' => 0,
                'issue_date' => null
            ];
            $paymentCreate->importPayment($novaParcela);
        }
    }
    
    // Atualizar parcelas existentes
    foreach ($novasParcelas as $index => $parcela) {
        $numeroParcela = $index + 1;
        if ($numeroParcela <= $totalParcelasExistentes) {
            $paymentCreate->atualizarPayment($parcela);
        }
    }
}

if ($arquivo['type'] == "text/csv") {
    $dados_arquivo = fopen($arquivo['tmp_name'], "r");
    $cabecalho = fgetcsv($dados_arquivo, 1000, ";");
    // Converter cabeçalho para UTF-8
    foreach ($cabecalho as &$campo) {
        $campo = mb_convert_encoding($campo, 'UTF-8', 'auto');
    }
    unset($campo);
    $faltando = validarCabecalho($cabecalho, $camposObrigatorios);
    if ($faltando) {
        echo "Faltam os campos obrigatórios: ".implode(', ', $faltando);
        exit;
    }
    
    $paymentCreate = new PaymentsRepository();
    $parcelasPorDocumento = [];
    
    // Primeiro, agrupar todas as parcelas por documento
    while ($linha = fgetcsv($dados_arquivo, 1000, ";")) {
        // Converter cada valor para UTF-8
        foreach ($linha as &$valor) {
            $valor = mb_convert_encoding($valor, 'UTF-8', 'auto');
        }
        unset($valor);
        $dados = array_combine($cabecalho, $linha);
        
        // Normalizar campos
        $dados['num_doc'] = trim((string)$dados['num_doc']);
        $dados['card_code_fornecedor'] = trim((string)$dados['card_code_fornecedor']);
        $dados['installment_number'] = (int)preg_replace('/[^0-9]/', '', $dados['installment_number']);
        
        if (isset($dados['due_date'])) {
            $dados['due_date'] = trim(converterData($dados['due_date']));
            $dados['expected_date'] = proximoDiaUtil($dados['due_date']);
        }
        
        // Ajustar para que doc_date receba o valor do arquivo e issue_date seja sempre null
        if (isset($dados['doc_date'])) {
            $dados['doc_date'] = trim(converterData($dados['doc_date']));
        } else {
            $dados['doc_date'] = null;
        }
        $dados['issue_date'] = null;
        
        // Remover campos removidos do banco
        // Ajustar para que original_value receba o valor de value do arquivo
        if (isset($dados['value'])) {
            $dados['original_value'] = $dados['value'];
            unset($dados['value']);
        }
        
        $chave = $dados['num_doc'] . '_' . $dados['card_code_fornecedor'];
        if (!isset($parcelasPorDocumento[$chave])) {
            $parcelasPorDocumento[$chave] = [];
        }
        $parcelasPorDocumento[$chave][] = $dados;
    }
    
    // Processar cada documento e suas parcelas
    foreach ($parcelasPorDocumento as $chave => $parcelas) {
        list($num_doc, $card_code_fornecedor) = explode('_', $chave);
        $num_doc = trim((string)$num_doc);
        $card_code_fornecedor = trim((string)$card_code_fornecedor);
        $parcelasExistentes = $paymentCreate->buscarParcelasDocumento($num_doc, $card_code_fornecedor);
        $totalParcelasExistentes = count($parcelasExistentes);
        $totalNovasParcelas = count($parcelas);

        // Se o número de parcelas diminuiu, apaga as excedentes
        if ($totalNovasParcelas < $totalParcelasExistentes) {
            for ($i = $totalNovasParcelas + 1; $i <= $totalParcelasExistentes; $i++) {
                $paymentCreate->deletarParcela($num_doc, $card_code_fornecedor, $i);
            }
            $parcelasExistentes = $paymentCreate->buscarParcelasDocumento($num_doc, $card_code_fornecedor);
        }

        foreach ($parcelas as $parcela) {
            error_log('Processando parcela: ' . print_r($parcela, true));
            $parcela['num_doc'] = trim((string)$parcela['num_doc']);
            $parcela['card_code_fornecedor'] = trim((string)$parcela['card_code_fornecedor']);
            $parcela['installment_number'] = (int)preg_replace('/[^0-9]/', '', $parcela['installment_number']);
            $existe = false;
            foreach ($parcelasExistentes as $existente) {
                if (
                    trim((string)$existente['num_doc']) === $parcela['num_doc'] &&
                    trim((string)$existente['card_code_fornecedor']) === $parcela['card_code_fornecedor'] &&
                    (int)$existente['installment_number'] === $parcela['installment_number']
                ) {
                    $existe = true;
                    // Verifica se algum campo relevante vindo do arquivo mudou
                    $deveAtualizar = false;
                    $diferencas = [];
                    if (!camposEquivalentes($existente['due_date'] ?? '', $parcela['due_date'] ?? '', 'data')) {
                        $deveAtualizar = true;
                        $diferencas['due_date'] = ['banco' => $existente['due_date'], 'arquivo' => $parcela['due_date']];
                    }
                    if (!camposEquivalentes($existente['expected_date'] ?? '', $parcela['expected_date'] ?? '', 'data')) {
                        $deveAtualizar = true;
                        $diferencas['expected_date'] = ['banco' => $existente['expected_date'], 'arquivo' => $parcela['expected_date']];
                    }
                    if (!camposEquivalentes($existente['issue_date'] ?? '', $parcela['issue_date'] ?? '', 'data')) {
                        $deveAtualizar = true;
                        $diferencas['issue_date'] = ['banco' => $existente['issue_date'], 'arquivo' => $parcela['issue_date']];
                    }
                    if (!camposEquivalentes($existente['description'] ?? '', $parcela['description'] ?? '')) {
                        $deveAtualizar = true;
                        $diferencas['description'] = ['banco' => $existente['description'], 'arquivo' => $parcela['description']];
                    }
                    if (!camposEquivalentes($existente['num_nota'] ?? '', $parcela['num_nota'] ?? '')) {
                        $deveAtualizar = true;
                        $diferencas['num_nota'] = ['banco' => $existente['num_nota'], 'arquivo' => $parcela['num_nota']];
                    }
                    if (!camposEquivalentes($existente['file'] ?? '', $parcela['file'] ?? '')) {
                        $deveAtualizar = true;
                        $diferencas['file'] = ['banco' => $existente['file'], 'arquivo' => $parcela['file']];
                    }
                    if ($deveAtualizar) {
                        logComparacaoCampos($num_doc, $parcela['installment_number'], $diferencas);
                        $dadosAtualizar = array_merge($existente, $parcela);
                        $paymentCreate->atualizarPayment($dadosAtualizar);
                        $atualizados++;
                    } else {
                        $ignorados++;
                    }
                    break;
                }
            }
            if (!$existe) {
                $paymentCreate->importPayment($parcela);
                $importados++;
                // Atualiza a lista de parcelas existentes após inserção
                $parcelasExistentes = $paymentCreate->buscarParcelasDocumento($num_doc, $card_code_fornecedor);
            }
        }
    }
    
    fclose($dados_arquivo);
} else {
    echo "Necessário enviar arquivo CSV!";
    exit;
}

// Após o fechamento do arquivo e contagem dos registros:
// Redireciona para list-payments com popup de resultado
echo "<script>alert('Importação finalizada!\\nRegistros importados: $importados\\nRegistros atualizados: $atualizados\\nRegistros ignorados (já existentes e sem alteração): $ignorados');window.location.href='/administrativo/list-payments';</script>";
exit;

// Função para converter encoding se necessário
function converter(&$dados_arquivo)
{
    $dados_arquivo = mb_convert_encoding($dados_arquivo, "UTF-8", "ISO-8859-1");
}

// Forçar log para arquivo específico
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/import_debug.log');

function logComparacaoCampos($num_doc, $installment_number, $diferencas) {
    $logDir = __DIR__ . '/../../../logs';
    if (!is_dir($logDir)) {
        mkdir($logDir, 0777, true);
    }
    $logFile = $logDir . '/import_compare.log';
    $mensagem = "\n" . str_repeat("=", 80) . "\n";
    $mensagem .= date('Y-m-d H:i:s') . "\n";
    $mensagem .= "Documento: $num_doc | Parcela: $installment_number\n";
    $mensagem .= "Diferenças encontradas:\n";
    foreach ($diferencas as $campo => $valores) {
        $mensagem .= "- $campo:\n";
        $mensagem .= "  Banco: " . $valores['banco'] . "\n";
        $mensagem .= "  Arquivo: " . $valores['arquivo'] . "\n";
    }
    $mensagem .= str_repeat("=", 80) . "\n";
    file_put_contents($logFile, $mensagem, FILE_APPEND);
}

function camposEquivalentes($a, $b, $tipo = 'string') {
    $vazios = ['', null, '0000-00-00', '0000-00-00 00:00:00'];
    if ($tipo === 'data') {
        if (in_array($a, $vazios) && in_array($b, $vazios)) return true;
        return normalizarData($a) === normalizarData($b);
    }
    if ($tipo === 'valor') {
        return normalizarValor($a) === normalizarValor($b);
    }
    // Para strings, ignora espaços e case
    return trim((string)$a) === trim((string)$b);
}
