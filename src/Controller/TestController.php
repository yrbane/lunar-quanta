<?php
/**
 * Lunar Quanta Framework - Contrôleur de Test.
 *
 * =============================================================================
 * QU'EST-CE QUE LE TESTCONTROLLER ?
 * =============================================================================
 *
 * Le TestController est un contrôleur UTILITAIRE destiné aux développeurs.
 * Il permet de :
 * - Tester le rendu des templates
 * - Vérifier la gestion des erreurs HTTP
 * - Tester les styles CSS
 * - Expérimenter des fonctionnalités
 *
 * ```
 * ⚠️ ATTENTION : EN PRODUCTION
 *
 *    Ce contrôleur ne devrait PAS être accessible en production !
 *    Il expose des fonctionnalités de debug qui peuvent être dangereuses.
 *
 *    COMMENT LE DÉSACTIVER :
 *    - Supprimer le fichier en production
 *    - Ou ajouter une vérification d'environnement :
 *
 *    if ($_ENV['APP_ENV'] === 'production') {
 *        throw new \RuntimeException('Not available in production');
 *    }
 * ```
 *
 * =============================================================================
 * POURQUOI UN CONTRÔLEUR DE TEST ?
 * =============================================================================
 *
 * ```
 * AVANTAGES POUR LE DÉVELOPPEMENT :
 *
 * 1. TESTER LES TEMPLATES
 *    → Vérifier que les templates se rendent correctement
 *    → Voir les variables disponibles
 *
 * 2. TESTER LA GESTION D'ERREURS
 *    → Vérifier que les erreurs 500 sont bien attrapées
 *    → Voir la page d'erreur générée
 *
 * 3. TESTER LE CSS
 *    → Pages dédiées pour visualiser les styles
 *    → Tester le responsive design
 *
 * 4. EXPÉRIMENTER
 *    → Tester de nouvelles fonctionnalités
 *    → Faire du prototypage rapide
 * ```
 *
 * =============================================================================
 * ROUTES DISPONIBLES
 * =============================================================================
 *
 * ```
 * ┌──────────────┬─────────────────────────────────────────────────────────┐
 * │ Route        │ Description                                             │
 * ├──────────────┼─────────────────────────────────────────────────────────┤
 * │ /test        │ Page de test basique (index)                            │
 * │ /test500     │ Simule une erreur 500 (Internal Server Error)           │
 * │ /testcss     │ Page pour tester les styles CSS                         │
 * │ /testflex    │ Page pour tester Flexbox                                │
 * └──────────────┴─────────────────────────────────────────────────────────┘
 * ```
 *
 * =============================================================================
 * SIMULATION D'ERREUR 500 : POURQUOI ?
 * =============================================================================
 *
 * ```
 * SCÉNARIO : On veut vérifier que les erreurs 500 sont bien gérées
 *
 *    1. L'utilisateur visite /test500
 *    2. Le contrôleur lance volontairement une exception
 *    3. Le FrontController attrape l'exception
 *    4. Une jolie page d'erreur 500 est affichée
 *
 *    ┌──────────────┐     GET /test500    ┌────────────────┐
 *    │  Navigateur  │────────────────────▶│ TestController │
 *    └──────────────┘                     │   test500()    │
 *                                         └───────┬────────┘
 *                                                 │
 *                                                 ▼ EXCEPTION !
 *                                         ┌────────────────┐
 *                                         │RuntimeException│
 *                                         │ "Intentional   │
 *                                         │  error..."     │
 *                                         └───────┬────────┘
 *                                                 │
 *                                                 ▼ Attrapée par
 *                                         ┌────────────────┐
 *                                         │FrontController │
 *                                         │ try/catch      │
 *                                         └───────┬────────┘
 *                                                 │
 *                                                 ▼ Affiche
 *                                         ┌────────────────┐
 *                                         │ ErrorController│
 *                                         │ index(500)     │
 *                                         └───────┬────────┘
 *                                                 │
 *    ┌──────────────┐                             │
 *    │  Navigateur  │◀────── Page erreur 500 ────┘
 *    │  (affiche)   │
 *    └──────────────┘
 * ```
 *
 * =============================================================================
 * BONNES PRATIQUES POUR LES TESTS
 * =============================================================================
 *
 * ```
 * 1. ENVIRONNEMENT DE DÉVELOPPEMENT UNIQUEMENT
 *    → Ne jamais exposer ces routes en production
 *    → Utiliser des variables d'environnement
 *
 * 2. NOMMAGE CLAIR
 *    → Les routes de test commencent par /test
 *    → Facile à identifier et à bloquer
 *
 * 3. PAS DE LOGIQUE MÉTIER
 *    → Ce contrôleur ne fait QUE du test
 *    → Pas de vraies opérations sur les données
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
 * @see \Lunar\Controller\ErrorController Gère les erreurs générées
 */
declare(strict_types=1);

namespace Lunar\Controller;

use Lunar\Attribute\Route;
use Lunar\Service\Core\BaseController;
use Lunar\Service\Core\Http\Request;
use Lunar\Service\Core\Http\Response;

/**
 * Contrôleur utilitaire pour le développement et les tests.
 *
 * Ce contrôleur fournit des routes de test pour :
 * - Vérifier le bon fonctionnement du rendu de templates
 * - Tester la gestion des erreurs HTTP
 * - Visualiser et tester les styles CSS
 *
 * =============================================================================
 * SÉCURITÉ
 * =============================================================================
 *
 * ```
 * ⚠️ CE CONTRÔLEUR NE DOIT PAS ÊTRE ACCESSIBLE EN PRODUCTION !
 *
 * Raisons :
 * - Il expose volontairement des erreurs (utile pour les attaquants)
 * - Il peut révéler des informations sur l'architecture
 * - Il n'a pas de protection d'authentification
 *
 * PROTECTION RECOMMANDÉE :
 *
 *    // Dans le constructeur ou les méthodes :
 *    if ($_ENV['APP_ENV'] === 'production') {
 *        throw new \RuntimeException('Route not available');
 *    }
 *
 *    // Ou via middleware :
 *    #[Route('/test', middlewares: [DevOnlyMiddleware::class])]
 * ```
 *
 * @package Lunar\Controller
 */
class TestController extends BaseController
{
    /**
     * Affiche la page de test principale.
     *
     * Cette action sert de point d'entrée pour les tests basiques.
     * Elle rend simplement un template de test.
     *
     * ==========================================================================
     * L'ATTRIBUT #[Route] EXPLIQUÉ
     * ==========================================================================
     *
     * ```php
     * #[Route(path: '/test', methods: ['GET'], name: 'test.index')]
     * ```
     *
     * Note : `path:` est explicite ici, mais optionnel car c'est le 1er argument.
     * Ces deux syntaxes sont équivalentes :
     * - `#[Route(path: '/test', ...)]`
     * - `#[Route('/test', ...)]`
     *
     * @param Request $request L'objet Request (non utilisé ici mais requis par convention)
     *
     * @return Response La réponse HTTP contenant le HTML du template de test
     */
    #[Route(path: '/test', methods: ['GET'], name: 'test.index')]
    public function index(Request $request): Response
    {
        // Rendre le template de test
        // TODO : implémenter la logique de l'action index
        $html = $this->render('test/index.html', []);

        return new Response($html);
    }

    /**
     * Simule une erreur HTTP 500 (Internal Server Error).
     *
     * Cette méthode lance VOLONTAIREMENT une exception pour :
     * - Tester que le système attrape bien les erreurs
     * - Vérifier l'affichage de la page d'erreur 500
     * - S'assurer que les erreurs ne révèlent pas d'infos sensibles
     *
     * ==========================================================================
     * COMMENT ÇA MARCHE ?
     * ==========================================================================
     *
     * ```
     * 1. L'utilisateur visite /test500
     * 2. Cette méthode est appelée
     * 3. throw new RuntimeException() lance une exception
     * 4. L'exception "remonte" jusqu'au FrontController
     * 5. Le FrontController l'attrape dans son try/catch
     * 6. Il appelle ErrorController::index($request, 500)
     * 7. Une jolie page d'erreur 500 est affichée
     *
     *    test500()
     *        │
     *        ▼
     *    throw RuntimeException ─────────────────┐
     *                                            │
     *    ┌───────────────────────────────────────┤
     *    │          FrontController              │
     *    │   try {                               │
     *    │       // ...dispatch()                │
     *    │   } catch (Throwable $e) { ◀──────────┘
     *    │       // Gérer l'erreur
     *    │       return $errorController->index(..., 500);
     *    │   }
     *    └───────────────────────────────────────┘
     * ```
     *
     * ==========================================================================
     * POURQUOI CETTE MÉTHODE ?
     * ==========================================================================
     *
     * ```
     * EN DÉVELOPPEMENT, on veut vérifier :
     *
     * ✓ Les erreurs ne cassent pas tout le site
     * ✓ Une page d'erreur propre est affichée
     * ✓ Les erreurs sont loggées correctement
     * ✓ Aucune info sensible n'est révélée (stack trace, chemins...)
     *
     * SANS CETTE MÉTHODE, il faudrait :
     * - Introduire un vrai bug dans le code (risqué)
     * - Ou ne jamais tester la gestion d'erreurs (mauvaise idée)
     * ```
     *
     * @param Request $request L'objet Request (non utilisé mais requis)
     *
     * @return Response Cette méthode ne retourne JAMAIS de Response
     *                  car elle lance toujours une exception
     *
     * @throws \RuntimeException Toujours lancée pour simuler une erreur serveur
     */
    #[Route('/test500', methods: ['GET'], name: 'test.500')]
    public function test500(Request $request): Response
    {
        // Lancer une exception pour simuler une erreur interne (500)
        // Cette exception sera attrapée par le FrontController
        // qui affichera une page d'erreur 500 appropriée
        throw new \RuntimeException('Intentional error to test HTTP 500 handling.');
    }

    /**
     * Affiche une page pour tester les styles CSS.
     *
     * Cette page contient divers éléments HTML pour visualiser
     * et tester le rendu des styles CSS du framework.
     *
     * ==========================================================================
     * UTILITÉ POUR LE DÉVELOPPEMENT
     * ==========================================================================
     *
     * ```
     * Une page de test CSS permet de :
     *
     * 1. VISUALISER TOUS LES COMPOSANTS
     *    → Boutons, formulaires, tableaux, alertes...
     *    → En un seul endroit
     *
     * 2. TESTER LE RESPONSIVE
     *    → Redimensionner la fenêtre
     *    → Vérifier sur différents appareils
     *
     * 3. DÉBUGGER LES STYLES
     *    → Identifier les problèmes CSS
     *    → Tester des corrections
     *
     * 4. DOCUMENTER LE DESIGN SYSTEM
     *    → Référence visuelle pour l'équipe
     *    → Exemples d'utilisation
     * ```
     *
     * @param Request $request L'objet Request (non utilisé)
     *
     * @return Response La page HTML avec les exemples CSS
     *
     * @throws \Exception Si le template n'existe pas
     */
    #[Route('/testcss', methods: ['GET'], name: 'test.css')]
    public function testcss(Request $request): Response
    {
        // Rendre le template de test CSS
        $html = $this->render('test/css.html', []);

        return new Response($html);
    }

    /**
     * Affiche une page pour tester le layout Flexbox.
     *
     * Flexbox est un système de mise en page CSS moderne qui permet
     * de créer des layouts flexibles et responsives.
     *
     * ==========================================================================
     * QU'EST-CE QUE FLEXBOX ?
     * ==========================================================================
     *
     * ```
     * Flexbox = "Flexible Box Layout"
     *
     * C'est un modèle de layout CSS qui permet de :
     * - Aligner des éléments facilement
     * - Distribuer l'espace disponible
     * - Réorganiser les éléments sans toucher au HTML
     *
     * EXEMPLE BASIQUE :
     *
     *    .container {
     *        display: flex;           // Activer Flexbox
     *        justify-content: center; // Centrer horizontalement
     *        align-items: center;     // Centrer verticalement
     *    }
     *
     * RÉSULTAT :
     *
     *    ┌────────────────────────────────────────┐
     *    │                                        │
     *    │              ┌──────────┐              │
     *    │              │ Élément  │              │
     *    │              │ centré ! │              │
     *    │              └──────────┘              │
     *    │                                        │
     *    └────────────────────────────────────────┘
     * ```
     *
     * ==========================================================================
     * POURQUOI UNE PAGE DE TEST FLEXBOX ?
     * ==========================================================================
     *
     * ```
     * FLEXBOX EST PUISSANT MAIS COMPLEXE :
     *
     * Propriétés du conteneur :
     * - display: flex
     * - flex-direction: row | column
     * - justify-content: flex-start | center | flex-end | space-between
     * - align-items: flex-start | center | flex-end | stretch
     * - flex-wrap: nowrap | wrap
     *
     * Propriétés des enfants :
     * - flex-grow: 0 | 1 | 2...
     * - flex-shrink: 0 | 1
     * - flex-basis: auto | 100px | 50%
     * - align-self: auto | flex-start | center | flex-end
     *
     * Une page de test permet de VISUALISER ces propriétés en action.
     * ```
     *
     * @param Request $request L'objet Request (non utilisé)
     *
     * @return Response La page HTML avec les exemples Flexbox
     */
    #[Route('/testflex', methods: ['GET'], name: 'test.flex')]
    public function testflex(Request $request): Response
    {
        // Rendre le template de test Flexbox
        $html = $this->render('test/flex.html', []);

        return new Response($html);
    }
}
