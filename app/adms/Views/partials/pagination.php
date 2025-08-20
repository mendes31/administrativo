<?php

// Monta a query string com os filtros (se existirem), removendo o parâmetro 'page'
$queryParams = $_GET;
unset($queryParams['page']);
// Garante que o parâmetro principal de rota esteja presente
if (empty($queryParams['url']) && !empty($this->data['pagination']['url_controller'])) {
    $queryParams['url'] = $this->data['pagination']['url_controller'];
}
$queryParams = http_build_query($queryParams);
$queryParams = $queryParams ? "&" . $queryParams : '';

// Verifica se há uma página final disponível e se não é a primeira página
if (($this->data['pagination']['last_page'] ?? false) and ($this->data['pagination']['last_page'] != 1)) {
?>
    <nav aria-label="...">
        <ul class="pagination pagination-sm">

            <?php
            // Se a página atual for maior que 1, exibe links para a primeira página e a página anterior
            if ($this->data['pagination']['current_page'] > 1) {

                // Gera um link para a primeira página
                echo "<li class='page-item'><a href='" . $_ENV['URL_ADM'] . ($this->data['pagination']['url_controller'] ?? '') . "?page=1" . ($queryParams ? "$queryParams" : "") . "' class='page-link'>Primeira</a></li>";

                // Calcula o número da página anterior
                $beforePage = $this->data['pagination']['current_page'] - 1;

                // Gera um link para a página anterior                
                echo "<li class='page-item'><a href='" . $_ENV['URL_ADM'] . ($this->data['pagination']['url_controller'] ?? '') . "?page=" . $beforePage . ($queryParams ? "$queryParams" : "") . "' class='page-link'>$beforePage</a></li>";
            } 

            $currentPage = ($this->data['pagination']['current_page'] ?? 1);

            // Exibe a pagina atual
            echo "<li class='page-item active' aria-current='page'><span class='page-link'>$currentPage</span></li>";

            // Se a página atual for menor que a página final, exibe links para a próxima página e a última página
            if ($this->data['pagination']['current_page'] < $this->data['pagination']['last_page']) {

                // Calcula o número da próxima página
                $afterPage = $this->data['pagination']['current_page'] + 1;

                // Gera um link para a próxima página
                echo "<li class='page-item'><a href='" . $_ENV['URL_ADM'] . ($this->data['pagination']['url_controller'] ?? '') . "?page=" . $afterPage . ($queryParams ? "$queryParams" : "") . "' class='page-link'>$afterPage</a></li>";

                // Gera um link para a última página                
                echo "<li class='page-item'><a href='" . $_ENV['URL_ADM'] . ($this->data['pagination']['url_controller'] ?? '') . "?page=" . ($this->data['pagination']['last_page'] ?? '') . ($queryParams ? "$queryParams" : "") . "' class='page-link'>Última</a></li>";
            } ?>

        </ul>
    </nav>

<?php
}
?>
