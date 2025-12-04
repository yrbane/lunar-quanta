<?php
/**
 *
 * @since 0.0.1
 * @link https://nethttp.net
 * @Author seb@nethttp.net
 *
 *
 */
declare(strict_types=1);

namespace Lunar\Service\Cache;

use Lunar\Config\Config;

class CacheService
{
    /**
     * @return array<array{status: string, message: string}>
     */
    public function clear(): array
    {
        $cacheDirConfig = Config::get('cache', 'cache.dir', 'cache');
        $cacheDir = Config::resolvePath(
            is_string($cacheDirConfig) ? $cacheDirConfig : 'cache'
        );

        /** @var array<array{status: string, message: string}> $results */
        $results = [];

        if (!is_dir($cacheDir)) {
            $results[] = ['status' => 'error', 'message' => "Le dossier cache '{$cacheDir}' n'existe pas."];

            return $results;
        }

        /** @var \RecursiveIteratorIterator<\RecursiveDirectoryIterator> $files */
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($cacheDir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($files as $fileinfo) {
            if (!$fileinfo instanceof \SplFileInfo) {
                continue;
            }
            $realPath = $fileinfo->getRealPath();
            if (false === $realPath) {
                continue;
            }
            if ($fileinfo->isDir()) {
                if (rmdir($realPath)) {
                    $results[] = ['status' => 'success', 'message' => "Dossier supprimé : {$realPath}"];
                } else {
                    $results[] = ['status' => 'error', 'message' => "Impossible de supprimer le dossier : {$realPath}"];
                }
            } else {
                if (unlink($realPath)) {
                    $results[] = ['status' => 'success', 'message' => "Fichier supprimé : {$realPath}"];
                } else {
                    $results[] = ['status' => 'error', 'message' => "Impossible de supprimer le fichier : {$realPath}"];
                }
            }
        }

        if (rmdir($cacheDir)) {
            $results[] = ['status' => 'success', 'message' => "Dossier cache principal supprimé : {$cacheDir}"];

            // Recreate the cache directory after clearing
            if (!mkdir($cacheDir, 0777, true)) {
                $results[] = ['status' => 'error', 'message' => "Impossible de recréer le dossier cache : {$cacheDir}"];
            }
        }

        return $results;
    }
}
