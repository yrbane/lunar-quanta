<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($title) ?></title>
    <link rel="stylesheet" href="/asset/css/style.css">
</head>
<body>
    <header>
        <h1><?= htmlspecialchars($message) ?></h1>
    </header>
    <main>
        <p>Bienvenue sur notre plateforme. Vous pouvez vous connecter pour accéder à votre espace personnel.</p>
        <a href="<?= htmlspecialchars($loginUrl) ?>" class="btn">Se connecter</a>
    </main>
</body>
</html>
