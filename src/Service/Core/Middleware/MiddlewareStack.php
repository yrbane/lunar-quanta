<?php
/**
 * Lunar Quanta Framework - Pile de Middlewares (Middleware Stack).
 *
 * =============================================================================
 * QU'EST-CE QU'UNE PILE DE MIDDLEWARES ?
 * =============================================================================
 *
 * Une PILE (Stack) de middlewares est une collection ordonnée de middlewares
 * qui s'exécutent en séquence pour traiter une requête HTTP.
 *
 * ANALOGIE : Une chaîne de montage
 *
 * ```
 *  Pièce brute (Requête)
 *      │
 *      ▼
 *  ┌───────────────┐
 *  │ Station 1     │  ← Middleware 1 (SessionMiddleware)
 *  │ Peinture      │     "Initialise la session"
 *  └───────────────┘
 *      │
 *      ▼
 *  ┌───────────────┐
 *  │ Station 2     │  ← Middleware 2 (AuthMiddleware)
 *  │ Assemblage    │     "Vérifie l'authentification"
 *  └───────────────┘
 *      │
 *      ▼
 *  ┌───────────────┐
 *  │ Station 3     │  ← Middleware 3 (CsrfMiddleware)
 *  │ Contrôle      │     "Vérifie le token CSRF"
 *  └───────────────┘
 *      │
 *      ▼
 *  ┌───────────────┐
 *  │ Destination   │  ← Contrôleur (handler final)
 *  │ Finale        │     "Traite la requête"
 *  └───────────────┘
 *      │
 *      ▼
 *  Produit fini (Réponse)
 * ```
 *
 * =============================================================================
 * ORDRE D'EXÉCUTION : FIFO (First In, First Out)
 * =============================================================================
 *
 * Les middlewares sont exécutés dans l'ORDRE où ils ont été ajoutés.
 *
 * FIFO signifie "Premier Entré, Premier Sorti" :
 * - Le premier middleware ajouté est le premier exécuté
 * - Le dernier middleware ajouté est le dernier exécuté (juste avant le contrôleur)
 *
 * ```php
 * $stack = new MiddlewareStack();
 * $stack->add(new SessionMiddleware());  // Exécuté en 1er
 * $stack->add(new AuthMiddleware());     // Exécuté en 2ème
 * $stack->add(new CsrfMiddleware());     // Exécuté en 3ème
 *
 * // Ordre d'exécution de la REQUÊTE :
 * // Session → Auth → CSRF → Contrôleur
 *
 * // Ordre de retour de la RÉPONSE :
 * // Contrôleur → CSRF → Auth → Session
 * ```
 *
 * =============================================================================
 * PATTERN "MATRYOSHKA" (Poupées russes)
 * =============================================================================
 *
 * L'implémentation utilise le pattern des poupées russes (Matryoshka) :
 * chaque middleware "enveloppe" le suivant.
 *
 * ```
 *  ┌──────────────────────────────────────────────────────────────────────────┐
 *  │  SessionMiddleware                                                       │
 *  │  ┌────────────────────────────────────────────────────────────────────┐  │
 *  │  │  AuthMiddleware                                                    │  │
 *  │  │  ┌──────────────────────────────────────────────────────────────┐  │  │
 *  │  │  │  CsrfMiddleware                                              │  │  │
 *  │  │  │  ┌────────────────────────────────────────────────────────┐  │  │  │
 *  │  │  │  │  Contrôleur                                            │  │  │  │
 *  │  │  │  │  (handler final)                                       │  │  │  │
 *  │  │  │  └────────────────────────────────────────────────────────┘  │  │  │
 *  │  │  └──────────────────────────────────────────────────────────────┘  │  │
 *  │  └────────────────────────────────────────────────────────────────────┘  │
 *  └──────────────────────────────────────────────────────────────────────────┘
 * ```
 *
 * La requête entre par l'extérieur et traverse chaque couche vers le centre.
 * La réponse sort du centre et traverse chaque couche vers l'extérieur.
 *
 * =============================================================================
 * CONSTRUCTION DE LA CHAÎNE
 * =============================================================================
 *
 * La méthode handle() utilise array_reduce() pour construire la chaîne
 * de l'INTÉRIEUR vers l'EXTÉRIEUR :
 *
 * 1. On commence avec le handler final (le contrôleur)
 * 2. On l'enveloppe avec le dernier middleware
 * 3. On enveloppe le résultat avec l'avant-dernier middleware
 * 4. Et ainsi de suite jusqu'au premier middleware
 *
 * ```
 *  array_reduce avec middlewares [A, B, C] et handler final F :
 *
 *  Étape 1 : F (handler final)
 *  Étape 2 : C(F)     → C enveloppe F
 *  Étape 3 : B(C(F))  → B enveloppe C
 *  Étape 4 : A(B(C(F))) → A enveloppe B
 *
 *  Résultat : A → B → C → F
 * ```
 *
 * @package    Lunar\Service\Core\Middleware
 * @author     Seb <seb@nethttp.net>
 * @copyright  2024 Lunar Quanta
 * @license    MIT License
 * @version    1.1.0
 * @link       https://nethttp.net
 * @since      1.1.0
 *
 * @see MiddlewareInterface Interface que chaque middleware doit implémenter
 * @see Request L'objet représentant la requête HTTP
 * @see Response L'objet représentant la réponse HTTP
 */
declare(strict_types=1);

namespace Lunar\Service\Core\Middleware;

use Lunar\Service\Core\Http\Request;
use Lunar\Service\Core\Http\Response;

/**
 * Pile de middlewares pour le traitement des requêtes HTTP.
 *
 * Cette classe gère une collection ordonnée de middlewares et les exécute
 * en séquence pour traiter les requêtes entrantes.
 *
 * =============================================================================
 * UTILISATION BASIQUE
 * =============================================================================
 *
 * ```php
 * // Création de la pile
 * $stack = new MiddlewareStack();
 *
 * // Ajout des middlewares (dans l'ordre d'exécution)
 * $stack->add(new SessionMiddleware());
 * $stack->add(new AuthMiddleware());
 * $stack->add(new CsrfMiddleware());
 *
 * // Définition du handler final (généralement le contrôleur)
 * $finalHandler = function (Request $request): Response {
 *     return $controller->action($request);
 * };
 *
 * // Exécution de la chaîne
 * $response = $stack->handle($request, $finalHandler);
 * ```
 *
 * =============================================================================
 * AVEC CHAÎNAGE DE MÉTHODES (Fluent Interface)
 * =============================================================================
 *
 * ```php
 * $response = (new MiddlewareStack())
 *     ->add(new SessionMiddleware())
 *     ->add(new AuthMiddleware())
 *     ->add(new CsrfMiddleware())
 *     ->handle($request, $finalHandler);
 * ```
 *
 * =============================================================================
 * AVEC addMany() POUR AJOUTER PLUSIEURS MIDDLEWARES
 * =============================================================================
 *
 * ```php
 * $middlewares = [
 *     new SessionMiddleware(),
 *     new AuthMiddleware(),
 *     new CsrfMiddleware(),
 * ];
 *
 * $response = (new MiddlewareStack())
 *     ->addMany($middlewares)
 *     ->handle($request, $finalHandler);
 * ```
 *
 * @package Lunar\Service\Core\Middleware
 */
class MiddlewareStack
{
    /**
     * Collection des middlewares de la pile.
     *
     * =========================================================================
     * STRUCTURE DE STOCKAGE
     * =========================================================================
     *
     * Les middlewares sont stockés dans un tableau indexé, dans l'ordre
     * où ils ont été ajoutés. Cet ordre détermine l'ordre d'exécution.
     *
     * ```php
     * // Après plusieurs add() :
     * $this->middlewares = [
     *     0 => SessionMiddleware,
     *     1 => AuthMiddleware,
     *     2 => CsrfMiddleware,
     * ];
     *
     * // Ordre d'exécution de la requête : 0 → 1 → 2 → handler
     * // Ordre de retour de la réponse : handler → 2 → 1 → 0
     * ```
     *
     * @var MiddlewareInterface[] Tableau indexé des middlewares
     */
    private array $middlewares = [];

    /**
     * Ajoute un middleware à la pile.
     *
     * =========================================================================
     * FLUENT INTERFACE (Interface fluide)
     * =========================================================================
     *
     * Cette méthode retourne $this (l'objet lui-même), ce qui permet de
     * CHAÎNER les appels de méthodes. C'est le pattern "Fluent Interface".
     *
     * ```php
     * // Sans fluent interface (verbeux)
     * $stack = new MiddlewareStack();
     * $stack->add(new MiddlewareA());
     * $stack->add(new MiddlewareB());
     * $stack->add(new MiddlewareC());
     *
     * // Avec fluent interface (élégant)
     * $stack = (new MiddlewareStack())
     *     ->add(new MiddlewareA())
     *     ->add(new MiddlewareB())
     *     ->add(new MiddlewareC());
     * ```
     *
     * QU'EST-CE QUE "return $this" ?
     * ------------------------------
     * $this fait référence à l'instance actuelle de l'objet.
     * Retourner $this permet d'appeler immédiatement une autre méthode
     * sur le même objet.
     *
     * @param MiddlewareInterface $middleware Le middleware à ajouter.
     *                                        Doit implémenter MiddlewareInterface.
     *
     * @return $this Retourne l'instance pour permettre le chaînage.
     *
     * @example
     * ```php
     * $stack = new MiddlewareStack();
     *
     * // Ajout simple
     * $stack->add(new AuthMiddleware());
     *
     * // Avec chaînage
     * $stack->add(new MiddlewareA())
     *       ->add(new MiddlewareB())
     *       ->add(new MiddlewareC());
     * ```
     */
    public function add(MiddlewareInterface $middleware): self
    {
        // Ajoute le middleware à la fin du tableau
        $this->middlewares[] = $middleware;

        // Retourne $this pour le chaînage
        return $this;
    }

    /**
     * Ajoute plusieurs middlewares à la pile d'un coup.
     *
     * =========================================================================
     * UTILISATION
     * =========================================================================
     *
     * Cette méthode est pratique quand vous avez déjà un tableau de
     * middlewares à ajouter, par exemple depuis une configuration.
     *
     * ```php
     * // Configuration des middlewares globaux
     * $globalMiddlewares = [
     *     new SessionMiddleware(),
     *     new CsrfMiddleware(),
     * ];
     *
     * // Middlewares spécifiques à une route
     * $routeMiddlewares = [
     *     new AuthMiddleware(),
     *     new RoleMiddleware('admin'),
     * ];
     *
     * // Ajout de tous les middlewares
     * $stack = (new MiddlewareStack())
     *     ->addMany($globalMiddlewares)
     *     ->addMany($routeMiddlewares);
     * ```
     *
     * @param MiddlewareInterface[] $middlewares Tableau de middlewares à ajouter.
     *                                           Chaque élément doit implémenter
     *                                           MiddlewareInterface.
     *
     * @return $this Retourne l'instance pour permettre le chaînage.
     */
    public function addMany(array $middlewares): self
    {
        // Ajoute chaque middleware individuellement
        foreach ($middlewares as $middleware) {
            $this->add($middleware);
        }

        // Retourne $this pour le chaînage
        return $this;
    }

    /**
     * Exécute la pile de middlewares avec la requête donnée.
     *
     * =========================================================================
     * ALGORITHME DE CONSTRUCTION DE LA CHAÎNE
     * =========================================================================
     *
     * Cette méthode construit une chaîne de fonctions imbriquées
     * en utilisant array_reduce().
     *
     * QU'EST-CE QUE array_reduce() ?
     * ------------------------------
     * array_reduce() applique une fonction de réduction à chaque élément
     * d'un tableau pour le "réduire" à une seule valeur.
     *
     * ```php
     * // Exemple simple : somme des nombres
     * $numbers = [1, 2, 3, 4];
     * $sum = array_reduce($numbers, fn($carry, $n) => $carry + $n, 0);
     * // $sum = 10
     *
     * // Étapes :
     * // 1. carry=0, n=1 → 0+1=1
     * // 2. carry=1, n=2 → 1+2=3
     * // 3. carry=3, n=3 → 3+3=6
     * // 4. carry=6, n=4 → 6+4=10
     * ```
     *
     * Ici, on l'utilise pour construire une chaîne de fonctions :
     *
     * ```
     *  Middlewares: [A, B, C]
     *  Handler final: F
     *
     *  array_reverse → [C, B, A]
     *
     *  array_reduce :
     *  1. carry=F,      middleware=C → C wraps F      → C(F)
     *  2. carry=C(F),   middleware=B → B wraps C(F)   → B(C(F))
     *  3. carry=B(C(F)), middleware=A → A wraps B(C(F)) → A(B(C(F)))
     *
     *  Résultat final : A(B(C(F)))
     *
     *  Quand on appelle cette fonction avec $request :
     *  A.process($request) → B.process($request) → C.process($request) → F($request)
     * ```
     *
     * QU'EST-CE QUE "fn() =>" ?
     * -------------------------
     * C'est une ARROW FUNCTION (fonction fléchée), introduite en PHP 7.4.
     * C'est une syntaxe courte pour les closures simples.
     *
     * ```php
     * // Closure classique
     * function ($x) { return $x * 2; }
     *
     * // Arrow function (équivalent)
     * fn($x) => $x * 2
     *
     * // Les arrow functions capturent automatiquement les variables
     * // du scope parent (pas besoin de "use")
     * ```
     *
     * @param Request $request La requête HTTP entrante à traiter.
     *
     * @param callable(Request): Response $finalHandler Le handler final.
     *                                                  C'est généralement
     *                                                  le contrôleur qui traite
     *                                                  la requête.
     *
     * @return Response La réponse HTTP générée par la chaîne.
     *
     * @example Utilisation complète
     * ```php
     * $stack = new MiddlewareStack();
     * $stack->add(new SessionMiddleware());
     * $stack->add(new AuthMiddleware());
     *
     * $finalHandler = function (Request $request): Response {
     *     // Logique du contrôleur
     *     return new Response('Hello World');
     * };
     *
     * $response = $stack->handle($request, $finalHandler);
     * // La requête passe par : Session → Auth → finalHandler
     * // La réponse revient par : finalHandler → Auth → Session
     * ```
     *
     * @example Sans middlewares
     * ```php
     * $stack = new MiddlewareStack();  // Pile vide
     *
     * $response = $stack->handle($request, fn($req) => new Response('OK'));
     * // Le finalHandler est appelé directement
     * ```
     */
    public function handle(Request $request, callable $finalHandler): Response
    {
        // =====================================================================
        // Construction de la chaîne de handlers
        // =====================================================================
        //
        // On construit la chaîne de l'intérieur vers l'extérieur.
        // Pour que le premier middleware ajouté soit exécuté en premier,
        // on inverse l'ordre des middlewares avant de les réduire.
        //
        // Exemple avec middlewares [A, B, C] :
        // - array_reverse → [C, B, A]
        // - reduce : F → C(F) → B(C(F)) → A(B(C(F)))
        // - Exécution : A → B → C → F
        $handler = array_reduce(
            // Inverse l'ordre : le dernier ajouté enveloppe le handler final
            array_reverse($this->middlewares),

            // Fonction de réduction : enveloppe le handler courant avec le middleware
            // $next = le handler courant (accumulé)
            // $middleware = le middleware à appliquer
            // Retourne un nouveau callable qui appelle $middleware->process()
            fn(callable $next, MiddlewareInterface $middleware): callable =>
                fn(Request $req): Response => $middleware->process($req, $next),

            // Valeur initiale : le handler final (contrôleur)
            $finalHandler
        );

        // =====================================================================
        // Exécution de la chaîne
        // =====================================================================
        // On appelle le handler avec la requête.
        // Ça déclenche la cascade : middleware 1 → middleware 2 → ... → finalHandler
        return $handler($request);
    }
}
