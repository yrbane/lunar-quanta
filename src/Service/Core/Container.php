<?php
/**
 * Lunar Quanta Framework - Conteneur d'Injection de Dépendances.
 *
 * =============================================================================
 * QU'EST-CE QUE L'INJECTION DE DÉPENDANCES ? (Dependency Injection - DI)
 * =============================================================================
 *
 * L'INJECTION DE DÉPENDANCES est un patron de conception (design pattern) qui
 * permet à une classe de recevoir ses dépendances de l'extérieur, plutôt que
 * de les créer elle-même.
 *
 * ANALOGIE : Pensez à un restaurant
 *
 * SANS injection de dépendances (mauvais) :
 * - Le chef doit aller lui-même au marché chercher les ingrédients
 * - Il doit connaître tous les fournisseurs
 * - S'il veut changer de fournisseur, il doit modifier son travail
 *
 * AVEC injection de dépendances (bien) :
 * - Un livreur apporte les ingrédients au chef
 * - Le chef ne se soucie pas d'où viennent les ingrédients
 * - On peut changer de fournisseur sans que le chef ne change rien
 *
 * ```php
 * // ❌ SANS injection de dépendances
 * class UserController
 * {
 *     public function __construct()
 *     {
 *         // Le contrôleur CRÉE lui-même ses dépendances
 *         // Problèmes : difficile à tester, couplage fort
 *         $this->userRepository = new UserRepository();
 *         $this->mailer = new Mailer();
 *         $this->logger = new Logger();
 *     }
 * }
 *
 * // ✅ AVEC injection de dépendances
 * class UserController
 * {
 *     public function __construct(
 *         private UserRepository $userRepository,
 *         private Mailer $mailer,
 *         private Logger $logger
 *     ) {
 *         // Les dépendances sont INJECTÉES de l'extérieur
 *         // Avantages : testable, découplé, flexible
 *     }
 * }
 * ```
 *
 * =============================================================================
 * QU'EST-CE QU'UN CONTENEUR DE SERVICES ? (Service Container)
 * =============================================================================
 *
 * Un CONTENEUR (Container) est comme un "registre central" qui :
 *
 * 1. CONNAÎT toutes les classes de l'application
 * 2. SAIT comment les créer (quelles dépendances elles ont besoin)
 * 3. CRÉE les instances à la demande
 * 4. GARDE en mémoire les instances créées (singletons)
 *
 * ```
 *  SANS CONTENEUR                          AVEC CONTENEUR
 *
 *  // Création manuelle                    // Le conteneur s'en charge
 *  $db = new Database();                   $controller = $container->get(
 *  $repo = new UserRepository($db);            UserController::class
 *  $mailer = new Mailer();                 );
 *  $logger = new Logger();                 // Le conteneur crée automatiquement
 *  $controller = new UserController(       // toutes les dépendances nécessaires
 *      $repo, $mailer, $logger
 *  );
 * ```
 *
 * =============================================================================
 * QU'EST-CE QUE L'AUTO-WIRING ? (Câblage automatique)
 * =============================================================================
 *
 * L'AUTO-WIRING est la capacité du conteneur à DEVINER automatiquement
 * quelles dépendances une classe a besoin, en analysant son constructeur.
 *
 * Le conteneur utilise la REFLECTION API de PHP pour :
 * 1. Lire les paramètres du constructeur
 * 2. Déterminer le TYPE de chaque paramètre
 * 3. Créer automatiquement une instance de ce type
 *
 * ```php
 * class UserService
 * {
 *     public function __construct(
 *         private UserRepository $userRepository,  // Type: UserRepository
 *         private MailerInterface $mailer          // Type: MailerInterface
 *     ) { }
 * }
 *
 * // Le conteneur "comprend" automatiquement :
 * // - UserService a besoin d'un UserRepository
 * // - UserService a besoin d'un MailerInterface
 * // - Il crée ces dépendances automatiquement (récursivement)
 * ```
 *
 * =============================================================================
 * QU'EST-CE QU'UNE DÉPENDANCE ?
 * =============================================================================
 *
 * Une DÉPENDANCE est un objet dont une classe a besoin pour fonctionner.
 *
 * Si UserController a besoin de UserRepository pour récupérer les utilisateurs,
 * alors UserRepository est une DÉPENDANCE de UserController.
 *
 * ```
 *  UserController                    UserRepository
 *  ─────────────────                ─────────────────
 *  - Affiche la liste               - Accède à la DB
 *  - A BESOIN des données           - Fournit les données
 *
 *  UserController ──────dépend de────────► UserRepository
 * ```
 *
 * =============================================================================
 * QU'EST-CE QU'UN SINGLETON ?
 * =============================================================================
 *
 * Un SINGLETON est un objet qui n'existe qu'en UN SEUL exemplaire.
 *
 * POURQUOI ?
 * - Certains objets coûtent cher à créer (connexion DB, parseur de config...)
 * - On veut réutiliser la même instance partout
 * - On évite de gaspiller de la mémoire
 *
 * ```
 *  SANS singleton :
 *
 *  $controller1 = new UserController(new Database());  // Nouvelle connexion
 *  $controller2 = new AdminController(new Database()); // Encore une connexion
 *  // → 2 connexions à la base de données !
 *
 *  AVEC singleton (ce conteneur) :
 *
 *  $db = $container->get(Database::class);      // Crée la connexion
 *  $db = $container->get(Database::class);      // Retourne LA MÊME instance
 *  // → 1 seule connexion, réutilisée partout
 * ```
 *
 * =============================================================================
 * QU'EST-CE QU'UNE DÉPENDANCE CIRCULAIRE ?
 * =============================================================================
 *
 * Une DÉPENDANCE CIRCULAIRE se produit quand :
 * - A dépend de B
 * - B dépend de A
 *
 * C'est un PROBLÈME car on ne peut pas créer A sans B, ni B sans A !
 *
 * ```
 *  ┌─────────────────────────────────────────────────────────────────────────┐
 *  │                     DÉPENDANCE CIRCULAIRE (impossible)                  │
 *  │                                                                         │
 *  │     ClasseA ──────► ClasseB ──────► ClasseA ──────► ClasseB ...         │
 *  │                                                                         │
 *  │     Pour créer A, on a besoin de B                                      │
 *  │     Pour créer B, on a besoin de A                                      │
 *  │     → Boucle infinie !                                                  │
 *  └─────────────────────────────────────────────────────────────────────────┘
 * ```
 *
 * Le conteneur DÉTECTE ces situations et lance une exception explicative.
 *
 * =============================================================================
 * VOCABULAIRE POO
 * =============================================================================
 *
 * CLASSE : Un plan, un modèle pour créer des objets
 *          Ex: "Plan d'une voiture"
 *
 * OBJET (ou INSTANCE) : Une réalisation concrète d'une classe
 *                       Ex: "Ma voiture rouge garée dehors"
 *
 * INSTANCIER : Créer un objet à partir d'une classe
 *              Ex: $voiture = new Voiture();
 *
 * CONSTRUCTEUR : Méthode spéciale appelée lors de la création d'un objet
 *                Ex: public function __construct() { }
 *
 * PARAMÈTRE : Valeur passée à une fonction/méthode
 *             Ex: function calcul($nombre) - $nombre est un paramètre
 *
 * TYPE : La nature d'une variable (int, string, ou nom de classe)
 *        Ex: function foo(int $a, string $b, User $user)
 *
 * INTERFACE : Contrat qu'une classe s'engage à respecter
 *             Ex: Si une classe implémente UserRepositoryInterface,
 *             elle DOIT avoir toutes les méthodes définies dans l'interface
 *
 * @package    Lunar\Service\Core
 * @author     Seb <seb@nethttp.net>
 * @copyright  2024 Lunar Quanta
 * @license    MIT License
 * @version    1.1.0
 * @link       https://nethttp.net
 * @since      0.0.1
 *
 * @see ContainerInterface Interface que ce conteneur implémente
 * @see ContainerException Exception lancée en cas d'erreur de résolution
 */
declare(strict_types=1);

namespace Lunar\Service\Core;

use Lunar\Exception\ContainerException;

/**
 * Conteneur d'Injection de Dépendances léger avec auto-wiring.
 *
 * Cette classe est le CŒUR de l'architecture du framework.
 * Elle permet de créer des objets automatiquement en résolvant
 * leurs dépendances de façon récursive.
 *
 * =============================================================================
 * FONCTIONNEMENT INTERNE
 * =============================================================================
 *
 * Quand vous demandez une classe au conteneur, voici ce qui se passe :
 *
 * ```
 *  $container->get(UserController::class)
 *        │
 *        ▼
 *  1. Instance déjà créée ?
 *     ├─ OUI → Retourne l'instance existante (singleton)
 *     └─ NON → Continue...
 *        │
 *        ▼
 *  2. Dépendance circulaire ?
 *     ├─ OUI → Lance une exception
 *     └─ NON → Continue...
 *        │
 *        ▼
 *  3. Analyse le constructeur avec Reflection
 *     │
 *     ▼
 *  4. Pour chaque paramètre du constructeur :
 *     ├─ Récupère le type (nom de classe)
 *     └─ Appelle get() récursivement pour ce type
 *        │
 *        ▼
 *  5. Crée l'instance avec tous les arguments
 *     │
 *        ▼
 *  6. Stocke l'instance (singleton)
 *     │
 *     ▼
 *  7. Retourne l'instance
 * ```
 *
 * =============================================================================
 * EXEMPLE CONCRET DE RÉSOLUTION
 * =============================================================================
 *
 * Imaginons ces classes :
 *
 * ```php
 * class Database { }
 *
 * class UserRepository
 * {
 *     public function __construct(private Database $db) { }
 * }
 *
 * class UserController
 * {
 *     public function __construct(private UserRepository $repo) { }
 * }
 * ```
 *
 * Quand on fait $container->get(UserController::class) :
 *
 * ```
 *  Demande: UserController
 *     │
 *     ├── Analyse: UserController a besoin de UserRepository
 *     │   │
 *     │   ├── Analyse: UserRepository a besoin de Database
 *     │   │   │
 *     │   │   └── Database n'a pas de dépendances
 *     │   │       → Crée new Database()
 *     │   │
 *     │   └── Crée new UserRepository($database)
 *     │
 *     └── Crée new UserController($userRepository)
 *
 *  → Retourne UserController (avec toutes ses dépendances créées)
 * ```
 *
 * =============================================================================
 * EXEMPLES D'UTILISATION
 * =============================================================================
 *
 * ```php
 * // Création du conteneur
 * $container = new Container();
 *
 * // Récupération d'un service (auto-wiring)
 * $userService = $container->get(UserService::class);
 * // → Le conteneur crée automatiquement UserService avec ses dépendances
 *
 * // Vérifier si une classe peut être résolue
 * if ($container->has(MyService::class)) {
 *     $service = $container->get(MyService::class);
 * }
 *
 * // Singleton : la même instance est retournée
 * $service1 = $container->get(UserService::class);
 * $service2 = $container->get(UserService::class);
 * var_dump($service1 === $service2);  // true (même objet)
 * ```
 *
 * @package Lunar\Service\Core
 */
class Container implements ContainerInterface
{
    /**
     * Services instanciés (singletons).
     *
     * =========================================================================
     * STOCKAGE DES SINGLETONS
     * =========================================================================
     *
     * Ce tableau stocke les instances déjà créées, indexées par leur nom
     * de classe. Quand on demande une classe déjà créée, on retourne
     * directement l'instance existante au lieu d'en créer une nouvelle.
     *
     * STRUCTURE :
     * ```php
     * [
     *     'Lunar\Service\Database' => <objet Database>,
     *     'Lunar\Service\UserRepository' => <objet UserRepository>,
     *     // ...
     * ]
     * ```
     *
     * POURQUOI DES SINGLETONS ?
     * -------------------------
     * 1. PERFORMANCE : Évite de recréer les objets coûteux (connexions DB...)
     * 2. COHÉRENCE : Tous les services utilisent la même instance
     * 3. MÉMOIRE : Un seul objet en mémoire, pas de doublons
     *
     * QU'EST-CE QUE "class-string" ?
     * ------------------------------
     * C'est une annotation PHPStan/Psalm qui indique que la chaîne contient
     * un nom de classe valide. Ça aide les outils d'analyse statique.
     *
     * @var array<class-string, object> Tableau [nom_classe => instance]
     */
    private array $instances = [];

    /**
     * Classes en cours de résolution (détection des dépendances circulaires).
     *
     * =========================================================================
     * DÉTECTION DES DÉPENDANCES CIRCULAIRES
     * =========================================================================
     *
     * Ce tableau garde une trace des classes qu'on est EN TRAIN de résoudre.
     * Si on rencontre une classe déjà dans ce tableau, c'est qu'on a une
     * dépendance circulaire !
     *
     * EXEMPLE DE DÉTECTION :
     *
     * ```
     *  get(A)
     *  ├── $resolving = [A => true]
     *  │   │
     *  │   └── get(B)  (A dépend de B)
     *  │       ├── $resolving = [A => true, B => true]
     *  │       │   │
     *  │       │   └── get(A)  (B dépend de A)
     *  │       │       │
     *  │       │       └── A est déjà dans $resolving !
     *  │       │           → ERREUR : Circular dependency: A -> B -> A
     * ```
     *
     * Le bloc finally dans get() s'assure qu'on nettoie $resolving
     * même si une exception est lancée.
     *
     * @var array<class-string, bool> Tableau [nom_classe => true] des classes en cours
     */
    private array $resolving = [];

    /**
     * Vérifie si un service est enregistré ou peut être instancié.
     *
     * =========================================================================
     * LOGIQUE DE VÉRIFICATION
     * =========================================================================
     *
     * Cette méthode retourne true si :
     * 1. L'instance existe déjà dans $instances (singleton déjà créé)
     * 2. OU la classe existe dans le code (peut être instanciée)
     *
     * Elle retourne false si :
     * - La classe n'existe pas
     * - L'ID fourni n'est pas un nom de classe valide
     *
     * ```php
     * $container->has(UserService::class);  // true (si la classe existe)
     * $container->has('ClasseInexistante'); // false
     * $container->has('pas-une-classe');    // false
     * ```
     *
     * QU'EST-CE QUE class_exists() ?
     * ------------------------------
     * Fonction PHP native qui vérifie si une classe est définie.
     * Elle déclenche aussi l'autoloader pour tenter de charger la classe.
     *
     * @param string $id Le nom complet de la classe (avec namespace)
     *                   Ex: 'Lunar\Service\UserService'
     *
     * @return bool true si le service peut être résolu, false sinon
     *
     * @example
     * ```php
     * // Vérification avant utilisation
     * if ($container->has(CacheService::class)) {
     *     $cache = $container->get(CacheService::class);
     * } else {
     *     echo "Le service de cache n'est pas disponible";
     * }
     * ```
     */
    public function has(string $id): bool
    {
        // 1. Vérifie si l'instance existe déjà (singleton)
        if (isset($this->instances[$id])) {
            return true;
        }

        // 2. Vérifie si la classe existe dans le code
        return class_exists($id);
    }

    /**
     * Instancie une classe en résolvant récursivement ses dépendances.
     *
     * =========================================================================
     * MÉTHODE PRINCIPALE DU CONTENEUR
     * =========================================================================
     *
     * C'est LA méthode centrale du conteneur. Elle :
     *
     * 1. Vérifie si l'instance existe déjà (retourne le singleton)
     * 2. Détecte les dépendances circulaires
     * 3. Analyse le constructeur de la classe (Reflection)
     * 4. Résout récursivement chaque dépendance
     * 5. Crée l'instance avec toutes les dépendances
     * 6. Stocke l'instance pour les prochains appels
     *
     * ```
     *  ┌─────────────────────────────────────────────────────────────────────┐
     *  │                    PROCESSUS DE RÉSOLUTION                          │
     *  │                                                                     │
     *  │  get(UserController::class)                                         │
     *  │      │                                                              │
     *  │      ├─► Singleton existe ? Non                                     │
     *  │      ├─► Circulaire ? Non                                           │
     *  │      ├─► Analyse constructeur:                                      │
     *  │      │   __construct(UserRepository $repo, Logger $logger)          │
     *  │      │                                                              │
     *  │      ├─► Résout UserRepository:                                     │
     *  │      │   ├─► Singleton existe ? Non                                 │
     *  │      │   ├─► Analyse: __construct(Database $db)                     │
     *  │      │   ├─► Résout Database (pas de dépendances)                   │
     *  │      │   └─► Crée UserRepository($database)                         │
     *  │      │                                                              │
     *  │      ├─► Résout Logger (pas de dépendances)                         │
     *  │      │                                                              │
     *  │      └─► Crée UserController($userRepository, $logger)              │
     *  │                                                                     │
     *  └─────────────────────────────────────────────────────────────────────┘
     * ```
     *
     * QU'EST-CE QUE @template T of object ?
     * -------------------------------------
     * C'est une annotation générique (generic) pour l'analyse statique.
     * Elle indique que la méthode retourne un objet du TYPE demandé.
     *
     * ```php
     * // PHPStan/Psalm comprend que $user est de type User
     * $user = $container->get(User::class);
     * // Pas besoin de cast ou @var
     * ```
     *
     * @template T of object
     *
     * @param class-string<T> $className Le nom complet de la classe à instancier
     *                                   Ex: 'Lunar\Service\UserService'
     *
     * @return T L'instance de la classe demandée
     *
     * @throws ContainerException Si la classe ne peut pas être instanciée
     *                            (classe abstraite, interface, dépendance circulaire,
     *                            paramètre non typé, type primitif...)
     *
     * @example Utilisation basique
     * ```php
     * $container = new Container();
     *
     * // Le conteneur crée automatiquement toutes les dépendances
     * $service = $container->get(UserService::class);
     *
     * // L'instance est mise en cache (singleton)
     * $same = $container->get(UserService::class);
     * var_dump($service === $same);  // true
     * ```
     *
     * @example Gestion des erreurs
     * ```php
     * try {
     *     $service = $container->get(InvalidService::class);
     * } catch (ContainerException $e) {
     *     echo "Impossible de créer le service : " . $e->getMessage();
     * }
     * ```
     */
    public function get(string $className): object
    {
        // =====================================================================
        // ÉTAPE 1 : Vérification du singleton
        // =====================================================================
        // Si l'instance existe déjà, on la retourne directement.
        // C'est le comportement "singleton" : une seule instance par classe.
        if (isset($this->instances[$className])) {
            /** @var T */
            return $this->instances[$className];
        }

        // =====================================================================
        // ÉTAPE 2 : Détection des dépendances circulaires
        // =====================================================================
        // Si la classe est déjà dans $resolving, c'est qu'on est en train
        // de la résoudre depuis un niveau supérieur → dépendance circulaire !
        if (isset($this->resolving[$className])) {
            // Reconstruit la chaîne de dépendances pour le message d'erreur
            $chain = array_keys($this->resolving);
            $chain[] = $className;

            throw new ContainerException(
                sprintf(
                    'Circular dependency detected: %s',
                    implode(' -> ', $chain)
                )
            );
        }

        // Marque cette classe comme "en cours de résolution"
        $this->resolving[$className] = true;

        // =====================================================================
        // ÉTAPE 3 : Résolution de la classe
        // =====================================================================
        // Le bloc try...finally garantit qu'on nettoie $resolving même
        // si une exception est lancée pendant la résolution.
        try {
            // -----------------------------------------------------------------
            // Analyse de la classe avec Reflection
            // -----------------------------------------------------------------
            // ReflectionClass permet d'inspecter une classe : son constructeur,
            // ses méthodes, ses paramètres, etc.
            $refClass = new \ReflectionClass($className);

            // Vérifie que la classe peut être instanciée
            // (pas une interface, pas abstraite, pas un trait)
            if (!$refClass->isInstantiable()) {
                throw new ContainerException("Class {$className} is not instantiable.");
            }

            // -----------------------------------------------------------------
            // Cas simple : pas de constructeur
            // -----------------------------------------------------------------
            // Si la classe n'a pas de constructeur, on peut l'instancier
            // directement sans arguments.
            $constructor = $refClass->getConstructor();
            if (null === $constructor) {
                $instance = new $className();
                $this->instances[$className] = $instance;

                return $instance;
            }

            // -----------------------------------------------------------------
            // Résolution récursive des paramètres du constructeur
            // -----------------------------------------------------------------
            // Pour chaque paramètre du constructeur, on :
            // 1. Récupère son type (nom de classe)
            // 2. Appelle get() récursivement pour ce type
            // 3. Stocke le résultat comme argument
            $args = array_map(function (\ReflectionParameter $param) use ($className): object {
                // Récupère le type du paramètre
                $type = $param->getType();

                // Vérifie que le type est valide
                // - Doit être un ReflectionNamedType (pas union, pas intersection)
                // - Ne doit pas être un type primitif (int, string, bool...)
                if (!$type instanceof \ReflectionNamedType || $type->isBuiltin()) {
                    throw new ContainerException(
                        "Cannot resolve dependency `{$param->getName()}` in {$className}."
                    );
                }

                // Récupère le nom de la classe dépendance
                /** @var class-string $dependencyClass */
                $dependencyClass = $type->getName();

                // Appel RÉCURSIF : résout la dépendance
                // Si la dépendance a elle-même des dépendances, elles seront
                // aussi résolues récursivement
                return $this->get($dependencyClass);
            }, $constructor->getParameters());

            // -----------------------------------------------------------------
            // Création de l'instance avec tous les arguments résolus
            // -----------------------------------------------------------------
            $instance = $refClass->newInstanceArgs($args);

            // Stocke l'instance pour les prochains appels (singleton)
            $this->instances[$className] = $instance;

            return $instance;
        } finally {
            // Nettoie le marqueur de résolution
            // (exécuté même si une exception est lancée)
            unset($this->resolving[$className]);
        }
    }
}
