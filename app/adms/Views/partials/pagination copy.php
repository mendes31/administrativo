<?php

// Verifica se há uma página final disponível e se não é a primeira página
if (($this->data['pagination']['last_page'] ?? false) and ($this->data['pagination']['last_page'] != 1)) {
    
    // Se a página atual for maior que 1, exibe links para a primeira página e a página anterior
    if($this->data['pagination']['current_page'] > 1){
        
        // Gera um link para a primeira página
        echo "<a href='" . $_ENV['URL_ADM'] . ($this->data['pagination']['url_controller'] ?? '') . "/1'>Primeira</a> ";

        // Calcula o número da página anterior
        $beforePage = $this->data['pagination']['current_page'] - 1;

        // Gera um link para a página anterior
        echo "<a href='" . $_ENV['URL_ADM'] . ($this->data['pagination']['url_controller'] ?? '') . "/" . $beforePage . "'>$beforePage</a> ";
    }

    // Exibe a página atual como um link (não clicável)
    echo "<a href='#'>" . ($this->data['pagination']['current_page'] ?? 1) . "</a> ";

    // Se a página atual for menor que a página final, exibe links para a próxima página e a última página
    if($this->data['pagination']['current_page'] < $this->data['pagination']['last_page']){

        // Calcula o número da próxima página
        $afterPage = $this->data['pagination']['current_page'] + 1;

        // Gera um link para a próxima página
        echo "<a href='" . $_ENV['URL_ADM'] . ($this->data['pagination']['url_controller'] ?? '') . "/" . $afterPage . "'>$afterPage</a> ";

        // Gera um link para a última página
        echo "<a href='" . $_ENV['URL_ADM'] . ($this->data['pagination']['url_controller'] ?? '') . "/" .($this->data['pagination']['last_page'] ?? ''). "'> Última</a> ";
    }

    
}
