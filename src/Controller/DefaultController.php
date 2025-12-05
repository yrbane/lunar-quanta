<?php
/**
 * Lunar Quanta Framework - Contrôleur par Défaut (Page d'Accueil).
 *
 * =============================================================================
 * QU'EST-CE QUE LE DEFAULTCONTROLLER ?
 * =============================================================================
 *
 * Le DefaultController est le contrôleur qui gère la PAGE D'ACCUEIL de votre site.
 * C'est la première page que voit un visiteur quand il accède à votre domaine.
 *
 * ```
 * Quand l'utilisateur visite : http://monsite.com/
 *
 *     ┌─────────────────────────────────────────────┐
 *     │                 Navigateur                   │
 *     │  ┌───────────────────────────────────────┐  │
 *     │  │ 🔗 http://monsite.com/                │  │
 *     │  └───────────────────────────────────────┘  │
 *     │                                             │
 *     │  ┌───────────────────────────────────────┐  │
 *     │  │                                       │  │
 *     │  │     🏠 Bienvenue sur notre site !     │  │
 *     │  │                                       │  │
 *     │  │    Cette page est générée par        │  │
 *     │  │    DefaultController::index()        │  │
 *     │  │                                       │  │
 *     │  └───────────────────────────────────────┘  │
 *     └─────────────────────────────────────────────┘
 * ```
 *
 * =============================================================================
 * LA ROUTE "/" (RACINE DU SITE)
 * =============================================================================
 *
 * La route "/" est spéciale : c'est la "racine" de votre site web.
 *
 * ```
 * ANATOMIE D'UNE URL :
 *
 *    https://monsite.com/users/42/profile
 *    └──┬──┘ └────┬─────┘└─────┬──────────┘
 *       │        │            │
 *   Protocole  Domaine      Chemin (path)
 *
 *
 * EXEMPLES DE ROUTES :
 *
 *    /           → Page d'accueil (DefaultController)
 *    /users      → Liste des utilisateurs
 *    /users/42   → Profil de l'utilisateur #42
 *    /contact    → Page de contact
 *    /login      → Page de connexion
 * ```
 *
 * =============================================================================
 * STRUCTURE D'UNE ACTION DE CONTRÔLEUR
 * =============================================================================
 *
 * ```php
 * // Une "action" est une méthode publique d'un contrôleur
 * // qui répond à une route spécifique
 *
 * #[Route('/', name: 'home', methods: ['GET'])]  // ← L'attribut définit la route
 * public function index(Request $request): Response  // ← La signature standard
 * {
 *     // 1. Récupérer/préparer les données
 *     $data = ['title' => 'Accueil'];
 *
 *     // 2. Rendre le template
 *     $html = $this->render('home.html', $data);
 *
 *     // 3. Retourner la Response
 *     return new Response($html);
 * }
 * ```
 *
 * =============================================================================
 * L'ATTRIBUT #[Route] DÉCORTIQUÉ
 * =============================================================================
 *
 * ```php
 * #[Route('/', name: 'home', methods: ['GET'])]
 *        │      │           │
 *        │      │           └── Méthodes HTTP autorisées
 *        │      │               (GET = lecture seule)
 *        │      │
 *        │      └── Nom de la route pour les liens
 *        │          Ex: $router->generate('home') → "/"
 *        │
 *        └── Le chemin URL qui déclenche cette action
 *            "/" = racine du site
 * ```
 *
 * =============================================================================
 * FLUX COMPLET D'UNE REQUÊTE VERS LA PAGE D'ACCUEIL
 * =============================================================================
 *
 * ```
 * 1. Utilisateur tape : http://monsite.com/
 *
 *    ┌──────────────┐
 *    │  Navigateur  │ ─── GET / ───▶
 *    └──────────────┘
 *
 * 2. Le routeur cherche quelle action correspond à "/"
 *
 *    ┌──────────────┐
 *    │   Router     │ "/" → DefaultController::index()
 *    └──────────────┘
 *
 * 3. Le contrôleur est instancié et l'action appelée
 *
 *    ┌────────────────────┐
 *    │ DefaultController  │
 *    │                    │
 *    │ index($request)    │ ← Exécuté
 *    │   │                │
 *    │   ▼                │
 *    │ render('home.html')│ ← Template chargé
 *    │   │                │
 *    │   ▼                │
 *    │ return Response    │ ← HTML généré
 *    └────────────────────┘
 *
 * 4. La réponse est envoyée au navigateur
 *
 *    ┌──────────────┐
 *    │  Navigateur  │ ◀─── HTML ───
 *    │  (affiche)   │
 *    └──────────────┘
 * ```
 *
 * =============================================================================
 * BONNES PRATIQUES POUR LA PAGE D'ACCUEIL
 * =============================================================================
 *
 * ```
 * ✅ FAIRE :
 *
 * 1. Garder la page d'accueil légère et rapide
 *    → C'est la première impression !
 *
 * 2. Mettre les informations essentielles en premier
 *    → Qu'est-ce que ce site ? Que peut-on y faire ?
 *
 * 3. Avoir des liens clairs vers les sections principales
 *    → Navigation intuitive
 *
 * ❌ NE PAS FAIRE :
 *
 * 1. Charger TOUTES les données du site sur la page d'accueil
 *    → Trop lent, trop lourd
 *
 * 2. Avoir une page blanche ou presque vide
 *    → L'utilisateur ne sait pas où aller
 *
 * 3. Oublier le responsive design
 *    → Le mobile représente souvent +50% du trafic
 * ```
 *
 * @package    Lunar\Controller
 * @author     Seb <seb@nethttp.net>
 * @copyright  2024 Lunar Quanta
 * @license    MIT License
 * @version    1.1.0
 * @link       https://nethttp.net
 * @since      0.0.1
 *
 * @see \Lunar\Service\Core\BaseController La classe parente
 * @see \Lunar\Attribute\Route L'attribut de routage
 */
declare(strict_types=1);

namespace Lunar\Controller;

use Lunar\Attribute\Route;
use Lunar\Service\Core\BaseController;
use Lunar\Service\Core\Http\Request;
use Lunar\Service\Core\Http\Response;

/**
 * Contrôleur pour la page d'accueil du site.
 *
 * Ce contrôleur gère l'affichage de la page principale du site,
 * celle que voient les utilisateurs en arrivant sur le domaine.
 *
 * =============================================================================
 * EXEMPLE DE PERSONNALISATION
 * =============================================================================
 *
 * ```php
 * // Pour personnaliser la page d'accueil, modifiez :
 *
 * // 1. Le template utilisé
 * $html = $this->render('ma-nouvelle-home.html', [...]);
 *
 * // 2. Les données passées au template
 * $html = $this->render('home.html', [
 *     'title' => 'Mon Super Site',
 *     'featured_products' => $productService->getFeatured(),
 *     'latest_news' => $newsService->getLatest(5),
 * ]);
 * ```
 *
 * =============================================================================
 * AJOUTER D'AUTRES PAGES
 * =============================================================================
 *
 * ```php
 * // Vous pouvez ajouter d'autres actions dans ce contrôleur
 * // pour des pages "statiques" simples :
 *
 * #[Route('/about', name: 'about', methods: ['GET'])]
 * public function about(Request $request): Response
 * {
 *     return new Response($this->render('about.html'));
 * }
 *
 * #[Route('/contact', name: 'contact', methods: ['GET'])]
 * public function contact(Request $request): Response
 * {
 *     return new Response($this->render('contact.html'));
 * }
 *
 * // Ou créer des contrôleurs dédiés pour plus d'organisation :
 * // - AboutController pour /about et ses sous-pages
 * // - ContactController pour /contact et le traitement du formulaire
 * ```
 *
 * @package Lunar\Controller
 */
class DefaultController extends BaseController
{
    /**
     * Affiche la page d'accueil du site.
     *
     * Cette méthode est appelée quand un utilisateur visite la racine du site.
     * Elle génère la page d'accueil avec les informations de bienvenue.
     *
     * ==========================================================================
     * DÉCORTICAGE DE L'ATTRIBUT #[Route]
     * ==========================================================================
     *
     * ```php
     * #[Route('/', name: 'home', methods: ['GET'])]
     * ```
     *
     * - `'/'` : Répond à la racine du site (http://monsite.com/)
     * - `name: 'home'` : Nom de la route pour générer des URLs
     * - `methods: ['GET']` : N'accepte que les requêtes GET (lecture)
     *
     * ```
     * POURQUOI SEULEMENT 'GET' ?
     *
     * La page d'accueil est une page de LECTURE.
     * On ne soumet pas de formulaire, on ne modifie rien.
     *
     * GET  = "Je veux VOIR quelque chose"      → Page d'accueil
     * POST = "Je veux ENVOYER quelque chose"   → Formulaire
     * ```
     *
     * ==========================================================================
     * PARAMÈTRES
     * ==========================================================================
     *
     * @param Request $request L'objet Request contenant toutes les infos
     *                         de la requête HTTP (URL, méthode, headers...)
     *
     *                         ```
     *                         Même si on ne l'utilise pas ici, le Request
     *                         est toujours passé car :
     *                         - Convention du framework
     *                         - Permet d'accéder aux paramètres si besoin
     *                         - Uniformité entre toutes les actions
     *                         ```
     *
     * @return Response La réponse HTTP contenant le HTML de la page d'accueil
     *
     * ==========================================================================
     * VARIABLES PASSÉES AU TEMPLATE
     * ==========================================================================
     *
     * ```
     * ┌────────────┬─────────────────────────────────────────────────┐
     * │ Variable   │ Description                                     │
     * ├────────────┼─────────────────────────────────────────────────┤
     * │ title      │ Titre de la page (pour <title> et <h1>)         │
     * │ message    │ Message de bienvenue affiché                    │
     * │ loginUrl   │ URL vers la page de connexion                   │
     * └────────────┴─────────────────────────────────────────────────┘
     * ```
     *
     * ==========================================================================
     * EXEMPLE DE TEMPLATE (examples/blog.html)
     * ==========================================================================
     *
     * ```html
     * <!DOCTYPE html>
     * <html>
     * <head>
     *     <title>{{ title }}</title>
     * </head>
     * <body>
     *     <h1>{{ message }}</h1>
     *
     *     <nav>
     *         <a href="{{ loginUrl }}">Se connecter</a>
     *     </nav>
     *
     *     <!-- Contenu de la page... -->
     * </body>
     * </html>
     * ```
     */
    #[Route('/', name: 'home', methods: ['GET'])]
    public function index(Request $request): Response
    {
        // Rendre le template de la page d'accueil avec les données
        $html = $this->render('examples/blog.html', [
            'title' => 'Accueil',                    // Titre de la page
            'message' => 'Bienvenue sur notre site !', // Message de bienvenue
            'loginUrl' => '/login',                  // Lien vers la connexion
        ]);

        // Retourner la réponse HTTP avec le HTML généré
        // Code 200 par défaut (OK)
        return new Response($html);
    }
}
