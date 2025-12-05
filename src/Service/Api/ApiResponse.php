<?php

declare(strict_types=1);

namespace Lunar\Service\Api;

use Lunar\Service\Core\Http\Response;

/**
 * Helper pour créer des réponses API JSON standardisées.
 *
 * Toutes les réponses API suivent une structure cohérente :
 *
 * **Succès :**
 * ```json
 * {
 *     "success": true,
 *     "data": { ... }
 * }
 * ```
 *
 * **Erreur :**
 * ```json
 * {
 *     "success": false,
 *     "error": {
 *         "code": "ERROR_CODE",
 *         "message": "Human readable message"
 *     }
 * }
 * ```
 *
 * **Collection paginée :**
 * ```json
 * {
 *     "success": true,
 *     "data": [ ... ],
 *     "meta": {
 *         "total": 100,
 *         "page": 1,
 *         "per_page": 10,
 *         "total_pages": 10,
 *         "has_more": true
 *     }
 * }
 * ```
 *
 * @example
 * ```php
 * // Succès simple
 * return ApiResponse::success(['id' => 1, 'name' => 'John']);
 *
 * // Création avec location
 * return ApiResponse::created(['id' => 42], '/api/users/42');
 *
 * // Erreur de validation
 * return ApiResponse::validationError([
 *     'email' => ['Email is required'],
 *     'password' => ['Password too short']
 * ]);
 *
 * // Collection paginée
 * return ApiResponse::paginated($users, total: 100, page: 1, perPage: 10);
 * ```
 */
final class ApiResponse
{
    /**
     * Options JSON par défaut.
     * - UNESCAPED_UNICODE : garde les accents lisibles
     * - UNESCAPED_SLASHES : garde les URLs lisibles
     */
    private const JSON_OPTIONS = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;

    /**
     * Réponse de succès avec données.
     *
     * @param mixed $data Les données à retourner
     * @param int $statusCode Code HTTP (default: 200)
     * @param bool $prettyPrint Formater le JSON pour la lisibilité
     */
    public static function success(
        mixed $data,
        int $statusCode = 200,
        bool $prettyPrint = false
    ): Response {
        return self::json([
            'success' => true,
            'data' => $data,
        ], $statusCode, [], $prettyPrint);
    }

    /**
     * Réponse de création réussie (201 Created).
     *
     * @param mixed $data Les données de la ressource créée
     * @param string|null $location URL de la nouvelle ressource
     */
    public static function created(mixed $data, ?string $location = null): Response
    {
        $headers = [];
        if ($location !== null) {
            $headers['Location'] = $location;
        }

        return self::json([
            'success' => true,
            'data' => $data,
        ], 201, $headers);
    }

    /**
     * Réponse sans contenu (204 No Content).
     *
     * Utilisé après une suppression réussie par exemple.
     */
    public static function noContent(): Response
    {
        return new Response('', 204, []);
    }

    /**
     * Réponse d'erreur générique.
     *
     * @param string $message Message d'erreur lisible
     * @param string $code Code d'erreur machine
     * @param int $statusCode Code HTTP
     * @param array<string, mixed> $details Détails supplémentaires
     */
    public static function error(
        string $message,
        string $code,
        int $statusCode = 400,
        array $details = []
    ): Response {
        $error = [
            'code' => $code,
            'message' => $message,
        ];

        if (!empty($details)) {
            $error['details'] = $details;
        }

        return self::json([
            'success' => false,
            'error' => $error,
        ], $statusCode);
    }

    /**
     * Erreur 400 Bad Request.
     */
    public static function badRequest(string $message = 'Bad request'): Response
    {
        return self::error($message, 'BAD_REQUEST', 400);
    }

    /**
     * Erreur 401 Unauthorized.
     */
    public static function unauthorized(string $message = 'Unauthorized'): Response
    {
        return self::error($message, 'UNAUTHORIZED', 401);
    }

    /**
     * Erreur 403 Forbidden.
     */
    public static function forbidden(string $message = 'Forbidden'): Response
    {
        return self::error($message, 'FORBIDDEN', 403);
    }

    /**
     * Erreur 404 Not Found.
     */
    public static function notFound(string $message = 'Not found'): Response
    {
        return self::error($message, 'NOT_FOUND', 404);
    }

    /**
     * Erreur 409 Conflict.
     */
    public static function conflict(string $message = 'Conflict'): Response
    {
        return self::error($message, 'CONFLICT', 409);
    }

    /**
     * Erreur 422 Validation Error.
     *
     * @param array<string, array<string>> $errors Erreurs par champ
     */
    public static function validationError(
        array $errors,
        string $message = 'Validation failed'
    ): Response {
        return self::json([
            'success' => false,
            'error' => [
                'code' => 'VALIDATION_ERROR',
                'message' => $message,
                'details' => $errors,
            ],
        ], 422);
    }

    /**
     * Erreur 500 Internal Server Error.
     */
    public static function internalError(string $message = 'Internal server error'): Response
    {
        return self::error($message, 'INTERNAL_ERROR', 500);
    }

    /**
     * Réponse paginée pour les collections.
     *
     * @param array<mixed> $items Les éléments de la page courante
     * @param int $total Nombre total d'éléments
     * @param int $page Page courante (1-indexed)
     * @param int $perPage Nombre d'éléments par page
     */
    public static function paginated(
        array $items,
        int $total,
        int $page,
        int $perPage
    ): Response {
        $totalPages = (int) ceil($total / $perPage);

        return self::json([
            'success' => true,
            'data' => $items,
            'meta' => [
                'total' => $total,
                'page' => $page,
                'per_page' => $perPage,
                'total_pages' => $totalPages,
                'has_more' => $page < $totalPages,
            ],
        ]);
    }

    /**
     * Crée une réponse JSON.
     *
     * @param array<string, mixed> $data
     * @param array<string, string> $headers
     */
    private static function json(
        array $data,
        int $statusCode = 200,
        array $headers = [],
        bool $prettyPrint = false
    ): Response {
        $options = self::JSON_OPTIONS;
        if ($prettyPrint) {
            $options |= JSON_PRETTY_PRINT;
        }

        $body = json_encode($data, $options);
        if ($body === false) {
            throw new \RuntimeException('Failed to encode JSON: ' . json_last_error_msg());
        }

        $headers['Content-Type'] = 'application/json';

        return new Response($body, $statusCode, $headers);
    }
}
