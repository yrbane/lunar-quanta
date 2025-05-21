[% extends 'base.html.tpl' %]

[% block content %]
<!-- Barre de navigation -->
<nav class="navbar">
    <div class="logo">MyFramework</div>
    <div class="menu">
        <a href="#cards">Cards</a>
        <a href="#alerts">Alerts</a>
        <a href="#buttons">Buttons</a>
        <a href="#forms">Forms</a>
    </div>
</nav>

<!-- Navbar variant -->
<nav class="navbar navbar-primary">
    <div class="logo">Logo</div>
    <div class="menu">
        <a href="#buttons">Boutons</a>
        <a href="#cards">Cartes</a>
        <a href="#alerts">Alertes</a>
        <a href="#forms">Formulaires</a>
    </div>
</nav>

<!-- Navbar variant -->
<nav class="navbar navbar-secondary">
    <div class="logo">Logo</div>
    <div class="menu">
        <a href="#buttons">Boutons</a>
        <a href="#cards">Cartes</a>
        <a href="#alerts">Alertes</a>
        <a href="#forms">Formulaires</a>
    </div>
</nav>

<!-- Navbar variant -->
<nav class="navbar navbar-tertiary">
    <div class="logo">Logo</div>
    <div class="menu">
        <a href="#buttons">Boutons</a>
        <a href="#cards">Cartes</a>
        <a href="#alerts">Alertes</a>
        <a href="#forms">Formulaires</a>
    </div>
</nav>

<!-- Navbar variant -->
<nav class="navbar navbar-quaternary">
    <div class="logo">Logo</div>
    <div class="menu">
        <a href="#buttons">Boutons</a>
        <a href="#cards">Cartes</a>
        <a href="#alerts">Alertes</a>
        <a href="#forms">Formulaires</a>
    </div>
</nav>

<div class="wrapper">

        <!-- Bouton switch pour changer de thème -->
        <div class="row" style="margin: 20px 0;">
            <div class="col-12" style="text-align: center;">
                <button id="themeToggle" class="btn btn-outline btn-primary">Switch to Dark Mode</button>
            </div>
        </div>

        <!-- Cartes -->
        <section id="cards">
            <h2>Cartes</h2>
            <div class="container">
                <div class="row">
                    <div class="col-3">
                        <div class="card card-primary">
                            <h3>Card Primary</h3>
                            <p>Carte avec bordure primary.</p>
                        </div>
                    </div>
                    <div class="col-3">
                        <div class="card card-secondary">
                            <h3>Card Secondary</h3>
                            <p>Carte avec bordure secondary.</p>
                        </div>
                    </div>
                    <div class="col-3">
                        <div class="card card-tertiary">
                            <h3>Card Tertiary</h3>
                            <p>Carte avec bordure tertiary.</p>
                        </div>
                    </div>
                    <div class="col-3">
                        <div class="card card-quaternary">
                            <h3>Card Quaternary</h3>
                            <p>Carte avec bordure quaternary.</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Boutons -->
        <section id="buttons">
            <h2>Boutons</h2>
            <div class="container">
                <div class="row">
                    <div class="col-6">
                        <h3>Boutons remplis</h3>
                        <button class="btn">Default</button>
                        <button class="btn primary">Primary</button>
                        <button class="btn secondary">Secondary</button>
                        <button class="btn tertiary">Tertiary</button>
                        <button class="btn quaternary">Quaternary</button>
                    </div>
                    <div class="col-6">
                        <h3>Boutons en contour</h3>
                        <button class="btn outline">Default</button>
                        <button class="btn outline primary">Primary</button>
                        <button class="btn outline secondary">Secondary</button>
                        <button class="btn outline tertiary">Tertiary</button>
                        <button class="btn outline quaternary">Quaternary</button>
                    </div>
                </div>
            </div>
        </section>

        <!-- Section Alertes -->
        <div class="row" id="alerts">
            <div class="col-12">
                <h2 style="margin: 20px 0; text-align: center;">Alerts Demo</h2>
                <div class="alert alert-info"><span class="icon">info</span> This is an informational alert.</div>
                <div class="alert alert-warning"><span class="icon">warning</span> This is a warning alert.</div>
                <div class="alert alert-error"><span class="icon">skull</span> This is an error alert.</div>
            </div>
        </div>

        <!-- Section Formulaires -->
        <div class="row" id="forms">
            <div class="col-12">
                <h2 style="margin: 20px 0; text-align: center;">Forms Demo</h2>
                <form>
                    <div class="form-group">
                        <label for="name">Name:</label>
                        <input type="text" id="name" name="name" placeholder="Enter your name">
                    </div>
                    <div class="form-group">
                        <label for="email">Email:</label>
                        <input type="email" id="email" name="email" placeholder="Enter your email">
                    </div>
                    <div class="form-group">
                        <label for="password">Password:</label>
                        <input type="password" id="password" name="password" placeholder="Enter your password">
                    </div>
                    <div class="form-group">
                        <label for="message">Message:</label>
                        <textarea id="message" name="message" rows="4" placeholder="Your message here"></textarea>
                    </div>
                    <button type="submit" class="btn btn-filled">Submit</button>
                </form>
            </div>
        </div>


    <h2>Exemple d'utilisation des grilles</h2>

    <!-- Rangée avec 3 colonnes (chacune 33.33%) -->
    <div class="container">
        <div class="row">
            <div class="col-4">Colonne 1</div>
            <div class="col-4">Colonne 2</div>
            <div class="col-4">Colonne 3</div>
        </div>
    </div>

    <!-- Rangée avec 2 colonnes (chacune 50%) -->
    <div class="container">
        <div class="row">
            <div class="col-6">Colonne 1</div>
            <div class="col-6">Colonne 2</div>
        </div>
    </div>

    <!-- Rangée pleine largeur -->
    <div class="container">
        <div class="row">
            <div class="col-12">Colonne pleine largeur</div>
        </div>
    </div>
</div>
<!-- JavaScript pour le changement de thème -->
<script>
    // Bascule entre le mode clair et le mode sombre en ajoutant/supprimant la classe "dark" sur body.
    document.getElementById('themeToggle').addEventListener('click', function() {
        document.body.classList.toggle('dark');
        // Mise à jour du texte du bouton en fonction du thème actif.
        if(document.body.classList.contains('dark')) {
            this.textContent = 'Switch to Light Mode';
        } else {
            this.textContent = 'Switch to Dark Mode';
        }
    });
</script>
[% endblock %]