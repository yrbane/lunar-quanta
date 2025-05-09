[% extends 'base.html.tpl' %]

[% block content %]
<div class="error-container">
    <h1>Erreur !</h1>
    <div class="error-code">[[ errorCode ]]</div>
    <div class="error-message">[[ errorMessage ]]</div>
</div>
[% endblock %]