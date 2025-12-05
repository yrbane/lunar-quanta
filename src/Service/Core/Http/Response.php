<?php
/**
 * Lunar Quanta Framework - Composant HTTP Response (Réponse HTTP).
 *
 * =============================================================================
 * QU'EST-CE QU'UNE RÉPONSE HTTP ? (HTTP Response)
 * =============================================================================
 *
 * Quand vous tapez une adresse dans votre navigateur (comme https://example.com),
 * votre navigateur envoie une REQUÊTE (Request) au serveur web. Le serveur traite
 * cette requête puis renvoie une RÉPONSE (Response).
 *
 * C'est comme au restaurant :
 * - Vous (le navigateur) passez une commande (requête) au serveur
 * - Le cuisinier (votre application PHP) prépare le plat
 * - Le serveur vous apporte votre plat (réponse)
 *
 * ```
 *   NAVIGATEUR                        SERVEUR WEB
 *   (Client)                          (Votre application)
 *
 *   ┌─────────┐                       ┌─────────────┐
 *   │ Chrome  │ ── Requête HTTP ───►  │  PHP        │
 *   │ Firefox │    "Je veux /home"    │  Application│
 *   │ Safari  │                       │             │
 *   └─────────┘                       └─────────────┘
 *        ▲                                  │
 *        │                                  │
 *        └─────── Réponse HTTP ─────────────┘
 *                 "Voici la page !"
 * ```
 *
 * =============================================================================
 * ANATOMIE D'UNE RÉPONSE HTTP
 * =============================================================================
 *
 * Une réponse HTTP est composée de 3 parties :
 *
 * 1. LIGNE DE STATUT (Status Line)
 *    - La version HTTP (HTTP/1.1)
 *    - Le CODE DE STATUT (200, 404, 500...)
 *    - Le MESSAGE DE STATUT ("OK", "Not Found", "Internal Server Error"...)
 *
 * 2. LES EN-TÊTES (Headers)
 *    - Des métadonnées sur la réponse
 *    - Content-Type, Content-Length, Set-Cookie, etc.
 *
 * 3. LE CORPS (Body)
 *    - Le contenu réel de la réponse
 *    - Le HTML de la page, le JSON d'une API, une image, etc.
 *
 * ```
 * ┌────────────────────────────────────────────────────────────────────────┐
 * │                    STRUCTURE D'UNE RÉPONSE HTTP                        │
 * ├────────────────────────────────────────────────────────────────────────┤
 * │                                                                        │
 * │  HTTP/1.1 200 OK                    ◄── Ligne de statut                │
 * │  ─────────────────────────────────────────────────────                 │
 * │  Content-Type: text/html            ◄── En-têtes                       │
 * │  Content-Length: 1234                   (métadonnées)                  │
 * │  Set-Cookie: session=abc123                                            │
 * │  Cache-Control: no-cache                                               │
 * │                                     ◄── Ligne vide (sépare headers/body)│
 * │  <!DOCTYPE html>                    ◄── Corps de la réponse            │
 * │  <html>                                 (le contenu visible)           │
 * │    <head>...</head>                                                    │
 * │    <body>Bonjour !</body>                                              │
 * │  </html>                                                               │
 * │                                                                        │
 * └────────────────────────────────────────────────────────────────────────┘
 * ```
 *
 * =============================================================================
 * LES CODES DE STATUT HTTP (Status Codes)
 * =============================================================================
 *
 * Le CODE DE STATUT est un nombre à 3 chiffres qui indique le résultat
 * de la requête. C'est comme un code de résultat standardisé.
 *
 * Les codes sont regroupés par catégories (premier chiffre) :
 *
 * ┌────────┬─────────────────────────────────────────────────────────────────┐
 * │ SÉRIE  │ SIGNIFICATION                                                   │
 * ├────────┼─────────────────────────────────────────────────────────────────┤
 * │ 1xx    │ INFORMATION - La requête est en cours de traitement             │
 * │        │ (rarement utilisé directement)                                  │
 * ├────────┼─────────────────────────────────────────────────────────────────┤
 * │ 2xx    │ SUCCÈS - Tout s'est bien passé !                                │
 * │        │ 200 = OK (la requête a réussi)                                  │
 * │        │ 201 = Created (une ressource a été créée)                       │
 * │        │ 204 = No Content (succès, mais rien à renvoyer)                 │
 * ├────────┼─────────────────────────────────────────────────────────────────┤
 * │ 3xx    │ REDIRECTION - Il faut aller ailleurs                            │
 * │        │ 301 = Moved Permanently (changement d'adresse définitif)        │
 * │        │ 302 = Found (redirection temporaire)                            │
 * │        │ 304 = Not Modified (utiliser le cache du navigateur)            │
 * ├────────┼─────────────────────────────────────────────────────────────────┤
 * │ 4xx    │ ERREUR CLIENT - Le visiteur a fait une erreur                   │
 * │        │ 400 = Bad Request (requête mal formée)                          │
 * │        │ 401 = Unauthorized (authentification requise)                   │
 * │        │ 403 = Forbidden (accès interdit, même authentifié)              │
 * │        │ 404 = Not Found (page introuvable - très courant !)             │
 * │        │ 405 = Method Not Allowed (GET au lieu de POST, etc.)            │
 * │        │ 422 = Unprocessable Entity (données invalides)                  │
 * │        │ 429 = Too Many Requests (trop de requêtes - rate limiting)      │
 * ├────────┼─────────────────────────────────────────────────────────────────┤
 * │ 5xx    │ ERREUR SERVEUR - Le serveur a un problème                       │
 * │        │ 500 = Internal Server Error (erreur PHP, bug, etc.)             │
 * │        │ 502 = Bad Gateway (problème de proxy/serveur intermédiaire)     │
 * │        │ 503 = Service Unavailable (serveur surchargé/maintenance)       │
 * │        │ 504 = Gateway Timeout (le serveur n'a pas répondu à temps)      │
 * └────────┴─────────────────────────────────────────────────────────────────┘
 *
 * ASTUCE MNÉMOTECHNIQUE :
 * - 2xx = "Tout va bien" (succès)
 * - 3xx = "Va voir ailleurs" (redirection)
 * - 4xx = "Tu as fait une bêtise" (erreur client)
 * - 5xx = "J'ai fait une bêtise" (erreur serveur)
 *
 * =============================================================================
 * LES EN-TÊTES DE RÉPONSE (Response Headers)
 * =============================================================================
 *
 * Les EN-TÊTES (headers) sont des métadonnées envoyées AVANT le contenu.
 * Ils informent le navigateur sur comment traiter la réponse.
 *
 * POURQUOI C'EST IMPORTANT ?
 * Imaginez recevoir un colis sans étiquette. Vous ne savez pas :
 * - Ce qu'il contient (nourriture ? vêtements ? fragile ?)
 * - Comment le manipuler
 * - D'où il vient
 *
 * Les headers sont comme les étiquettes sur un colis !
 *
 * ┌─────────────────────────────────────────────────────────────────────────┐
 * │                    EN-TÊTES DE RÉPONSE COURANTS                         │
 * ├──────────────────────┬──────────────────────────────────────────────────┤
 * │ Content-Type         │ Type du contenu (HTML, JSON, image...)           │
 * │                      │ Ex: "text/html", "application/json"              │
 * │                      │ Le navigateur utilise ça pour savoir comment     │
 * │                      │ afficher le contenu                              │
 * ├──────────────────────┼──────────────────────────────────────────────────┤
 * │ Content-Length       │ Taille du contenu en octets                      │
 * │                      │ Permet au navigateur de savoir quand             │
 * │                      │ le téléchargement est terminé                    │
 * ├──────────────────────┼──────────────────────────────────────────────────┤
 * │ Location             │ URL de redirection                               │
 * │                      │ Utilisé avec les codes 301, 302, etc.            │
 * │                      │ Ex: "Location: /nouvelle-page"                   │
 * ├──────────────────────┼──────────────────────────────────────────────────┤
 * │ Set-Cookie           │ Définit un cookie dans le navigateur             │
 * │                      │ Ex: "Set-Cookie: session=abc123; HttpOnly"       │
 * │                      │ Le navigateur stocke cette info localement       │
 * ├──────────────────────┼──────────────────────────────────────────────────┤
 * │ Cache-Control        │ Instructions de mise en cache                    │
 * │                      │ "no-cache" = toujours redemander au serveur      │
 * │                      │ "max-age=3600" = garder en cache 1 heure         │
 * ├──────────────────────┼──────────────────────────────────────────────────┤
 * │ Content-Disposition  │ Comment présenter le contenu                     │
 * │                      │ "inline" = afficher dans le navigateur           │
 * │                      │ "attachment; filename=doc.pdf" = télécharger     │
 * ├──────────────────────┼──────────────────────────────────────────────────┤
 * │ X-Content-Type-Options│ Sécurité : empêche le "sniffing" de type        │
 * │                      │ "nosniff" = respecter Content-Type strictement   │
 * ├──────────────────────┼──────────────────────────────────────────────────┤
 * │ X-Frame-Options      │ Sécurité : protection contre le clickjacking    │
 * │                      │ "DENY" = interdit d'afficher dans une iframe     │
 * └──────────────────────┴──────────────────────────────────────────────────┘
 *
 * =============================================================================
 * LE CORPS DE LA RÉPONSE (Response Body)
 * =============================================================================
 *
 * Le CORPS (body) est le contenu réel de la réponse. C'est ce que le
 * visiteur va voir ou utiliser.
 *
 * Le corps peut être :
 * - Du HTML (une page web)
 * - Du JSON (données pour une API)
 * - Du XML (données structurées)
 * - Une image (PNG, JPEG, etc.)
 * - Un fichier PDF, ZIP, etc.
 * - Rien du tout (pour une redirection ou un 204 No Content)
 *
 * IMPORTANT : Le Content-Type doit correspondre au contenu !
 *
 * ┌───────────────────────────────────────────────────────────────────────────┐
 * │ EXEMPLES DE CONTENT-TYPE SELON LE CONTENU                                │
 * ├─────────────────────────────┬─────────────────────────────────────────────┤
 * │ CONTENU                     │ CONTENT-TYPE                                │
 * ├─────────────────────────────┼─────────────────────────────────────────────┤
 * │ Page HTML                   │ text/html; charset=UTF-8                    │
 * │ Données JSON                │ application/json                            │
 * │ Texte brut                  │ text/plain                                  │
 * │ CSS                         │ text/css                                    │
 * │ JavaScript                  │ application/javascript                      │
 * │ Image PNG                   │ image/png                                   │
 * │ Image JPEG                  │ image/jpeg                                  │
 * │ PDF                         │ application/pdf                             │
 * │ Fichier à télécharger       │ application/octet-stream                    │
 * └─────────────────────────────┴─────────────────────────────────────────────┘
 *
 * =============================================================================
 * COMMENT PHP ENVOIE UNE RÉPONSE ?
 * =============================================================================
 *
 * PHP utilise des fonctions natives pour envoyer la réponse au navigateur :
 *
 * 1. http_response_code(200)  → Définit le code de statut HTTP
 * 2. header("Content-Type: text/html") → Envoie un en-tête
 * 3. echo "Bonjour !"  → Envoie le corps de la réponse
 *
 * ATTENTION À L'ORDRE !
 * Les headers DOIVENT être envoyés AVANT le corps.
 * Une fois que vous avez fait un "echo", vous ne pouvez plus envoyer de headers !
 *
 * ```php
 * // ✅ CORRECT - headers d'abord, contenu ensuite
 * http_response_code(200);
 * header('Content-Type: application/json');
 * echo '{"status": "ok"}';
 *
 * // ❌ ERREUR - "Cannot modify header information - headers already sent"
 * echo "Bonjour";
 * header('Content-Type: text/plain');  // ERREUR !
 * ```
 *
 * Cette classe Response vous protège de cette erreur en accumulant tout
 * (code, headers, contenu) puis en envoyant dans le bon ordre avec send().
 *
 * =============================================================================
 * FLUX COMPLET D'UNE RÉPONSE DANS LE FRAMEWORK
 * =============================================================================
 *
 * ```
 *  1. Requête arrive
 *         │
 *         ▼
 *  2. Router trouve le contrôleur
 *         │
 *         ▼
 *  3. Contrôleur traite la requête
 *         │
 *         ▼
 *  4. Contrôleur crée un objet Response
 *     ┌────────────────────────────────────────┐
 *     │ $response = new Response(              │
 *     │     '<h1>Bonjour</h1>',  // contenu    │
 *     │     200,                  // statut    │
 *     │     ['Content-Type: text/html']        │
 *     │ );                                     │
 *     └────────────────────────────────────────┘
 *         │
 *         ▼
 *  5. Framework appelle $response->send()
 *     ┌────────────────────────────────────────┐
 *     │ → http_response_code(200)              │
 *     │ → header('Content-Type: text/html')    │
 *     │ → echo '<h1>Bonjour</h1>'              │
 *     └────────────────────────────────────────┘
 *         │
 *         ▼
 *  6. Navigateur reçoit et affiche la page
 * ```
 *
 * =============================================================================
 * POURQUOI UTILISER UNE CLASSE RESPONSE ?
 * =============================================================================
 *
 * Au lieu d'appeler directement echo et header(), on utilise cette classe
 * pour plusieurs raisons :
 *
 * 1. TESTABILITÉ
 *    On peut créer une Response et vérifier son contenu sans rien envoyer.
 *    Très utile pour les tests automatisés !
 *
 * 2. MANIPULATION
 *    On peut modifier la réponse (ajouter des headers, changer le contenu)
 *    à n'importe quel moment avant l'envoi.
 *
 * 3. MIDDLEWARES
 *    Les middlewares peuvent intercepter et modifier la réponse.
 *    Ex: ajouter automatiquement des headers de sécurité.
 *
 * 4. COHÉRENCE
 *    Toutes les réponses du framework passent par la même classe.
 *    Comportement prévisible et standardisé.
 *
 * @package    Lunar\Service\Core\Http
 * @author     Seb <seb@nethttp.net>
 * @copyright  2024 Lunar Quanta
 * @license    MIT License
 * @version    1.1.0
 * @link       https://nethttp.net
 * @since      0.0.1
 *
 * @see Request La classe qui représente la requête entrante (l'inverse de Response)
 * @see https://developer.mozilla.org/fr/docs/Web/HTTP/Status Liste complète des codes HTTP
 * @see https://developer.mozilla.org/fr/docs/Web/HTTP/Headers Liste des en-têtes HTTP
 */
declare(strict_types=1);

namespace Lunar\Service\Core\Http;

/**
 * Classe de gestion des réponses HTTP.
 *
 * Cette classe ENCAPSULE (enveloppe, regroupe) toutes les informations
 * nécessaires pour construire et envoyer une réponse HTTP complète
 * au navigateur du visiteur.
 *
 * =============================================================================
 * QU'EST-CE QUE L'ENCAPSULATION ? (Concept POO)
 * =============================================================================
 *
 * L'ENCAPSULATION est un principe fondamental de la Programmation Orientée
 * Objet (POO). C'est le fait de :
 *
 * 1. REGROUPER des données liées dans un même objet
 * 2. PROTÉGER ces données avec une visibilité (private, protected, public)
 * 3. CONTRÔLER l'accès via des méthodes (getters/setters)
 *
 * ANALOGIE : Pensez à une voiture
 * - Le moteur, l'essence, l'électronique sont ENCAPSULÉS sous le capot
 * - Vous n'y accédez pas directement
 * - Vous utilisez des INTERFACES : le volant, les pédales, le tableau de bord
 * - La complexité est cachée, l'utilisation est simple
 *
 * Ici, Response encapsule :
 * - $content (le corps de la réponse)
 * - $statusCode (le code de statut)
 * - $headers (les en-têtes)
 *
 * Et expose des méthodes simples : getStatusCode(), getBody(), send()
 *
 * =============================================================================
 * EXEMPLES D'UTILISATION
 * =============================================================================
 *
 * ```php
 * // ═══════════════════════════════════════════════════════════════════════
 * // EXEMPLE 1 : Réponse HTML simple
 * // ═══════════════════════════════════════════════════════════════════════
 * // Crée une réponse avec du HTML, code 200 (succès)
 *
 * $response = new Response('<html><body>Bonjour !</body></html>');
 * $response->send();
 *
 * // Ce que le navigateur reçoit :
 * // HTTP/1.1 200 OK
 * // (headers par défaut de PHP)
 * //
 * // <html><body>Bonjour !</body></html>
 *
 *
 * // ═══════════════════════════════════════════════════════════════════════
 * // EXEMPLE 2 : Réponse JSON pour une API
 * // ═══════════════════════════════════════════════════════════════════════
 * // Les APIs modernes utilisent JSON pour échanger des données
 *
 * $data = ['status' => 'success', 'user' => ['id' => 1, 'name' => 'Jean']];
 * $response = new Response(
 *     json_encode($data),                    // Convertit le tableau en JSON
 *     200,                                   // Code de succès
 *     ['Content-Type: application/json']    // Dit au navigateur que c'est du JSON
 * );
 * $response->send();
 *
 * // Ce que le navigateur reçoit :
 * // HTTP/1.1 200 OK
 * // Content-Type: application/json
 * //
 * // {"status":"success","user":{"id":1,"name":"Jean"}}
 *
 *
 * // ═══════════════════════════════════════════════════════════════════════
 * // EXEMPLE 3 : Redirection vers une autre page
 * // ═══════════════════════════════════════════════════════════════════════
 * // Après une connexion réussie, on redirige vers le tableau de bord
 *
 * $response = new Response(
 *     '',                           // Pas de contenu (le navigateur va ailleurs)
 *     302,                          // Code "Found" = redirection temporaire
 *     ['Location: /dashboard']      // L'URL où aller
 * );
 * $response->send();
 *
 * // Le navigateur voit le code 302 et l'header Location,
 * // puis fait automatiquement une nouvelle requête vers /dashboard
 *
 *
 * // ═══════════════════════════════════════════════════════════════════════
 * // EXEMPLE 4 : Page non trouvée (erreur 404)
 * // ═══════════════════════════════════════════════════════════════════════
 *
 * $response = new Response(
 *     '<h1>Page non trouvée</h1><p>Désolé, cette page n\'existe pas.</p>',
 *     404,                                   // Code "Not Found"
 *     ['Content-Type: text/html; charset=UTF-8']
 * );
 * $response->send();
 *
 *
 * // ═══════════════════════════════════════════════════════════════════════
 * // EXEMPLE 5 : Téléchargement de fichier
 * // ═══════════════════════════════════════════════════════════════════════
 *
 * $fileContent = file_get_contents('/chemin/vers/document.pdf');
 * $response = new Response(
 *     $fileContent,
 *     200,
 *     [
 *         'Content-Type: application/pdf',
 *         'Content-Disposition: attachment; filename="document.pdf"',
 *         'Content-Length: ' . strlen($fileContent)
 *     ]
 * );
 * $response->send();
 *
 * // Le navigateur télécharge le fichier au lieu de l'afficher
 *
 *
 * // ═══════════════════════════════════════════════════════════════════════
 * // EXEMPLE 6 : Réponse avec cookie de session
 * // ═══════════════════════════════════════════════════════════════════════
 *
 * $response = new Response(
 *     'Connexion réussie !',
 *     200,
 *     [
 *         'Content-Type: text/html',
 *         'Set-Cookie: session_id=abc123; HttpOnly; Secure; SameSite=Strict'
 *     ]
 * );
 * $response->send();
 *
 * // Le navigateur stocke le cookie et l'enverra avec les prochaines requêtes
 * ```
 *
 * @package Lunar\Service\Core\Http
 */
class Response
{
    /**
     * Contenu du corps de la réponse.
     *
     * =========================================================================
     * QU'EST-CE QUE LE CORPS DE LA RÉPONSE ?
     * =========================================================================
     *
     * Le CORPS (body) est le contenu réel de la réponse. C'est ce que le
     * visiteur va voir ou ce que l'application cliente va traiter.
     *
     * ```
     *  ┌─────────────────────────────────────────────────────────────────────┐
     *  │                    STRUCTURE D'UNE RÉPONSE HTTP                     │
     *  ├─────────────────────────────────────────────────────────────────────┤
     *  │  HTTP/1.1 200 OK                         │ Ligne de statut          │
     *  │  Content-Type: text/html                 │                          │
     *  │  Content-Length: 42                      │ En-têtes                 │
     *  │                                          │                          │
     *  │  ════════════════════════════════════════╪══════════════════════════│
     *  │                                          │                          │
     *  │  <html>                                  │                          │
     *  │    <body>                                │ ◄── $content             │
     *  │      Bonjour le monde !                  │     (Corps de la         │
     *  │    </body>                               │      réponse)            │
     *  │  </html>                                 │                          │
     *  │                                          │                          │
     *  └─────────────────────────────────────────────────────────────────────┘
     * ```
     *
     * Le contenu peut être :
     * - Du HTML (page web)
     * - Du JSON (données API)
     * - Du texte brut
     * - Des données binaires (image, PDF...)
     * - Vide (pour les redirections ou 204 No Content)
     *
     * VISIBILITÉ "PRIVATE"
     * --------------------
     * Cette propriété est PRIVATE (privée), ce qui signifie qu'elle ne peut
     * être accédée que depuis l'intérieur de cette classe.
     *
     * Pourquoi private ?
     * - Protection : empêche les modifications accidentelles
     * - Contrôle : toute modification passe par des méthodes
     * - Évolution : on peut changer l'implémentation interne sans casser le code externe
     *
     * @var string Le contenu textuel ou binaire de la réponse
     *
     * @see getBody() Méthode pour lire le contenu
     */
    private string $content;

    /**
     * Code de statut HTTP de la réponse.
     *
     * =========================================================================
     * QU'EST-CE QU'UN CODE DE STATUT HTTP ?
     * =========================================================================
     *
     * Le CODE DE STATUT est un nombre à 3 chiffres qui indique le résultat
     * du traitement de la requête. C'est un "code retour" standardisé que
     * tous les navigateurs et applications comprennent.
     *
     * ANALOGIE : C'est comme les feux de signalisation
     * - 2xx (Vert)  = Succès, tout va bien, continuez
     * - 3xx (Orange) = Attention, allez ailleurs (redirection)
     * - 4xx (Rouge) = Erreur de votre part (client)
     * - 5xx (Rouge clignotant) = Erreur de notre part (serveur)
     *
     * ```
     *  CODES LES PLUS UTILISÉS
     *  ───────────────────────
     *
     *  SUCCÈS (2xx)
     *  ┌─────┬────────────────────────────────────────────────────────────────┐
     *  │ 200 │ OK - La requête a réussi. C'est le code par défaut.            │
     *  ├─────┼────────────────────────────────────────────────────────────────┤
     *  │ 201 │ Created - Une ressource a été créée (ex: nouvel utilisateur).  │
     *  ├─────┼────────────────────────────────────────────────────────────────┤
     *  │ 204 │ No Content - Succès, mais rien à renvoyer (ex: suppression).   │
     *  └─────┴────────────────────────────────────────────────────────────────┘
     *
     *  REDIRECTIONS (3xx)
     *  ┌─────┬────────────────────────────────────────────────────────────────┐
     *  │ 301 │ Moved Permanently - L'URL a changé définitivement.             │
     *  │     │ Les moteurs de recherche mettent à jour leurs liens.           │
     *  ├─────┼────────────────────────────────────────────────────────────────┤
     *  │ 302 │ Found - Redirection temporaire (ex: après un formulaire).      │
     *  │     │ L'URL originale reste valide.                                  │
     *  ├─────┼────────────────────────────────────────────────────────────────┤
     *  │ 304 │ Not Modified - Le contenu n'a pas changé, utiliser le cache.   │
     *  └─────┴────────────────────────────────────────────────────────────────┘
     *
     *  ERREURS CLIENT (4xx)
     *  ┌─────┬────────────────────────────────────────────────────────────────┐
     *  │ 400 │ Bad Request - La requête est mal formée (JSON invalide, etc.). │
     *  ├─────┼────────────────────────────────────────────────────────────────┤
     *  │ 401 │ Unauthorized - Authentification requise (pas connecté).        │
     *  ├─────┼────────────────────────────────────────────────────────────────┤
     *  │ 403 │ Forbidden - Accès interdit (connecté mais pas les droits).     │
     *  ├─────┼────────────────────────────────────────────────────────────────┤
     *  │ 404 │ Not Found - La ressource demandée n'existe pas.                │
     *  ├─────┼────────────────────────────────────────────────────────────────┤
     *  │ 422 │ Unprocessable Entity - Données invalides (validation échouée). │
     *  └─────┴────────────────────────────────────────────────────────────────┘
     *
     *  ERREURS SERVEUR (5xx)
     *  ┌─────┬────────────────────────────────────────────────────────────────┐
     *  │ 500 │ Internal Server Error - Bug dans le code, exception non gérée. │
     *  ├─────┼────────────────────────────────────────────────────────────────┤
     *  │ 502 │ Bad Gateway - Problème avec un serveur intermédiaire.          │
     *  ├─────┼────────────────────────────────────────────────────────────────┤
     *  │ 503 │ Service Unavailable - Serveur en maintenance ou surchargé.     │
     *  └─────┴────────────────────────────────────────────────────────────────┘
     * ```
     *
     * POURQUOI C'EST IMPORTANT ?
     * - Le navigateur adapte son comportement selon le code
     * - Les robots (Google) comprennent si une page existe ou non
     * - Les développeurs peuvent diagnostiquer les problèmes
     * - Les outils de monitoring détectent les erreurs
     *
     * @var int Code de statut HTTP (entre 100 et 599)
     *
     * @see getStatusCode() Méthode pour lire le code de statut
     * @see https://developer.mozilla.org/fr/docs/Web/HTTP/Status Liste complète
     */
    private int $statusCode;

    /**
     * En-têtes HTTP de la réponse.
     *
     * =========================================================================
     * QU'EST-CE QU'UN EN-TÊTE HTTP (HTTP Header) ?
     * =========================================================================
     *
     * Les EN-TÊTES sont des MÉTADONNÉES envoyées avec la réponse.
     * Ce sont des informations SUR la réponse, pas le contenu lui-même.
     *
     * ANALOGIE : C'est comme l'enveloppe d'une lettre
     * - L'enveloppe (headers) contient : destinataire, expéditeur, timbre, type d'envoi
     * - La lettre (body) contient : le message réel
     * - Le facteur (navigateur) lit l'enveloppe pour savoir quoi faire de la lettre
     *
     * ```
     *  ┌────────────────────────────────────────────────────────────────────────┐
     *  │  ANATOMIE D'UN EN-TÊTE                                                 │
     *  │                                                                        │
     *  │  Content-Type: application/json; charset=UTF-8                         │
     *  │  ─────────────  ──────────────────────────────                         │
     *  │      │                      │                                          │
     *  │      │                      └── Valeur de l'en-tête                    │
     *  │      │                          (peut avoir des paramètres séparés     │
     *  │      │                           par des points-virgules)              │
     *  │      │                                                                 │
     *  │      └── Nom de l'en-tête                                              │
     *  │          (insensible à la casse : "content-type" = "Content-Type")     │
     *  │                                                                        │
     *  └────────────────────────────────────────────────────────────────────────┘
     * ```
     *
     * EN-TÊTES COURANTS ET LEUR UTILITÉ
     * ---------------------------------
     *
     * CONTENU :
     * - Content-Type : Type MIME du contenu (text/html, application/json...)
     * - Content-Length : Taille en octets (bytes) du corps
     * - Content-Encoding : Compression utilisée (gzip, deflate...)
     *
     * CACHE :
     * - Cache-Control : Instructions de mise en cache (no-cache, max-age=3600...)
     * - ETag : Identifiant unique de version pour le cache
     * - Last-Modified : Date de dernière modification
     *
     * REDIRECTION :
     * - Location : URL de destination pour les codes 3xx
     *
     * COOKIES :
     * - Set-Cookie : Définit un cookie dans le navigateur
     *
     * SÉCURITÉ :
     * - X-Content-Type-Options : Empêche le "sniffing" de type MIME
     * - X-Frame-Options : Protection contre le clickjacking (iframes malveillantes)
     * - Content-Security-Policy : Politique de sécurité du contenu
     * - Strict-Transport-Security : Force l'utilisation de HTTPS
     *
     * FORMAT DANS CETTE CLASSE
     * ------------------------
     * Les headers sont stockés comme des chaînes complètes "Nom: Valeur".
     * C'est le format attendu par la fonction PHP header().
     *
     * ```php
     * $headers = [
     *     'Content-Type: application/json',        // En-tête de type
     *     'X-Custom-Header: ma-valeur',            // En-tête personnalisé
     *     'Set-Cookie: session=abc; HttpOnly',     // Cookie
     * ];
     * ```
     *
     * @var array<int, string> Tableau indexé de chaînes d'en-têtes au format "Nom: Valeur"
     *
     * @see getHeaders() Méthode pour récupérer les en-têtes
     * @see send() Méthode qui envoie les en-têtes au navigateur
     */
    private array $headers;

    /**
     * Crée une nouvelle réponse HTTP.
     *
     * =========================================================================
     * QU'EST-CE QU'UN CONSTRUCTEUR ? (Concept POO)
     * =========================================================================
     *
     * Le CONSTRUCTEUR est une méthode spéciale appelée automatiquement
     * quand on crée un nouvel objet avec "new".
     *
     * En PHP, le constructeur s'appelle toujours __construct().
     *
     * ```php
     * // Quand vous écrivez ceci :
     * $response = new Response('Bonjour', 200, ['Content-Type: text/html']);
     *
     * // PHP fait automatiquement :
     * // 1. Alloue la mémoire pour l'objet
     * // 2. Appelle __construct('Bonjour', 200, ['Content-Type: text/html'])
     * // 3. Retourne l'objet initialisé
     * ```
     *
     * PARAMÈTRES OPTIONNELS AVEC VALEURS PAR DÉFAUT
     * ----------------------------------------------
     * En PHP, on peut donner des valeurs par défaut aux paramètres.
     * Si l'appelant ne fournit pas la valeur, la valeur par défaut est utilisée.
     *
     * ```php
     * // Tous ces appels sont valides :
     *
     * new Response();
     * // → $content = '', $statusCode = 200, $headers = []
     *
     * new Response('Bonjour');
     * // → $content = 'Bonjour', $statusCode = 200, $headers = []
     *
     * new Response('Bonjour', 404);
     * // → $content = 'Bonjour', $statusCode = 404, $headers = []
     *
     * new Response('Bonjour', 200, ['Content-Type: text/plain']);
     * // → Tous les paramètres sont explicitement fournis
     * ```
     *
     * =========================================================================
     * EXEMPLES D'UTILISATION
     * =========================================================================
     *
     * ```php
     * // Réponse HTML simple (code 200 par défaut)
     * $response = new Response('<h1>Bienvenue</h1>');
     *
     * // Réponse d'erreur 404
     * $response = new Response('Page non trouvée', 404);
     *
     * // Réponse JSON pour une API
     * $response = new Response(
     *     json_encode(['success' => true]),
     *     200,
     *     ['Content-Type: application/json']
     * );
     *
     * // Redirection (contenu vide, le navigateur va ailleurs)
     * $response = new Response('', 302, ['Location: /nouvelle-page']);
     * ```
     *
     * @param string            $content    Le contenu (corps) de la réponse.
     *                                      Par défaut : chaîne vide ''.
     *                                      Peut être du HTML, JSON, texte, etc.
     *
     * @param int               $statusCode Le code de statut HTTP.
     *                                      Par défaut : 200 (OK, succès).
     *                                      Valeurs courantes : 200, 201, 301, 302, 400, 401, 403, 404, 500.
     *
     * @param array<int,string> $headers    Les en-têtes HTTP à envoyer.
     *                                      Par défaut : tableau vide [].
     *                                      Format : ['Nom: Valeur', 'Autre: Valeur'].
     *
     * @example Réponse HTML basique
     * ```php
     * $response = new Response('<html><body>Hello</body></html>');
     * ```
     *
     * @example Réponse JSON pour une API REST
     * ```php
     * $response = new Response(
     *     json_encode(['id' => 1, 'name' => 'Produit']),
     *     201,  // Created
     *     ['Content-Type: application/json', 'Location: /api/products/1']
     * );
     * ```
     *
     * @example Redirection après soumission de formulaire (pattern POST-Redirect-GET)
     * ```php
     * // Après un POST réussi, on redirige pour éviter la resoumission
     * $response = new Response('', 302, ['Location: /merci']);
     * ```
     */
    public function __construct(string $content = '', int $statusCode = 200, array $headers = [])
    {
        $this->content = $content;
        $this->statusCode = $statusCode;
        $this->headers = $headers;
    }

    /**
     * Retourne le code de statut HTTP de la réponse.
     *
     * =========================================================================
     * QU'EST-CE QU'UN GETTER ? (Concept POO)
     * =========================================================================
     *
     * Un GETTER est une méthode qui permet de LIRE la valeur d'une propriété
     * privée depuis l'extérieur de la classe.
     *
     * POURQUOI UTILISER UN GETTER AU LIEU D'UNE PROPRIÉTÉ PUBLIQUE ?
     *
     * 1. CONTRÔLE : On peut ajouter de la logique (validation, formatage)
     * 2. PROTECTION : La propriété reste private, impossible de la modifier
     * 3. FLEXIBILITÉ : On peut changer l'implémentation interne sans casser le code externe
     *
     * ```php
     * // ❌ Propriété publique (risqué)
     * class Response {
     *     public int $statusCode;  // N'importe qui peut modifier !
     * }
     * $response->statusCode = -999;  // Valeur invalide acceptée !
     *
     * // ✅ Getter avec propriété privée (sûr)
     * class Response {
     *     private int $statusCode;
     *     public function getStatusCode(): int {
     *         return $this->statusCode;  // Lecture seule
     *     }
     * }
     * $response->statusCode = -999;  // ERREUR ! Propriété privée
     * $code = $response->getStatusCode();  // ✅ OK, lecture autorisée
     * ```
     *
     * CONVENTION DE NOMMAGE
     * ---------------------
     * Par convention, les getters commencent par "get" suivi du nom de la propriété
     * avec la première lettre en majuscule :
     * - $statusCode → getStatusCode()
     * - $content → getContent() ou getBody()
     * - $headers → getHeaders()
     *
     * Pour les booléens, on utilise souvent "is" ou "has" :
     * - $active → isActive()
     * - $permission → hasPermission()
     *
     * =========================================================================
     * UTILISATION
     * =========================================================================
     *
     * ```php
     * $response = new Response('OK', 200);
     *
     * // Lire le code de statut
     * $code = $response->getStatusCode();  // 200
     *
     * // Vérifier si c'est un succès (code 2xx)
     * if ($response->getStatusCode() >= 200 && $response->getStatusCode() < 300) {
     *     echo "La requête a réussi !";
     * }
     *
     * // Vérifier si c'est une erreur client (code 4xx)
     * if ($response->getStatusCode() >= 400 && $response->getStatusCode() < 500) {
     *     echo "Erreur côté client";
     * }
     *
     * // Utilisation dans les tests
     * $this->assertEquals(404, $response->getStatusCode());
     * ```
     *
     * @return int Le code de statut HTTP (100-599).
     *             Exemples : 200 (OK), 404 (Not Found), 500 (Server Error).
     *
     * @see $statusCode Pour la documentation complète des codes de statut
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * Retourne le contenu (corps) de la réponse.
     *
     * =========================================================================
     * POURQUOI "getBody()" ET NON "getContent()" ?
     * =========================================================================
     *
     * Les deux noms sont valides, mais "body" fait référence au vocabulaire HTTP
     * standard où on parle de "response body" (corps de la réponse).
     *
     * Dans le protocole HTTP :
     * - Headers = en-tête du message
     * - Body = corps du message
     *
     * C'est comme une lettre :
     * - L'enveloppe et ses informations = headers
     * - Le contenu de la lettre = body
     *
     * =========================================================================
     * UTILISATION
     * =========================================================================
     *
     * ```php
     * // Créer une réponse
     * $response = new Response('<h1>Bienvenue</h1>');
     *
     * // Lire le contenu
     * $html = $response->getBody();  // '<h1>Bienvenue</h1>'
     *
     * // Vérifier si la réponse est vide
     * if ($response->getBody() === '') {
     *     echo "Réponse sans contenu (probablement une redirection)";
     * }
     *
     * // Utilisation dans les tests
     * $response = $controller->showHomepage();
     * $this->assertStringContainsString('Bienvenue', $response->getBody());
     *
     * // Parser du JSON depuis le body
     * $response = new Response('{"status":"ok"}');
     * $data = json_decode($response->getBody(), true);
     * echo $data['status'];  // 'ok'
     * ```
     *
     * @return string Le contenu textuel ou binaire de la réponse.
     *                Peut être vide (chaîne vide '') pour les redirections
     *                ou les réponses 204 No Content.
     *
     * @see $content Pour plus de détails sur le corps de la réponse
     */
    public function getBody(): string
    {
        return $this->content;
    }

    /**
     * Retourne les en-têtes HTTP de la réponse.
     *
     * =========================================================================
     * FORMAT DES EN-TÊTES RETOURNÉS
     * =========================================================================
     *
     * Cette méthode retourne un tableau de chaînes, chaque chaîne étant
     * un en-tête complet au format "Nom: Valeur".
     *
     * ```php
     * $response = new Response('...', 200, [
     *     'Content-Type: application/json',
     *     'X-Custom-Header: valeur',
     *     'Set-Cookie: session=abc123'
     * ]);
     *
     * $headers = $response->getHeaders();
     * // Retourne :
     * // [
     * //     0 => 'Content-Type: application/json',
     * //     1 => 'X-Custom-Header: valeur',
     * //     2 => 'Set-Cookie: session=abc123'
     * // ]
     * ```
     *
     * TABLEAU INDEXÉ VS TABLEAU ASSOCIATIF
     * ------------------------------------
     * Les headers sont stockés dans un TABLEAU INDEXÉ (avec des clés numériques)
     * et non un tableau associatif (avec des clés textuelles).
     *
     * Pourquoi ? Parce que certains headers peuvent apparaître plusieurs fois !
     * Par exemple, plusieurs Set-Cookie.
     *
     * ```php
     * // ❌ Tableau associatif - problème avec les doublons
     * $headers = [
     *     'Set-Cookie' => 'session=abc',
     *     'Set-Cookie' => 'preferences=xyz',  // Écrase le précédent !
     * ];
     *
     * // ✅ Tableau indexé - plusieurs valeurs possibles
     * $headers = [
     *     'Set-Cookie: session=abc',
     *     'Set-Cookie: preferences=xyz',  // Les deux sont conservés
     * ];
     * ```
     *
     * =========================================================================
     * UTILISATION
     * =========================================================================
     *
     * ```php
     * $response = new Response('...', 200, [
     *     'Content-Type: text/html',
     *     'X-Frame-Options: DENY'
     * ]);
     *
     * // Parcourir les headers
     * foreach ($response->getHeaders() as $header) {
     *     echo $header . "\n";
     * }
     *
     * // Vérifier si un header spécifique existe (recherche basique)
     * $headers = $response->getHeaders();
     * $hasJson = false;
     * foreach ($headers as $header) {
     *     if (str_starts_with($header, 'Content-Type: application/json')) {
     *         $hasJson = true;
     *         break;
     *     }
     * }
     *
     * // Utilisation dans les tests
     * $this->assertContains('Content-Type: application/json', $response->getHeaders());
     * ```
     *
     * @return array<int, string> Tableau indexé des en-têtes au format "Nom: Valeur".
     *                            Peut être vide si aucun en-tête n'a été défini.
     *
     * @see $headers Pour plus de détails sur les en-têtes HTTP
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * Envoie la réponse HTTP au client (navigateur).
     *
     * =========================================================================
     * QUE FAIT CETTE MÉTHODE ?
     * =========================================================================
     *
     * Cette méthode est le POINT FINAL du cycle requête/réponse.
     * Elle envoie physiquement la réponse au navigateur du visiteur.
     *
     * Elle effectue 3 opérations dans l'ordre :
     *
     * 1. DÉFINIT LE CODE DE STATUT HTTP
     *    Via http_response_code($this->statusCode)
     *    → Le serveur annonce "200 OK" ou "404 Not Found", etc.
     *
     * 2. ENVOIE CHAQUE EN-TÊTE
     *    Via header($header) pour chaque header
     *    → Content-Type, Set-Cookie, Location, etc.
     *
     * 3. ENVOIE LE CORPS DE LA RÉPONSE
     *    Via echo $this->content
     *    → Le HTML, JSON, ou autre contenu visible
     *
     * ```
     *  ORDRE D'ENVOI (CRITIQUE !)
     *  ─────────────────────────────────────────────────────────────
     *
     *  1. Code de statut ──► http_response_code(200)
     *           │
     *           ▼
     *  2. En-têtes ────────► header('Content-Type: text/html')
     *           │            header('X-Frame-Options: DENY')
     *           │
     *           ▼
     *  3. Ligne vide ──────► (automatique, sépare headers du body)
     *           │
     *           ▼
     *  4. Corps ───────────► echo '<html>...</html>'
     *
     *  ─────────────────────────────────────────────────────────────
     *  IMPORTANT : Une fois le corps commencé (echo), impossible
     *  d'envoyer d'autres headers ! PHP lève une erreur.
     * ```
     *
     * =========================================================================
     * FONCTIONS PHP UTILISÉES
     * =========================================================================
     *
     * http_response_code(int $code): int|bool
     * ----------------------------------------
     * Définit ou récupère le code de statut HTTP de la réponse.
     *
     * ```php
     * http_response_code(404);  // Définit le code à 404
     * $code = http_response_code();  // Retourne le code actuel
     * ```
     *
     * header(string $header, bool $replace = true): void
     * ---------------------------------------------------
     * Envoie un en-tête HTTP brut.
     *
     * ```php
     * header('Content-Type: application/json');
     * header('Set-Cookie: session=abc123');
     * header('Location: /autre-page');  // Redirection
     * ```
     *
     * ATTENTION : header() doit être appelé AVANT tout echo/print !
     * Sinon : "Cannot modify header information - headers already sent"
     *
     * echo
     * ----
     * Envoie du texte au navigateur (le corps de la réponse).
     *
     * =========================================================================
     * POURQUOI UTILISER send() PLUTÔT QUE echo DIRECTEMENT ?
     * =========================================================================
     *
     * 1. ORDRE GARANTI
     *    send() garantit que les headers sont envoyés avant le body.
     *    Pas de risque d'erreur "headers already sent".
     *
     * 2. ENCAPSULATION
     *    Toute la logique d'envoi est au même endroit.
     *    Facile à maintenir et à modifier.
     *
     * 3. TESTABILITÉ
     *    On peut créer une Response, vérifier son contenu avec getBody(),
     *    sans jamais appeler send(). Parfait pour les tests unitaires !
     *
     * 4. MIDDLEWARES
     *    Les middlewares peuvent modifier la Response avant l'envoi.
     *    Exemple : ajouter automatiquement des headers de sécurité.
     *
     * ```php
     * // Dans un middleware de sécurité
     * public function process(Request $request, Handler $handler): Response
     * {
     *     $response = $handler->handle($request);
     *
     *     // Modifier la réponse avant l'envoi
     *     // (Note: nécessiterait une méthode addHeader() pour être propre)
     *
     *     return $response;
     * }
     * ```
     *
     * =========================================================================
     * QUAND APPELER send() ?
     * =========================================================================
     *
     * send() doit être appelé UNE SEULE FOIS, à la FIN du cycle de requête.
     * C'est généralement le framework qui s'en charge, pas votre code applicatif.
     *
     * ```php
     * // Dans le FrontController du framework
     * public function handle(Request $request): void
     * {
     *     // 1. Router trouve le bon contrôleur
     *     // 2. Contrôleur retourne une Response
     *     // 3. Middlewares modifient éventuellement la Response
     *
     *     $response = $this->processRequest($request);
     *
     *     // 4. Envoi final au navigateur
     *     $response->send();  // ◄── Appelé une seule fois ici
     * }
     * ```
     *
     * =========================================================================
     * ATTENTION : EFFETS DE BORD
     * =========================================================================
     *
     * Cette méthode a des EFFETS DE BORD (side effects), c'est-à-dire qu'elle
     * modifie l'état externe du programme (envoie des données au navigateur).
     *
     * Après l'appel à send() :
     * - Les headers ont été envoyés → impossible d'en ajouter d'autres
     * - Le body a été envoyé → il est affiché dans le navigateur
     * - Le script peut continuer, mais ne devrait rien afficher de plus
     *
     * @return void Cette méthode ne retourne rien (void).
     *              Elle produit une sortie (output) vers le navigateur.
     *
     * @see http_response_code() Fonction PHP pour le code de statut
     * @see header() Fonction PHP pour envoyer des en-têtes
     *
     * @example Envoi simple
     * ```php
     * $response = new Response('<h1>Hello World</h1>');
     * $response->send();
     * // Le navigateur affiche maintenant "Hello World"
     * ```
     *
     * @example Redirection (le navigateur change automatiquement de page)
     * ```php
     * $response = new Response('', 302, ['Location: /dashboard']);
     * $response->send();
     * // Le navigateur est redirigé vers /dashboard
     * ```
     */
    public function send(): void
    {
        // 1. Définit le code de statut HTTP (200, 404, 500, etc.)
        //    Le serveur web l'inclura dans la ligne de statut de la réponse
        http_response_code($this->statusCode);

        // 2. Envoie chaque en-tête HTTP
        //    DOIT être fait AVANT tout echo/print !
        foreach ($this->headers as $header) {
            header($header);
        }

        // 3. Envoie le corps de la réponse
        //    C'est ce que le visiteur verra dans son navigateur
        echo $this->content;
    }
}
