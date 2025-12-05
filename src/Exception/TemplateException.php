<?php
/**
 * Lunar Quanta Framework - Exception de Rendu de Template.
 *
 * =============================================================================
 * QUAND CETTE EXCEPTION EST-ELLE LANCÉE ?
 * =============================================================================
 *
 * TemplateException est lancée pour toutes les erreurs liées au
 * RENDU DES TEMPLATES (fichiers HTML, vues).
 *
 * CAS D'UTILISATION :
 *
 * 1. FICHIER TEMPLATE NON TROUVÉ
 *    Le fichier .html ou .php spécifié n'existe pas.
 *
 * 2. ERREUR DE SYNTAXE DANS LE TEMPLATE
 *    Le template contient une erreur qui empêche son rendu.
 *
 * 3. VARIABLE MANQUANTE
 *    Une variable attendue par le template n'a pas été passée.
 *
 * 4. ERREUR D'INCLUSION
 *    Un template inclus (partiel) n'existe pas ou cause une erreur.
 *
 * ```
 * EXEMPLES DE SITUATIONS
 *
 *    // Template demandé : "users/profile.html"
 *
 *    Structure des templates :
 *    templates/
 *    ├── home.html         ✓ existe
 *    ├── users/
 *    │   ├── list.html     ✓ existe
 *    │   └── edit.html     ✓ existe
 *    └── (profile.html)    ✗ N'EXISTE PAS !
 *
 *    → TemplateException : "Template 'users/profile.html' not found"
 * ```
 *
 * =============================================================================
 * COMMENT GÉRER CETTE EXCEPTION ?
 * =============================================================================
 *
 * ```php
 * // Dans un contrôleur
 * public function profile(Request $request): Response
 * {
 *     try {
 *         $html = $this->render('users/profile.html', [
 *             'user' => $user,
 *         ]);
 *         return new Response($html);
 *     } catch (TemplateException $e) {
 *         // Template manquant ou erreur de rendu
 *         error_log("Erreur template : " . $e->getMessage());
 *         return new Response('Erreur d\'affichage', 500);
 *     }
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
 * @see \Lunar\Service\Core\Template\LunarTemplateAdapter Le moteur de templates
 * @see LunarException Classe parente
 */
declare(strict_types=1);

namespace Lunar\Exception;

/**
 * Exception lancée pour les erreurs de rendu de templates.
 *
 * Cette exception signale des problèmes lors du rendu des vues :
 * - Fichier template introuvable
 * - Erreur de syntaxe dans le template
 * - Variable manquante
 * - Erreur lors de l'inclusion d'un partiel
 *
 * =============================================================================
 * EXEMPLES D'UTILISATION
 * =============================================================================
 *
 * ```php
 * // Dans le moteur de templates
 * public function render(string $template, array $data = []): string
 * {
 *     $path = $this->templatesPath . '/' . $template;
 *
 *     if (!file_exists($path)) {
 *         throw new TemplateException(
 *             "Le template '$template' n'existe pas dans '$this->templatesPath'"
 *         );
 *     }
 *
 *     // ...
 * }
 *
 * // Gestion dans l'application
 * try {
 *     return $this->render('dashboard.html', $data);
 * } catch (TemplateException $e) {
 *     // Afficher une page d'erreur générique
 *     return $this->render('error.html', [
 *         'message' => 'Impossible d\'afficher cette page',
 *     ]);
 * }
 * ```
 *
 * @package Lunar\Exception
 */
class TemplateException extends LunarException
{
}
