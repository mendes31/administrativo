<?php

use App\adms\Helpers\CSRFHelper;

// Exibe o título da página de esqueceu a senha
echo "<h3>Recuperar Senha</h3>";

// Inclui o arquivo que exibe mensagens de sucesso e erro
include './app/adms/Views/partials/alerts.php';

?>

<!-- Formulário para recuperar senha -->
<form action="" method="POST">

    <!-- Campo oculto para o token CSRF para proteger o formulário contra ataques de falsificação de solicitação entre sites -->
    <input type="hidden" name="csrf_token" value="<?php echo CSRFHelper::generateCSRFToken('form_forgot_password'); ?>">

    
    <!-- Campo para o e-mail do usuário -->
    <label for="email">Email: </label>
    <input type="email" name="email" id="email" placeholder="Digite o email cadastrado." value="<?php echo $this->data['form']['email'] ?? ''; ?>"><br><br>

    <!-- Botão para submeter o formulário -->
    <button type="submit">Recuperar</button><br><br>

</form>

<a href="<?php echo $_ENV['URL_ADM']; ?>login">Login</a><br><br>
