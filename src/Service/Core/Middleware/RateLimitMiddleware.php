<?php

declare(strict_types=1);

namespace Lunar\Service\Core\Middleware;

use Lunar\Service\Core\Http\Request;
use Lunar\Service\Core\Http\Response;

/**
 * Middleware de limitation de requêtes (rate limiting).
 *
 * Limite le nombre de requêtes par IP sur une période donnée.
 * Utilise un stockage fichier simple pour la persistance.
 *
 * @example
 * ```php
 * // 100 requêtes par minute
 * $middleware = new RateLimitMiddleware(100, 60);
 *
 * // 1000 requêtes par heure pour les API
 * $middleware = new RateLimitMiddleware(1000, 3600, '/tmp/rate-limits');
 * ```
 */
final class RateLimitMiddleware implements MiddlewareInterface
{
    private int $maxRequests;
    private int $windowSeconds;
    private string $storagePath;

    /**
     * @param int $maxRequests Nombre maximum de requêtes autorisées
     * @param int $windowSeconds Fenêtre de temps en secondes
     * @param string $storagePath Chemin de stockage des compteurs
     */
    public function __construct(
        int $maxRequests = 100,
        int $windowSeconds = 60,
        string $storagePath = '/tmp/lunar-rate-limits'
    ) {
        $this->maxRequests = $maxRequests;
        $this->windowSeconds = $windowSeconds;
        $this->storagePath = $storagePath;

        if (!is_dir($this->storagePath)) {
            mkdir($this->storagePath, 0755, true);
        }
    }

    public function process(Request $request, callable $next): Response
    {
        $clientId = $this->getClientIdentifier($request);
        $limitInfo = $this->checkLimit($clientId);

        // Ajouter les headers de rate limit
        $headers = [
            'X-RateLimit-Limit' => (string) $this->maxRequests,
            'X-RateLimit-Remaining' => (string) max(0, $this->maxRequests - $limitInfo['count']),
            'X-RateLimit-Reset' => (string) $limitInfo['reset'],
        ];

        // Limite dépassée ?
        if ($limitInfo['count'] > $this->maxRequests) {
            $retryAfter = $limitInfo['reset'] - time();

            return new Response(
                json_encode([
                    'error' => 'Too Many Requests',
                    'message' => 'Rate limit exceeded. Please wait before making more requests.',
                    'retry_after' => $retryAfter,
                ]),
                429,
                array_merge($headers, [
                    'Content-Type' => 'application/json',
                    'Retry-After' => (string) $retryAfter,
                ])
            );
        }

        // Incrémenter le compteur
        $this->incrementCounter($clientId);

        // Continuer le traitement
        $response = $next($request);

        // Ajouter les headers à la réponse
        foreach ($headers as $name => $value) {
            $response = $response->withHeader($name, $value);
        }

        return $response;
    }

    /**
     * Identifie le client (par IP).
     */
    private function getClientIdentifier(Request $request): string
    {
        $ip = $request->getServerParams()['REMOTE_ADDR']
            ?? $request->getServerParams()['HTTP_X_FORWARDED_FOR']
            ?? '127.0.0.1';

        // Prendre la première IP si X-Forwarded-For contient plusieurs IPs
        if (str_contains($ip, ',')) {
            $ip = trim(explode(',', $ip)[0]);
        }

        return md5($ip);
    }

    /**
     * Vérifie le compteur de requêtes pour un client.
     *
     * @return array{count: int, reset: int}
     */
    private function checkLimit(string $clientId): array
    {
        $file = $this->storagePath . '/' . $clientId . '.json';

        if (!file_exists($file)) {
            return ['count' => 0, 'reset' => time() + $this->windowSeconds];
        }

        $data = json_decode(file_get_contents($file), true);

        if (!is_array($data) || !isset($data['reset']) || $data['reset'] < time()) {
            // Fenêtre expirée, réinitialiser
            return ['count' => 0, 'reset' => time() + $this->windowSeconds];
        }

        return [
            'count' => $data['count'] ?? 0,
            'reset' => $data['reset'],
        ];
    }

    /**
     * Incrémente le compteur de requêtes.
     */
    private function incrementCounter(string $clientId): void
    {
        $file = $this->storagePath . '/' . $clientId . '.json';
        $limitInfo = $this->checkLimit($clientId);

        $data = [
            'count' => $limitInfo['count'] + 1,
            'reset' => $limitInfo['count'] === 0 ? time() + $this->windowSeconds : $limitInfo['reset'],
        ];

        file_put_contents($file, json_encode($data), LOCK_EX);
    }

    /**
     * Nettoie les fichiers de rate limit expirés.
     */
    public function cleanup(): int
    {
        $count = 0;
        $files = glob($this->storagePath . '/*.json');

        foreach ($files as $file) {
            $data = json_decode(file_get_contents($file), true);
            if (!is_array($data) || !isset($data['reset']) || $data['reset'] < time()) {
                unlink($file);
                $count++;
            }
        }

        return $count;
    }
}
