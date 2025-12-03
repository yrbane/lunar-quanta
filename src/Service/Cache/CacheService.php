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

namespace App\Service\Cache;

use App\Service\Core\Config\Config;

class CacheService
{
    /**
     * @return array<array{status: string, message: string}>
     */
    public function clear(): array
    {
        $cacheDir = Config::getProjectRoot().'/'.Config::get('cache.dir');

        /** @var array<array{status: string, message: string}> $results */
        $results = [];

        if (!is_dir($cacheDir)) {
            $results[] = ['status' => 'error', 'message' => "Le dossier cache '{$cacheDir}' n'existe pas."];

            return $results;
        }

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($cacheDir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($files as $fileinfo) {
            if ($fileinfo->isDir()) {
                if (rmdir($fileinfo->getRealPath())) {
                    $results[] = ['status' => 'success', 'message' => "Dossier supprimé : {$fileinfo->getRealPath()}"];
                } else {
                    $results[] = ['status' => 'error', 'message' => "Impossible de supprimer le dossier : {$fileinfo->getRealPath()}"];
                }
            } else {
                if (unlink($fileinfo->getRealPath())) {
                    $results[] = ['status' => 'success', 'message' => "Fichier supprimé : {$fileinfo->getRealPath()}"];
                } else {
                    $results[] = ['status' => 'error', 'message' => "Impossible de supprimer le fichier : {$fileinfo->getRealPath()}"];
                }
            }
        }

        if (rmdir($cacheDir)) {
            $results[] = ['status' => 'success', 'message' => "Dossier cache principal supprimé : {$cacheDir}"];
        }

        if (!mkdir($cacheDir, 0777, true) && !is_dir($cacheDir)) {
            $results[] = ['status' => 'error', 'message' => "Impossible de recréer le dossier cache : {$cacheDir}"];
        }

        return $results;
    }
}
