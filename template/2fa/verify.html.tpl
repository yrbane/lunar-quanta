[% extends 'base.html' %]

[% block header %]
<style>
    .twofa-verify {
        max-width: 400px;
        margin: 2rem auto;
        padding: 2rem;
        background: var(--color-secondary);
        border-radius: 8px;
    }
    .twofa-verify h2 {
        text-align: center;
        margin-bottom: 1.5rem;
    }
    .twofa-verify p {
        text-align: center;
        color: var(--color-text-muted);
        margin-bottom: 1.5rem;
    }
    .form-group {
        margin: 1.5rem 0;
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
        font-size: 1.5rem;
        text-align: center;
        letter-spacing: 0.5rem;
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
    .alert-error {
        background: #fee2e2;
        color: #dc2626;
        padding: 0.75rem;
        border-radius: 4px;
        margin-bottom: 1rem;
    }
    .recovery-link {
        text-align: center;
        margin-top: 1.5rem;
        font-size: 0.875rem;
    }
    .recovery-link a {
        color: var(--color-primary);
    }
</style>
[% endblock %]

[% block content %]
<div class="twofa-verify">
    <h2>Verification en deux etapes</h2>
    <p>Entrez le code a 6 chiffres affiche par votre application d'authentification.</p>

    [% if error %]
    <div class="alert-error">[[ error ]]</div>
    [% endif %]

    <form method="POST" action="/2fa/verify">
        <div class="form-group">
            <label for="code">Code d'authentification</label>
            <input type="text" id="code" name="code" required autofocus
                   maxlength="10" placeholder="000000"
                   autocomplete="one-time-code">
        </div>

        <button type="submit" class="btn-submit">Verifier</button>
    </form>

    <div class="recovery-link">
        <p>Vous n'avez pas acces a votre application ?<br>
        Utilisez un <strong>code de recuperation</strong> a la place.</p>
    </div>
</div>
[% endblock %]
