<?php
/**
 * Lunar Quanta Framework - Contrôleur de Gestion des Utilisateurs.
 *
 * =============================================================================
 * QU'EST-CE QU'UN CONTRÔLEUR CRUD ?
 * =============================================================================
 *
 * CRUD signifie : Create, Read, Update, Delete (Créer, Lire, Modifier, Supprimer).
 * Ce sont les 4 opérations de base sur des données.
 *
 * ```
 * ┌───────────────────────────────────────────────────────────────────────────┐
 * │                           OPÉRATIONS CRUD                                 │
 * ├──────────┬────────────┬─────────────────────────────────────────────────┤
 * │ Opération│ Méthode HTTP│ Description                                     │
 * ├──────────┼────────────┼─────────────────────────────────────────────────┤
 * │ CREATE   │ POST       │ Créer un nouvel enregistrement                  │
 * │ READ     │ GET        │ Lire/afficher un ou plusieurs enregistrements   │
 * │ UPDATE   │ PUT/PATCH  │ Modifier un enregistrement existant             │
 * │ DELETE   │ DELETE     │ Supprimer un enregistrement                     │
 * └──────────┴────────────┴─────────────────────────────────────────────────┘
 * ```
 *
 * =============================================================================
 * FLUX D'UN FORMULAIRE (GET + POST)
 * =============================================================================
 *
 * ```
 * ÉTAPE 1 : L'utilisateur veut créer un compte (GET /user)
 *
 *    ┌──────────────┐     GET /user     ┌────────────────┐
 *    │  Navigateur  │─────────────────▶│ UserController │
 *    └──────────────┘                  │   index()      │
 *           │                          └───────┬────────┘
 *           │                                  │
 *           │                                  ▼
 *           │                          ┌────────────────┐
 *           │                          │   Template     │
 *           │                          │  user_form.html│
 *           │                          └───────┬────────┘
 *           │                                  │
 *           ▼                                  ▼
 *    ┌──────────────────────────────────────────────────┐
 *    │           FORMULAIRE AFFICHÉ                      │
 *    │  ┌──────────────────────────────────────────┐    │
 *    │  │ Email:    [________________]             │    │
 *    │  │ Nom:      [________________]             │    │
 *    │  │ Mot de passe: [____________]             │    │
 *    │  │                                          │    │
 *    │  │           [Créer le compte]              │    │
 *    │  └──────────────────────────────────────────┘    │
 *    └──────────────────────────────────────────────────┘
 *
 *
 * ÉTAPE 2 : L'utilisateur remplit et soumet le formulaire (POST /user)
 *
 *    ┌──────────────────────────────────────────────────┐
 *    │  Email:    alice@example.com                     │
 *    │  Nom:      Alice Dupont                          │
 *    │  Mot de passe: ********                          │
 *    │             [Créer le compte] ← CLIC !           │
 *    └──────────────────────────────────────────────────┘
 *           │
 *           │  POST /user
 *           │  Body: email=alice@example.com&name=Alice&password=...
 *           │
 *           ▼
 *    ┌────────────────┐
 *    │ UserController │
 *    │   index()      │
 *    │                │
 *    │ 1. Valider     │ → Email valide ? Nom présent ? Mot de passe ok ?
 *    │ 2. Créer User  │ → new User($email, $name, $password)
 *    │ 3. Sauvegarder │ → $storage->saveUser($user)
 *    │ 4. Confirmer   │ → render('confirmation')
 *    └────────────────┘
 *           │
 *           ▼
 *    ┌──────────────────────────────────────────────────┐
 *    │        PAGE DE CONFIRMATION                       │
 *    │                                                  │
 *    │   ✓ Le compte a été créé avec succès !          │
 *    │                                                  │
 *    │   [Aller à l'accueil]                            │
 *    └──────────────────────────────────────────────────┘
 * ```
 *
 * =============================================================================
 * POURQUOI GET ET POST SUR LA MÊME ROUTE ?
 * =============================================================================
 *
 * ```
 * C'est un pattern courant pour les formulaires :
 *
 *    MÊME URL : /user
 *
 *    ┌─────────────────────────────────────────────────────────────┐
 *    │                                                             │
 *    │   GET /user  ─────▶  Afficher le formulaire vide            │
 *    │                                                             │
 *    │   POST /user ─────▶  Traiter les données soumises           │
 *    │                                                             │
 *    └─────────────────────────────────────────────────────────────┘
 *
 * AVANTAGES :
 * - Une seule URL à retenir
 * - Le formulaire pointe vers lui-même (<form action="/user">)
 * - Facile de ré-afficher le formulaire avec les erreurs
 * ```
 *
 * =============================================================================
 * VALIDATION DES DONNÉES : POURQUOI ET COMMENT ?
 * =============================================================================
 *
 * ```
 * RÈGLE D'OR : Ne JAMAIS faire confiance aux données utilisateur !
 *
 * L'utilisateur peut :
 * - Faire des erreurs de frappe
 * - Soumettre des champs vides
 * - Tenter des attaques (injection SQL, XSS...)
 *
 * VALIDATION EN PHP :
 *
 *    // Valider un email
 *    $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
 *    // Retourne l'email si valide, false sinon
 *
 *    // Nettoyer une chaîne
 *    $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
 *    // Supprime les caractères dangereux
 *
 * EXEMPLE DE VALIDATION COMPLÈTE :
 *
 *    $errors = [];
 *
 *    if (!$email) {
 *        $errors['email'] = "L'email n'est pas valide";
 *    }
 *
 *    if (empty($name)) {
 *        $errors['name'] = "Le nom est obligatoire";
 *    }
 *
 *    if (strlen($password) < 8) {
 *        $errors['password'] = "Le mot de passe doit faire au moins 8 caractères";
 *    }
 *
 *    if (!empty($errors)) {
 *        // Ré-afficher le formulaire avec les erreurs
 *    }
 * ```
 *
 * =============================================================================
 * LE SERVICE DE STOCKAGE (JsonStorage)
 * =============================================================================
 *
 * ```
 * Le JsonStorage sauvegarde les données dans des fichiers JSON.
 * C'est simple pour commencer, mais en production on utilise une base de données.
 *
 * FICHIER JSON GÉNÉRÉ :
 *
 *    /var/data/users.json
 *    ┌────────────────────────────────────────────────────┐
 *    │ [                                                  │
 *    │   {                                                │
 *    │     "email": "alice@example.com",                  │
 *    │     "name": "Alice Dupont",                        │
 *    │     "password": "$2y$10$..."  (hashé !)            │
 *    │   },                                               │
 *    │   {                                                │
 *    │     "email": "bob@example.com",                    │
 *    │     "name": "Bob Martin",                          │
 *    │     "password": "$2y$10$..."                       │
 *    │   }                                                │
 *    │ ]                                                  │
 *    └────────────────────────────────────────────────────┘
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
 * @see \Lunar\Entity\User L'entité User
 * @see \Lunar\Service\Storage\JsonStorage Le service de stockage
 * @see \Lunar\Service\Core\BaseController La classe parente
 */
declare(strict_types=1);

namespace Lunar\Controller;

use Lunar\Attribute\Route;
use Lunar\Entity\User;
use Lunar\Service\Core\BaseController;
use Lunar\Service\Core\Http\Request;
use Lunar\Service\Core\Http\Response;
use Lunar\Service\Storage\JsonStorage;

/**
 * Contrôleur pour la gestion des utilisateurs.
 *
 * Ce contrôleur gère la création d'utilisateurs via un formulaire.
 * Il démontre le pattern GET (afficher) + POST (traiter) sur une même route.
 *
 * =============================================================================
 * PRÉFIXE DE ROUTE (OPTIONNEL)
 * =============================================================================
 *
 * On peut ajouter un préfixe à toutes les routes d'un contrôleur :
 *
 * ```php
 * // Si on décommente ceci :
 * #[Route('/user')]
 * class UserController extends BaseController
 * {
 *     #[Route('/create')]  // Devient : /user/create
 *     #[Route('/list')]    // Devient : /user/list
 *     #[Route('/{id}')]    // Devient : /user/{id}
 * }
 * ```
 *
 * =============================================================================
 * INJECTION DE DÉPENDANCES
 * =============================================================================
 *
 * ```
 * DANS CET EXEMPLE (simple mais pas idéal) :
 *
 *    Le constructeur crée directement le JsonStorage.
 *    → Fonctionne mais difficile à tester
 *
 * MEILLEURE APPROCHE (injection) :
 *
 *    public function __construct(JsonStorage $storage)
 *    {
 *        $this->storage = $storage;
 *    }
 *
 *    → Le conteneur DI fournit le storage
 *    → Facile à mocker pour les tests
 * ```
 *
 * @package Lunar\Controller
 */
class UserController extends BaseController
{
    /**
     * Service de stockage pour sauvegarder les utilisateurs.
     *
     * JsonStorage sauvegarde les données dans des fichiers JSON.
     * En production, on remplacerait par un repository de base de données.
     *
     * @var JsonStorage
     */
    private JsonStorage $storage;

    /**
     * Constructeur du contrôleur utilisateur.
     *
     * Initialise le service de stockage JSON pour la persistance des données.
     *
     * ```php
     * // Ce qui se passe à l'instanciation :
     * $controller = new UserController();
     * // 1. Appelle parent::__construct() (BaseController)
     * // 2. Crée une instance de JsonStorage
     * ```
     *
     * @see JsonStorage Le service de stockage utilisé
     */
    public function __construct()
    {
        parent::__construct();
        $this->storage = new JsonStorage();
    }

    /**
     * Affiche le formulaire de création OU traite la soumission.
     *
     * Cette méthode gère deux cas distincts selon la méthode HTTP :
     * - GET : Affiche le formulaire vide
     * - POST : Traite les données soumises et crée l'utilisateur
     *
     * ==========================================================================
     * L'ATTRIBUT #[Route] EXPLIQUÉ
     * ==========================================================================
     *
     * ```php
     * #[Route('/user', methods: ['GET', 'POST'], name: 'user.index')]
     * ```
     *
     * - `/user` : L'URL qui déclenche cette action
     * - `methods: ['GET', 'POST']` : Accepte les deux méthodes
     * - `name: 'user.index'` : Nom pour générer l'URL
     *
     * ```
     * CONVENTION DE NOMMAGE DES ROUTES :
     *
     *    resource.action
     *    │         │
     *    │         └── Ce qu'on fait (index, show, create, edit, delete)
     *    │
     *    └── La ressource (user, product, order)
     *
     * Exemples :
     *    user.index  → Liste / formulaire création
     *    user.show   → Afficher un utilisateur
     *    user.edit   → Formulaire d'édition
     *    user.delete → Supprimer un utilisateur
     * ```
     *
     * ==========================================================================
     * FLUX DE LA MÉTHODE
     * ==========================================================================
     *
     * ```
     * index($request)
     *     │
     *     ▼
     * ┌─────────────────────────────────────────┐
     * │ $request->getMethod() == 'POST' ?       │
     * └─────────────────────────────────────────┘
     *     │                    │
     *     │ OUI                │ NON
     *     ▼                    ▼
     * ┌───────────────┐    ┌───────────────┐
     * │ TRAITEMENT    │    │ AFFICHAGE     │
     * │ DU FORMULAIRE │    │ DU FORMULAIRE │
     * │               │    │               │
     * │ 1. Valider    │    │ Rendre        │
     * │ 2. Créer User │    │ user_form.html│
     * │ 3. Sauvegarder│    │               │
     * │ 4. Confirmer  │    │               │
     * └───────────────┘    └───────────────┘
     * ```
     *
     * ==========================================================================
     * PARAMÈTRES
     * ==========================================================================
     *
     * @param Request $request L'objet Request contenant :
     *                         - La méthode HTTP (GET ou POST)
     *                         - Les données POST soumises
     *                         - L'URL demandée
     *
     * @return Response La réponse HTTP contenant :
     *                  - En GET : le formulaire HTML
     *                  - En POST valide : la page de confirmation
     *                  - En POST invalide : la page d'erreur
     *
     * ==========================================================================
     * VALIDATION DES DONNÉES
     * ==========================================================================
     *
     * ```php
     * // FILTER_VALIDATE_EMAIL : Vérifie que c'est un email valide
     * $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
     * // "alice@example.com" → "alice@example.com"
     * // "pas-un-email"      → false
     *
     * // FILTER_SANITIZE_STRING : Nettoie la chaîne (enlève les tags HTML)
     * $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
     * // "<script>alert('XSS')</script>Alice" → "Alice"
     * ```
     *
     * ==========================================================================
     * CRÉATION DE L'UTILISATEUR
     * ==========================================================================
     *
     * ```php
     * // L'entité User encapsule les données d'un utilisateur
     * $user = new User($email, $name, $password);
     *
     * // Le storage sauvegarde l'utilisateur dans un fichier JSON
     * $this->storage->saveUser($user);
     * ```
     *
     * ==========================================================================
     * TEMPLATES UTILISÉS
     * ==========================================================================
     *
     * ```
     * ┌─────────────────┬────────────────────────────────────────────┐
     * │ Template        │ Quand                                      │
     * ├─────────────────┼────────────────────────────────────────────┤
     * │ user_form       │ GET (afficher le formulaire)               │
     * │ confirmation    │ POST valide (utilisateur créé)             │
     * │ error           │ POST invalide (données incorrectes)        │
     * └─────────────────┴────────────────────────────────────────────┘
     * ```
     */
    #[Route('/user', methods: ['GET', 'POST'], name: 'user.index')]
    public function index(Request $request): Response
    {
        // =====================================================================
        // CAS POST : Traitement du formulaire soumis
        // =====================================================================
        if ('POST' === $request->getMethod()) {
            // Récupérer et valider les données du formulaire
            // filter_input() retourne false si la validation échoue
            $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
            $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
            $password = filter_input(INPUT_POST, 'password', FILTER_SANITIZE_STRING);

            // Vérifier que toutes les données sont valides
            if ($email && $name && $password) {
                // Créer l'entité User avec les données validées
                $user = new User($email, $name, $password);

                // Sauvegarder l'utilisateur via le service de stockage
                $this->storage->saveUser($user);

                // Afficher la page de confirmation
                $html = $this->render('confirmation', [
                    'title' => 'User Created',
                    'content' => 'The user has been created successfully.',
                ]);

                return new Response($html);
            }

            // Si les données sont invalides, afficher une erreur
            $html = $this->render('error', [
                'title' => 'Invalid Input',
                'content' => 'Please check your input.',
            ]);

            return new Response($html);
        }

        // =====================================================================
        // CAS GET : Affichage du formulaire vide
        // =====================================================================
        $html = $this->render('user_form', [
            'title' => 'Create User',
        ]);

        return new Response($html);
    }
}
