<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <?php
    /**
     * @var string $base_url
     * @var string $url_adm
     * @var string $view
     * @var string $title
     */
    require __DIR__ . '/_view_scope.php';
    ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#1a2a4a">
    <title><?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?></title>
    <link rel="stylesheet" href="<?php echo htmlspecialchars($url_adm, ENT_QUOTES, 'UTF-8'); ?>public/adms/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($url_adm, ENT_QUOTES, 'UTF-8'); ?>public/adms/fontawesome/css/all.min.css">
    <style>
        html {
            -webkit-text-size-adjust: 100%;
            scroll-behavior: smooth;
        }
        body.canal-body {
            background: linear-gradient(135deg, #1a2a4a 0%, #2d4a6f 50%, #1e3a5f 100%);
            min-height: 100vh;
            min-height: 100dvh;
            margin: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding:
                max(0.75rem, env(safe-area-inset-top))
                max(0.75rem, env(safe-area-inset-right))
                max(0.75rem, env(safe-area-inset-bottom))
                max(0.75rem, env(safe-area-inset-left));
        }
        .canal-wrap {
            width: 100%;
            max-width: 800px;
            margin: auto;
            flex-shrink: 0;
        }
        .canal-card {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 20px 50px rgba(0,0,0,0.25);
            padding: 2rem;
            width: 100%;
            margin: 0 auto;
        }
        .canal-card--wide {
            max-width: 800px;
        }
        .canal-header {
            text-align: center;
            margin-bottom: 1.5rem;
        }
        .canal-header h1 {
            color: #1a2a4a;
            font-size: 1.75rem;
            font-weight: 700;
            line-height: 1.25;
        }
        .canal-header p {
            font-size: 0.95rem;
        }
        .anon-badge {
            background: #e8f4fd;
            border: 1px solid #b8daff;
            border-radius: 10px;
            padding: 1rem 1.25rem;
            margin-bottom: 1rem;
            font-size: 0.9rem;
            color: #1a3a5c;
            text-align: left;
        }
        .anon-badge--alert {
            background: #fff8e6;
            border-color: #f0d78c;
        }
        .anon-badge ul {
            margin: 0.5rem 0 0;
            padding-left: 1.25rem;
        }
        .anon-badge li {
            margin-bottom: 0.35rem;
        }
        .canal-field {
            margin-bottom: 0.85rem;
        }
        .canal-form {
            text-align: left;
        }
        .canal-form .form-label {
            margin-bottom: 0.25rem;
            font-size: 0.9rem;
        }
        .canal-form-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-top: 0.5rem;
            justify-content: center;
        }
        .btn-canal-primary {
            background: linear-gradient(45deg, #1a5f9e, #2d8bc9);
            border: none;
            color: #fff;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: 600;
        }
        .btn-canal-primary:hover {
            color: #fff;
            opacity: 0.92;
        }
        .btn-canal-outline {
            border: 2px solid #1a5f9e;
            color: #1a5f9e;
            background: transparent;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: 600;
        }
        .protocol-box {
            background: #fff8e6;
            border: 2px dashed #e6a817;
            border-radius: 12px;
            padding: 1.5rem;
            text-align: center;
            margin: 1rem 0;
        }
        .protocol-box .code {
            font-family: 'Courier New', monospace;
            font-size: 1.5rem;
            font-weight: 700;
            color: #1a2a4a;
            letter-spacing: 2px;
            word-break: break-all;
        }
        .msg-thread {
            max-height: 400px;
            overflow-y: auto;
            -webkit-overflow-scrolling: touch;
        }
        .msg-item {
            border-radius: 10px;
            padding: 0.75rem 1rem;
            margin-bottom: 0.75rem;
        }
        .msg-comite {
            background: #e8f4fd;
            border-left: 4px solid #2d8bc9;
        }
        .msg-denunciante {
            background: #f0f4f8;
            border-left: 4px solid #6c757d;
        }
        .canal-actions-grid {
            display: grid;
            gap: 0.75rem;
        }

        @media (max-width: 575.98px) {
            .canal-card {
                padding: 1rem;
                border-radius: 12px;
                box-shadow: 0 8px 24px rgba(0,0,0,0.2);
            }
            .canal-header {
                margin-bottom: 0.75rem;
            }
            .canal-header h1 {
                font-size: 1.25rem;
            }
            .canal-header p {
                font-size: 0.85rem;
            }
            .anon-badge {
                padding: 0.75rem;
                font-size: 0.82rem;
                margin-bottom: 0.75rem;
            }
            .anon-badge ul {
                padding-left: 1rem;
            }
            .anon-badge li {
                margin-bottom: 0.25rem;
                line-height: 1.4;
            }
            .canal-field {
                margin-bottom: 0.65rem;
            }
            .canal-form .form-control,
            .canal-form .form-select {
                font-size: 16px;
                min-height: 44px;
            }
            .canal-form textarea.form-control {
                min-height: 80px;
            }
            .canal-form-actions,
            .canal-actions-grid {
                grid-template-columns: 1fr;
            }
            .canal-form-actions .btn,
            .canal-actions-grid .btn {
                width: 100%;
                min-height: 44px;
            }
            .protocol-box {
                padding: 1rem;
            }
            .protocol-box .code {
                font-size: 1.1rem;
                letter-spacing: 1px;
            }
            .alert {
                font-size: 0.85rem;
            }
            .msg-thread {
                max-height: 45vh;
            }
        }
    </style>
</head>
<body class="canal-body">
    <main class="canal-wrap">
        <?php
        $viewFile = __DIR__ . '/' . $view . '.php';
        if (is_readable($viewFile)) {
            include $viewFile;
        }
        ?>
    </main>
</body>
</html>
