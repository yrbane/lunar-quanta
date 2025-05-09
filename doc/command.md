# Documentation : Créer une nouvelle commande

Cette page décrit la procédure pour ajouter une **nouvelle commande** à la console du mini-framework, en suivant les conventions existantes (PSR, SOLID, etc.).

## Sommaire

1. [Introduction](#introduction)
2. [Structure du framework](#structure-du-framework)
3. [Étapes pour créer une nouvelle commande](#étapes-pour-créer-une-nouvelle-commande)
4. [Exemple de code](#exemple-de-code)
5. [Exécution de la commande](#exécution-de-la-commande)
6. [Bonnes pratiques](#bonnes-pratiques)

---

## Introduction

Le projet inclut un mini‐système de console (fichier `bin/console`), qui permet d’exécuter des commandes internes. Les commandes sont répertoriées **automatiquement** en analysant les classes du répertoire `src/Command/` portant l’attribut `#[Command(...)]`.

Chaque commande doit :

- **Implémenter** l’interface `App\Service\Command\CommandInterface`,
- **Étendre** (souvent) la classe abstraite `AbstractCommand` pour hériter de facilités comme la détection de `--help`, le parsing d’arguments nommés, etc.
- **Définir** l’attribut `#[Command(name: "...", description: "...")]` pour être détectée par le script de console.

---

## Structure du framework

Le **mini-framework** est organisé ainsi :

```
/public             => Fichiers accessibles par le serveur web (index.php, .htaccess, etc.)
/src
  /Command          => Classes de commande CLI (ex. MyNewCommand.php)
  /Controller       => Contrôleurs web
  /Entity           => Entités (stockées en JSON, etc.)
  /Service          => Services divers (Router, Command, Template, etc.)
/bin
  /console          => Script principal de la console
/vendor
  (et autres dossiers liés à Composer)
```

Le script `bin/console` charge les commandes situées dans `src/Command/` et exécute la commande correspondante si l’utilisateur saisit `bin/console <nom>`.

---

## Étapes pour créer une nouvelle commande

1. **Créer un fichier** dans `src/Command/` (par exemple `MyNewCommand.php`).
   - Son nom doit se terminer par `Command.php` (pour être trouvé plus facilement).

2. **Ajouter l’attribut** `#[Command(name: "...", description: "...")]` au début de la classe.
   - Le paramètre `name` est le **nom exact** de la commande en console, par ex. `"user:create"` ou `"router:debug"`.
   - Le paramètre `description` est le texte affiché lors du listing des commandes.

3. **Hériter** de `AbstractCommand` et **implémenter** `CommandInterface`.
   - `AbstractCommand` fournit quelques méthodes utiles (ex. `wantsHelp()`, `parseNamedArgs()`, etc.).
   - Vous devrez **obligatoirement** implémenter deux méthodes : `execute(array $args)` et `getHelp()`.

4. **Implémenter la logique** dans la méthode `execute(array $args)`.
   - Cette méthode sera appelée automatiquement quand on saisit la commande.
   - Elle doit retourner un **code de sortie** (int). 0 si tout s’est bien passé, ou un code d’erreur (>0) si nécessaire.

5. **Définir le texte d’aide** dans `getHelp()`.
   - Appelé si l’utilisateur saisit `bin/console <nom_commande> --help`.
   - Retournez un texte qui explique l’usage, les options, etc.

6. **(Optionnel) Ajouter** des méthodes privées pour organiser la logique (chargement de données, affichage, etc.).
   - Respectez la **responsabilité unique** (principe S de SOLID) : la commande doit surtout orchestrer, le détail du traitement peut être confié à des services dans `src/Service`.

---

## Exemple de code

Voici un exemple **simplifié** de création d’une commande `HelloCommand` qui affiche un message :

```php
<?php

declare(strict_types=1);

namespace App\Command;

use App\Attribute\Command;
use App\Service\Command\AbstractCommand;
use App\Service\Command\CommandInterface;
use App\Service\Command\ConsoleHelper as C;

/**
 * Commande "hello" qui salue l'utilisateur en console.
 */
#[Command(
    name: "hello",
    description: "Affiche un message de salutation."
)]
class HelloCommand extends AbstractCommand implements CommandInterface
{
    /**
     * Exécuté quand on lance `bin/console hello`.
     *
     * @param string[] $args Arguments de la ligne de commande
     */
    public function execute(array $args): int
    {
        // Vérifie si l'utilisateur veut l'aide :
        if ($this->wantsHelp($args)) {
            C::info($this->getHelp());
            return 0;
        }

        // Exemple: on affiche juste un message
        C::success("Bonjour depuis la commande 'hello' !");
        return 0; 
    }

    /**
     * Retourne l'aide détaillée, affichée via `bin/console hello --help`.
     */
    public function getHelp(): string
    {
        return <<<HELP
Cette commande affiche un simple message de salutation.

Usage:
  bin/console hello

Options:
  --help       Affiche l'aide de cette commande
HELP;
    }
}
```

---

## Exécution de la commande

1. **Listing de toutes les commandes** :
   ```bash
   bin/console
   ```
   Vous verrez un tableau contenant, entre autres, votre nouvelle commande.  
   Exemple :
   ```
   ➤ Commandes disponibles :

   +----------+---------------------------------+
   | Commande | Description                     |
   +----------+---------------------------------+
   | hello    | Affiche un message de salutation.
   +----------+---------------------------------+
   ...
   ```

2. **Exécuter la commande** :
   ```bash
   bin/console hello
   ```
   Elle va lancer la méthode `execute(...)` de votre classe `HelloCommand`.

3. **Afficher l’aide** :
   ```bash
   bin/console hello --help
   ```
   La méthode `getHelp()` sera utilisée pour décrire la commande, ses options, etc.

---

## Bonnes pratiques

- **Nommez la commande** avec un préfixe logique (ex. `user:create`, `cache:clear`, etc.) pour un groupement cohérent lors de l’affichage.
- **Déclarez vos dépendances** sous forme de services (si elles sont complexes). Évitez de mettre toute la logique métier dans la commande, pour rester conforme au principe de responsabilité unique.
- **Gérez les erreurs** en retournant un code différent de 0. L’utilisateur pourra ainsi savoir qu’une erreur est survenue (et des scripts d’intégration continue aussi).
- **Documentez** la commande (`getHelp()`), pour faciliter son utilisation par les autres développeurs.
- **Utilisez** le typage strict (`declare(strict_types=1);`), suivez PSR‑12 pour la mise en forme, et commentez en français vos méthodes (phpDoc).

---

Avec ces étapes, vous pouvez créer et exécuter rapidement une nouvelle commande dans le mini‐framework. Les développeurs et l’intégration continue (CI) pourront ensuite exploiter `bin/console` pour automatiser diverses tâches (maintenance, génération de fichiers, etc.).