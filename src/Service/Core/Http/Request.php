<?php
/**
 * Lunar Quanta Framework - Composant Request HTTP.
 *
 * =============================================================================
 * GUIDE POUR DÉBUTANTS : COMPRENDRE LES REQUÊTES HTTP
 * =============================================================================
 *
 * Ce fichier est un excellent point de départ pour comprendre le fonctionnement
 * du web. Prenez le temps de lire ces explications, elles vous seront utiles
 * tout au long de votre carrière de développeur.
 *
 * -----------------------------------------------------------------------------
 * QU'EST-CE QUE HTTP ?
 * -----------------------------------------------------------------------------
 *
 * HTTP (HyperText Transfer Protocol) est le "langage" utilisé par les navigateurs
 * web (Chrome, Firefox, Safari) pour communiquer avec les serveurs web.
 *
 * Imaginez HTTP comme une conversation par courrier postal :
 *
 * 1. VOUS (le navigateur) écrivez une LETTRE (requête HTTP) pour demander quelque chose
 * 2. LA POSTE (Internet) transporte votre lettre jusqu'au DESTINATAIRE (serveur web)
 * 3. LE DESTINATAIRE lit votre lettre, prépare ce que vous avez demandé
 * 4. LE DESTINATAIRE vous envoie une RÉPONSE (réponse HTTP) avec ce que vous vouliez
 *
 * Concrètement, quand vous tapez "https://google.com" dans votre navigateur :
 *
 * ```
 * ┌──────────────┐                                    ┌──────────────┐
 * │  NAVIGATEUR  │                                    │   SERVEUR    │
 * │   (Chrome)   │                                    │   (Google)   │
 * └──────┬───────┘                                    └──────┬───────┘
 *        │                                                   │
 *        │  ──── REQUÊTE HTTP ────────────────────────────▶  │
 *        │  "Bonjour, je voudrais la page d'accueil SVP"     │
 *        │                                                   │
 *        │                                                   │ Le serveur
 *        │                                                   │ prépare la page
 *        │                                                   │
 *        │  ◀──────────────────────── RÉPONSE HTTP ────────  │
 *        │  "Voici le HTML de la page d'accueil Google"      │
 *        │                                                   │
 *        ▼                                                   ▼
 *   Le navigateur                                      Le serveur attend
 *   affiche la page                                    d'autres requêtes
 * ```
 *
 * ANATOMIE D'UNE REQUÊTE HTTP :
 *
 * Une requête HTTP contient plusieurs parties :
 *
 * ```
 * GET /search?q=php HTTP/1.1        ← Ligne de requête (méthode + URL + version)
 * Host: www.google.com              ← En-têtes (headers) = métadonnées
 * User-Agent: Mozilla/5.0...        ← Quel navigateur envoie la requête
 * Accept: text/html                 ← Quel type de réponse on accepte
 * Accept-Language: fr-FR            ← Quelle langue on préfère
 *                                   ← Ligne vide = fin des headers
 * username=john&password=123        ← Corps (body) = données envoyées (optionnel)
 * ```
 *
 * -----------------------------------------------------------------------------
 * LES MÉTHODES HTTP (ou "verbes HTTP")
 * -----------------------------------------------------------------------------
 *
 * La MÉTHODE HTTP indique ce que vous voulez FAIRE. C'est comme un verbe
 * dans une phrase : "DONNE-moi cette page" ou "SUPPRIME ce fichier".
 *
 * Méthodes les plus courantes :
 *
 * GET  = "Donne-moi quelque chose" (récupérer une page, une image, des données)
 *        Exemple : Afficher un article de blog
 *        C'est la méthode par défaut quand vous cliquez sur un lien
 *
 * POST = "Voici des données, traite-les" (envoyer un formulaire, créer quelque chose)
 *        Exemple : Soumettre un formulaire de contact, créer un compte
 *        Les données sont envoyées dans le corps de la requête (invisibles dans l'URL)
 *
 * PUT  = "Remplace cette ressource par ce que je t'envoie"
 *        Exemple : Mettre à jour complètement un profil utilisateur
 *
 * PATCH = "Modifie juste cette partie de la ressource"
 *        Exemple : Changer uniquement l'email d'un utilisateur
 *
 * DELETE = "Supprime cette ressource"
 *        Exemple : Supprimer un commentaire
 *
 * -----------------------------------------------------------------------------
 * QU'EST-CE QUE PHP ?
 * -----------------------------------------------------------------------------
 *
 * PHP est un langage de programmation qui s'exécute SUR LE SERVEUR (pas dans
 * le navigateur). Quand une requête HTTP arrive au serveur, PHP peut :
 * - Lire les données de la requête
 * - Consulter une base de données
 * - Faire des calculs
 * - Générer du HTML
 * - Renvoyer une réponse au navigateur
 *
 * -----------------------------------------------------------------------------
 * LES SUPERGLOBALES PHP
 * -----------------------------------------------------------------------------
 *
 * Les "superglobales" sont des variables spéciales que PHP crée AUTOMATIQUEMENT
 * pour vous donner accès aux données de la requête HTTP.
 *
 * Ce sont des TABLEAUX (arrays) prédéfinis, accessibles partout dans votre code.
 * Le "$" au début indique que c'est une variable en PHP.
 * Le "_" au début indique que c'est une variable spéciale de PHP.
 *
 * Voici les principales superglobales :
 *
 * $_GET :
 *   Contient les paramètres de l'URL (après le "?")
 *   URL : /search?q=php&page=2
 *   $_GET = ['q' => 'php', 'page' => '2']
 *
 * $_POST :
 *   Contient les données envoyées par un formulaire
 *   Si un formulaire envoie username=john et password=secret
 *   $_POST = ['username' => 'john', 'password' => 'secret']
 *
 * $_SERVER :
 *   Contient des informations sur le serveur et la requête
 *   $_SERVER['REQUEST_METHOD'] = 'GET' ou 'POST'
 *   $_SERVER['REQUEST_URI'] = '/page/demandee'
 *   $_SERVER['REMOTE_ADDR'] = Adresse IP du visiteur
 *
 * $_COOKIE :
 *   Contient les cookies envoyés par le navigateur
 *
 * $_SESSION :
 *   Contient les données de session (maintenues entre les pages)
 *
 * POURQUOI NE PAS UTILISER LES SUPERGLOBALES DIRECTEMENT ?
 *
 * Dans un vrai projet, on évite d'utiliser $_GET, $_POST directement car :
 * 1. C'est difficile à tester automatiquement
 * 2. Les données sont dispersées partout dans le code
 * 3. On ne peut pas facilement ajouter des validations
 *
 * La solution : ENCAPSULER ces données dans une CLASSE (cette classe Request).
 *
 * -----------------------------------------------------------------------------
 * QU'EST-CE QUE LA PROGRAMMATION ORIENTÉE OBJET (POO) ?
 * -----------------------------------------------------------------------------
 *
 * La POO est une façon d'organiser son code en regroupant les DONNÉES et les
 * ACTIONS qui vont ensemble dans des "boîtes" appelées OBJETS.
 *
 * VOCABULAIRE DE BASE :
 *
 * CLASSE = Le "plan" ou "moule" pour créer des objets
 *          Comme un plan d'architecte pour construire des maisons
 *          Exemple : La classe "Request" décrit ce qu'est une requête
 *
 * OBJET = Une "instance" créée à partir d'une classe
 *         Comme une vraie maison construite à partir du plan
 *         Exemple : new Request() crée un objet requête
 *
 * PROPRIÉTÉ = Une variable à l'intérieur d'un objet (ses données)
 *             Exemple : $this->method contient la méthode HTTP
 *
 * MÉTHODE = Une fonction à l'intérieur d'un objet (ses actions)
 *           Exemple : $request->getMethod() retourne la méthode
 *
 * ENCAPSULATION = Cacher les détails internes d'un objet
 *                 On utilise des méthodes publiques pour accéder aux données
 *                 au lieu d'accéder directement aux propriétés
 *
 * VISIBILITÉ :
 *   private   = Accessible uniquement dans cette classe (caché)
 *   protected = Accessible dans cette classe et ses enfants
 *   public    = Accessible par tout le monde
 *
 * Exemple concret :
 *
 * ```php
 * // Sans POO (mauvais)
 * $method = $_SERVER['REQUEST_METHOD'];
 * $uri = $_SERVER['REQUEST_URI'];
 * // ... ces données sont éparpillées partout
 *
 * // Avec POO (bon)
 * $request = new Request();
 * $method = $request->getMethod();
 * $uri = $request->getUri();
 * // ... tout est regroupé dans un objet propre
 * ```
 *
 * -----------------------------------------------------------------------------
 * POURQUOI CETTE CLASSE REQUEST ?
 * -----------------------------------------------------------------------------
 *
 * Cette classe "emballe" (encapsule) toutes les données de la requête HTTP
 * dans un seul objet facile à manipuler.
 *
 * Au lieu de :
 *   - Accéder à $_GET['page']
 *   - Accéder à $_POST['username']
 *   - Accéder à $_SERVER['REQUEST_METHOD']
 *
 * On utilise :
 *   - $request->getQueryParams()['page']
 *   - $request->getPostParams()['username']
 *   - $request->getMethod()
 *
 * AVANTAGES :
 * 1. CODE PLUS LISIBLE : $request->getMethod() est plus clair que $_SERVER['REQUEST_METHOD']
 * 2. TESTABILITÉ : On peut créer de "fausses" requêtes pour les tests
 * 3. MAINTENABILITÉ : Si on veut changer comment on lit les données, on change qu'ici
 * 4. SÉCURITÉ : On peut ajouter des validations dans les méthodes
 *
 * @package    Lunar\Service\Core\Http
 * @author     Seb <seb@nethttp.net>
 * @copyright  2024 Lunar Quanta
 * @license    MIT License
 * @version    1.1.0
 * @link       https://nethttp.net
 * @since      0.0.1
 * @see        Response La classe qui représente la réponse HTTP (ce qu'on renvoie)
 * @see        Router   Le routeur qui "aiguille" les requêtes vers le bon contrôleur
 */
declare(strict_types=1);

namespace Lunar\Service\Core\Http;

/**
 * Classe Request - Représentation objet d'une requête HTTP.
 *
 * Cette classe est le "conteneur" de toutes les informations envoyées par
 * le navigateur au serveur. Elle lit les superglobales PHP ($_GET, $_POST,
 * $_SERVER) et les stocke dans des propriétés bien organisées.
 *
 * =============================================================================
 * COMMENT UTILISER CETTE CLASSE ?
 * =============================================================================
 *
 * Vous n'avez généralement pas besoin de créer l'objet Request vous-même.
 * Le framework le crée automatiquement et vous le passe dans vos contrôleurs :
 *
 * ```php
 * // Dans un contrôleur, la Request est passée automatiquement
 * class MonControleur
 * {
 *     #[Route('/ma-page', methods: ['GET', 'POST'])]
 *     public function maMethode(Request $request): Response
 *     {
 *         // Récupérer la méthode HTTP (GET ou POST ?)
 *         $method = $request->getMethod();
 *
 *         if ($method === 'POST') {
 *             // L'utilisateur a soumis un formulaire
 *             $donnees = $request->getPostParams();
 *             $nom = $donnees['nom'] ?? '';  // ?? '' = valeur par défaut si absent
 *         }
 *
 *         // Récupérer un paramètre de l'URL (?page=2)
 *         $page = $request->getQueryParams()['page'] ?? 1;
 *
 *         // Récupérer l'utilisateur connecté (si authentifié)
 *         $user = $request->getAttribute('user');
 *
 *         return new Response('Ma réponse HTML');
 *     }
 * }
 * ```
 *
 * =============================================================================
 * LES ATTRIBUTS DE REQUÊTE - UN CONCEPT IMPORTANT
 * =============================================================================
 *
 * Les "attributs" sont des données AJOUTÉES à la requête pendant son traitement.
 *
 * DIFFÉRENCE avec les paramètres GET/POST :
 * - GET/POST : viennent du NAVIGATEUR (l'utilisateur les envoie)
 * - Attributs : ajoutés par le SERVEUR (votre code les ajoute)
 *
 * À QUOI ÇA SERT ?
 *
 * Imaginez une chaîne de montage dans une usine. La requête est comme une
 * voiture qui passe sur le tapis roulant, et chaque "middleware" (ouvrier)
 * peut ajouter quelque chose à cette voiture.
 *
 * ```
 * Requête HTTP ───▶ [Session Middleware] ───▶ [Auth Middleware] ───▶ [Contrôleur]
 *                          │                        │                     │
 *                   Ajoute 'session'         Ajoute 'user'          Utilise tout
 * ```
 *
 * Le SessionMiddleware ajoute l'objet session à la requête.
 * L'AuthMiddleware ajoute l'utilisateur connecté à la requête.
 * Le contrôleur peut ensuite récupérer ces informations.
 *
 * ```php
 * // Dans un middleware
 * $request->setAttribute('user', $utilisateurConnecté);
 *
 * // Dans un contrôleur
 * $user = $request->getAttribute('user');
 * ```
 *
 * @package Lunar\Service\Core\Http
 */
class Request
{
    /**
     * La méthode HTTP de la requête (GET, POST, PUT, DELETE, etc.).
     *
     * =========================================================================
     * C'EST QUOI LA MÉTHODE HTTP ?
     * =========================================================================
     *
     * La méthode HTTP est un "verbe" qui indique l'INTENTION de la requête.
     * C'est comme dire "je veux LIRE cette page" ou "je veux CRÉER un compte".
     *
     * TABLEAU DES MÉTHODES HTTP :
     *
     * ┌─────────┬────────────────────────────────────┬─────────────────────────┐
     * │ Méthode │ Signification                      │ Exemple concret         │
     * ├─────────┼────────────────────────────────────┼─────────────────────────┤
     * │ GET     │ "Donne-moi cette ressource"        │ Afficher une page       │
     * │         │ Ne modifie JAMAIS les données      │ Voir la liste des users │
     * ├─────────┼────────────────────────────────────┼─────────────────────────┤
     * │ POST    │ "Crée quelque chose de nouveau"    │ Soumettre un formulaire │
     * │         │ Envoie des données au serveur      │ Créer un nouveau compte │
     * ├─────────┼────────────────────────────────────┼─────────────────────────┤
     * │ PUT     │ "Remplace complètement cette       │ Modifier tout un profil │
     * │         │  ressource par ce que j'envoie"    │                         │
     * ├─────────┼────────────────────────────────────┼─────────────────────────┤
     * │ PATCH   │ "Modifie juste une partie"         │ Changer juste l'email   │
     * ├─────────┼────────────────────────────────────┼─────────────────────────┤
     * │ DELETE  │ "Supprime cette ressource"         │ Supprimer un article    │
     * ├─────────┼────────────────────────────────────┼─────────────────────────┤
     * │ HEAD    │ "Comme GET mais sans le contenu"   │ Vérifier si page existe │
     * ├─────────┼────────────────────────────────────┼─────────────────────────┤
     * │ OPTIONS │ "Quelles méthodes sont permises ?" │ Requêtes CORS           │
     * └─────────┴────────────────────────────────────┴─────────────────────────┘
     *
     * MÉTHODES "SAFE" (sûres) vs "UNSAFE" (non sûres) :
     *
     * Safe (ne changent rien sur le serveur) : GET, HEAD, OPTIONS
     * Unsafe (peuvent changer des données)   : POST, PUT, PATCH, DELETE
     *
     * Règle d'or : Une requête GET ne doit JAMAIS modifier des données !
     * Sinon, un simple clic sur un lien pourrait supprimer des données.
     *
     * DANS UN FORMULAIRE HTML :
     *
     * ```html
     * <!-- Méthode GET (données dans l'URL) -->
     * <form method="GET" action="/search">
     *     <input name="q" value="php">
     *     <!-- Résultat : /search?q=php -->
     * </form>
     *
     * <!-- Méthode POST (données dans le corps, invisibles) -->
     * <form method="POST" action="/login">
     *     <input name="username" value="john">
     *     <input name="password" value="secret">
     *     <!-- Les données ne sont PAS dans l'URL -->
     * </form>
     * ```
     *
     * @var string La méthode HTTP en MAJUSCULES ("GET", "POST", etc.)
     */
    private string $method;

    /**
     * Le chemin URI de la requête (la partie après le nom de domaine, sans les paramètres).
     *
     * =========================================================================
     * C'EST QUOI UNE URI ?
     * =========================================================================
     *
     * URI = Uniform Resource Identifier = "Identifiant Uniforme de Ressource"
     *
     * C'est l'adresse qui identifie une ressource spécifique sur le serveur.
     * Dans le contexte web, c'est la partie de l'URL qui vient après le
     * nom de domaine.
     *
     * DÉCOMPOSITION D'UNE URL COMPLÈTE :
     *
     * ```
     * https://www.example.com:443/blog/article?id=123&lang=fr#comments
     * └──┬──┘ └───────┬───────┘└┬┘└─────┬─────┘└──────┬──────┘└───┬───┘
     *    │           │         │       │             │           │
     * Protocole    Hôte       Port    CHEMIN     Paramètres   Fragment
     * (scheme)    (host)            (= notre URI)  (query)    (anchor)
     *
     * Cette propriété contient : "/blog/article"
     * ```
     *
     * POURQUOI SÉPARER LE CHEMIN DES PARAMÈTRES ?
     *
     * Le CHEMIN (/blog/article) détermine QUELLE ressource vous voulez.
     * Les PARAMÈTRES (?id=123) précisent COMMENT vous la voulez.
     *
     * Le routeur utilise le chemin pour trouver le bon contrôleur.
     * Le contrôleur utilise les paramètres pour personnaliser la réponse.
     *
     * EXEMPLES DE VALEURS :
     *
     * URL tapée                          → $uri contient
     * ─────────────────────────────────────────────────────
     * https://monsite.com/               → "/"
     * https://monsite.com/contact        → "/contact"
     * https://monsite.com/users/profile  → "/users/profile"
     * https://monsite.com/search?q=test  → "/search" (sans ?q=test)
     *
     * @var string Le chemin URI, commence toujours par "/"
     */
    private string $uri;

    /**
     * Les paramètres de la "query string" (équivalent de la superglobale $_GET).
     *
     * =========================================================================
     * C'EST QUOI LA QUERY STRING ?
     * =========================================================================
     *
     * La "query string" (chaîne de requête) est la partie de l'URL qui vient
     * APRÈS le point d'interrogation "?". Elle contient des paramètres
     * sous forme de paires clé=valeur, séparées par "&".
     *
     * EXEMPLE CONCRET :
     *
     * URL : https://google.com/search?q=chat+mignon&tbm=isch&safe=active
     *                                 └─────────────────────────────────┘
     *                                      Ceci est la query string
     *
     * PHP transforme automatiquement cette chaîne en tableau :
     *
     * ```php
     * $_GET = [
     *     'q'    => 'chat mignon',  // Le + devient un espace
     *     'tbm'  => 'isch',         // Type de recherche (images)
     *     'safe' => 'active'        // SafeSearch activé
     * ];
     * ```
     *
     * ENCODAGE DES CARACTÈRES SPÉCIAUX :
     *
     * Certains caractères ne peuvent pas apparaître directement dans une URL.
     * Ils sont "encodés" avec un % suivi de leur code hexadécimal :
     *
     * Caractère  →  Encodé
     * ─────────────────────
     * Espace     →  %20 ou +
     * &          →  %26
     * =          →  %3D
     * ?          →  %3F
     * #          →  %23
     * é          →  %C3%A9
     *
     * PHP décode automatiquement ces caractères pour vous.
     *
     * TABLEAUX DANS LA QUERY STRING :
     *
     * Vous pouvez passer des tableaux en utilisant [] :
     *
     * URL : /filter?colors[]=red&colors[]=blue&colors[]=green
     *
     * Résultat :
     * ```php
     * $_GET['colors'] = ['red', 'blue', 'green'];
     * ```
     *
     * ⚠️ SÉCURITÉ - TRÈS IMPORTANT :
     *
     * Les données GET viennent de l'UTILISATEUR. Un utilisateur malveillant
     * peut modifier l'URL à volonté ! Ne faites JAMAIS confiance à ces données.
     *
     * ```php
     * // ❌ DANGEREUX - Ne jamais faire ça !
     * $page = $_GET['page'];  // Et si page = "'; DROP TABLE users; --" ?
     *
     * // ✅ TOUJOURS valider et filtrer
     * $page = filter_var($_GET['page'] ?? 1, FILTER_VALIDATE_INT);
     * if ($page === false || $page < 1) {
     *     $page = 1;
     * }
     * ```
     *
     * @var array<string, mixed> Tableau associatif des paramètres GET
     *                           Clé = nom du paramètre, Valeur = sa valeur
     */
    private array $query;

    /**
     * Les données POST envoyées dans le corps de la requête (équivalent de $_POST).
     *
     * =========================================================================
     * C'EST QUOI LES DONNÉES POST ?
     * =========================================================================
     *
     * Contrairement aux paramètres GET (visibles dans l'URL), les données POST
     * sont envoyées dans le "corps" (body) de la requête HTTP. Elles sont
     * INVISIBLES dans l'URL.
     *
     * QUAND UTILISE-T-ON POST ?
     *
     * - Formulaires de connexion (mot de passe = données sensibles)
     * - Création de compte
     * - Envoi de fichiers
     * - Toute action qui MODIFIE des données sur le serveur
     * - Quand on envoie beaucoup de données (GET est limité à ~2000 caractères)
     *
     * COMMENT LE NAVIGATEUR ENVOIE LES DONNÉES POST ?
     *
     * ```html
     * <form method="POST" action="/login">
     *     <input type="text" name="username" value="john">
     *     <input type="password" name="password" value="secret123">
     *     <button type="submit">Connexion</button>
     * </form>
     * ```
     *
     * Quand l'utilisateur clique sur "Connexion", le navigateur envoie :
     *
     * ```
     * POST /login HTTP/1.1
     * Host: monsite.com
     * Content-Type: application/x-www-form-urlencoded
     * Content-Length: 32
     *
     * username=john&password=secret123
     * ```
     *
     * PHP remplit automatiquement $_POST :
     * ```php
     * $_POST = [
     *     'username' => 'john',
     *     'password' => 'secret123'
     * ];
     * ```
     *
     * LES DIFFÉRENTS FORMATS DE DONNÉES POST :
     *
     * 1. application/x-www-form-urlencoded (par défaut des formulaires HTML)
     *    → username=john&password=secret
     *    → PHP remplit $_POST automatiquement
     *
     * 2. multipart/form-data (pour l'upload de fichiers)
     *    → Format binaire complexe
     *    → PHP remplit $_POST et $_FILES
     *
     * 3. application/json (pour les APIs REST modernes)
     *    → {"username": "john", "password": "secret"}
     *    → ⚠️ $_POST sera VIDE ! Utilisez file_get_contents('php://input')
     *
     * COMPARAISON GET vs POST :
     *
     * ┌────────────────────┬────────────────────┬────────────────────┐
     * │ Critère            │ GET                │ POST               │
     * ├────────────────────┼────────────────────┼────────────────────┤
     * │ Visible dans l'URL │ Oui                │ Non                │
     * │ Taille max         │ ~2000 caractères   │ Illimitée*         │
     * │ Peut être favoris  │ Oui                │ Non                │
     * │ Mise en cache      │ Oui                │ Non                │
     * │ Données sensibles  │ Non recommandé     │ Oui                │
     * │ Modifie données    │ Non                │ Oui                │
     * │ Upload fichiers    │ Non                │ Oui                │
     * └────────────────────┴────────────────────┴────────────────────┘
     * * Configurable dans php.ini avec post_max_size
     *
     * @var array<string, mixed> Tableau associatif des données POST
     */
    private array $post;

    /**
     * Les variables serveur (équivalent de la superglobale $_SERVER).
     *
     * =========================================================================
     * C'EST QUOI $_SERVER ?
     * =========================================================================
     *
     * $_SERVER est un tableau créé par PHP qui contient des informations sur :
     * - Le serveur web (Apache, Nginx)
     * - La requête HTTP reçue
     * - L'environnement d'exécution
     *
     * C'est une "mine d'or" d'informations, mais attention : certaines peuvent
     * être falsifiées par un utilisateur malveillant !
     *
     * VARIABLES LES PLUS UTILES :
     *
     * ┌─────────────────────┬──────────────────────────────────────────┐
     * │ Variable            │ Description                              │
     * ├─────────────────────┼──────────────────────────────────────────┤
     * │ REQUEST_METHOD      │ Méthode HTTP : "GET", "POST", etc.       │
     * │ REQUEST_URI         │ URI demandée : "/page?id=1"              │
     * │ QUERY_STRING        │ Paramètres : "id=1&page=2"               │
     * │ HTTP_HOST           │ Nom de domaine : "www.monsite.com"       │
     * │ REMOTE_ADDR         │ IP du visiteur : "192.168.1.100"         │
     * │ HTTP_USER_AGENT     │ Navigateur : "Mozilla/5.0..."            │
     * │ HTTPS               │ Connexion sécurisée ? "on" ou absent     │
     * │ DOCUMENT_ROOT       │ Chemin racine du site sur le serveur     │
     * │ SERVER_NAME         │ Nom du serveur                           │
     * │ SERVER_PORT         │ Port : 80 (HTTP) ou 443 (HTTPS)          │
     * │ REQUEST_TIME        │ Timestamp Unix de la requête             │
     * │ CONTENT_TYPE        │ Type MIME des données envoyées           │
     * │ CONTENT_LENGTH      │ Taille des données en octets             │
     * └─────────────────────┴──────────────────────────────────────────┘
     *
     * HEADERS HTTP DANS $_SERVER :
     *
     * Les headers HTTP sont préfixés par "HTTP_" et les tirets "-" deviennent "_" :
     *
     * Header HTTP            →  Variable $_SERVER
     * ────────────────────────────────────────────
     * Accept-Language        →  HTTP_ACCEPT_LANGUAGE
     * X-Requested-With       →  HTTP_X_REQUESTED_WITH
     * Authorization          →  HTTP_AUTHORIZATION (parfois absent !)
     *
     * ⚠️ SÉCURITÉ - VARIABLES FIABLES vs NON FIABLES :
     *
     * ✅ FIABLES (définies par le serveur) :
     *    REMOTE_ADDR, REQUEST_TIME, SERVER_PORT, DOCUMENT_ROOT
     *
     * ⚠️ PEUVENT ÊTRE FALSIFIÉES par le client :
     *    HTTP_X_FORWARDED_FOR (fausse IP via proxy)
     *    HTTP_REFERER (page précédente, souvent vide ou faux)
     *    HTTP_USER_AGENT (n'importe qui peut le modifier)
     *
     * Exemple d'attaque : Un utilisateur modifie HTTP_X_FORWARDED_FOR pour
     * contourner une restriction par IP.
     *
     * Le mot-clé "readonly" signifie que cette propriété ne peut pas être
     * modifiée après la création de l'objet. C'est une protection.
     *
     * @var array<string, mixed> Tableau des variables $_SERVER
     */
    private readonly array $server;

    /**
     * Les en-têtes HTTP de la requête (headers).
     *
     * =========================================================================
     * C'EST QUOI LES HEADERS HTTP ?
     * =========================================================================
     *
     * Les "headers" (en-têtes) sont des MÉTADONNÉES envoyées avec la requête.
     * Ce sont des informations SUPPLÉMENTAIRES sur la requête elle-même.
     *
     * Pensez aux headers comme aux informations sur une enveloppe :
     * - L'adresse de l'expéditeur (votre navigateur)
     * - Le type de contenu (comme "fragile" sur un colis)
     * - Des instructions spéciales
     *
     * FORMAT D'UN HEADER :
     *
     * ```
     * Nom-Du-Header: valeur du header
     * ```
     *
     * Exemples :
     * ```
     * Content-Type: application/json
     * Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...
     * Accept-Language: fr-FR, en-US;q=0.9
     * ```
     *
     * HEADERS LES PLUS COURANTS :
     *
     * ┌───────────────────┬─────────────────────────────────────────────────┐
     * │ Header            │ Description                                     │
     * ├───────────────────┼─────────────────────────────────────────────────┤
     * │ Content-Type      │ Type de données envoyées                        │
     * │                   │ Ex: "application/json", "text/html"             │
     * ├───────────────────┼─────────────────────────────────────────────────┤
     * │ Accept            │ Types de réponse acceptés par le client         │
     * │                   │ Ex: "text/html, application/json"               │
     * ├───────────────────┼─────────────────────────────────────────────────┤
     * │ Accept-Language   │ Langues préférées du navigateur                 │
     * │                   │ Ex: "fr-FR, en;q=0.9" (français préféré)        │
     * ├───────────────────┼─────────────────────────────────────────────────┤
     * │ Authorization     │ Informations d'authentification                 │
     * │                   │ Ex: "Bearer <token>" ou "Basic base64..."       │
     * ├───────────────────┼─────────────────────────────────────────────────┤
     * │ Cookie            │ Cookies envoyés au serveur                      │
     * │                   │ Ex: "session_id=abc123; user=john"              │
     * ├───────────────────┼─────────────────────────────────────────────────┤
     * │ User-Agent        │ Information sur le navigateur/client            │
     * │                   │ Ex: "Mozilla/5.0 (Windows NT 10.0) Chrome/91.0" │
     * ├───────────────────┼─────────────────────────────────────────────────┤
     * │ X-Requested-With  │ Indique une requête AJAX (JavaScript)           │
     * │                   │ Valeur: "XMLHttpRequest"                        │
     * ├───────────────────┼─────────────────────────────────────────────────┤
     * │ X-CSRF-Token      │ Token de protection CSRF (sécurité)             │
     * │                   │ Généré par le serveur, renvoyé par le client    │
     * └───────────────────┴─────────────────────────────────────────────────┘
     *
     * HEADERS PERSONNALISÉS :
     *
     * Vous pouvez créer vos propres headers, traditionnellement préfixés par "X-" :
     * X-API-Key, X-Request-ID, X-Custom-Header
     *
     * (Note : Le préfixe X- est déprécié mais encore largement utilisé)
     *
     * @var array<string, mixed> Tableau associatif nom => valeur des headers
     */
    private array $headers;

    /**
     * Les attributs personnalisés de la requête.
     *
     * =========================================================================
     * C'EST QUOI LES ATTRIBUTS DE REQUÊTE ?
     * =========================================================================
     *
     * Les attributs sont des données AJOUTÉES à la requête CÔTÉ SERVEUR,
     * pendant que votre application traite la requête.
     *
     * DIFFÉRENCE FONDAMENTALE :
     *
     * ┌────────────────────┬────────────────────────────────────────────────┐
     * │ GET/POST           │ Attributs                                      │
     * ├────────────────────┼────────────────────────────────────────────────┤
     * │ Viennent du CLIENT │ Ajoutés par le SERVEUR                         │
     * │ (navigateur)       │ (votre code PHP)                               │
     * │                    │                                                │
     * │ L'utilisateur les  │ L'utilisateur ne peut PAS les modifier         │
     * │ contrôle           │ (ils n'existent pas encore côté client)        │
     * └────────────────────┴────────────────────────────────────────────────┘
     *
     * À QUOI SERVENT-ILS ?
     *
     * Les attributs permettent aux différentes parties de votre application
     * de se "passer des informations" via l'objet Request.
     *
     * Exemple : Une chaîne de middlewares
     *
     * ```
     * Requête      ┌─────────────────┐    ┌────────────────┐    ┌────────────┐
     * HTTP    ────▶│ SessionMiddleware│───▶│ AuthMiddleware │───▶│ Contrôleur │
     *              └─────────────────┘    └────────────────┘    └────────────┘
     *                      │                     │                    │
     *              setAttribute           setAttribute          getAttribute
     *              ('session', ...)       ('user', ...)        ('user')
     * ```
     *
     * Le SessionMiddleware démarre la session et l'attache à la requête.
     * L'AuthMiddleware vérifie si l'utilisateur est connecté et l'attache.
     * Le contrôleur peut ensuite récupérer l'utilisateur facilement.
     *
     * ATTRIBUTS STANDARDS DANS LUNAR QUANTA :
     *
     * ┌──────────┬─────────────────────────┬─────────────────────────────────┐
     * │ Attribut │ Ajouté par              │ Contenu                         │
     * ├──────────┼─────────────────────────┼─────────────────────────────────┤
     * │ 'session'│ SessionMiddleware       │ Objet SessionInterface          │
     * │ 'user'   │ AuthMiddleware          │ Objet UserInterface (ou null)   │
     * │ 'csrf'   │ CsrfMiddleware          │ Objet CsrfTokenManagerInterface │
     * └──────────┴─────────────────────────┴─────────────────────────────────┘
     *
     * EXEMPLE D'UTILISATION :
     *
     * ```php
     * // Dans AuthMiddleware
     * public function process(Request $request, callable $next): Response
     * {
     *     $user = $this->authenticator->user();  // Récupère l'utilisateur
     *     $request->setAttribute('user', $user); // L'attache à la requête
     *     return $next($request);                // Passe au suivant
     * }
     *
     * // Dans un contrôleur
     * public function dashboard(Request $request): Response
     * {
     *     $user = $request->getAttribute('user');  // Récupère l'utilisateur
     *     if ($user === null) {
     *         return new Response('Non connecté', 401);
     *     }
     *     return new Response('Bienvenue ' . $user->getIdentifier());
     * }
     * ```
     *
     * @var array<string, mixed> Tableau associatif nom => valeur des attributs
     */
    private array $attributes = [];

    /**
     * Constructeur - Crée une nouvelle instance Request à partir des superglobales PHP.
     *
     * =========================================================================
     * QUE FAIT LE CONSTRUCTEUR ?
     * =========================================================================
     *
     * Le constructeur est une méthode spéciale appelée automatiquement quand
     * on crée un nouvel objet avec "new Request()".
     *
     * Son rôle ici : lire toutes les superglobales PHP et les copier dans
     * les propriétés de l'objet.
     *
     * CE QUI SE PASSE ÉTAPE PAR ÉTAPE :
     *
     * 1. RÉCUPÉRATION DE LA MÉTHODE HTTP
     *    $this->method = $_SERVER['REQUEST_METHOD']
     *    Par défaut : 'GET' si non défini
     *
     * 2. EXTRACTION DU CHEMIN URI
     *    $_SERVER['REQUEST_URI'] peut contenir "/page?id=1"
     *    On utilise parse_url() pour extraire juste "/page"
     *
     * 3. COPIE DES PARAMÈTRES GET
     *    $this->query = $_GET
     *
     * 4. COPIE DES DONNÉES POST
     *    $this->post = $_POST
     *
     * 5. COPIE DES VARIABLES SERVEUR
     *    $this->server = $_SERVER (readonly = non modifiable ensuite)
     *
     * 6. RÉCUPÉRATION DES HEADERS
     *    Via getallheaders() si disponible (fonction Apache/PHP-FPM)
     *
     * POURQUOI COPIER AU LIEU D'UTILISER DIRECTEMENT ?
     *
     * - Isolation : Modifier $this->query n'affecte pas $_GET
     * - Testabilité : On pourrait créer une Request avec des données fictives
     * - Sécurité : Les données serveur sont marquées readonly
     *
     * QUAND EST-IL APPELÉ ?
     *
     * ```php
     * // Le FrontController crée la Request au début de chaque requête
     * $request = new Request();  // <-- Le constructeur s'exécute ici
     *
     * // Puis passe la Request au routeur
     * $response = $router->dispatch($request);
     * ```
     */
    public function __construct()
    {
        // 1. Récupérer la méthode HTTP (GET, POST, etc.)
        // $_SERVER['REQUEST_METHOD'] contient la méthode en majuscules
        // Le "??" est l'opérateur de coalescence nulle : si la valeur est null, utilise 'GET'
        $requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $this->method = is_string($requestMethod) ? $requestMethod : 'GET';

        // 2. Extraire le chemin de l'URI
        // $_SERVER['REQUEST_URI'] = "/page?id=1"
        // parse_url(..., PHP_URL_PATH) extrait juste "/page"
        $requestUri = $_SERVER['REQUEST_URI'] ?? '/';
        $parsedPath = parse_url(is_string($requestUri) ? $requestUri : '/', PHP_URL_PATH);
        $this->uri = is_string($parsedPath) ? $parsedPath : '/';

        // 3. Copier les paramètres GET (la query string)
        /** @var array<string, mixed> $query */
        $query = $_GET;
        $this->query = $query;

        // 4. Copier les données POST
        /** @var array<string, mixed> $post */
        $post = $_POST;
        $this->post = $post;

        // 5. Copier les variables serveur (readonly = non modifiable après)
        /** @var array<string, mixed> $server */
        $server = $_SERVER;
        $this->server = $server;

        // 6. Récupérer les headers HTTP
        // getallheaders() est une fonction PHP disponible avec Apache/PHP-FPM
        // Elle retourne un tableau associatif des headers
        /** @var array<string, mixed> $headers */
        $headers = function_exists('getallheaders') ? (getallheaders() ?: []) : [];
        $this->headers = $headers;
    }

    /**
     * Récupère la méthode HTTP de la requête.
     *
     * Cette méthode retourne le "verbe" HTTP utilisé pour cette requête :
     * GET, POST, PUT, PATCH, DELETE, etc.
     *
     * UTILISATION TYPIQUE :
     *
     * ```php
     * // Vérifier si c'est une soumission de formulaire
     * if ($request->getMethod() === 'POST') {
     *     // Traiter les données du formulaire
     *     $data = $request->getPostParams();
     *     // ...
     * }
     *
     * // Ou avec un switch/match
     * match ($request->getMethod()) {
     *     'GET'    => $this->show($id),     // Afficher
     *     'POST'   => $this->create(),      // Créer
     *     'PUT'    => $this->update($id),   // Mettre à jour
     *     'DELETE' => $this->delete($id),   // Supprimer
     * };
     * ```
     *
     * @return string La méthode HTTP en MAJUSCULES ("GET", "POST", etc.)
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * Récupère le chemin URI de la requête.
     *
     * Retourne la partie "chemin" de l'URL, sans le nom de domaine,
     * sans les paramètres (query string), et sans le fragment (#).
     *
     * EXEMPLES :
     *
     * URL complète                         →  getUri() retourne
     * ─────────────────────────────────────────────────────────────
     * https://monsite.com/                 →  "/"
     * https://monsite.com/contact          →  "/contact"
     * https://monsite.com/users/123        →  "/users/123"
     * https://monsite.com/search?q=test    →  "/search" (PAS ?q=test)
     *
     * UTILISATION :
     *
     * ```php
     * $uri = $request->getUri();  // Ex: "/users/profile"
     *
     * // Le routeur utilise cette URI pour trouver le bon contrôleur
     * // C'est fait automatiquement, vous n'avez généralement pas
     * // besoin d'utiliser getUri() directement dans vos contrôleurs.
     * ```
     *
     * @return string Le chemin URI, commence toujours par "/"
     */
    public function getUri(): string
    {
        return $this->uri;
    }

    /**
     * Récupère les paramètres de la query string (GET).
     *
     * Retourne un tableau associatif de tous les paramètres passés dans
     * l'URL après le point d'interrogation "?".
     *
     * EXEMPLE :
     *
     * URL : /products?category=electronics&sort=price&order=asc
     *
     * ```php
     * $params = $request->getQueryParams();
     * // Résultat :
     * // [
     * //     'category' => 'electronics',
     * //     'sort'     => 'price',
     * //     'order'    => 'asc'
     * // ]
     *
     * // Récupérer un paramètre spécifique avec valeur par défaut
     * $category = $params['category'] ?? 'all';
     * $page = (int)($params['page'] ?? 1);
     * ```
     *
     * ⚠️ SÉCURITÉ : Toujours valider ces données !
     *
     * ```php
     * // ❌ DANGEREUX
     * $id = $request->getQueryParams()['id'];
     *
     * // ✅ SÉCURISÉ
     * $id = filter_var(
     *     $request->getQueryParams()['id'] ?? 0,
     *     FILTER_VALIDATE_INT
     * );
     * ```
     *
     * @return array<string, mixed> Tableau associatif des paramètres GET
     */
    public function getQueryParams(): array
    {
        return $this->query;
    }

    /**
     * Récupère les données POST de la requête.
     *
     * Retourne un tableau associatif des données envoyées dans le corps
     * de la requête (généralement via un formulaire HTML).
     *
     * EXEMPLE :
     *
     * Formulaire HTML :
     * ```html
     * <form method="POST" action="/register">
     *     <input name="username" value="john">
     *     <input name="email" value="john@example.com">
     *     <button type="submit">S'inscrire</button>
     * </form>
     * ```
     *
     * Côté PHP :
     * ```php
     * $data = $request->getPostParams();
     * // Résultat :
     * // [
     * //     'username' => 'john',
     * //     'email'    => 'john@example.com'
     * // ]
     *
     * $username = trim($data['username'] ?? '');
     * $email = filter_var($data['email'] ?? '', FILTER_VALIDATE_EMAIL);
     * ```
     *
     * ⚠️ NOTE SUR LES DONNÉES JSON :
     *
     * Si le client envoie du JSON (APIs REST), $_POST sera VIDE !
     * Utilisez plutôt :
     * ```php
     * $json = file_get_contents('php://input');
     * $data = json_decode($json, true);
     * ```
     *
     * @return array<string, mixed> Tableau associatif des données POST
     */
    public function getPostParams(): array
    {
        return $this->post;
    }

    /**
     * Récupère les en-têtes HTTP de la requête.
     *
     * Retourne un tableau associatif de tous les headers envoyés par le client.
     *
     * EXEMPLE :
     *
     * ```php
     * $headers = $request->getHeaders();
     *
     * // Vérifier si c'est une requête AJAX (JavaScript)
     * $isAjax = ($headers['X-Requested-With'] ?? '') === 'XMLHttpRequest';
     *
     * // Récupérer le token d'authentification Bearer
     * $authHeader = $headers['Authorization'] ?? '';
     * if (str_starts_with($authHeader, 'Bearer ')) {
     *     $token = substr($authHeader, 7);  // Enlève "Bearer "
     * }
     *
     * // Vérifier le type de contenu accepté
     * $acceptsJson = str_contains($headers['Accept'] ?? '', 'application/json');
     * ```
     *
     * @return array<string, mixed> Tableau associatif des headers HTTP
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * Récupère les variables serveur ($_SERVER).
     *
     * Retourne le tableau complet des informations sur le serveur et la requête.
     *
     * EXEMPLE :
     *
     * ```php
     * $server = $request->getServerParams();
     *
     * // Obtenir l'IP du visiteur
     * $ip = $server['REMOTE_ADDR'];
     *
     * // Vérifier si HTTPS (connexion sécurisée)
     * $isSecure = !empty($server['HTTPS']) && $server['HTTPS'] !== 'off';
     *
     * // Obtenir le nom de domaine
     * $host = $server['HTTP_HOST'];
     *
     * // Reconstruire l'URL complète
     * $protocol = $isSecure ? 'https' : 'http';
     * $fullUrl = "{$protocol}://{$host}{$server['REQUEST_URI']}";
     * ```
     *
     * @return array<string, mixed> Tableau des variables $_SERVER
     */
    public function getServerParams(): array
    {
        return $this->server;
    }

    /**
     * Définit un attribut personnalisé sur la requête.
     *
     * Les attributs permettent aux middlewares de "attacher" des données
     * à la requête pour que les contrôleurs puissent les récupérer ensuite.
     *
     * EXEMPLE :
     *
     * ```php
     * // Dans un middleware d'authentification
     * class AuthMiddleware implements MiddlewareInterface
     * {
     *     public function process(Request $request, callable $next): Response
     *     {
     *         $user = $this->authenticator->user();
     *
     *         // Attacher l'utilisateur à la requête
     *         $request->setAttribute('user', $user);
     *
     *         // Passer au middleware/contrôleur suivant
     *         return $next($request);
     *     }
     * }
     * ```
     *
     * @param string $name  Le nom de l'attribut (ex: 'user', 'session')
     * @param mixed  $value La valeur à stocker (peut être n'importe quoi)
     *
     * @return void (ne retourne rien)
     */
    public function setAttribute(string $name, mixed $value): void
    {
        $this->attributes[$name] = $value;
    }

    /**
     * Récupère un attribut personnalisé de la requête.
     *
     * Retourne la valeur d'un attribut précédemment défini par un middleware.
     * Si l'attribut n'existe pas, retourne la valeur par défaut.
     *
     * EXEMPLE :
     *
     * ```php
     * // Dans un contrôleur
     * public function profile(Request $request): Response
     * {
     *     // Récupérer l'utilisateur (défini par AuthMiddleware)
     *     $user = $request->getAttribute('user');
     *
     *     if ($user === null) {
     *         return new Response('Non connecté', 401);
     *     }
     *
     *     // Récupérer la session (définie par SessionMiddleware)
     *     $session = $request->getAttribute('session');
     *     $lastLogin = $session->get('last_login');
     *
     *     return $this->render('profile', [
     *         'user' => $user,
     *         'lastLogin' => $lastLogin
     *     ]);
     * }
     *
     * // Avec une valeur par défaut
     * $locale = $request->getAttribute('locale', 'fr');  // 'fr' si pas défini
     * ```
     *
     * @param string $name    Le nom de l'attribut à récupérer
     * @param mixed  $default Valeur retournée si l'attribut n'existe pas (défaut: null)
     *
     * @return mixed La valeur de l'attribut ou la valeur par défaut
     */
    public function getAttribute(string $name, mixed $default = null): mixed
    {
        return $this->attributes[$name] ?? $default;
    }

    /**
     * Récupère tous les attributs personnalisés de la requête.
     *
     * Utile pour le débogage ou pour voir tout ce qui a été attaché à la requête.
     *
     * EXEMPLE :
     *
     * ```php
     * // Débogage : voir tous les attributs
     * dump($request->getAttributes());
     * // Affiche quelque chose comme :
     * // [
     * //     'session' => SessionService {...},
     * //     'user'    => User {...},
     * //     'csrf'    => CsrfTokenManager {...},
     * // ]
     *
     * // Dans un test unitaire
     * $middleware->process($request, fn($r) => new Response('ok'));
     * $this->assertArrayHasKey('session', $request->getAttributes());
     * ```
     *
     * @return array<string, mixed> Tableau associatif de tous les attributs
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    // =========================================================================
    // PARAMÈTRES DE ROUTE DYNAMIQUE
    // =========================================================================

    /**
     * Récupère un paramètre de route dynamique.
     *
     * =========================================================================
     * QU'EST-CE QU'UN PARAMÈTRE DE ROUTE ?
     * =========================================================================
     *
     * Les routes dynamiques permettent de capturer des parties de l'URL :
     *
     * ```
     * Route définie : /user/{id}/post/{postId}
     * URL appelée   : /user/42/post/123
     *
     * Paramètres extraits :
     *   'id'     => '42'
     *   'postId' => '123'
     * ```
     *
     * DIFFÉRENCE AVEC LES QUERY PARAMS :
     *
     * ```
     * /user/42           → Route param 'id' = '42'
     * /user?id=42        → Query param 'id' = '42'
     *
     * Route params : font partie de l'URL elle-même
     * Query params : après le ? dans l'URL
     * ```
     *
     * UTILISATION :
     *
     * ```php
     * // Dans un contrôleur
     * #[Route('/user/{id}', methods: ['GET'])]
     * public function show(Request $request): Response
     * {
     *     $userId = $request->getRouteParam('id');
     *     // $userId = '42'
     *
     *     // Avec valeur par défaut
     *     $format = $request->getRouteParam('format', 'json');
     *
     *     return new Response("User ID: $userId");
     * }
     * ```
     *
     * @param string $name    Le nom du paramètre de route
     * @param mixed  $default Valeur par défaut si le paramètre n'existe pas
     *
     * @return mixed La valeur du paramètre ou la valeur par défaut
     */
    public function getRouteParam(string $name, mixed $default = null): mixed
    {
        // Cherche dans l'attribut individuel d'abord
        return $this->getAttribute('_route_' . $name, $default);
    }

    /**
     * Récupère tous les paramètres de route dynamique.
     *
     * =========================================================================
     * QUAND L'UTILISER ?
     * =========================================================================
     *
     * Cette méthode est utile quand vous avez besoin de tous les paramètres
     * de route en une seule fois, par exemple pour :
     *
     * - Passer à une fonction/service
     * - Logger la requête
     * - Déboguer
     *
     * EXEMPLE :
     *
     * ```php
     * // Route: /api/{version}/user/{id}/post/{postId}
     * // URL:   /api/v2/user/42/post/123
     *
     * $params = $request->getRouteParams();
     * // Résultat :
     * // [
     * //     'version' => 'v2',
     * //     'id'      => '42',
     * //     'postId'  => '123'
     * // ]
     *
     * // Passer tous les params à un service
     * $this->userService->fetchPost($params);
     * ```
     *
     * @return array<string, string> Tableau associatif nom => valeur des paramètres
     */
    public function getRouteParams(): array
    {
        /** @var array<string, string> $params */
        $params = $this->getAttribute('_route_params', []);

        return $params;
    }

    /**
     * Vérifie si un paramètre de route existe.
     *
     * =========================================================================
     * UTILITÉ
     * =========================================================================
     *
     * Cette méthode permet de vérifier l'existence d'un paramètre avant
     * de l'utiliser, utile pour les paramètres optionnels.
     *
     * EXEMPLE :
     *
     * ```php
     * // Route: /blog/{slug}/{?format}  (format optionnel)
     *
     * if ($request->hasRouteParam('format')) {
     *     $format = $request->getRouteParam('format');
     * } else {
     *     $format = 'html';  // Défaut
     * }
     *
     * // Équivalent plus court :
     * $format = $request->getRouteParam('format', 'html');
     * ```
     *
     * @param string $name Le nom du paramètre à vérifier
     *
     * @return bool true si le paramètre existe, false sinon
     */
    public function hasRouteParam(string $name): bool
    {
        $params = $this->getRouteParams();

        return isset($params[$name]);
    }
}
