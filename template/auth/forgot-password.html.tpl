[% extends 'base.html' %]

[% block header %]
<style>
    .auth-form {
        max-width: 400px;
        margin: 2rem auto;
        padding: 2rem;
        background: var(--color-secondary);
        border-radius: 8px;
    }
    .auth-form h2 {
        text-align: center;
        margin-bottom: 1.5rem;
    }
    .auth-form p.intro {
        text-align: center;
        color: var(--color-text-muted);
        margin-bottom: 1.5rem;
    }
    .form-group {
        margin-bottom: 1rem;
    }
    .form-group label {
        display: block;
        margin-bottom: 0.5rem;
        font-weight: 500;
    }
    .form-group input {
        width: 100%;
        padding: 0.75rem;
        border: 1px solid var(--color-border);
        border-radius: 4px;
        font-size: 1rem;
    }
    .form-group input:focus {
        outline: none;
        border-color: var(--color-primary);
    }
    .btn-submit {
        width: 100%;
        padding: 0.75rem;
        background: var(--color-primary);
        color: white;
        border: none;
        border-radius: 4px;
        font-size: 1rem;
        cursor: pointer;
        margin-top: 1rem;
    }
    .btn-submit:hover {
        opacity: 0.9;
    }
    .alert {
        padding: 0.75rem 1rem;
        border-radius: 4px;
        margin-bottom: 1rem;
    }
    .alert-error {
        background: #fee2e2;
        color: #dc2626;
        border: 1px solid #fecaca;
    }
    .alert-success {
        background: #dcfce7;
        color: #16a34a;
        border: 1px solid #bbf7d0;
    }
    .auth-links {
        text-align: center;
        margin-top: 1rem;
    }
    .auth-links a {
        color: var(--color-primary);
    }
</style>
[% endblock %]

[% block content %]
<div class="auth-form">
    <h2>Mot de passe oublie</h2>
    <p class="intro">
        Entrez votre adresse email et nous vous enverrons un lien pour reinitialiser votre mot de passe.
    </p>

    [% if error %]
    <div class="alert alert-error">
        [[ error ]]
    </div>
    [% endif %]

    [% if success %]
    <div class="alert alert-success">
        [[ success ]]
    </div>
    [% endif %]

    <form method="POST" action="/forgot-password">
        <div class="form-group">
            <label for="email">Adresse email</label>
            <input type="email" id="email" name="email" required autofocus
                   placeholder="votre@email.com">
        </div>

        <button type="submit" class="btn-submit">Envoyer le lien</button>
    </form>

    <div class="auth-links">
        <p><a href="/login">Retour a la connexion</a></p>
    </div>
</div>
[% endblock %]
