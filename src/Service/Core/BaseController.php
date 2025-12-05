<?php
/**
 * Lunar Quanta Framework - Contrôleur de Base (Classe Abstraite).
 *
 * =============================================================================
 * QU'EST-CE QU'UN CONTRÔLEUR ?
 * =============================================================================
 *
 * Un CONTRÔLEUR est une classe qui reçoit les requêtes HTTP et décide quoi faire.
 * C'est le "chef d'orchestre" de votre application web.
 *
 * ```
 * ANALOGIE : LE SERVEUR DE RESTAURANT
 *
 *    Le contrôleur est comme un serveur dans un restaurant :
 *
 *    1. Il reçoit la commande du client (la requête HTTP)
 *    2. Il la transmet à la cuisine (les services métier)
 *    3. Il récupère le plat préparé (les données)
 *    4. Il le présente joliment au client (le rendu HTML via les templates)
 *
 *    ┌─────────┐        ┌────────────┐        ┌─────────┐
 *    │ Client  │───────▶│ Contrôleur │───────▶│ Service │
 *    │ (User)  │        │ (Serveur)  │        │(Cuisine)│
 *    └─────────┘        └────────────┘        └─────────┘
 *         ▲                   │                    │
 *         │                   │                    ▼
 *         │                   │              ┌──────────┐
 *         │                   ◀──────────────│ Données  │
 *         │                                  └──────────┘
 *         │
 *         │             ┌────────────┐
 *         ◀─────────────│  Template  │
 *                       │   (HTML)   │
 *                       └────────────┘
 * ```
 *
 * =============================================================================
 * QU'EST-CE QU'UNE CLASSE ABSTRAITE ?
 * =============================================================================
 *
 * Une CLASSE ABSTRAITE est une classe qui ne peut pas être utilisée directement.
 * Elle sert de "modèle" ou de "plan" pour d'autres classes.
 *
 * ```
 * ANALOGIE : LE PLAN D'ARCHITECTE
 *
 *    Une classe abstraite, c'est comme un plan d'architecte :
 *
 *    ┌─────────────────────────────┐
 *    │      PLAN D'ARCHITECTE      │  ← Classe abstraite
 *    │    (BaseController)         │
 *    │                             │
 *    │  - Pièces de base définies  │  ← Méthodes communes (render)
 *    │  - Pas habitable tel quel   │  ← Pas instanciable
 *    └──────────────┬──────────────┘
 *                   │
 *          ┌────────┴────────┐
 *          ▼                 ▼
 *    ┌───────────┐     ┌───────────┐
 *    │  Maison A │     │  Maison B │   ← Classes concrètes
 *    │(UserCtrl) │     │(HomeCtrl) │
 *    │           │     │           │
 *    │ + Garage  │     │ + Jardin  │   ← Fonctionnalités propres
 *    └───────────┘     └───────────┘
 * ```
 *
 * Le mot-clé `abstract` devant `class` signifie :
 * - On NE PEUT PAS faire `new BaseController()` (erreur !)
 * - On DOIT créer une classe enfant qui `extends BaseController`
 *
 * =============================================================================
 * POURQUOI UN CONTRÔLEUR DE BASE ?
 * =============================================================================
 *
 * ```
 * SANS BASECONTROLLER (code répété partout) :
 *
 *    class UserController {
 *        protected function render($template, $vars) {
 *            // 20 lignes de code...
 *        }
 *    }
 *
 *    class ProductController {
 *        protected function render($template, $vars) {
 *            // Les MÊMES 20 lignes de code... 😱
 *        }
 *    }
 *
 *    class OrderController {
 *        protected function render($template, $vars) {
 *            // Encore les MÊMES 20 lignes... 😱😱
 *        }
 *    }
 *
 * AVEC BASECONTROLLER (code factorisé) :
 *
 *    abstract class BaseController {
 *        protected function render($template, $vars) {
 *            // 20 lignes de code UNE SEULE FOIS ✓
 *        }
 *    }
 *
 *    class UserController extends BaseController {
 *        // Hérite automatiquement de render() !
 *    }
 *
 *    class ProductController extends BaseController {
 *        // Hérite automatiquement de render() !
 *    }
 * ```
 *
 * C'est le principe DRY : "Don't Repeat Yourself" (Ne te répète pas).
 *
 * =============================================================================
 * ARCHITECTURE MVC (MODÈLE-VUE-CONTRÔLEUR)
 * =============================================================================
 *
 * Le pattern MVC sépare l'application en 3 parties distinctes :
 *
 * ```
 * ┌───────────────────────────────────────────────────────────────────┐
 * │                         ARCHITECTURE MVC                          │
 * ├───────────────────────────────────────────────────────────────────┤
 * │                                                                   │
 * │   ┌─────────────────┐                                             │
 * │   │     MODÈLE      │  Gère les données et la logique métier      │
 * │   │    (Entity)     │  Ex: User, Product, Order                   │
 * │   └────────┬────────┘                                             │
 * │            │                                                      │
 * │            ▼                                                      │
 * │   ┌─────────────────┐                                             │
 * │   │   CONTRÔLEUR    │  Reçoit les requêtes, coordonne M et V      │
 * │   │  (Controller)   │  Ex: UserController, ProductController      │
 * │   └────────┬────────┘                                             │
 * │            │                                                      │
 * │            ▼                                                      │
 * │   ┌─────────────────┐                                             │
 * │   │      VUE        │  Affiche les données (templates HTML)       │
 * │   │   (Template)    │  Ex: user/profile.html, product/list.html   │
 * │   └─────────────────┘                                             │
 * │                                                                   │
 * └───────────────────────────────────────────────────────────────────┘
 * ```
 *
 * =============================================================================
 * FLUX D'UNE REQUÊTE DANS UN CONTRÔLEUR
 * =============================================================================
 *
 * ```
 * Utilisateur tape : http://monsite.com/user/42
 *
 *    ┌──────────┐     ┌──────────┐     ┌────────────────┐
 *    │ Navigateur│────▶│ Routeur  │────▶│ UserController │
 *    │          │     │          │     │                │
 *    └──────────┘     └──────────┘     └───────┬────────┘
 *                                              │
 *                     1. Le routeur trouve     │ show($id = 42)
 *                        le bon contrôleur     │
 *                                              ▼
 *                                      ┌───────────────┐
 *                                      │ UserService   │
 *                                      │ (récupère     │
 *                                      │  l'utilisateur)│
 *                                      └───────┬───────┘
 *                                              │
 *                                              ▼
 *                                      ┌───────────────┐
 *                                      │ Base de       │
 *                                      │ données       │
 *                                      └───────┬───────┘
 *                                              │
 *                      Les données             │ User { id: 42, name: "Alice" }
 *                      remontent               │
 *                                              ▼
 *                                      ┌───────────────┐
 *                                      │ Template      │
 *                                      │ user/show.html│
 *                                      └───────┬───────┘
 *                                              │
 *    ┌──────────┐                              │ HTML final
 *    │Navigateur│◀─────────────────────────────┘
 *    │ (affiche │
 *    │ la page) │
 *    └──────────┘
 * ```
 *
 * =============================================================================
 * LA MÉTHODE RENDER() EXPLIQUÉE
 * =============================================================================
 *
 * La méthode `render()` est la méthode la plus importante du BaseController.
 * Elle transforme un template + des données en HTML.
 *
 * ```php
 * // Dans un contrôleur enfant :
 * $html = $this->render('user/profile.html', [
 *     'user' => $user,           // Variable $user dans le template
 *     'title' => 'Mon Profil',   // Variable $title dans le template
 * ]);
 *
 * // Le moteur de template :
 * // 1. Charge le fichier templates/user/profile.html
 * // 2. Remplace {{ user.name }} par la valeur réelle
 * // 3. Retourne le HTML final
 * ```
 *
 * ```
 * COMMENT ÇA MARCHE :
 *
 *    Template (user/profile.html)         +    Variables PHP
 *    ┌─────────────────────────────┐          ┌─────────────────┐
 *    │ <h1>Bonjour {{ name }}</h1> │    +     │ ['name'=>'Alice']│
 *    │ <p>Email: {{ email }}</p>   │          │ ['email'=>'...'] │
 *    └─────────────────────────────┘          └─────────────────┘
 *                     │                              │
 *                     └──────────────┬───────────────┘
 *                                    │
 *                                    ▼ render()
 *                     ┌─────────────────────────────────┐
 *                     │ <h1>Bonjour Alice</h1>          │
 *                     │ <p>Email: alice@example.com</p> │
 *                     └─────────────────────────────────┘
 *                            HTML final retourné
 * ```
 *
 * =============================================================================
 * EXEMPLE COMPLET D'UN CONTRÔLEUR ENFANT
 * =============================================================================
 *
 * ```php
 * <?php
 * namespace App\Controller;
 *
 * use Lunar\Service\Core\BaseController;
 * use Lunar\Service\Core\Http\Request;
 * use Lunar\Service\Core\Http\Response;
 * use Lunar\Attribute\Route;
 *
 * class ProductController extends BaseController
 * {
 *     // Action pour lister tous les produits
 *     #[Route('/products', methods: ['GET'], name: 'product.list')]
 *     public function list(Request $request): Response
 *     {
 *         // 1. Récupérer les données (via un service)
 *         $products = $this->productService->findAll();
 *
 *         // 2. Rendre le template avec les données
 *         $html = $this->render('product/list.html', [
 *             'products' => $products,
 *             'title' => 'Nos Produits',
 *         ]);
 *
 *         // 3. Retourner la réponse HTTP
 *         return new Response($html);
 *     }
 *
 *     // Action pour afficher un produit spécifique
 *     #[Route('/products/{id}', methods: ['GET'], name: 'product.show')]
 *     public function show(Request $request, int $id): Response
 *     {
 *         $product = $this->productService->find($id);
 *
 *         if (!$product) {
 *             // Produit non trouvé → erreur 404
 *             $html = $this->render('error.html', [
 *                 'message' => 'Produit introuvable',
 *             ]);
 *             return new Response($html, 404);
 *         }
 *
 *         $html = $this->render('product/show.html', [
 *             'product' => $product,
 *         ]);
 *
 *         return new Response($html);
 *     }
 * }
 * ```
 *
 * =============================================================================
 * BONNES PRATIQUES POUR LES CONTRÔLEURS
 * =============================================================================
 *
 * ```
 * ✅ BONNES PRATIQUES :
 *
 * 1. Un contrôleur doit être LÉGER (thin controller)
 *    → La logique métier va dans les Services
 *    → Le contrôleur ne fait que coordonner
 *
 * 2. Une action = une responsabilité
 *    → list() liste les éléments
 *    → show() affiche un élément
 *    → create() crée un élément
 *
 * 3. Toujours retourner une Response
 *    → Même en cas d'erreur
 *
 * 4. Valider les entrées utilisateur
 *    → Ne jamais faire confiance aux données reçues
 *
 * ❌ MAUVAISES PRATIQUES :
 *
 * 1. Mettre toute la logique dans le contrôleur
 *    → Contrôleurs de 500 lignes = ingérable
 *
 * 2. Accéder directement à la base de données
 *    → Utiliser des Services ou Repositories
 *
 * 3. Faire des `echo` dans un contrôleur
 *    → Toujours retourner une Response
 * ```
 *
 * @package    Lunar\Service\Core
 * @author     Seb <seb@nethttp.net>
 * @copyright  2024 Lunar Quanta
 * @license    MIT License
 * @version    1.1.0
 * @link       https://nethttp.net
 * @since      0.0.1
 *
 * @see \Lunar\Controller\DefaultController Exemple de contrôleur simple
 * @see \Lunar\Controller\UserController Exemple avec formulaire
 * @see \Lunar\Service\Core\Template\LunarTemplateAdapter Le moteur de templates
 */
declare(strict_types=1);

namespace Lunar\Service\Core;

use Lunar\Config\Config;
use Lunar\Service\Core\Template\LunarTemplateAdapter;

/**
 * Classe abstraite de base pour tous les contrôleurs.
 *
 * Cette classe fournit les fonctionnalités communes à tous les contrôleurs :
 * - Rendu de templates via la méthode render()
 * - Configuration automatique du moteur de templates
 *
 * =============================================================================
 * HÉRITAGE ET CLASSES ABSTRAITES
 * =============================================================================
 *
 * ```
 * POURQUOI "ABSTRACT" ?
 *
 *    Le mot-clé "abstract" signifie :
 *
 *    ❌ new BaseController()     // IMPOSSIBLE ! Erreur fatale
 *    ✅ new UserController()     // OK, car UserController extends BaseController
 *
 *    C'est comme un formulaire à remplir :
 *    - Le formulaire vide (BaseController) n'est pas utilisable
 *    - Le formulaire rempli (UserController) est utilisable
 * ```
 *
 * =============================================================================
 * CRÉATION D'UN NOUVEAU CONTRÔLEUR
 * =============================================================================
 *
 * ```php
 * // Étape 1 : Créer une classe qui étend BaseController
 * class MonController extends BaseController
 * {
 *     // Étape 2 : Définir des actions (méthodes publiques)
 *     #[Route('/ma-page', methods: ['GET'], name: 'ma.page')]
 *     public function maPage(Request $request): Response
 *     {
 *         // Étape 3 : Utiliser render() pour générer le HTML
 *         $html = $this->render('ma-page.html', [
 *             'message' => 'Hello World!',
 *         ]);
 *
 *         // Étape 4 : Retourner une Response
 *         return new Response($html);
 *     }
 * }
 * ```
 *
 * @package Lunar\Service\Core
 */
abstract class BaseController
{
    /**
     * Constructeur du contrôleur de base.
     *
     * Le constructeur est vide par défaut, mais les classes enfants peuvent
     * l'étendre pour injecter des dépendances (services, repositories, etc.).
     *
     * ```php
     * // Exemple dans un contrôleur enfant :
     * class UserController extends BaseController
     * {
     *     private UserService $userService;
     *
     *     public function __construct(UserService $userService)
     *     {
     *         parent::__construct();  // Appeler le constructeur parent
     *         $this->userService = $userService;
     *     }
     * }
     * ```
     */
    public function __construct() {}

    /**
     * Rend un template avec des variables et retourne le HTML généré.
     *
     * Cette méthode est le coeur du système de rendu. Elle :
     * 1. Charge la configuration du moteur de templates
     * 2. Instancie le moteur de templates
     * 3. Passe les variables au template
     * 4. Retourne le HTML généré
     *
     * ==========================================================================
     * PARAMÈTRES EXPLIQUÉS
     * ==========================================================================
     *
     * @param string $template Nom du fichier template (avec ou sans extension)
     *
     *                         ```
     *                         Exemples valides :
     *                         - 'home.html'           → templates/home.html
     *                         - 'user/profile.html'   → templates/user/profile.html
     *                         - 'admin/dashboard'     → templates/admin/dashboard.html
     *                         ```
     *
     * @param array<string, mixed> $variables Variables à injecter dans le template
     *
     *                                        ```php
     *                                        $this->render('user.html', [
     *                                            'name' => 'Alice',      // {{ name }} dans le template
     *                                            'age' => 25,            // {{ age }} dans le template
     *                                            'items' => [1, 2, 3],   // {% for item in items %}
     *                                        ]);
     *                                        ```
     *
     * @return string Le contenu HTML généré par le moteur de templates
     *
     * @throws \Exception Si le moteur de templates n'existe pas
     *
     * ==========================================================================
     * EXEMPLES D'UTILISATION
     * ==========================================================================
     *
     * ```php
     * // Exemple 1 : Page simple
     * $html = $this->render('home.html', [
     *     'title' => 'Accueil',
     * ]);
     *
     * // Exemple 2 : Liste d'éléments
     * $html = $this->render('products/list.html', [
     *     'products' => $products,
     *     'total' => count($products),
     * ]);
     *
     * // Exemple 3 : Formulaire avec erreurs
     * $html = $this->render('user/edit.html', [
     *     'user' => $user,
     *     'errors' => ['email' => 'Email invalide'],
     * ]);
     * ```
     *
     * ==========================================================================
     * FONCTIONNEMENT INTERNE
     * ==========================================================================
     *
     * ```
     * render('user/profile.html', ['name' => 'Alice'])
     *     │
     *     ▼
     * ┌─────────────────────────────────────────────────────────┐
     * │ 1. Lire la config : template.engine = LunarTemplate     │
     * │ 2. Lire la config : template.path = /var/www/templates  │
     * │ 3. Créer l'instance : new LunarTemplateAdapter(...)     │
     * │ 4. Appeler : $engine->render('user/profile.html', ...)  │
     * └─────────────────────────────────────────────────────────┘
     *     │
     *     ▼
     * HTML final : "<h1>Bonjour Alice</h1>..."
     * ```
     */
    protected function render(string $template, array $variables = []): string
    {
        // Récupérer la classe du moteur de templates depuis la configuration
        // Par défaut : LunarTemplateAdapter (le moteur intégré au framework)
        $engineClassConfig = Config::get('template', 'template.engine', LunarTemplateAdapter::class);
        $engineClass = is_string($engineClassConfig) ? $engineClassConfig : LunarTemplateAdapter::class;

        // Récupérer le chemin vers le dossier des templates
        // Par défaut : 'template' (relatif à la racine du projet)
        $templatePathConfig = Config::get('template', 'template.template_path', 'template');
        $templatePath = Config::resolvePath(
            is_string($templatePathConfig) ? $templatePathConfig : 'template'
        );

        // Vérifier que la classe du moteur existe
        if (!class_exists($engineClass)) {
            throw new \Exception("Template engine class {$engineClass} does not exist.");
        }

        // Instancier le moteur de templates et effectuer le rendu
        /** @var LunarTemplateAdapter $engine */
        $engine = new $engineClass($templatePath);

        return $engine->render($template, $variables);
    }
}
