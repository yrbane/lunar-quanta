<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>[[ title ]]</title>
    <link rel="stylesheet" href="/css/potatoes.css">
    [% block header %][% endblock %]
</head>
<body>
    <!-- Barre de navigation -->
    <nav class="navbar navbar-primary">
        <div class="container wrapper">
            <a href="#" class="text-quaternary">LOGO</a>
            <div>
            <a href="#">Home</a>
            <a href="#">About</a>
            <a href="#">Contact</a>
            </div>
        </div>
    </nav>

    <!-- Header du blog -->
    <header class="photo-filter" style="text-align: center; background-image: url('/img/header.png'); background-size: cover; background-position: center; padding: 2em;min-height:50vh;">
        <h1 class="fade-in">Welcome to Our Blog</h1>
        <p class="fade-in" style="animation-delay: 0.2s;">
        Discover stories, tips, and inspiration.
        </p>
    </header>
    <main class="wrapper">
        [% block content %]
        Contenu par défaut.
        [% endblock %]
    </main>
    <footer>
        <p>&copy; <?= date('Y'); ?></p>
    </footer>
</body>
</html>
