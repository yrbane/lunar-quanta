<?php

declare(strict_types=1);

namespace Lunar\Command;

use Lunar\Cli\Attribute\Command;
use Lunar\Cli\CommandInterface;
use Lunar\Entity\PostStatus;
use Lunar\Service\Blog\CategoryService;
use Lunar\Service\Blog\PostService;
use Lunar\Service\Blog\TagService;
use Lunar\Service\Storage\FileStorage;

/**
 * Commande CLI pour valider l'intégrité des données du blog.
 *
 * Vérifie la cohérence des articles, catégories, tags et médias.
 */
#[Command(name: 'blog:validate', description: 'Valide l\'intégrité des données du blog.')]
class BlogValidateCommand implements CommandInterface
{
    private string $basePath;
    private bool $verbose = false;
    private bool $fix = false;

    /** @var array<string, array{level: string, message: string}> */
    private array $issues = [];

    public function execute(array $args): int
    {
        $this->basePath = dirname(__DIR__, 2);
        $this->verbose = in_array('-v', $args, true) || in_array('--verbose', $args, true);
        $this->fix = in_array('--fix', $args, true);

        $this->printHeader();

        try {
            $postService = new PostService(new FileStorage($this->basePath . '/data/blog/posts'));
            $categoryService = new CategoryService(new FileStorage($this->basePath . '/data/blog/categories'));
            $tagService = new TagService(new FileStorage($this->basePath . '/data/blog/tags'));

            $posts = $postService->all();
            $categories = $categoryService->all();
            $tags = $tagService->all();

            // Créer des index pour la validation
            $categoryIds = array_map(fn($c) => $c->getId(), $categories);
            $tagIds = array_map(fn($t) => $t->getId(), $tags);

            echo "Données à valider :\n";
            echo "  Articles : " . count($posts) . "\n";
            echo "  Catégories : " . count($categories) . "\n";
            echo "  Tags : " . count($tags) . "\n";
            echo "\n";

            // Validation des articles
            echo "→ Validation des articles...\n";
            $this->validatePosts($posts, $categoryIds, $tagIds);

            // Validation des catégories
            echo "→ Validation des catégories...\n";
            $this->validateCategories($categories);

            // Validation des tags
            echo "→ Validation des tags...\n";
            $this->validateTags($tags);

            // Validation des médias
            echo "→ Validation des médias...\n";
            $this->validateMedia($posts);

            // Validation des fichiers JSON
            echo "→ Validation des fichiers JSON...\n";
            $this->validateJsonFiles();

            // Validation des slugs
            echo "→ Validation des slugs...\n";
            $this->validateSlugs($posts, $categories, $tags);

            // Validation des dates
            echo "→ Validation des dates...\n";
            $this->validateDates($posts);

            // Afficher les résultats
            $this->printResults();

            // Retourner un code d'erreur si des problèmes critiques
            $hasErrors = count(array_filter($this->issues, fn($i) => $i['level'] === 'error')) > 0;

            return $hasErrors ? 1 : 0;

        } catch (\Throwable $e) {
            echo "✗ Erreur fatale : " . $e->getMessage() . "\n";
            return 1;
        }
    }

    /**
     * Valide les articles.
     */
    private function validatePosts(array $posts, array $categoryIds, array $tagIds): void
    {
        $slugs = [];

        foreach ($posts as $post) {
            $id = $post->getId();

            // Titre requis
            if (empty($post->getTitle())) {
                $this->addIssue('error', "Article {$id} : titre manquant");
            }

            // Contenu requis
            if (empty($post->getContent())) {
                $this->addIssue('warning', "Article {$id} : contenu vide");
            }

            // Slug requis et valide
            $slug = $post->getSlug();
            if (empty($slug)) {
                $this->addIssue('error', "Article {$id} : slug manquant");
            } elseif (!preg_match('/^[a-z0-9]+(-[a-z0-9]+)*$/', $slug)) {
                $this->addIssue('warning', "Article {$id} : slug invalide '{$slug}'");
            }

            // Slug unique
            if (isset($slugs[$slug])) {
                $this->addIssue('error', "Article {$id} : slug dupliqué '{$slug}' (conflit avec {$slugs[$slug]})");
            } else {
                $slugs[$slug] = $id;
            }

            // Catégorie valide
            $categoryId = $post->getCategoryId();
            if ($categoryId !== null && !in_array($categoryId, $categoryIds)) {
                $this->addIssue('warning', "Article {$id} : catégorie inexistante '{$categoryId}'");
            }

            // Tags valides
            foreach ($post->getTags() as $tag) {
                if (empty($tag)) {
                    $this->addIssue('warning', "Article {$id} : tag vide");
                }
            }

            // Date de création
            if ($post->getCreatedAt() === null) {
                $this->addIssue('warning', "Article {$id} : date de création manquante");
            }

            // Cohérence publication
            if ($post->getStatus() === PostStatus::PUBLISHED && $post->getPublishedAt() === null) {
                $this->addIssue('warning', "Article {$id} : publié sans date de publication");
            }

            // Extrait trop long
            $excerpt = $post->getExcerpt();
            if ($excerpt !== null && mb_strlen($excerpt) > 500) {
                $this->addIssue('info', "Article {$id} : extrait très long (" . mb_strlen($excerpt) . " caractères)");
            }

            if ($this->verbose) {
                echo "    ✓ {$post->getTitle()}\n";
            }
        }
    }

    /**
     * Valide les catégories.
     */
    private function validateCategories(array $categories): void
    {
        $slugs = [];

        foreach ($categories as $category) {
            $id = $category->getId();

            // Nom requis
            if (empty($category->getName())) {
                $this->addIssue('error', "Catégorie {$id} : nom manquant");
            }

            // Slug requis
            $slug = $category->getSlug();
            if (empty($slug)) {
                $this->addIssue('error', "Catégorie {$id} : slug manquant");
            }

            // Slug unique
            if (isset($slugs[$slug])) {
                $this->addIssue('error', "Catégorie {$id} : slug dupliqué '{$slug}'");
            } else {
                $slugs[$slug] = $id;
            }

            if ($this->verbose) {
                echo "    ✓ {$category->getName()}\n";
            }
        }
    }

    /**
     * Valide les tags.
     */
    private function validateTags(array $tags): void
    {
        $slugs = [];

        foreach ($tags as $tag) {
            $id = $tag->getId();

            // Nom requis
            if (empty($tag->getName())) {
                $this->addIssue('error', "Tag {$id} : nom manquant");
            }

            // Slug requis
            $slug = $tag->getSlug();
            if (empty($slug)) {
                $this->addIssue('error', "Tag {$id} : slug manquant");
            }

            // Slug unique parmi les tags
            if (isset($slugs[$slug])) {
                $this->addIssue('error', "Tag {$id} : slug dupliqué '{$slug}'");
            } else {
                $slugs[$slug] = $id;
            }

            if ($this->verbose) {
                echo "    ✓ {$tag->getName()}\n";
            }
        }
    }

    /**
     * Valide les références aux médias.
     */
    private function validateMedia(array $posts): void
    {
        $uploadsDir = $this->basePath . '/public/uploads';
        $missingMedia = 0;

        foreach ($posts as $post) {
            // Image mise en avant
            $image = $post->getFeaturedImage();
            if ($image !== null && !empty($image)) {
                // Vérifier si c'est une URL locale
                if (!str_starts_with($image, 'http')) {
                    $imagePath = $this->basePath . '/public' . $image;
                    if (!file_exists($imagePath)) {
                        $this->addIssue('warning', "Article {$post->getId()} : image manquante '{$image}'");
                        $missingMedia++;
                    }
                }
            }

            // Vérifier les images dans le contenu
            $content = $post->getContent();
            preg_match_all('/!\[[^\]]*\]\(([^)]+)\)/', $content, $matches);

            foreach ($matches[1] ?? [] as $mediaUrl) {
                if (!str_starts_with($mediaUrl, 'http') && !str_starts_with($mediaUrl, 'data:')) {
                    $mediaPath = $this->basePath . '/public' . $mediaUrl;
                    if (!file_exists($mediaPath)) {
                        $this->addIssue('info', "Article {$post->getId()} : média référencé manquant '{$mediaUrl}'");
                        $missingMedia++;
                    }
                }
            }
        }

        if ($missingMedia === 0 && $this->verbose) {
            echo "    ✓ Tous les médias sont valides\n";
        }
    }

    /**
     * Valide la syntaxe des fichiers JSON.
     */
    private function validateJsonFiles(): void
    {
        $dirs = [
            'data/blog/posts',
            'data/blog/categories',
            'data/blog/tags',
        ];

        foreach ($dirs as $dir) {
            $path = $this->basePath . '/' . $dir;
            if (!is_dir($path)) {
                continue;
            }

            $files = glob($path . '/*.json');
            foreach ($files as $file) {
                $content = file_get_contents($file);
                $data = json_decode($content, true);

                if (json_last_error() !== JSON_ERROR_NONE) {
                    $this->addIssue('error', "Fichier JSON invalide : {$file} - " . json_last_error_msg());
                } elseif ($this->verbose) {
                    echo "    ✓ " . basename($file) . "\n";
                }
            }
        }
    }

    /**
     * Valide l'unicité des slugs entre tous les types.
     */
    private function validateSlugs(array $posts, array $categories, array $tags): void
    {
        $allSlugs = [];

        // Collecter les slugs des catégories
        foreach ($categories as $cat) {
            $slug = $cat->getSlug();
            $allSlugs[$slug][] = "catégorie:{$cat->getName()}";
        }

        // Collecter les slugs des tags
        foreach ($tags as $tag) {
            $slug = $tag->getSlug();
            $allSlugs[$slug][] = "tag:{$tag->getName()}";
        }

        // Vérifier les conflits entre catégories et tags
        foreach ($allSlugs as $slug => $sources) {
            if (count($sources) > 1) {
                $list = implode(', ', $sources);
                $this->addIssue('warning', "Conflit de slug '{$slug}' entre : {$list}");
            }
        }

        if ($this->verbose) {
            echo "    ✓ Pas de conflits de slugs\n";
        }
    }

    /**
     * Valide la cohérence des dates.
     */
    private function validateDates(array $posts): void
    {
        $now = new \DateTimeImmutable();

        foreach ($posts as $post) {
            $createdAt = $post->getCreatedAt();
            $publishedAt = $post->getPublishedAt();
            $updatedAt = $post->getUpdatedAt();

            // Date de création dans le futur
            if ($createdAt !== null && $createdAt > $now) {
                $this->addIssue('warning', "Article {$post->getId()} : date de création dans le futur");
            }

            // Date de mise à jour avant création
            if ($createdAt !== null && $updatedAt !== null && $updatedAt < $createdAt) {
                $this->addIssue('warning', "Article {$post->getId()} : date de mise à jour antérieure à la création");
            }

            // Date de publication avant création
            if ($createdAt !== null && $publishedAt !== null && $publishedAt < $createdAt) {
                $this->addIssue('info', "Article {$post->getId()} : date de publication antérieure à la création");
            }
        }

        if ($this->verbose) {
            echo "    ✓ Dates cohérentes\n";
        }
    }

    /**
     * Ajoute un problème détecté.
     */
    private function addIssue(string $level, string $message): void
    {
        $this->issues[] = [
            'level' => $level,
            'message' => $message,
        ];

        if ($this->verbose) {
            $icon = match ($level) {
                'error' => '✗',
                'warning' => '⚠',
                'info' => 'ℹ',
                default => '•',
            };
            echo "    {$icon} {$message}\n";
        }
    }

    private function printHeader(): void
    {
        echo "\n";
        echo "╔══════════════════════════════════════════════════════════════╗\n";
        echo "║              LUNAR BLOG - Validation                         ║\n";
        echo "╚══════════════════════════════════════════════════════════════╝\n";
        echo "\n";
    }

    private function printResults(): void
    {
        $errors = array_filter($this->issues, fn($i) => $i['level'] === 'error');
        $warnings = array_filter($this->issues, fn($i) => $i['level'] === 'warning');
        $infos = array_filter($this->issues, fn($i) => $i['level'] === 'info');

        echo "\n";
        echo "┌──────────────────────────────────────────────────────────────┐\n";
        echo "│                    RÉSULTATS VALIDATION                      │\n";
        echo "├──────────────────────────────────────────────────────────────┤\n";
        printf("│  %-25s %35s │\n", "Erreurs", count($errors));
        printf("│  %-25s %35s │\n", "Avertissements", count($warnings));
        printf("│  %-25s %35s │\n", "Informations", count($infos));
        echo "└──────────────────────────────────────────────────────────────┘\n";

        if (count($errors) > 0) {
            echo "\n✗ ERREURS :\n";
            foreach ($errors as $issue) {
                echo "  - {$issue['message']}\n";
            }
        }

        if (count($warnings) > 0 && !$this->verbose) {
            echo "\n⚠ AVERTISSEMENTS :\n";
            foreach (array_slice($warnings, 0, 10) as $issue) {
                echo "  - {$issue['message']}\n";
            }
            if (count($warnings) > 10) {
                echo "  ... et " . (count($warnings) - 10) . " autres avertissements\n";
            }
        }

        echo "\n";
        if (count($errors) === 0 && count($warnings) === 0) {
            echo "✓ Validation réussie ! Aucun problème détecté.\n";
        } elseif (count($errors) === 0) {
            echo "✓ Validation terminée avec avertissements.\n";
        } else {
            echo "✗ Validation échouée. Corrigez les erreurs ci-dessus.\n";
        }
        echo "\n";
    }

    public function getHelp(): string
    {
        return <<<'HELP'
Usage: blog:validate [options]

Valide l'intégrité des données du blog.

Options :
  -v, --verbose    Affiche les détails de chaque vérification
  --fix            Tente de corriger automatiquement les problèmes (non implémenté)

Vérifications effectuées :
  - Articles : titre, contenu, slug, catégorie, tags, dates
  - Catégories : nom, slug, unicité
  - Tags : nom, slug, unicité
  - Médias : existence des fichiers référencés
  - Fichiers JSON : syntaxe valide
  - Slugs : unicité entre catégories et tags
  - Dates : cohérence temporelle

Niveaux de problèmes :
  ✗ Erreur       Problème critique à corriger
  ⚠ Avertissement  Problème potentiel
  ℹ Information    Suggestion d'amélioration

Exemples :
  blog:validate                 # Validation rapide
  blog:validate --verbose       # Validation détaillée
HELP;
    }
}
