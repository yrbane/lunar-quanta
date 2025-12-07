<?php

declare(strict_types=1);

namespace Lunar\Command;

use Lunar\Cli\Attribute\Command;
use Lunar\Cli\CommandInterface;
use Lunar\Service\Blog\PostService;
use Lunar\Service\Storage\FileStorage;

/**
 * Commande CLI pour dupliquer un article existant.
 */
#[Command(name: 'blog:duplicate', description: 'Duplique un article existant.')]
class BlogDuplicateCommand implements CommandInterface
{
    public function execute(array $args): int
    {
        $basePath = dirname(__DIR__, 2);
        $postService = new PostService(new FileStorage($basePath . '/data/blog/posts'));

        // Obtenir l'identifiant de l'article
        $identifier = $args[0] ?? null;

        if ($identifier === null) {
            echo "Usage: blog:duplicate <id|slug> [options]\n";
            echo "Utilisez --help pour plus d'informations.\n";
            return 1;
        }

        // Nouvelles valeurs optionnelles
        $newTitle = $this->parseOption($args, '--title');
        $newSlug = $this->parseOption($args, '--slug');
        $asDraft = in_array('--draft', $args, true);

        try {
            // Trouver l'article
            $post = null;

            // Essayer par ID
            try {
                $post = $postService->get($identifier);
            } catch (\Exception $e) {
                // Essayer par slug
                $posts = $postService->all();
                foreach ($posts as $p) {
                    if ($p->getSlug() === $identifier) {
                        $post = $p;
                        break;
                    }
                }
            }

            if ($post === null) {
                echo "✗ Article non trouvé : {$identifier}\n";
                return 1;
            }

            echo "\n";
            echo "╔══════════════════════════════════════════════════════════════╗\n";
            echo "║              LUNAR BLOG - Duplication                        ║\n";
            echo "╚══════════════════════════════════════════════════════════════╝\n";
            echo "\n";

            echo "Article source :\n";
            echo "  Titre : {$post->getTitle()}\n";
            echo "  Slug  : {$post->getSlug()}\n";
            echo "  ID    : {$post->getId()}\n";
            echo "\n";

            // Déterminer le nouveau titre
            $duplicateTitle = $newTitle ?? $post->getTitle() . ' (copie)';

            // Créer la copie
            $duplicate = $postService->create($duplicateTitle, $post->getContent());

            // Copier les métadonnées
            $duplicate->setExcerpt($post->getExcerpt());
            $duplicate->setAuthor($post->getAuthor());
            $duplicate->setAuthorBio($post->getAuthorBio());
            $duplicate->setAuthorAvatar($post->getAuthorAvatar());
            $duplicate->setAuthorInstitution($post->getAuthorInstitution());
            $duplicate->setFeaturedImage($post->getFeaturedImage());
            $duplicate->setCategoryId($post->getCategoryId());

            // Copier les tags
            foreach ($post->getTags() as $tag) {
                $duplicate->addTag($tag);
            }

            // Appliquer le nouveau slug si spécifié
            if ($newSlug !== null) {
                $duplicate->setSlug($newSlug);
            }

            // Sauvegarder comme brouillon
            $postService->update($duplicate);

            echo "Article dupliqué :\n";
            echo "  Titre : {$duplicate->getTitle()}\n";
            echo "  Slug  : {$duplicate->getSlug()}\n";
            echo "  ID    : {$duplicate->getId()}\n";
            echo "  Statut: Brouillon\n";
            echo "\n";
            echo "✓ Duplication réussie !\n";
            echo "\n";

            return 0;

        } catch (\Throwable $e) {
            echo "✗ Erreur : " . $e->getMessage() . "\n";
            return 1;
        }
    }

    /**
     * Parse une option string.
     */
    private function parseOption(array $args, string $option): ?string
    {
        foreach ($args as $i => $arg) {
            if ($arg === $option && isset($args[$i + 1])) {
                return $args[$i + 1];
            }
            if (str_starts_with($arg, $option . '=')) {
                return substr($arg, strlen($option) + 1);
            }
        }
        return null;
    }

    public function getHelp(): string
    {
        return <<<'HELP'
Usage: blog:duplicate <id|slug> [options]

Duplique un article existant.

Arguments :
  <id|slug>           ID ou slug de l'article à dupliquer

Options :
  --title=<title>     Titre de la copie (défaut: "... (copie)")
  --slug=<slug>       Slug personnalisé pour la copie
  --draft             Créer comme brouillon (par défaut)

Exemples :
  blog:duplicate mon-article
  blog:duplicate abc123-def456 --title="Nouvelle version"
  blog:duplicate mon-article --slug=mon-article-v2

Données copiées :
  - Titre (avec suffixe "(copie)" par défaut)
  - Contenu complet
  - Extrait
  - Auteur et métadonnées auteur
  - Image mise en avant
  - Catégorie
  - Tags

Note :
  L'article dupliqué est créé comme brouillon non publié.
HELP;
    }
}
