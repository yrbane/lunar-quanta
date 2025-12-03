<?php
/**
 * Macro pour générer des URLs d'assets.
 *
 * @since 1.0.0
 * @author seb@nethttp.net
 */
declare(strict_types=1);

namespace Lunar\Template\Macro;

class AssetMacro implements MacroInterface
{
    private string $baseUrl;

    public function __construct(string $baseUrl = '/')
    {
        $this->baseUrl = rtrim($baseUrl, '/');
    }

    public function getName(): string
    {
        return 'asset';
    }

    /**
     * Génère une URL d'asset.
     *
     * @param array<int, mixed> $args [0] = path de l'asset
     * @return string URL complète de l'asset
     */
    public function execute(array $args): string
    {
        $assetPath = $args[0] ?? '';
        
        if (empty($assetPath)) {
            return '';
        }

        // Nettoyage du chemin
        $assetPath = ltrim($assetPath, '/');
        
        return $this->baseUrl . '/' . $assetPath;
    }
}