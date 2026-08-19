<?php
/** @var string $title @var string $view @var string $base_url @var string $url_adm @var string $empresa */
$url_adm = rtrim((string) ($url_adm ?? ($_ENV['URL_ADM'] ?? '')), '/') . '/';
$base_url = rtrim((string) ($base_url ?? $url_adm . 'lgpd'), '/');
$empresa = (string) ($empresa ?? 'Tiaraju');
$show_hero = !empty($show_hero);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#00995D">
    <title><?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?></title>
    <link rel="shortcut icon" href="<?php echo htmlspecialchars($url_adm, ENT_QUOTES, 'UTF-8'); ?>public/adms/image/logo/logo.ico">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($url_adm, ENT_QUOTES, 'UTF-8'); ?>public/adms/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($url_adm, ENT_QUOTES, 'UTF-8'); ?>public/adms/fontawesome/css/all.min.css">
    <style>
        :root {
            --lgpd-green: #00995D;
            --lgpd-green-dark: #007a4a;
            --lgpd-ink: #1f2937;
        }
        body.lgpd-pub-body {
            margin: 0;
            min-height: 100vh;
            background: #f4f7f6;
            color: var(--lgpd-ink);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .lgpd-pub-top {
            background: #fff;
            border-bottom: 3px solid var(--lgpd-green);
            padding: 0.7rem 1.25rem;
        }
        .lgpd-pub-top-inner {
            max-width: 1100px;
            margin: 0 auto;
            display: flex;
            align-items: center;
        }
        .lgpd-pub-top img { height: 40px; }
        .lgpd-hero {
            background:
                linear-gradient(105deg, rgba(0,153,93,0.92) 0%, rgba(0,122,74,0.88) 48%, rgba(10,91,48,0.9) 100%),
                radial-gradient(circle at 20% 40%, rgba(255,255,255,0.18) 0 12%, transparent 13%),
                radial-gradient(circle at 70% 60%, rgba(255,255,255,0.12) 0 18%, transparent 19%),
                #00995D;
            color: #fff;
            padding: 2.75rem 1.25rem 2.4rem;
        }
        .lgpd-hero h1 {
            max-width: 1100px;
            margin: 0 auto;
            font-size: clamp(1.7rem, 4vw, 2.6rem);
            font-weight: 800;
            letter-spacing: -0.02em;
        }
        .lgpd-crumb {
            max-width: 1100px;
            margin: 0 auto 0.75rem;
            font-size: 0.85rem;
            opacity: 0.9;
        }
        .lgpd-crumb a { color: #fff; text-decoration: none; }
        .lgpd-pub-wrap {
            width: 100%;
            max-width: 1100px;
            margin: -1.5rem auto 2.5rem;
            padding: 0 1rem;
            position: relative;
            z-index: 2;
        }
        .lgpd-pub-card {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 8px 28px rgba(16, 24, 40, 0.08);
            padding: 2rem 1.75rem 2.25rem;
        }
        .lgpd-pub-hero {
            font-size: 1.45rem;
            font-weight: 700;
            color: var(--lgpd-green);
            margin: 0 0 0.75rem;
        }
        .lgpd-dpo {
            background: #f7fbf9;
            border-left: 4px solid var(--lgpd-green);
            padding: 1.1rem 1.2rem;
            border-radius: 0 8px 8px 0;
            margin: 1.25rem 0 0;
        }
        .lgpd-dpo h2 {
            font-size: 0.82rem;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            color: var(--lgpd-green-dark);
            margin: 0 0 0.5rem;
            font-weight: 700;
        }
        .lgpd-dpo a {
            color: #00995D;
            text-decoration: underline;
            font-weight: 600;
            cursor: pointer;
            pointer-events: auto;
        }
        .lgpd-dpo a:hover { color: #007a4a; }
        .lgpd-comite {
            background: #fff;
            border: 1px solid #e6eee9;
            border-radius: 8px;
            padding: 1.25rem 1.2rem;
        }
        .lgpd-comite h2 {
            font-size: 1.05rem;
            font-weight: 700;
            color: var(--lgpd-green-dark);
            margin: 0 0 0.75rem;
        }
        .lgpd-comite-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 0.85rem;
        }
        .lgpd-comite-card {
            background: #f7fbf9;
            border-radius: 8px;
            padding: 0.9rem 1rem;
            font-size: 0.95rem;
        }
        .lgpd-comite-cargo {
            color: #6b7280;
            font-size: 0.88rem;
            margin: 0.15rem 0 0.35rem;
        }
        .lgpd-comite-card a {
            color: #00995D;
            text-decoration: underline;
            font-weight: 600;
        }
        .lgpd-pub-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-top: 1.75rem;
        }
        a.lgpd-pub-tile {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 0.65rem;
            background: #fff;
            border: 1px solid #e6eee9;
            border-radius: 10px;
            padding: 1.5rem 1rem;
            min-height: 160px;
            text-align: center;
            text-decoration: none;
            color: var(--lgpd-green);
            font-weight: 600;
            box-shadow: 0 4px 14px rgba(16, 24, 40, 0.06);
            transition: transform 0.15s ease, box-shadow 0.15s ease, border-color 0.15s ease;
        }
        a.lgpd-pub-tile:hover {
            transform: translateY(-3px);
            border-color: var(--lgpd-green);
            box-shadow: 0 10px 22px rgba(0, 153, 93, 0.16);
            color: var(--lgpd-green-dark);
        }
        a.lgpd-pub-tile i {
            width: 3.1rem;
            height: 3.1rem;
            border-radius: 50%;
            background: #e8f7f0;
            color: var(--lgpd-green);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.35rem;
        }
        .lgpd-term-body {
            line-height: 1.65;
            color: #374151;
        }
        .lgpd-term-body h1, .lgpd-term-body h2, .lgpd-term-body h3 { color: var(--lgpd-green-dark); }
        .btn-lgpd {
            background: var(--lgpd-green);
            border: none;
            color: #fff;
            font-weight: 600;
            padding: 0.7rem 1.4rem;
            border-radius: 8px;
        }
        .btn-lgpd:hover { background: var(--lgpd-green-dark); color: #fff; }
        .lgpd-right { border: 1px solid #e8edf3; border-radius: 10px; padding: 0.85rem 1rem; margin-bottom: 0.75rem; }
        .lgpd-hp { position: absolute; left: -9999px; }
        .lgpd-foot {
            text-align: center;
            color: #6b7280;
            font-size: 0.82rem;
            padding: 0 1rem 1.5rem;
        }
        @media (max-width: 575.98px) {
            .lgpd-pub-card { padding: 1.15rem; }
            .lgpd-hero { padding: 1.75rem 1rem 2rem; }
        }
    </style>
</head>
<body class="lgpd-pub-body">
    <header class="lgpd-pub-top">
        <div class="lgpd-pub-top-inner">
            <a href="<?php echo htmlspecialchars($base_url, ENT_QUOTES, 'UTF-8'); ?>">
                <img src="<?php echo htmlspecialchars($url_adm, ENT_QUOTES, 'UTF-8'); ?>public/adms/image/logo/Logo-Tiaraju.png" alt="<?php echo htmlspecialchars($empresa, ENT_QUOTES, 'UTF-8'); ?>">
            </a>
        </div>
    </header>
    <?php if ($show_hero): ?>
        <section class="lgpd-hero">
            <div class="lgpd-crumb">Início &nbsp;›&nbsp; LGPD</div>
            <h1>Lei Geral de Proteção de Dados</h1>
        </section>
    <?php endif; ?>
    <div class="lgpd-pub-wrap" style="<?php echo $show_hero ? '' : 'margin-top:1.5rem'; ?>">
        <?php include __DIR__ . '/' . $view . '.php'; ?>
    </div>
    <p class="lgpd-foot">
        &copy; <?php echo date('Y') . ' ' . htmlspecialchars($empresa, ENT_QUOTES, 'UTF-8'); ?> · Lei 13.709/2018
    </p>
</body>
</html>
