<?php
/**
 * Lunar Quanta Framework - Exception de Base.
 *
 * =============================================================================
 * QU'EST-CE QU'UNE EXCEPTION ?
 * =============================================================================
 *
 * Une EXCEPTION est un mécanisme qui permet de signaler et gérer les ERREURS
 * dans un programme de manière structurée.
 *
 * ANALOGIE : L'alarme incendie
 *
 * Imaginez un immeuble avec une alarme incendie :
 * - Quand un détecteur détecte de la fumée, il "lance" l'alarme
 * - L'alarme remonte jusqu'au système central
 * - Le système décide quoi faire (évacuer, appeler les pompiers...)
 *
 * ```
 * EXCEPTION = ALARME
 *
 *    Problème détecté                    Traitement de l'erreur
 *    (throw exception)                   (try/catch)
 *           │                                   │
 *           ▼                                   │
 *    ┌─────────────┐                           │
 *    │  EXCEPTION  │  "Quelque chose           │
 *    │   LANCÉE    │   ne va pas !"            │
 *    └─────────────┘                           │
 *           │                                   │
 *           └───────── remonte ─────────────────┘
 *                                               │
 *                                               ▼
 *                                        ┌─────────────┐
 *                                        │ catch {}    │
 *                                        │ "Je gère    │
 *                                        │  l'erreur"  │
 *                                        └─────────────┘
 * ```
 *
 * =============================================================================
 * SANS EXCEPTION vs AVEC EXCEPTION
 * =============================================================================
 *
 * ```php
 * // ❌ SANS EXCEPTION (ancien style, moins propre)
 * function diviser($a, $b) {
 *     if ($b == 0) {
 *         return false;  // Comment savoir que c'est une erreur ?
 *     }
 *     return $a / $b;
 * }
 *
 * $resultat = diviser(10, 0);
 * if ($resultat === false) {
 *     echo "Erreur !";  // Confusion possible : false peut être un résultat valide
 * }
 *
 * // ✅ AVEC EXCEPTION (moderne, clair)
 * function diviser($a, $b) {
 *     if ($b == 0) {
 *         throw new \InvalidArgumentException("Division par zéro !");
 *     }
 *     return $a / $b;
 * }
 *
 * try {
 *     $resultat = diviser(10, 0);
 *     echo "Résultat : $resultat";
 * } catch (\InvalidArgumentException $e) {
 *     echo "Erreur : " . $e->getMessage();  // "Division par zéro !"
 * }
 * ```
 *
 * =============================================================================
 * POURQUOI UNE EXCEPTION DE BASE (LunarException) ?
 * =============================================================================
 *
 * Toutes les exceptions du framework héritent de LunarException pour :
 *
 * 1. IDENTIFICATION : Distinguer les erreurs du framework des erreurs PHP
 * 2. COHÉRENCE : Toutes les exceptions framework se comportent pareil
 * 3. GESTION GLOBALE : Attraper toutes les erreurs framework d'un coup
 *
 * ```
 * HIÉRARCHIE DES EXCEPTIONS
 *
 *    \Exception (PHP standard)
 *         │
 *         └── LunarException (base framework)
 *                  │
 *                  ├── RouterException     (erreurs de routage)
 *                  ├── ContainerException  (erreurs d'injection)
 *                  ├── TemplateException   (erreurs de rendu)
 *                  ├── SecurityException   (erreurs de sécurité)
 *                  └── StorageException    (erreurs de stockage)
 * ```
 *
 * =============================================================================
 * COMMENT ATTRAPER LES EXCEPTIONS ?
 * =============================================================================
 *
 * ```php
 * // Attraper TOUTES les exceptions du framework
 * try {
 *     $router->dispatch($request);
 * } catch (LunarException $e) {
 *     // Toutes les erreurs framework passent ici
 *     error_log("Erreur Lunar : " . $e->getMessage());
 * }
 *
 * // Attraper une exception SPÉCIFIQUE
 * try {
 *     $router->dispatch($request);
 * } catch (RouterException $e) {
 *     // Seulement les erreurs de routage (404, etc.)
 *     return new Response('Page non trouvée', 404);
 * } catch (LunarException $e) {
 *     // Autres erreurs framework
 *     return new Response('Erreur serveur', 500);
 * }
 * ```
 *
 * =============================================================================
 * LES INFORMATIONS D'UNE EXCEPTION
 * =============================================================================
 *
 * Chaque exception contient :
 *
 * ```php
 * try {
 *     throw new LunarException("Quelque chose ne va pas", 500);
 * } catch (LunarException $e) {
 *     $e->getMessage();   // "Quelque chose ne va pas"
 *     $e->getCode();      // 500
 *     $e->getFile();      // "/path/to/file.php"
 *     $e->getLine();      // 42 (ligne où l'exception a été lancée)
 *     $e->getTrace();     // Pile d'appels (debug)
 * }
 * ```
 *
 * @package    Lunar\Exception
 * @author     Seb <seb@nethttp.net>
 * @copyright  2024 Lunar Quanta
 * @license    MIT License
 * @version    1.1.0
 * @link       https://nethttp.net
 * @since      0.0.1
 *
 * @see RouterException Exception de routage
 * @see ContainerException Exception d'injection de dépendances
 * @see TemplateException Exception de rendu de template
 * @see SecurityException Exception de sécurité
 * @see StorageException Exception de stockage
 */
declare(strict_types=1);

namespace Lunar\Exception;

/**
 * Exception de base pour le framework Lunar Quanta.
 *
 * Toutes les exceptions spécifiques au framework doivent hériter de cette
 * classe pour permettre une gestion cohérente des erreurs.
 *
 * =============================================================================
 * UTILISATION
 * =============================================================================
 *
 * ```php
 * // Lancer une exception générique du framework
 * throw new LunarException("Configuration invalide");
 *
 * // Avec un code d'erreur
 * throw new LunarException("Route non trouvée", 404);
 *
 * // Avec une exception cause (chaînage)
 * try {
 *     $pdo->query("...");
 * } catch (\PDOException $e) {
 *     throw new LunarException("Erreur base de données", 500, $e);
 * }
 * ```
 *
 * =============================================================================
 * GESTION GLOBALE DES ERREURS
 * =============================================================================
 *
 * ```php
 * // Dans le point d'entrée (index.php)
 * try {
 *     $response = $frontController->handle($request);
 *     $response->send();
 * } catch (LunarException $e) {
 *     // Erreur framework : afficher une page d'erreur propre
 *     $errorResponse = new Response(
 *         "Une erreur est survenue : " . $e->getMessage(),
 *         500
 *     );
 *     $errorResponse->send();
 * } catch (\Throwable $e) {
 *     // Erreur PHP inattendue
 *     error_log($e->getMessage());
 *     http_response_code(500);
 *     echo "Erreur interne du serveur";
 * }
 * ```
 *
 * @package Lunar\Exception
 */
class LunarException extends \Exception
{
}
