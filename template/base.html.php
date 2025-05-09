<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title><?= isset($title) ? htmlspecialchars($title) : 'Mini Système' ?></title>
    <link rel="stylesheet" href="/asset/css/style.css">
</head>
<body>
    <header>
        <h1><?= isset($title) ? htmlspecialchars($title) : 'Mini Système' ?></h1>
    </header>
    <main>
        <?= $content ?? '' ?>
    </main>
    <footer>
        <p>&copy; <?= date('Y'); ?></p>
    </footer>
    <script type="module" src="/asset/js/ajaxManager.js"></script>
</body>
</html>
