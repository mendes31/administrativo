<?php
if (!isset($_ENV['DB_HOST'])) {
    require_once __DIR__ . '/../../Helpers/EnvLoader.php';
    \App\adms\Helpers\EnvLoader::load();
}
?>
<!DOCTYPE html>
<html lang="<?php echo $_ENV['APP_LOCALE']; ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- Prevenir cache para evitar problemas de sessão -->
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">

    <link rel="shortcut icon" href="<?php echo $_ENV['URL_ADM']; ?>public/adms/image/logo/logo.ico">
    <link rel="apple-touch-icon" href="<?php echo $_ENV['URL_ADM']; ?>public/adms/uploads/users/1/pwa-icon-512.png">
    <link rel="manifest" href="<?php echo $_ENV['URL_ADM']; ?>public/adms/manifest.json?v=20260520-2">
    <meta name="theme-color" content="#2E9263">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="Tiaraju">

    <link rel="stylesheet" href="<?php echo $_ENV['URL_ADM'] ?>public/adms/css/sbadmin.css">

    <link rel="stylesheet" href="<?php echo $_ENV['URL_ADM'] ?>public/adms/css/bootstrap.min.css">

    <link rel="stylesheet" href="<?php echo $_ENV['URL_ADM'] ?>public/adms/css/styles_admin.css">

    <link rel="stylesheet" href="<?php echo $_ENV['URL_ADM']; ?>public/adms/fontawesome/css/all.min.css?v=20250822">

    <title>
        <?php
        echo $_ENV['APP_NAME'] . " - " . ($this->data['title_head'] ?? "");
        ?>
    </title>
</head>

<body class="bg-login">
    <div id="layoutAuthentication">
        <div id="layoutAuthentication_content">
            <main>
                <div class="container">
                    <div class="row justify-content-center">

                        <?php

                        // Inclui o conteúdo principal da página, que é especificado pela propriedade $this->view. Este arquivo é dinâmico e pode variar conforme a lógica do controlador ou o contexto da página.
                        include $this->view;

                        ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="<?php echo $_ENV['URL_ADM'] ?>public/adms/js/bootstrap.bundle.min.js"></script>

    <script src="<?php echo $_ENV['URL_ADM'] ?>public/adms/js/sbadmin.js"></script>

    <script>
    // Registro do Service Worker raiz para PWA (cobre /administrativo/)
    (function() {
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function() {
                const swUrl = '<?php echo rtrim($_ENV['URL_ADM'], '/'); ?>/service-worker.js?v=20260520-10';
                navigator.serviceWorker.register(swUrl).catch(function(error) {
                    console.warn('Falha ao registrar Service Worker (login):', error);
                });
            });
        }
    })();
    </script>

</body>

</html>