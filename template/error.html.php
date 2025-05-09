<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($title) ?></title>
    <link rel="stylesheet" href="/asset/css/style.css">
    <style>
        /* Styles spécifiques pour la page d'erreur */
        .error-container {
            text-align: center;
            padding: 3em;
        }
        .error-code {
            font-size: 4em;
            color: #e74c3c;
            margin-bottom: 0.5em;
        }
        .error-message {
            font-size: 1.5em;
            color: #333;
        }
        .btn {
            display: inline-block;
            margin-top: 2em;
            padding: 0.5em 1em;
            background-color: #3498db;
            color: #fff;
            text-decoration: none;
            border-radius: 4px;
        }
        .btn:hover {
            background-color: #2980b9;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-code"><?= htmlspecialchars($errorCode) ?></div>
        <div class="error-message"><?= htmlspecialchars($errorMessage) ?></div>
        <a href="/" class="btn">Retour à l'accueil</a>
    </div>
</body>
</html>
