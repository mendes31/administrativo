<?php

// namespace App\adms\Controllers\pay;

// /** @var array|string|null $data Dados que devem ser enviados para a VIEW */
// private array|string|null $arquivo = null;


// class ProcessFile

use App\adms\Models\Repository\PaymentsRepository;

$arquivo = $_FILES['arquivo'];
var_dump($arquivo);


$primeira_linha = true;

if ($arquivo['type'] == "text/csv") {

    // Instanciar o Repository para criar o Fornecedor        
    $paymentCreate = new PaymentsRepository();

    $dados_arquivo = fopen($arquivo['tmp_name'], "r");

    while ($linha = fgetcsv($dados_arquivo, 1000, ";")) {

        // if($primeira_linha){
        //     $primeira_linha = false;
        //     continue;
        // }

        array_walk_recursive($linha, 'converter');
        var_dump($linha);
      

        $resultbd = $paymentCreate->validaPayment($linha[0]);

        if(!$resultbd){
        // Criar o Fornecedor com os dados (incluindo o novo código)
        $result = $paymentCreate->importPayment($linha);
        }
    }
} else {
    echo "Necessário enviar arquivo csv!";
}

// Criar função valor por referencia, isto é, quando alterar o valor dentro da função, vale para a variável fora da função.
function converter(&$dados_arquivo)
{
    // Converter dados de ISO-8859-1 para UTF-8
    $dados_arquivo = mb_convert_encoding($dados_arquivo, "UTF-8", "ISO-8859-1");
}
