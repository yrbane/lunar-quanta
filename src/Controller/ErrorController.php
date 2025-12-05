<?php
/**
 * Lunar Quanta Framework - Contrôleur des Pages d'Erreur.
 *
 * =============================================================================
 * QU'EST-CE QU'UNE PAGE D'ERREUR ?
 * =============================================================================
 *
 * Quand quelque chose ne va pas sur un site web, le serveur envoie un
 * CODE D'ERREUR HTTP au navigateur. Les plus connus sont :
 *
 * ```
 * ┌───────┬─────────────────────────────┬────────────────────────────────┐
 * │ Code  │ Nom                         │ Signification                  │
 * ├───────┼─────────────────────────────┼────────────────────────────────┤
 * │  400  │ Bad Request                 │ Requête mal formée             │
 * │  401  │ Unauthorized                │ Non authentifié                │
 * │  403  │ Forbidden                   │ Accès interdit                 │
 * │  404  │ Not Found                   │ Page introuvable               │
 * │  405  │ Method Not Allowed          │ Méthode HTTP non autorisée     │
 * │  500  │ Internal Server Error       │ Erreur serveur                 │
 * │  502  │ Bad Gateway                 │ Passerelle invalide            │
 * │  503  │ Service Unavailable         │ Service indisponible           │
 * └───────┴─────────────────────────────┴────────────────────────────────┘
 * ```
 *
 * =============================================================================
 * RÔLE DE L'ERRORCONTROLLER
 * =============================================================================
 *
 * Au lieu d'afficher une page blanche avec juste "404 Not Found",
 * l'ErrorController crée une JOLIE page d'erreur personnalisée.
 *
 * ```
 * SANS ErrorController :              AVEC ErrorController :
 *
 * ┌──────────────────────┐           ┌──────────────────────────────┐
 * │                      │           │  ╔══════════════════════╗    │
 * │  404 Not Found       │           │  ║   Oups ! Erreur 404  ║    │
 * │                      │           │  ╚══════════════════════╝    │
 * │                      │           │                              │
 * │                      │           │  La page que vous cherchez   │
 * │                      │           │  n'existe pas.               │
 * │                      │           │                              │
 * │                      │           │  [← Retour à l'accueil]      │
 * └──────────────────────┘           └──────────────────────────────┘
 *     Moche et froid !                   Joli et accueillant !
 * ```
 *
 * =============================================================================
 * FLUX D'UNE ERREUR DANS LE FRAMEWORK
 * =============================================================================
 *
 * ```
 * Utilisateur visite : http://monsite.com/page-inexistante
 *
 *     ┌──────────────┐
 *     │  Navigateur  │ GET /page-inexistante
 *     └──────┬───────┘
 *            │
 *            ▼
 *     ┌──────────────┐
 *     │   Router     │ Cherche la route "/page-inexistante"
 *     └──────┬───────┘
 *            │
 *            ▼ Route non trouvée !
 *     ┌──────────────┐
 *     │ FrontController │ Attrape l'erreur
 *     └──────┬───────┘
 *            │
 *            ▼ Appelle ErrorController
 *     ┌──────────────┐
 *     │ErrorController│ index($request, 404, "Page non trouvée")
 *     └──────┬───────┘
 *            │
 *            ▼ Rend le template error.html
 *     ┌──────────────┐
 *     │  Template    │ Génère le HTML de la page d'erreur
 *     │ error.html   │
 *     └──────┬───────┘
 *            │
 *            ▼ Response avec code 404
 *     ┌──────────────┐
 *     │  Navigateur  │ Affiche la jolie page d'erreur
 *     └──────────────┘
 * ```
 *
 * =============================================================================
 * LES CODES HTTP PAR CATÉGORIE
 * =============================================================================
 *
 * ```
 * CATÉGORIES DES CODES HTTP :
 *
 *    1xx - Information      (rarement utilisé)
 *    2xx - Succès           (tout va bien !)
 *    3xx - Redirection      (va voir ailleurs)
 *    4xx - Erreur CLIENT    (c'est de ta faute)
 *    5xx - Erreur SERVEUR   (c'est de ma faute)
 *
 * ERREURS COURANTES (4xx - problème côté utilisateur) :
 *
 *    404 → La page n'existe pas
 *          Ex: /utilisateur/999 (l'utilisateur 999 n'existe pas)
 *
 *    401 → Non authentifié (pas connecté)
 *          Ex: /admin/dashboard (il faut se connecter d'abord)
 *
 *    403 → Interdit (connecté mais pas le droit)
 *          Ex: /admin/delete/1 (pas les droits admin)
 *
 *    405 → Mauvaise méthode HTTP
 *          Ex: DELETE /contact (la route n'accepte que GET/POST)
 *
 * ERREURS SERVEUR (5xx - problème côté application) :
 *
 *    500 → Erreur interne (bug dans le code)
 *          Ex: Division par zéro, variable non définie
 *
 *    502 → Passerelle invalide (proxy/load balancer KO)
 *
 *    503 → Service indisponible (maintenance, surcharge)
 * ```
 *
 * =============================================================================
 * UTILISATION DE L'ERRORCONTROLLER
 * =============================================================================
 *
 * ```php
 * // Dans le FrontController ou un middleware :
 *
 * // Erreur 404 (page non trouvée)
 * $errorController = new ErrorController();
 * return $errorController->index($request, 404);
 *
 * // Erreur 403 avec message personnalisé
 * return $errorController->index($request, 403, "Vous n'avez pas les droits");
 *
 * // Erreur 500 (erreur serveur)
 * return $errorController->index($request, 500, "Une erreur est survenue");
 * ```
 *
 * =============================================================================
 * PERSONNALISATION DU TEMPLATE D'ERREUR
 * =============================================================================
 *
 * Le template `templates/error.html` reçoit ces variables :
 *
 * ```html
 * <!-- templates/error.html -->
 * <html>
 * <head>
 *     <title>{{ title }}</title>
 * </head>
 * <body>
 *     <h1>Erreur {{ errorCode }}</h1>
 *     <p>{{ errorMessage }}</p>
 *
 *     <a href="/">Retour à l'accueil</a>
 * </body>
 * </html>
 * ```
 *
 * ```
 * Variables disponibles :
 *
 * ┌──────────────┬─────────────────────────────────────────┐
 * │ Variable     │ Description                             │
 * ├──────────────┼─────────────────────────────────────────┤
 * │ title        │ "Error 404", "Error 500", etc.          │
 * │ errorCode    │ Le code HTTP (404, 500, etc.)           │
 * │ errorMessage │ Message descriptif de l'erreur          │
 * └──────────────┴─────────────────────────────────────────┘
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
 * @see \Lunar\Service\Core\Http\HttpStatus Les codes HTTP disponibles
 * @see \Lunar\Service\Core\FrontController Qui appelle ce contrôleur
 * @see \Lunar\Service\Core\BaseController La classe parente
 */
declare(strict_types=1);

namespace Lunar\Controller;

use Lunar\Attribute\Route;
use Lunar\Service\Core\BaseController;
use Lunar\Service\Core\Http\HttpStatus;
use Lunar\Service\Core\Http\Request;
use Lunar\Service\Core\Http\Response;

/**
 * Contrôleur pour l'affichage des pages d'erreur HTTP.
 *
 * Ce contrôleur génère des pages d'erreur jolies et informatives
 * au lieu des messages d'erreur bruts du navigateur.
 *
 * =============================================================================
 * POURQUOI UN CONTRÔLEUR DÉDIÉ AUX ERREURS ?
 * =============================================================================
 *
 * ```
 * AVANTAGES :
 *
 * 1. EXPÉRIENCE UTILISATEUR
 *    → Une page d'erreur bien conçue rassure l'utilisateur
 *    → Elle peut proposer des solutions (liens, recherche...)
 *
 * 2. COHÉRENCE VISUELLE
 *    → La page d'erreur utilise le même design que le reste du site
 *    → Logo, couleurs, navigation restent présents
 *
 * 3. CENTRALISATION
 *    → Toutes les erreurs passent par le même endroit
 *    → Facile de changer le style de toutes les erreurs d'un coup
 *
 * 4. LOGGING / MONITORING
 *    → On peut facilement ajouter du logging des erreurs
 *    → Statistiques sur les 404 les plus fréquentes
 * ```
 *
 * =============================================================================
 * EXEMPLES D'UTILISATION
 * =============================================================================
 *
 * ```php
 * // Utilisation directe (rarement nécessaire)
 * $errorController = new ErrorController();
 *
 * // Page 404 standard
 * $response = $errorController->index($request, 404);
 *
 * // Page 403 personnalisée
 * $response = $errorController->index(
 *     $request,
 *     403,
 *     "Vous devez être administrateur pour accéder à cette page"
 * );
 *
 * // Page 500 (erreur serveur)
 * $response = $errorController->index(
 *     $request,
 *     500,
 *     "Une erreur inattendue s'est produite"
 * );
 * ```
 *
 * @package Lunar\Controller
 */
class ErrorController extends BaseController
{
    /**
     * Affiche une page d'erreur HTTP personnalisée.
     *
     * Cette méthode génère une page d'erreur avec :
     * - Le code d'erreur HTTP approprié
     * - Un message explicatif pour l'utilisateur
     * - Un design cohérent avec le reste du site
     *
     * ==========================================================================
     * L'ATTRIBUT #[Route] EXPLIQUÉ
     * ==========================================================================
     *
     * ```php
     * #[Route('/error', methods: ['GET'], name: 'error')]
     * ```
     *
     * Cet attribut permet d'accéder directement à la page d'erreur via l'URL
     * `/error`. C'est utile pour :
     * - Tester le design de la page d'erreur
     * - Avoir une URL de fallback
     *
     * Mais en pratique, cette méthode est surtout appelée DIRECTEMENT
     * par le FrontController quand une erreur se produit, sans passer
     * par le routeur.
     *
     * ==========================================================================
     * PARAMÈTRES DÉTAILLÉS
     * ==========================================================================
     *
     * @param Request $request L'objet Request contenant les infos de la requête HTTP
     *
     *                         ```
     *                         Pourquoi on a besoin de la Request ?
     *
     *                         Même si on ne l'utilise pas toujours, avoir accès
     *                         à la requête permet de :
     *                         - Savoir quelle URL a causé l'erreur
     *                         - Adapter l'affichage (AJAX vs HTML)
     *                         - Logger des informations de debug
     *                         ```
     *
     * @param int $code Le code d'erreur HTTP (404, 500, etc.)
     *
     *                  ```
     *                  Valeur par défaut : 404 (Not Found)
     *
     *                  Le code détermine :
     *                  - Le titre affiché ("Error 404", "Error 500"...)
     *                  - Le statut HTTP de la réponse
     *                  - Le message par défaut si aucun n'est fourni
     *
     *                  Codes courants :
     *                  - 400 : Bad Request
     *                  - 401 : Unauthorized
     *                  - 403 : Forbidden
     *                  - 404 : Not Found (défaut)
     *                  - 405 : Method Not Allowed
     *                  - 500 : Internal Server Error
     *                  ```
     *
     * @param string|null $message Message d'erreur personnalisé (optionnel)
     *
     *                             ```
     *                             Si null, utilise le message par défaut de HttpStatus :
     *                             - 404 → "Not Found"
     *                             - 500 → "Internal Server Error"
     *                             - etc.
     *
     *                             Personnalisé :
     *                             - "L'utilisateur demandé n'existe pas"
     *                             - "Vous n'avez pas les droits nécessaires"
     *                             ```
     *
     * @return Response La réponse HTTP avec le HTML de la page d'erreur
     *                  et le code HTTP approprié
     *
     * ==========================================================================
     * FONCTIONNEMENT INTERNE
     * ==========================================================================
     *
     * ```
     * index($request, 404, "Page introuvable")
     *     │
     *     ▼
     * ┌─────────────────────────────────────────────────────┐
     * │ 1. Déterminer le message                            │
     * │    → Si $message est fourni : l'utiliser            │
     * │    → Sinon : HttpStatus::getDefaultMessage(404)     │
     * │                                                     │
     * │ 2. Rendre le template error.html                    │
     * │    → title: "Error 404"                             │
     * │    → errorCode: 404                                 │
     * │    → errorMessage: "Page introuvable"               │
     * │                                                     │
     * │ 3. Créer la Response                                │
     * │    → Corps : HTML généré                            │
     * │    → Code HTTP : 404                                │
     * └─────────────────────────────────────────────────────┘
     *     │
     *     ▼
     * Response(html, 404) → Envoyée au navigateur
     * ```
     */
    #[Route('/error', methods: ['GET'], name: 'error')]
    public function index(Request $request, int $code = HttpStatus::NOT_FOUND, ?string $message = null): Response
    {
        // Déterminer le message d'erreur à afficher
        // Si aucun message n'est fourni, utiliser le message par défaut du code HTTP
        $errorMessage = $message ?? HttpStatus::getDefaultMessage($code);

        // Rendre le template d'erreur avec les données
        $html = $this->render('error.html', [
            'title' => 'Error '.$code,       // Ex: "Error 404"
            'errorCode' => $code,            // Ex: 404
            'errorMessage' => $errorMessage, // Ex: "Not Found" ou message custom
        ]);

        // Retourner la réponse avec le code HTTP approprié
        // Important : le 2ème paramètre de Response est le code HTTP
        return new Response($html, $code);
    }
}
