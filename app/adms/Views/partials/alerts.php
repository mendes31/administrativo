<?php

use App\adms\Helpers\FlashMessageHelper;

$scopedFlash = FlashMessageHelper::consumeForCurrentRoute();
if (is_array($scopedFlash) && !empty($scopedFlash['message'])) {
    $alertType = in_array(($scopedFlash['type'] ?? 'info'), ['success', 'warning', 'danger', 'info'], true)
        ? $scopedFlash['type']
        : 'info';
    $safeMessage = htmlspecialchars((string)$scopedFlash['message'], ENT_QUOTES, 'UTF-8');
    echo "<div class='alert alert-{$alertType} adms-inline-alert' role='alert'>{$safeMessage}</div>";
}

// Exibe mensagens de sucesso e erro armazenadas na sessão.
// Usar operador ternário para verificar se existe a mensagem de sucesso e erro
if (!empty($_SESSION['success'])) {
    echo "<div class='alert alert-success adms-inline-alert' role='alert'>{$_SESSION['success']}</div>";
}

// Filtrar mensagens genéricas de roteador (Erro 004) para não poluir telas de negócio
if (!empty($_SESSION['error'])) {
    $errorMsg = (string) $_SESSION['error'];
    if (stripos($errorMsg, 'Erro 004') === false) {
        echo "<div class='alert alert-danger adms-inline-alert' role='alert'>{$errorMsg}</div>";
    }
}

// Sistema unificado com msg e msg_type
if (isset($_SESSION['msg']) && isset($_SESSION['msg_type'])) {
    $alertType = $_SESSION['msg_type'] === 'success' ? 'success' : 
                 ($_SESSION['msg_type'] === 'warning' ? 'warning' : 'danger');
    echo "<div class='alert alert-{$alertType} adms-inline-alert' role='alert'>{$_SESSION['msg']}</div>";
} elseif (!empty($_SESSION['msg'])) {
    // Fallback: só msg (ex.: fluxos antigos com HTML já formatado)
    echo $_SESSION['msg'];
}

// Mensagem de aviso/warning (amarelo)
if (isset($_SESSION['msg_warning'])) {
    echo "<div class='alert alert-warning adms-inline-alert' role='alert'><i class='fas fa-exclamation-triangle me-2'></i>{$_SESSION['msg_warning']}</div>";
}

// Verifica se há erros armazenados em $_SESSION['errors'].
if (isset($_SESSION['errors'])) {
    foreach ($_SESSION['errors'] as $error) {
        echo "<div class='alert alert-danger adms-inline-alert' role='alert'>$error</div>";
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

        echo "<div class='alert alert-danger adms-inline-alert' role='alert'>$error</div>";
    }
}