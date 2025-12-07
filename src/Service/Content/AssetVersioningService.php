<?php

declare(strict_types=1);

namespace Lunar\Service\Content;

/**
 * Service pour le versioning des assets (cache busting).
 *
 * Ajoute des hashes ou timestamps aux URLs des assets pour invalider le cache.
 *
 * @example
 * ```php
 * $versioning = new AssetVersioningService('/var/www/public');
 *
 * // Ajouter un hash basé sur le contenu
 * $url = $versioning->version('/css/style.css');
 * // Résultat: /css/style.css?v=a1b2c3d4
 *
 * // Générer un manifest
 * $manifest = $versioning->generateManifest(['css/*.css', 'js/*.js']);
 * ```
 */
final class AssetVersioningService
{
    private string $publicPath;
    private string $hashAlgorithm = 'md5';
    private int $hashLength = 8;
    private string $queryParam = 'v';
    private bool $useContentHash = true;

    /** @var array<string, string> */
    private array $manifest = [];

    public function __construct(string $publicPath = '')
    {
        $this->publicPath = rtrim($publicPath, '/');
    }

    /**
     * Définit le chemin public.
     */
    public function setPublicPath(string $path): self
    {
        $this->publicPath = rtrim($path, '/');
        return $this;
    }

    /**
     * Définit l'algorithme de hash.
     */
    public function setHashAlgorithm(string $algorithm): self
    {
        $this->hashAlgorithm = $algorithm;
        return $this;
    }

    /**
     * Définit la longueur du hash.
     */
    public function setHashLength(int $length): self
    {
        $this->hashLength = max(4, min(32, $length));
        return $this;
    }

    /**
     * Définit le paramètre de query string.
     */
    public function setQueryParam(string $param): self
    {
        $this->queryParam = $param;
        return $this;
    }

    /**
     * Utilise le hash de contenu ou le timestamp.
     */
    public function setUseContentHash(bool $use): self
    {
        $this->useContentHash = $use;
        return $this;
    }

    /**
     * Charge un manifest existant.
     *
     * @param array<string, string> $manifest
     */
    public function loadManifest(array $manifest): self
    {
        $this->manifest = $manifest;
        return $this;
    }

    /**
     * Charge un manifest depuis un fichier JSON.
     */
    public function loadManifestFromFile(string $path): self
    {
        if (file_exists($path)) {
            $content = file_get_contents($path);
            $manifest = json_decode($content, true);
            if (is_array($manifest)) {
                $this->manifest = $manifest;
            }
        }
        return $this;
    }

    /**
     * Versionne une URL d'asset.
     */
    public function version(string $url): string
    {
        // Vérifier le manifest d'abord
        if (isset($this->manifest[$url])) {
            return $this->manifest[$url];
        }

        // Générer le hash
        $hash = $this->generateHash($url);

        if ($hash === null) {
            return $url;
        }

        // Ajouter le paramètre de version
        $separator = str_contains($url, '?') ? '&' : '?';
        return $url . $separator . $this->queryParam . '=' . $hash;
    }

    /**
     * Versionne plusieurs URLs.
     *
     * @param string[] $urls
     * @return array<string, string>
     */
    public function versionMany(array $urls): array
    {
        $result = [];
        foreach ($urls as $url) {
            $result[$url] = $this->version($url);
        }
        return $result;
    }

    /**
     * Génère le hash pour un fichier.
     */
    public function generateHash(string $url): ?string
    {
        $filePath = $this->getFilePath($url);

        if ($filePath === null || !file_exists($filePath)) {
            return null;
        }

        if ($this->useContentHash) {
            $content = file_get_contents($filePath);
            $hash = hash($this->hashAlgorithm, $content);
        } else {
            $hash = hash($this->hashAlgorithm, (string) filemtime($filePath));
        }

        return substr($hash, 0, $this->hashLength);
    }

    /**
     * Génère un manifest pour les assets.
     *
     * @param string[] $patterns Glob patterns relatifs au publicPath
     * @return array<string, string>
     */
    public function generateManifest(array $patterns): array
    {
        $manifest = [];

        foreach ($patterns as $pattern) {
            $fullPattern = $this->publicPath . '/' . ltrim($pattern, '/');
            $files = glob($fullPattern);

            if ($files === false) {
                continue;
            }

            foreach ($files as $file) {
                // Convertir le chemin de fichier en URL
                $url = '/' . ltrim(str_replace($this->publicPath, '', $file), '/');
                $manifest[$url] = $this->version($url);
            }
        }

        $this->manifest = array_merge($this->manifest, $manifest);

        return $manifest;
    }

    /**
     * Sauvegarde le manifest dans un fichier.
     */
    public function saveManifest(string $path): bool
    {
        $json = json_encode($this->manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        return file_put_contents($path, $json) !== false;
    }

    /**
     * Retourne le manifest actuel.
     *
     * @return array<string, string>
     */
    public function getManifest(): array
    {
        return $this->manifest;
    }

    /**
     * Traite le contenu HTML pour versionner les assets.
     */
    public function processHtml(string $html): string
    {
        // CSS links
        $html = preg_replace_callback(
            '/<link([^>]*href=["\'])([^"\']+\.css)(["\'][^>]*)>/i',
            fn ($m) => '<link' . $m[1] . $this->version($m[2]) . $m[3] . '>',
            $html
        );

        // JS scripts
        $html = preg_replace_callback(
            '/<script([^>]*src=["\'])([^"\']+\.js)(["\'][^>]*)>/i',
            fn ($m) => '<script' . $m[1] . $this->version($m[2]) . $m[3] . '>',
            $html
        );

        // Images
        $html = preg_replace_callback(
            '/<img([^>]*src=["\'])([^"\']+\.(jpg|jpeg|png|gif|webp|svg))(["\'][^>]*)>/i',
            fn ($m) => '<img' . $m[1] . $this->version($m[2]) . $m[4] . '>',
            $html
        );

        return $html;
    }

    /**
     * Génère une balise link avec version.
     */
    public function cssLink(string $url, array $attributes = []): string
    {
        $versionedUrl = $this->version($url);
        $attrs = ['rel="stylesheet"', 'href="' . htmlspecialchars($versionedUrl) . '"'];

        foreach ($attributes as $key => $value) {
            $attrs[] = $key . '="' . htmlspecialchars($value) . '"';
        }

        return '<link ' . implode(' ', $attrs) . '>';
    }

    /**
     * Génère une balise script avec version.
     */
    public function jsScript(string $url, array $attributes = []): string
    {
        $versionedUrl = $this->version($url);
        $attrs = ['src="' . htmlspecialchars($versionedUrl) . '"'];

        foreach ($attributes as $key => $value) {
            if ($value === true) {
                $attrs[] = $key;
            } else {
                $attrs[] = $key . '="' . htmlspecialchars($value) . '"';
            }
        }

        return '<script ' . implode(' ', $attrs) . '></script>';
    }

    /**
     * Génère une URL d'image versionnée.
     */
    public function imgSrc(string $url): string
    {
        return $this->version($url);
    }

    /**
     * Convertit une URL en chemin de fichier.
     */
    private function getFilePath(string $url): ?string
    {
        // Supprimer les query strings existantes
        $url = preg_replace('/\?.*$/', '', $url);

        // URL absolue ou relative
        if (str_starts_with($url, '/')) {
            return $this->publicPath . $url;
        }

        return null;
    }
}
