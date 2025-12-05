[% extends 'base.html' %]

[% block header %]
<style>
    .twofa-recovery {
        max-width: 500px;
        margin: 2rem auto;
        padding: 2rem;
        background: var(--color-secondary);
        border-radius: 8px;
    }
    .twofa-recovery h2 {
        text-align: center;
        margin-bottom: 1.5rem;
    }
    .alert-success {
        background: #dcfce7;
        color: #16a34a;
        padding: 0.75rem;
        border-radius: 4px;
        margin-bottom: 1rem;
    }
    .alert-warning {
        background: #fef3c7;
        color: #d97706;
        padding: 0.75rem;
        border-radius: 4px;
        margin-bottom: 1rem;
    }
    .codes-container {
        background: var(--color-tertiary);
        padding: 1.5rem;
        border-radius: 8px;
        margin: 1.5rem 0;
    }
    .codes-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 0.75rem;
    }
    .recovery-code {
        font-family: monospace;
        font-size: 1.1rem;
        background: white;
        padding: 0.5rem 1rem;
        border-radius: 4px;
        text-align: center;
    }
    .warning-box {
        background: #fef3c7;
        border: 1px solid #fcd34d;
        padding: 1rem;
        border-radius: 4px;
        margin: 1.5rem 0;
    }
    .warning-box ul {
        margin: 0.5rem 0 0 1.5rem;
        padding: 0;
    }
    .btn-group {
        display: flex;
        gap: 1rem;
        margin-top: 1.5rem;
    }
    .btn {
        flex: 1;
        padding: 0.75rem;
        border: none;
        border-radius: 4px;
        font-size: 1rem;
        cursor: pointer;
    }
    .btn-primary {
        background: var(--color-primary);
        color: white;
    }
    .btn-danger {
        background: #dc2626;
        color: white;
    }
    .btn-secondary {
        background: var(--color-tertiary);
        color: var(--color-text);
    }
    .disable-form {
        margin-top: 2rem;
        padding-top: 1.5rem;
        border-top: 1px solid var(--color-border);
    }
    .disable-form h3 {
        color: #dc2626;
    }
    .form-group {
        margin: 1rem 0;
    }
    .form-group label {
        display: block;
        margin-bottom: 0.5rem;
    }
    .form-group input {
        width: 100%;
        padding: 0.5rem;
        border: 1px solid var(--color-border);
        border-radius: 4px;
    }
</style>
[% endblock %]

[% block content %]
<div class="twofa-recovery">
    <h2>Codes de recuperation</h2>

    [% if success %]
    <div class="alert-success">[[ success ]]</div>
    [% endif %]

    [% if recoveryCodes %]
    <div class="alert-warning">
        <strong>Important !</strong> Ces codes ne seront affiches qu'une seule fois.
    </div>

    <div class="codes-container">
        <div class="codes-grid">
            [% for code in recoveryCodes %]
            <div class="recovery-code">[[ code ]]</div>
            [% endfor %]
        </div>
    </div>

    <div class="warning-box">
        <strong>Conservez ces codes en lieu sur :</strong>
        <ul>
            <li>Chaque code ne peut etre utilise qu'une seule fois</li>
            <li>Utilisez-les si vous perdez l'acces a votre application</li>
            <li>Stockez-les dans un gestionnaire de mots de passe</li>
        </ul>
    </div>
    [% else %]
    <p>Vous avez <strong>[[ remainingCount ]]</strong> codes de recuperation restants.</p>

    <form method="POST" action="/2fa/recovery">
        <button type="submit" class="btn btn-secondary">
            Regenerer les codes de recuperation
        </button>
    </form>
    [% endif %]

    <div class="disable-form">
        <h3>Desactiver l'authentification a deux facteurs</h3>
        <p style="color: var(--color-text-muted); font-size: 0.875rem;">
            Vous devrez confirmer votre mot de passe pour desactiver le 2FA.
        </p>
        <form method="POST" action="/2fa/disable">
            <div class="form-group">
                <label for="password">Mot de passe actuel</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit" class="btn btn-danger">Desactiver 2FA</button>
        </form>
    </div>
</div>
[% endblock %]
