[% extends 'base.html.tpl' %]

[% block content %]
<div class="error-container" role="main">
    <h1>Erreur [[ errorCode ]]</h1>
    <div class="error-code">[[ errorCode ]]</div>
    <div class="error-message">[[ errorMessage ]]</div>
    <nav class="error-actions">
        <a href="/" class="btn btn-primary">Retour à l'accueil</a>
        <a href="/blog/" class="btn btn-secondary">Voir le blog</a>
    </nav>
</div>
[% endblock %]