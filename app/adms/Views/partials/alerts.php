<?php

// Exibe mensagens de sucesso e erro armazenadas na sessão.
// Usar operador ternário para verificar se existe a mensagem de sucesso e erro
echo isset($_SESSION['success']) ? "<div class='alert alert-success' role='alert'>{$_SESSION['success']}</div>" : "";

echo isset($_SESSION['error']) ? "<div class='alert alert-danger' role='alert'>{$_SESSION['error']}</div>" : "";

// Sistema unificado com msg e msg_type
if (isset($_SESSION['msg']) && isset($_SESSION['msg_type'])) {
    $alertType = $_SESSION['msg_type'] === 'success' ? 'success' : 
                 ($_SESSION['msg_type'] === 'warning' ? 'warning' : 'danger');
    echo "<div class='alert alert-{$alertType}' role='alert'>{$_SESSION['msg']}</div>";
}

// Mensagem de aviso/warning (amarelo)
if (isset($_SESSION['msg_warning'])) {
    echo "<div class='alert alert-warning' role='alert'><i class='fas fa-exclamation-triangle me-2'></i>{$_SESSION['msg_warning']}</div>";
}

// Verifica se há erros armazenados em $_SESSION['errors'].
if (isset($_SESSION['errors'])) {
    foreach ($_SESSION['errors'] as $error) {
        echo "<div class='alert alert-danger' role='alert'>$error</div>";
    }
}

// Destruir o que estiver dentro dessas sessões
// Remove as mensagens da sessão após exibi-las
unset($_SESSION['success'], $_SESSION['error'], $_SESSION['errors'], $_SESSION['msg'], $_SESSION['msg_type'], $_SESSION['msg_warning']);

// Acessa o IF quando encontrar elementos no array errors
// Verifica se há erros armazenados em $this->data['errors'].
// Se a chave 'errors' estiver presente no array, itera sobre cada erro e o exibe com estilo de texto vermelho (#f00).
if(isset($this->data['errors'])){

    foreach($this->data['errors'] as $error){

        echo "<div class='alert alert-danger' role='alert'>$error</div>";
    }
}