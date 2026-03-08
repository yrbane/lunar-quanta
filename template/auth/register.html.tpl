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
    .form-group .error {
        color: #dc2626;
        font-size: 0.875rem;
        margin-top: 0.25rem;
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
    .auth-links {
        text-align: center;
        margin-top: 1rem;
    }
    .auth-links a {
        color: var(--color-primary);
    }
    .password-hint {
        font-size: 0.75rem;
        color: var(--color-text-muted);
        margin-top: 0.25rem;
    }
</style>
[% endblock %]

[% block content %]
<div class="auth-form">
    <h2>Inscription</h2>

    <form method="POST" action="/register">
        <div class="form-group">
            <label for="name">Nom</label>
            <input type="text" id="name" name="name" required autofocus
                   placeholder="Votre nom"
                   value="[[ old.name ]]">
            [% if errors.name %]
            <p class="error" role="alert">[[ errors.name.0 ]]</p>
            [% endif %]
        </div>

        <div class="form-group">
            <label for="email">Adresse email</label>
            <input type="email" id="email" name="email" required
                   placeholder="votre@email.com"
                   value="[[ old.email ]]">
            [% if errors.email %]
            <p class="error">[[ errors.email.0 ]]</p>
            [% endif %]
        </div>

        <div class="form-group">
            <label for="password">Mot de passe</label>
            <input type="password" id="password" name="password" required
                   placeholder="Minimum 8 caracteres">
            <p class="password-hint">Minimum 8 caracteres</p>
            [% if errors.password %]
            <p class="error">[[ errors.password.0 ]]</p>
            [% endif %]
        </div>

        <div class="form-group">
            <label for="password_confirm">Confirmer le mot de passe</label>
            <input type="password" id="password_confirm" name="password_confirm" required
                   placeholder="Retapez votre mot de passe">
            [% if errors.password_confirm %]
            <p class="error">[[ errors.password_confirm.0 ]]</p>
            [% endif %]
        </div>

        <button type="submit" class="btn-submit">S'inscrire</button>
    </form>

    <div class="auth-links">
        <p>Deja inscrit ? <a href="/login">Se connecter</a></p>
    </div>
</div>
[% endblock %]
