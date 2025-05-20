# Mini Système PHP8

Ce projet est un mini système développé en PHP8, respectant les standards PSR (notamment PSR-1 et PSR-12) et les principes SOLID.  
L'architecture est pensée pour isoler les outils de développement (tests, analyses statiques, formatage du code, etc.) des dépendances du projet principal.

## Table des matières

- [Introduction](#introduction)
- [Architecture générale](#architecture-g%C3%A9n%C3%A9rale)
- [Prérequis](#prérequis)
- [Installation](#installation)
    - [Dépendances du projet principal](#dépendances-du-projet-principal)
    - [Outils de développement](#outils-de-développement)
- [Utilisation des outils de développement](#utilisation-des-outils-de-développement)
    - [Exécution directe de PHP CS Fixer](#exécution-directe-de-php-cs-fixer)
    - [Lien symbolique dans le dossier bin/](#lien-symbolique-dans-le-dossier-bin)
- [Gestion de la configuration Git](#gestion-de-la-configuration-git)
- [Conventions de code](#conventions-de-code)
- [Contribution](#contribution)
- [Licence](#licence)

## Introduction

Ce projet utilise une séparation claire entre :
- **Les dépendances du projet principal** (installées dans `vendor/` et définies dans `composer.json` à la racine)
- **Les outils de développement** (installés dans le répertoire `tools/` avec leur propre fichier `composer.json`)

Cette organisation permet de :
- **Isoler les versions** des outils de développement, évitant ainsi les conflits avec les dépendances du projet.
- **Fournir des raccourcis** (dans le dossier `bin/`) pour une exécution simple des outils, sans interférer avec la configuration de l'application principale.

## Architecture générale

### Structure des répertoires

```
/src
  /Command      -> Commandes CLI
  /Controller   -> Contrôleurs web
  /Entity       -> Entités (ex. User)
  /Service      -> Services (Router, Template, etc.)
/bin            -> Entrée CLI (script console)
/public         -> Front web (index.php, assets)
config/         -> Fichiers de configuration JSON
template/       -> Templates .tpl utilisés par le moteur
```

### Principaux composants

1. **FrontController** – point d'entrée de `public/index.php` qui charge la configuration, transmet la requête au routeur et gère les erreurs.
2. **Router** – lit les attributs `#[Route]` dans `src/Controller`, met en cache les routes et associe une requête à la bonne action.
3. **Container** – petit conteneur permettant d'injecter les dépendances via la réflexion.
4. **Templating** – `BaseController` s'appuie sur `AdvancedTemplateEngine` pour rendre les vues avec héritage de blocs et macros.
5. **Sécurité et stockage** – `EncryptionService` fournit le chiffrement AES‑256 et `JsonStorage` gère le stockage chiffré des entités `User`.
6. **CLI** – les classes annotées `#[Command]` dans `src/Command/` sont exécutables via le script `bin/console` (voir `doc/command.md`).

### Points de repère pour apprendre

- **Configuration** : options dans `config/*.json` et variables dans `.env` (APP_ENV, APP_DEBUG…).
- **Routage** : ajouter un contrôleur dans `src/Controller` avec `#[Route('/chemin', methods: ['GET'], name: 'nom')]`.
- **Templates** : placer les fichiers `.tpl` dans `template/` et étendre `base.html.tpl`.
- **Commandes CLI** : suivre `doc/command.md` pour créer une nouvelle commande.
- **Conventions** : respecter PSR‑12, écrire le code en anglais et commenter en français.

## Prérequis

- [PHP 8.3](https://www.php.net/) ou supérieur
- [Composer](https://getcomposer.org/)
- Git (pour la gestion de version)

## Installation

### Dépendances du projet principal

Dans la racine du projet, installez les dépendances du projet principal :

```bash
composer install
```

### Outils de développement

Les outils de développement (tests, analyse statique, formattage, etc.) sont isolés dans le répertoire tools/.
Pour installer ces dépendances, exécutez :

```bash
composer install --working-dir=tools
```
    Remarque : Le fichier tools/composer.json contient des dépendances comme friendsofphp/php-cs-fixer, phpunit/phpunit et phpstan/phpstan.

## Utilisation des outils de développement
### Exécution directe de PHP CS Fixer

Vous pouvez exécuter PHP CS Fixer directement en appelant l'exécutable installé dans tools/vendor/bin.
Pour corriger le code du projet principal, utilisez par exemple :

./tools/vendor/bin/php-cs-fixer fix --config=./.php-cs-fixer.dist.php --working-dir=.

Ce mode permet d'exécuter PHP CS Fixer avec l'autoloader et la configuration propre aux outils de développement tout en ciblant la racine du projet principal.
Lien symbolique dans le dossier bin/

Pour faciliter l'utilisation, vous pouvez créer un lien symbolique dans le répertoire bin/ qui pointe vers l'exécutable de PHP CS Fixer dans tools/vendor/bin.

Pour cela, exécutez depuis la racine du projet :

```bash
# Supprimez l'ancien script, le cas échéant
rm -f bin/php-cs-fixer

# Créez un lien symbolique vers l'exécutable de PHP CS Fixer
ln -s ../tools/vendor/bin/php-cs-fixer bin/php-cs-fixer
```

L'avantage est de pouvoir exécuter la commande simplement avec :

```bash
bin/php-cs-fixer fix --config=./.php-cs-fixer.dist.php --working-dir=.
```

    Alternative : Vous pouvez également modifier le script dans bin/php-cs-fixer pour pointer vers ../tools/vendor/autoload.php, mais la solution par lien symbolique est plus propre et évite de modifier du code fourni par les packages.

## Gestion de la configuration Git

Pour éviter de versionner les dépendances installées par Composer, ajoutez les entrées suivantes dans votre fichier .gitignore :

```
# Dépendances du projet principal
/vendor/

# Dépendances des outils de développement
/tools/vendor/
```

Ainsi, seuls les fichiers de configuration (composer.json, composer.lock, etc.) sont suivis dans Git, ce qui permet de garantir que chaque développeur installe localement les dépendances adéquates.

De plus, versionnez également les fichiers de configuration des outils de dev (par exemple, .php-cs-fixer.dist.php, phpstan.dist.neon, etc.) afin que toute l'équipe soit alignée.

## Conventions de code

- Langue du code : Tout le code est écrit en anglais (y compris les identifiants, noms de classes, variables, etc.).

- Documentation : Les blocs de documentation sont en français, en suivant les standards appropriés (phpDoc pour PHP).

- Typage strict : Utilisez le typage strict dans vos fichiers PHP et appliquez les type hints (PHP8).

- Principes SOLID : Chaque classe doit avoir une unique responsabilité, respecter l’extension sans modification, suivre la substitution de Liskov, proposer des interfaces spécifiques et dépendre des abstractions.

- Standards PSR : Le code doit être conforme aux recommandations PSR, notamment PSR-1 et PSR-12.

## Contribution

Les contributions sont les bienvenues !
Pour contribuer :

- Forkez le dépôt.

- Créez une branche dédiée à votre fonctionnalité ou correction.

- Envoyez une pull request en décrivant les modifications apportées.

## Licence

Ce projet est distribué sous la licence MIT.

N'hésitez pas à consulter ce README pour bien comprendre l'organisation et les bonnes pratiques à suivre dans ce projet.



