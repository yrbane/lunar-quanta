<?php
/**
 *
 * @since 0.0.1
 * @link https://nethttp.net
 * @Author seb@nethttp.net
 *
 *
 */

namespace Lunar\Command;

use Lunar\Cli\Attribute\Command;
use Lunar\Cli\CommandInterface;
use Lunar\Config\Config;

#[Command(name: 'filesystem:tree', description: 'Affiche l\'arborescence du projet.')]
class FilesystemTreeCommand implements CommandInterface
{
    private const BRANCH = '├── ';
    private const LAST_BRANCH = '└── ';
    private const VERTICAL = '│   ';
    private const SPACE = '    ';

    /**
     * @var array<string>
     */
    private array $excludedDirs = ['vendor', 'node_modules', '.git', '.idea', 'cache', '.phpunit.cache'];

    public function execute(array $args): int
    {
        $rootDir = Config::resolvePath('');
        $maxDepth = 3;

        // Parse arguments
        foreach ($args as $i => $arg) {
            if ('--depth' === $arg && isset($args[$i + 1])) {
                $maxDepth = (int) $args[$i + 1];
            }
            if ('--all' === $arg) {
                $this->excludedDirs = [];
            }
        }

        echo basename($rootDir)."/\n";
        $this->printTree($rootDir, '', $maxDepth, 0);

        return 0;
    }

    public function getHelp(): string
    {
        return <<<'HELP'
Commande : filesystem:tree
Affiche l'arborescence du projet.

Utilisation :
  ./bin/console filesystem:tree [--depth N] [--all] [--help]

Options :
    --depth N      Profondeur maximale (défaut: 3)
    --all          Inclure tous les répertoires (vendor, node_modules, etc.)
    --help         Affiche cette aide

Description :
    Cette commande affiche l'arborescence des fichiers et dossiers du projet.
    Par défaut, les répertoires vendor, node_modules, .git, .idea et cache sont exclus.

Exemples :
    ./bin/console filesystem:tree
    ./bin/console filesystem:tree --depth 5
    ./bin/console filesystem:tree --all

HELP;
    }

    private function printTree(string $path, string $prefix, int $maxDepth, int $currentDepth): void
    {
        if ($currentDepth >= $maxDepth) {
            return;
        }

        $items = $this->getDirectoryContents($path);
        $count = count($items);

        foreach ($items as $index => $item) {
            $isLast = ($index === $count - 1);
            $itemPath = $path.DIRECTORY_SEPARATOR.$item;
            $isDir = is_dir($itemPath);

            $branch = $isLast ? self::LAST_BRANCH : self::BRANCH;
            $newPrefix = $prefix.($isLast ? self::SPACE : self::VERTICAL);

            echo $prefix.$branch.$item.($isDir ? '/' : '')."\n";

            if ($isDir && !in_array($item, $this->excludedDirs, true)) {
                $this->printTree($itemPath, $newPrefix, $maxDepth, $currentDepth + 1);
            }
        }
    }

    /**
     * @return array<string>
     */
    private function getDirectoryContents(string $path): array
    {
        $items = scandir($path);
        if (false === $items) {
            return [];
        }

        $filtered = array_filter($items, function (string $item): bool {
            return '.' !== $item && '..' !== $item;
        });

        // Sort: directories first, then files
        usort($filtered, function (string $a, string $b) use ($path): int {
            $aIsDir = is_dir($path.DIRECTORY_SEPARATOR.$a);
            $bIsDir = is_dir($path.DIRECTORY_SEPARATOR.$b);

            if ($aIsDir && !$bIsDir) {
                return -1;
            }
            if (!$aIsDir && $bIsDir) {
                return 1;
            }

            return strcasecmp($a, $b);
        });

        return array_values($filtered);
    }
}
