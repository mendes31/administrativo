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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?></title>
    <link rel="stylesheet" href="<?php echo htmlspecialchars($url_adm, ENT_QUOTES, 'UTF-8'); ?>public/adms/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($url_adm, ENT_QUOTES, 'UTF-8'); ?>public/adms/fontawesome/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #1a2a4a 0%, #2d4a6f 50%, #1e3a5f 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .canal-card {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 20px 50px rgba(0,0,0,0.25);
            padding: 2rem;
            margin: 2rem auto;
            max-width: 720px;
        }
        .canal-header { text-align: center; margin-bottom: 1.5rem; }
        .canal-header h1 { color: #1a2a4a; font-size: 1.75rem; font-weight: 700; }
        .anon-badge {
            background: #e8f4fd;
            border: 1px solid #b8daff;
            border-radius: 10px;
            padding: 1rem 1.25rem;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
            color: #1a3a5c;
        }
        .anon-badge ul { margin: 0.5rem 0 0; padding-left: 1.25rem; }
        .anon-badge li { margin-bottom: 0.35rem; }
        .btn-canal-primary {
            background: linear-gradient(45deg, #1a5f9e, #2d8bc9);
            border: none;
            color: #fff;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: 600;
        }
        .btn-canal-primary:hover { color: #fff; opacity: 0.92; }
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
            margin: 1.5rem 0;
        }
        .protocol-box .code {
            font-family: 'Courier New', monospace;
            font-size: 1.5rem;
            font-weight: 700;
            color: #1a2a4a;
            letter-spacing: 2px;
        }
        .msg-thread { max-height: 400px; overflow-y: auto; }
        .msg-item {
            border-radius: 10px;
            padding: 0.75rem 1rem;
            margin-bottom: 0.75rem;
        }
        .msg-comite { background: #e8f4fd; border-left: 4px solid #2d8bc9; }
        .msg-denunciante { background: #f0f4f8; border-left: 4px solid #6c757d; }
    </style>
</head>
<body>
    <div class="container py-4">
        <?php
        $viewFile = __DIR__ . '/' . $view . '.php';
        if (is_readable($viewFile)) {
            include $viewFile;
        }
        ?>
    </div>
</body>
</html>
