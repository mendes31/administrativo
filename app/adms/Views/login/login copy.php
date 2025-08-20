<?php

use App\adms\Helpers\CSRFHelper;

// Exibe o título da página de cadastro de usuário
echo "<h3>Formulário de Login</h3>";

// Inclui o arquivo que exibe mensagens de sucesso e erro
include './app/adms/Views/partials/alerts.php';

?>

<!-- Formulário para login -->
<form action="" method="POST">

    <!-- Campo oculto para o token CSRF para proteger o formulário contra ataques de falsificação de solicitação entre sites -->
    <input type="hidden" name="csrf_token" value="<?php echo CSRFHelper::generateCSRFToken('form_login'); ?>">

    <!-- Campo usuário -->
    <label for="username">Usuário: </label>
    <input type="text" name="username" id="username" placeholder="Digite seu usuário" value="<?php echo $this->data['form']['username'] ?? ''; ?>"><br><br>


    <!-- Campo para a senha do usuário -->
    <label for="password">Senha: </label>
    <input type="password" name="password" id="password" placeholder="Digite sua senha" value="<?php echo $this->data['form']['password'] ?? ''; ?>"><br><br>

    <!-- Botão para submeter o formulário -->
    <button type="submit" class="btn btn-primary">Acessar</button><br><br>

</form>

<a href="<?php echo $_ENV['URL_ADM']; ?>new-user">Novo Usuário</a> - <a href="<?php echo $_ENV['URL_ADM']; ?>forgot-password">Esqueceu a Senha ?</a>

