[% extends 'base.html' %]

[% block header %]
<style>
    .twofa-setup {
        max-width: 500px;
        margin: 2rem auto;
        padding: 2rem;
        background: var(--color-secondary);
        border-radius: 8px;
    }
    .twofa-setup h2 {
        text-align: center;
        margin-bottom: 1.5rem;
    }
    .qr-container {
        text-align: center;
        margin: 2rem 0;
    }
    .qr-container img {
        border: 4px solid white;
        border-radius: 8px;
    }
    .secret-code {
        background: var(--color-tertiary);
        padding: 1rem;
        border-radius: 4px;
        font-family: monospace;
        font-size: 1.1rem;
        text-align: center;
        word-break: break-all;
        margin: 1rem 0;
    }
    .instructions {
        margin: 1.5rem 0;
    }
    .instructions ol {
        padding-left: 1.5rem;
    }
    .instructions li {
        margin: 0.5rem 0;
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
        font-size: 1.2rem;
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
    }
    .alert-error {
        background: #fee2e2;
        color: #dc2626;
        padding: 0.75rem;
        border-radius: 4px;
        margin-bottom: 1rem;
    }
</style>
[% endblock %]

[% block content %]
<div class="twofa-setup">
    <h2>Activer l'authentification a deux facteurs</h2>

    [% if error %]
    <div class="alert-error">[[ error ]]</div>
    [% endif %]

    <div class="instructions">
        <ol>
            <li>Installez une application d'authentification (Google Authenticator, Authy, etc.)</li>
            <li>Scannez le QR code ci-dessous avec l'application</li>
            <li>Entrez le code a 6 chiffres affiche par l'application</li>
        </ol>
    </div>

    <div class="qr-container">
        <img src="[[ qrCodeUrl ]]" alt="QR Code 2FA">
    </div>

    <p style="text-align: center; color: var(--color-text-muted); font-size: 0.875rem;">
        Si vous ne pouvez pas scanner le QR code, entrez ce code manuellement :
    </p>
    <div class="secret-code">[[ secret ]]</div>

    <form method="POST" action="/2fa/setup">
        <div class="form-group">
            <label for="code">Code de verification</label>
            <input type="text" id="code" name="code" required autofocus
                   maxlength="6" pattern="[0-9]{6}" placeholder="000000">
        </div>

        <button type="submit" class="btn-submit">Activer 2FA</button>
    </form>
</div>
[% endblock %]
