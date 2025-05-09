<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>[[ title ]]</title>
    <link rel="stylesheet" href="/css/potatoes.css">
</head>
<body>
    <header>
        <h1>Header de base</h1>
    </header>
    <main>
        [% block content %]
        Contenu par défaut.
        [% endblock %]
    </main>
    <footer>
        <p>&copy; <?= date('Y'); ?></p>
    </footer>
</body>
</html>
