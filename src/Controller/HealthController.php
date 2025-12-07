<?php

declare(strict_types=1);

namespace Lunar\Controller;

use Lunar\Attribute\Route;
use Lunar\Service\Core\Http\Request;
use Lunar\Service\Core\Http\Response;
use Lunar\Service\Storage\FileStorage;

/**
 * Contrôleur de vérification de l'état de santé du système.
 *
 * Fournit des endpoints pour :
 * - Vérification basique (liveness)
 * - Vérification complète (readiness)
 * - Métriques système
 */
class HealthController
{
    /**
     * Vérification de vie simple (liveness probe).
     *
     * Retourne 200 si l'application répond.
     */
    #[Route('/health', methods: ['GET'], name: 'health.liveness')]
    public function liveness(Request $request): Response
    {
        return $this->json([
            'status' => 'ok',
            'timestamp' => date('c'),
        ]);
    }

    /**
     * Vérification de disponibilité (readiness probe).
     *
     * Vérifie tous les composants du système.
     */
    #[Route('/health/ready', methods: ['GET'], name: 'health.readiness')]
    public function readiness(Request $request): Response
    {
        $checks = [];
        $allHealthy = true;

        // Vérifier le stockage des posts
        $checks['posts_storage'] = $this->checkStorage('data/blog/posts');
        if (!$checks['posts_storage']['healthy']) {
            $allHealthy = false;
        }

        // Vérifier le stockage des catégories
        $checks['categories_storage'] = $this->checkStorage('data/blog/categories');
        if (!$checks['categories_storage']['healthy']) {
            $allHealthy = false;
        }

        // Vérifier le stockage des tags
        $checks['tags_storage'] = $this->checkStorage('data/blog/tags');
        if (!$checks['tags_storage']['healthy']) {
            $allHealthy = false;
        }

        // Vérifier le répertoire de sortie du blog
        $checks['blog_output'] = $this->checkWritable('public/blog');
        if (!$checks['blog_output']['healthy']) {
            $allHealthy = false;
        }

        // Vérifier le répertoire de cache
        $checks['cache'] = $this->checkWritable('var/cache');
        if (!$checks['cache']['healthy']) {
            $allHealthy = false;
        }

        // Vérifier les templates
        $checks['templates'] = $this->checkDirectory('template');
        if (!$checks['templates']['healthy']) {
            $allHealthy = false;
        }

        $status = $allHealthy ? 200 : 503;

        return $this->json([
            'status' => $allHealthy ? 'healthy' : 'unhealthy',
            'timestamp' => date('c'),
            'checks' => $checks,
        ], $status);
    }

    /**
     * Métriques système.
     */
    #[Route('/health/metrics', methods: ['GET'], name: 'health.metrics')]
    public function metrics(Request $request): Response
    {
        $basePath = dirname(__DIR__, 2);

        $metrics = [
            'timestamp' => date('c'),
            'php' => [
                'version' => PHP_VERSION,
                'memory_usage' => memory_get_usage(true),
                'memory_peak' => memory_get_peak_usage(true),
                'memory_limit' => ini_get('memory_limit'),
            ],
            'system' => [
                'load_average' => function_exists('sys_getloadavg') ? sys_getloadavg() : null,
                'disk_free' => disk_free_space($basePath),
                'disk_total' => disk_total_space($basePath),
            ],
            'blog' => $this->getBlogMetrics($basePath),
        ];

        return $this->json($metrics);
    }

    /**
     * Vérifie un répertoire de stockage.
     *
     * @return array{healthy: bool, message: string, count?: int}
     */
    private function checkStorage(string $path): array
    {
        $basePath = dirname(__DIR__, 2);
        $fullPath = $basePath . '/' . $path;

        if (!is_dir($fullPath)) {
            return [
                'healthy' => false,
                'message' => 'Directory does not exist',
            ];
        }

        if (!is_readable($fullPath)) {
            return [
                'healthy' => false,
                'message' => 'Directory is not readable',
            ];
        }

        if (!is_writable($fullPath)) {
            return [
                'healthy' => false,
                'message' => 'Directory is not writable',
            ];
        }

        $count = count(glob($fullPath . '/*.json'));

        return [
            'healthy' => true,
            'message' => 'OK',
            'count' => $count,
        ];
    }

    /**
     * Vérifie qu'un répertoire est accessible en écriture.
     *
     * @return array{healthy: bool, message: string}
     */
    private function checkWritable(string $path): array
    {
        $basePath = dirname(__DIR__, 2);
        $fullPath = $basePath . '/' . $path;

        if (!is_dir($fullPath)) {
            // Essayer de le créer
            if (!@mkdir($fullPath, 0755, true)) {
                return [
                    'healthy' => false,
                    'message' => 'Cannot create directory',
                ];
            }
        }

        if (!is_writable($fullPath)) {
            return [
                'healthy' => false,
                'message' => 'Directory is not writable',
            ];
        }

        return [
            'healthy' => true,
            'message' => 'OK',
        ];
    }

    /**
     * Vérifie qu'un répertoire existe et est lisible.
     *
     * @return array{healthy: bool, message: string}
     */
    private function checkDirectory(string $path): array
    {
        $basePath = dirname(__DIR__, 2);
        $fullPath = $basePath . '/' . $path;

        if (!is_dir($fullPath)) {
            return [
                'healthy' => false,
                'message' => 'Directory does not exist',
            ];
        }

        if (!is_readable($fullPath)) {
            return [
                'healthy' => false,
                'message' => 'Directory is not readable',
            ];
        }

        return [
            'healthy' => true,
            'message' => 'OK',
        ];
    }

    /**
     * Récupère les métriques du blog.
     *
     * @return array<string, mixed>
     */
    private function getBlogMetrics(string $basePath): array
    {
        $postsDir = $basePath . '/data/blog/posts';
        $outputDir = $basePath . '/public/blog';

        $totalPosts = count(glob($postsDir . '/*.json'));
        $generatedPages = count(glob($outputDir . '/posts/*.html'));

        return [
            'total_posts' => $totalPosts,
            'generated_pages' => $generatedPages,
            'categories' => count(glob($basePath . '/data/blog/categories/*.json')),
            'tags' => count(glob($basePath . '/data/blog/tags/*.json')),
        ];
    }

    /**
     * Retourne une réponse JSON.
     */
    private function json(array $data, int $status = 200): Response
    {
        return new Response(
            json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
            $status,
            ['Content-Type' => 'application/json']
        );
    }
}
