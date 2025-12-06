<?php

declare(strict_types=1);

namespace Lunar\Command;

use Lunar\Cli\Attribute\Command;
use Lunar\Cli\CommandInterface;
use Lunar\Entity\Post;
use Lunar\Entity\PostStatus;
use Lunar\Service\Blog\PostService;
use Lunar\Service\Blog\CategoryService;
use Lunar\Service\Blog\SlugGenerator;
use Lunar\Service\Storage\FileStorage;

/**
 * Commande pour importer des articles sous licence Creative Commons.
 *
 * Sources supportées :
 * - The Conversation (CC BY-ND 4.0)
 * - Wikipédia (CC BY-SA)
 * - ArXiv (articles open access)
 * - HAL Archives Ouvertes (CC BY)
 *
 * Les articles importés sont verrouillés et ne peuvent pas être modifiés par une IA.
 */
#[Command(name: 'blog:import-cc', description: 'Importe des articles Creative Commons (The Conversation, Wikipedia, ArXiv, HAL).')]
final class BlogImportCCCommand implements CommandInterface
{
    private PostService $postService;
    private ?CategoryService $categoryService;
    private string $basePath;

    /**
     * Sources Creative Commons disponibles.
     */
    private const SOURCES = [
        'theconversation' => [
            'name' => 'The Conversation',
            'license' => 'CC BY-ND 4.0',
            'feeds' => [
                'all' => 'https://theconversation.com/fr/articles.atom',
                'science' => 'https://theconversation.com/fr/technologie/articles.atom',
                'environment' => 'https://theconversation.com/fr/environnement/articles.atom',
                'health' => 'https://theconversation.com/fr/sante/articles.atom',
                'economy' => 'https://theconversation.com/fr/economie/articles.atom',
                'politics' => 'https://theconversation.com/fr/politique/articles.atom',
                'education' => 'https://theconversation.com/fr/education/articles.atom',
                'culture' => 'https://theconversation.com/fr/arts/articles.atom',
                'international' => 'https://theconversation.com/fr/international/articles.atom',
            ],
            'attribution' => 'Cet article est republié à partir de The Conversation sous licence Creative Commons.',
            'logo' => 'https://cdn.theconversation.com/static/tc/logo-republish.png',
        ],
        'wikipedia' => [
            'name' => 'Wikipédia',
            'license' => 'CC BY-SA 4.0',
            'api' => 'https://fr.wikipedia.org/w/api.php',
            'attribution' => 'Contenu adapté de Wikipédia sous licence Creative Commons Attribution-ShareAlike.',
        ],
        'arxiv' => [
            'name' => 'ArXiv',
            'license' => 'Various (CC)',
            'api' => 'http://export.arxiv.org/api/query',
            'categories' => ['cs.AI', 'cs.LG', 'q-bio', 'physics'],
            'attribution' => 'Article publié sur arXiv.org sous licence Creative Commons.',
        ],
        'hal' => [
            'name' => 'HAL Archives Ouvertes',
            'license' => 'CC BY',
            'api' => 'https://api.archives-ouvertes.fr/search/',
            'attribution' => 'Article publié sur HAL Archives Ouvertes.',
        ],
    ];

    public function execute(array $args): int
    {
        $this->basePath = dirname(__DIR__, 2);
        $postStorage = new FileStorage($this->basePath . '/data/blog/posts');
        $this->postService = new PostService($postStorage);

        $categoryPath = $this->basePath . '/data/blog/categories';
        if (is_dir($categoryPath)) {
            $categoryStorage = new FileStorage($categoryPath);
            $this->categoryService = new CategoryService($categoryStorage);
        } else {
            $this->categoryService = null;
        }

        $this->printHeader();

        // Parse arguments
        $source = $args[0] ?? null;
        $options = $this->parseOptions(array_slice($args, 1));

        // List sources
        if (isset($options['list-sources']) || $source === '--list-sources') {
            $this->listSources();
            return 0;
        }

        // Validate source
        if ($source === null) {
            echo "❌ Erreur : Veuillez spécifier une source.\n\n";
            $this->printUsage();
            return 1;
        }

        if (!isset(self::SOURCES[$source])) {
            echo "❌ Erreur : Source inconnue '{$source}'.\n\n";
            $this->listSources();
            return 1;
        }

        $limit = (int) ($options['limit'] ?? 10);
        $category = $options['category'] ?? 'all';
        $publish = isset($options['publish']);

        echo "→ Source : " . self::SOURCES[$source]['name'] . "\n";
        echo "→ Licence : " . self::SOURCES[$source]['license'] . "\n";
        echo "→ Catégorie : {$category}\n";
        echo "→ Limite : {$limit} articles\n";
        echo "→ Publication automatique : " . ($publish ? 'Oui' : 'Non') . "\n\n";

        // Import articles
        $imported = match ($source) {
            'theconversation' => $this->importFromTheConversation($category, $limit, $publish),
            'wikipedia' => $this->importFromWikipedia($options['query'] ?? 'intelligence artificielle', $limit, $publish),
            'arxiv' => $this->importFromArxiv($category, $limit, $publish),
            'hal' => $this->importFromHAL($options['query'] ?? 'machine learning', $limit, $publish),
            default => 0,
        };

        $this->printResults($imported);

        return 0;
    }

    /**
     * Import articles from The Conversation.
     */
    private function importFromTheConversation(string $category, int $limit, bool $publish): int
    {
        $sourceInfo = self::SOURCES['theconversation'];
        $feedUrl = $sourceInfo['feeds'][$category] ?? $sourceInfo['feeds']['all'];

        echo "→ Récupération du flux Atom : {$feedUrl}\n\n";

        $content = @file_get_contents($feedUrl);
        if ($content === false) {
            echo "❌ Erreur : Impossible de récupérer le flux.\n";
            return 0;
        }

        $xml = @simplexml_load_string($content);
        if ($xml === false) {
            echo "❌ Erreur : Flux XML invalide.\n";
            return 0;
        }

        $imported = 0;
        $entries = $xml->entry ?? [];

        foreach ($entries as $entry) {
            if ($imported >= $limit) {
                break;
            }

            $title = (string) $entry->title;
            $link = (string) $entry->link['href'];
            $summary = (string) $entry->summary;
            $published = (string) $entry->published;
            $updated = (string) $entry->updated;

            // Get author info
            $authorName = (string) ($entry->author->name ?? 'The Conversation');

            // Check if already imported
            if ($this->isAlreadyImported($link)) {
                echo "  ⏭ Déjà importé : {$title}\n";
                continue;
            }

            // Fetch full article content
            $fullContent = $this->fetchTheConversationArticle($link);
            if ($fullContent === null) {
                echo "  ⚠ Impossible de récupérer : {$title}\n";
                continue;
            }

            // Create post
            $post = $this->postService->create($title, $fullContent['content']);
            $post->setExcerpt($this->cleanHtml($summary));
            $post->setAuthor($authorName);
            $post->setAuthorBio($fullContent['authorBio'] ?? '');
            $post->setAuthorInstitution($fullContent['authorInstitution'] ?? '');
            $post->setAuthorAvatar($fullContent['authorAvatar'] ?? '');
            $post->setFeaturedImage($fullContent['image'] ?? null);
            $post->setOriginalUrl($link);
            $post->setOriginalSource('The Conversation');
            $post->setLicense('CC BY-ND 4.0');
            $post->lock(); // Verrouiller l'article

            // Add source attribution
            $post->addSource(
                'Article original sur The Conversation',
                $link,
                $sourceInfo['attribution']
            );

            // Add tags from categories
            foreach ($fullContent['tags'] ?? [] as $tag) {
                $post->addTag($tag);
            }

            // Match category
            $categoryId = $this->matchCategory($fullContent['category'] ?? $category);
            if ($categoryId !== null) {
                $post->setCategoryId($categoryId);
            }

            // Publish if requested
            if ($publish) {
                $post->publish();
            }

            $this->postService->update($post);
            $imported++;

            echo "  ✓ Importé : {$title}\n";
            echo "    → Auteur : {$authorName}\n";
            echo "    → Verrouillé : Oui (CC BY-ND)\n";
        }

        return $imported;
    }

    /**
     * Fetch full article from The Conversation.
     */
    private function fetchTheConversationArticle(string $url): ?array
    {
        $html = @file_get_contents($url);
        if ($html === false) {
            return null;
        }

        $doc = new \DOMDocument();
        @$doc->loadHTML('<?xml encoding="UTF-8">' . $html, LIBXML_NOERROR);
        $xpath = new \DOMXPath($doc);

        // Get article content
        $contentNode = $xpath->query('//div[@itemprop="articleBody"]')->item(0);
        $content = '';
        if ($contentNode !== null) {
            $content = $this->nodeToMarkdown($contentNode);
        }

        // Get featured image
        $imageNode = $xpath->query('//figure[@class="content-header-figure"]//img/@src')->item(0);
        $image = $imageNode !== null ? $imageNode->nodeValue : null;

        // Get author info
        $authorBioNode = $xpath->query('//p[@class="author-bio"]')->item(0);
        $authorBio = $authorBioNode !== null ? trim($authorBioNode->textContent) : '';

        $authorInstitutionNode = $xpath->query('//span[@class="author-institution"]')->item(0);
        $authorInstitution = $authorInstitutionNode !== null ? trim($authorInstitutionNode->textContent) : '';

        $authorAvatarNode = $xpath->query('//img[@class="author-avatar"]/@src')->item(0);
        $authorAvatar = $authorAvatarNode !== null ? $authorAvatarNode->nodeValue : '';

        // Get tags/topics
        $tags = [];
        $tagNodes = $xpath->query('//a[@rel="tag"]');
        foreach ($tagNodes as $tagNode) {
            $tags[] = trim($tagNode->textContent);
        }

        // Get category
        $categoryNode = $xpath->query('//a[@class="section-link"]')->item(0);
        $category = $categoryNode !== null ? trim($categoryNode->textContent) : '';

        // Add attribution footer to content
        $content .= "\n\n---\n\n";
        $content .= "📄 *Cet article est republié à partir de [The Conversation]({$url}) sous licence [Creative Commons BY-ND 4.0](https://creativecommons.org/licenses/by-nd/4.0/deed.fr). Lire l'[article original]({$url}).*";

        return [
            'content' => $content,
            'image' => $image,
            'authorBio' => $authorBio,
            'authorInstitution' => $authorInstitution,
            'authorAvatar' => $authorAvatar,
            'tags' => $tags,
            'category' => $category,
        ];
    }

    /**
     * Import articles from Wikipedia.
     */
    private function importFromWikipedia(string $query, int $limit, bool $publish): int
    {
        echo "→ Recherche Wikipedia : {$query}\n\n";

        $searchUrl = 'https://fr.wikipedia.org/w/api.php?' . http_build_query([
            'action' => 'query',
            'list' => 'search',
            'srsearch' => $query,
            'srlimit' => $limit,
            'format' => 'json',
        ]);

        $response = @file_get_contents($searchUrl);
        if ($response === false) {
            echo "❌ Erreur : Impossible d'interroger Wikipedia.\n";
            return 0;
        }

        $data = json_decode($response, true);
        $results = $data['query']['search'] ?? [];
        $imported = 0;

        foreach ($results as $result) {
            $title = $result['title'];
            $pageUrl = 'https://fr.wikipedia.org/wiki/' . urlencode(str_replace(' ', '_', $title));

            // Check if already imported
            if ($this->isAlreadyImported($pageUrl)) {
                echo "  ⏭ Déjà importé : {$title}\n";
                continue;
            }

            // Get page content
            $contentUrl = 'https://fr.wikipedia.org/w/api.php?' . http_build_query([
                'action' => 'query',
                'titles' => $title,
                'prop' => 'extracts|pageimages',
                'exintro' => false,
                'explaintext' => true,
                'piprop' => 'original',
                'format' => 'json',
            ]);

            $contentResponse = @file_get_contents($contentUrl);
            if ($contentResponse === false) {
                continue;
            }

            $contentData = json_decode($contentResponse, true);
            $pages = $contentData['query']['pages'] ?? [];
            $page = reset($pages);

            if (!isset($page['extract'])) {
                continue;
            }

            $content = $page['extract'];
            $image = $page['original']['source'] ?? null;

            // Create excerpt
            $excerpt = mb_substr($content, 0, 300) . '...';

            // Create post
            $post = $this->postService->create($title, $content);
            $post->setExcerpt($excerpt);
            $post->setAuthor('Wikipedia');
            $post->setAuthorBio('Encyclopedie libre collaborative');
            $post->setFeaturedImage($image);
            $post->setOriginalUrl($pageUrl);
            $post->setOriginalSource('Wikipedia');
            $post->setLicense('CC BY-SA 4.0');
            $post->lock();

            $post->addSource(
                "Article Wikipedia : {$title}",
                $pageUrl,
                'Contenu sous licence Creative Commons Attribution-ShareAlike'
            );

            if ($publish) {
                $post->publish();
            }

            $this->postService->update($post);
            $imported++;

            echo "  ✓ Importé : {$title}\n";
        }

        return $imported;
    }

    /**
     * Import articles from ArXiv.
     */
    private function importFromArxiv(string $category, int $limit, bool $publish): int
    {
        $arxivCategory = match ($category) {
            'ai', 'science' => 'cs.AI',
            'ml', 'machine-learning' => 'cs.LG',
            'physics' => 'physics',
            'biology' => 'q-bio',
            default => 'cs.AI',
        };

        echo "→ Recherche ArXiv : catégorie {$arxivCategory}\n\n";

        $url = 'http://export.arxiv.org/api/query?' . http_build_query([
            'search_query' => "cat:{$arxivCategory}",
            'start' => 0,
            'max_results' => $limit,
            'sortBy' => 'submittedDate',
            'sortOrder' => 'descending',
        ]);

        $response = @file_get_contents($url);
        if ($response === false) {
            echo "❌ Erreur : Impossible d'interroger ArXiv.\n";
            return 0;
        }

        $xml = @simplexml_load_string($response);
        if ($xml === false) {
            echo "❌ Erreur : Réponse XML invalide.\n";
            return 0;
        }

        $imported = 0;
        $ns = $xml->getNamespaces(true);

        foreach ($xml->entry as $entry) {
            $title = (string) $entry->title;
            $title = trim(preg_replace('/\s+/', ' ', $title));
            $link = (string) $entry->id;
            $summary = (string) $entry->summary;

            // Get authors
            $authors = [];
            foreach ($entry->author as $author) {
                $authors[] = (string) $author->name;
            }
            $authorStr = implode(', ', $authors);

            // Check if already imported
            if ($this->isAlreadyImported($link)) {
                echo "  ⏭ Déjà importé : {$title}\n";
                continue;
            }

            $content = "# {$title}\n\n";
            $content .= "**Auteurs** : {$authorStr}\n\n";
            $content .= "## Résumé\n\n{$summary}\n\n";
            $content .= "---\n\n";
            $content .= "📄 *Article disponible sur [ArXiv]({$link}).*";

            $excerpt = mb_substr(trim($summary), 0, 300) . '...';

            $post = $this->postService->create($title, $content);
            $post->setExcerpt($excerpt);
            $post->setAuthor($authorStr);
            $post->setAuthorBio('Chercheurs - Publication ArXiv');
            $post->setOriginalUrl($link);
            $post->setOriginalSource('ArXiv');
            $post->setLicense('ArXiv License');
            $post->lock();

            $post->addSource('Article ArXiv', $link, 'Preprint scientifique');
            $post->addTag('recherche');
            $post->addTag('science');

            if ($publish) {
                $post->publish();
            }

            $this->postService->update($post);
            $imported++;

            echo "  ✓ Importé : {$title}\n";
            echo "    → Auteurs : {$authorStr}\n";
        }

        return $imported;
    }

    /**
     * Import articles from HAL Archives Ouvertes.
     */
    private function importFromHAL(string $query, int $limit, bool $publish): int
    {
        echo "→ Recherche HAL : {$query}\n\n";

        $url = 'https://api.archives-ouvertes.fr/search/?' . http_build_query([
            'q' => $query,
            'rows' => $limit,
            'fl' => 'docid,title_s,abstract_s,authFullName_s,uri_s,thumbId_s,producedDate_s',
            'sort' => 'producedDate_s desc',
            'wt' => 'json',
        ]);

        $response = @file_get_contents($url);
        if ($response === false) {
            echo "❌ Erreur : Impossible d'interroger HAL.\n";
            return 0;
        }

        $data = json_decode($response, true);
        $docs = $data['response']['docs'] ?? [];
        $imported = 0;

        foreach ($docs as $doc) {
            $title = is_array($doc['title_s']) ? $doc['title_s'][0] : ($doc['title_s'] ?? 'Sans titre');
            $link = $doc['uri_s'] ?? '';
            $abstract = is_array($doc['abstract_s']) ? ($doc['abstract_s'][0] ?? '') : ($doc['abstract_s'] ?? '');
            $authors = $doc['authFullName_s'] ?? [];

            if (empty($link)) {
                continue;
            }

            // Check if already imported
            if ($this->isAlreadyImported($link)) {
                echo "  ⏭ Déjà importé : {$title}\n";
                continue;
            }

            $authorStr = is_array($authors) ? implode(', ', $authors) : $authors;

            $content = "# {$title}\n\n";
            $content .= "**Auteurs** : {$authorStr}\n\n";
            if (!empty($abstract)) {
                $content .= "## Résumé\n\n{$abstract}\n\n";
            }
            $content .= "---\n\n";
            $content .= "📄 *Article disponible sur [HAL Archives Ouvertes]({$link}).*";

            $excerpt = !empty($abstract) ? mb_substr($abstract, 0, 300) . '...' : '';

            $post = $this->postService->create($title, $content);
            $post->setExcerpt($excerpt);
            $post->setAuthor($authorStr);
            $post->setAuthorBio('Chercheurs - HAL Archives Ouvertes');
            $post->setOriginalUrl($link);
            $post->setOriginalSource('HAL Archives Ouvertes');
            $post->setLicense('CC BY');
            $post->lock();

            $post->addSource('Article HAL', $link, 'Archive ouverte française');
            $post->addTag('recherche');
            $post->addTag('science');

            if ($publish) {
                $post->publish();
            }

            $this->postService->update($post);
            $imported++;

            echo "  ✓ Importé : {$title}\n";
        }

        return $imported;
    }

    /**
     * Check if an article has already been imported.
     */
    private function isAlreadyImported(string $originalUrl): bool
    {
        $posts = $this->postService->all();
        foreach ($posts as $post) {
            if ($post->getOriginalUrl() === $originalUrl) {
                return true;
            }
        }
        return false;
    }

    /**
     * Match category name to existing category ID.
     */
    private function matchCategory(string $categoryName): ?string
    {
        if ($this->categoryService === null || empty($categoryName)) {
            return null;
        }

        $categories = $this->categoryService->all();
        $categoryName = strtolower($categoryName);

        foreach ($categories as $category) {
            if (stripos($category->getName(), $categoryName) !== false ||
                stripos($categoryName, $category->getName()) !== false) {
                return $category->getId();
            }
        }

        return null;
    }

    /**
     * Convert DOM node to Markdown.
     */
    private function nodeToMarkdown(\DOMNode $node): string
    {
        $markdown = '';

        foreach ($node->childNodes as $child) {
            if ($child->nodeType === XML_TEXT_NODE) {
                $markdown .= $child->textContent;
            } elseif ($child->nodeType === XML_ELEMENT_NODE) {
                $tagName = strtolower($child->nodeName);
                $innerContent = $this->nodeToMarkdown($child);

                switch ($tagName) {
                    case 'p':
                        $markdown .= "\n\n" . trim($innerContent) . "\n\n";
                        break;
                    case 'h1':
                        $markdown .= "\n\n# " . trim($innerContent) . "\n\n";
                        break;
                    case 'h2':
                        $markdown .= "\n\n## " . trim($innerContent) . "\n\n";
                        break;
                    case 'h3':
                        $markdown .= "\n\n### " . trim($innerContent) . "\n\n";
                        break;
                    case 'h4':
                        $markdown .= "\n\n#### " . trim($innerContent) . "\n\n";
                        break;
                    case 'strong':
                    case 'b':
                        $markdown .= "**" . trim($innerContent) . "**";
                        break;
                    case 'em':
                    case 'i':
                        $markdown .= "*" . trim($innerContent) . "*";
                        break;
                    case 'a':
                        $href = $child->getAttribute('href');
                        $markdown .= "[" . trim($innerContent) . "]({$href})";
                        break;
                    case 'ul':
                    case 'ol':
                        $markdown .= "\n" . $innerContent . "\n";
                        break;
                    case 'li':
                        $markdown .= "- " . trim($innerContent) . "\n";
                        break;
                    case 'blockquote':
                        $lines = explode("\n", trim($innerContent));
                        $markdown .= "\n\n" . implode("\n", array_map(fn($l) => "> " . $l, $lines)) . "\n\n";
                        break;
                    case 'code':
                        $markdown .= "`" . trim($innerContent) . "`";
                        break;
                    case 'pre':
                        $markdown .= "\n\n```\n" . trim($innerContent) . "\n```\n\n";
                        break;
                    case 'br':
                        $markdown .= "\n";
                        break;
                    case 'img':
                        $src = $child->getAttribute('src');
                        $alt = $child->getAttribute('alt') ?: 'Image';
                        $markdown .= "![{$alt}]({$src})";
                        break;
                    case 'figure':
                        $markdown .= $innerContent;
                        break;
                    case 'figcaption':
                        $markdown .= "\n*" . trim($innerContent) . "*\n";
                        break;
                    default:
                        $markdown .= $innerContent;
                }
            }
        }

        return preg_replace('/\n{3,}/', "\n\n", $markdown);
    }

    /**
     * Clean HTML to plain text.
     */
    private function cleanHtml(string $html): string
    {
        $text = strip_tags($html);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/\s+/', ' ', $text);
        return trim($text);
    }

    /**
     * Parse command line options.
     */
    private function parseOptions(array $args): array
    {
        $options = [];
        foreach ($args as $arg) {
            if (str_starts_with($arg, '--')) {
                $arg = substr($arg, 2);
                if (str_contains($arg, '=')) {
                    [$key, $value] = explode('=', $arg, 2);
                    $options[$key] = $value;
                } else {
                    $options[$arg] = true;
                }
            }
        }
        return $options;
    }

    private function printHeader(): void
    {
        echo "\n";
        echo "╔══════════════════════════════════════════════════════════════╗\n";
        echo "║       IMPORT ARTICLES CREATIVE COMMONS                       ║\n";
        echo "╚══════════════════════════════════════════════════════════════╝\n";
        echo "\n";
    }

    private function printUsage(): void
    {
        echo "Usage: php bin/console blog:import-cc <source> [options]\n\n";
        echo "Options:\n";
        echo "  --limit=N         Nombre maximum d'articles (défaut: 10)\n";
        echo "  --category=NAME   Catégorie à importer (défaut: all)\n";
        echo "  --query=TEXT      Terme de recherche (pour Wikipedia/HAL)\n";
        echo "  --publish         Publier automatiquement les articles\n";
        echo "  --list-sources    Afficher les sources disponibles\n\n";
        echo "Exemples:\n";
        echo "  php bin/console blog:import-cc theconversation --limit=5\n";
        echo "  php bin/console blog:import-cc theconversation --category=science --publish\n";
        echo "  php bin/console blog:import-cc wikipedia --query=\"intelligence artificielle\"\n";
        echo "  php bin/console blog:import-cc arxiv --category=ai --limit=10\n";
        echo "  php bin/console blog:import-cc hal --query=\"deep learning\"\n";
        echo "\n";
    }

    private function listSources(): void
    {
        echo "Sources Creative Commons disponibles :\n\n";
        foreach (self::SOURCES as $key => $source) {
            echo "  📚 {$key}\n";
            echo "     Nom : {$source['name']}\n";
            echo "     Licence : {$source['license']}\n";
            if (isset($source['feeds'])) {
                echo "     Catégories : " . implode(', ', array_keys($source['feeds'])) . "\n";
            }
            echo "\n";
        }
    }

    private function printResults(int $imported): void
    {
        echo "\n";
        echo "┌──────────────────────────────────────────────────────────────┐\n";
        echo "│                      RÉSULTATS                               │\n";
        echo "├──────────────────────────────────────────────────────────────┤\n";
        printf("│  %-25s %32d │\n", "Articles importés", $imported);
        echo "└──────────────────────────────────────────────────────────────┘\n";
        echo "\n";
        if ($imported > 0) {
            echo "✓ Import terminé !\n";
            echo "⚠ Les articles importés sont VERROUILLÉS et ne peuvent pas être modifiés.\n";
            echo "\n";
        }
    }

    public function getHelp(): string
    {
        return <<<'HELP'
Commande : blog:import-cc
Importe des articles sous licence Creative Commons depuis diverses sources.

Utilisation :
  ./bin/console blog:import-cc <source> [options]

Sources disponibles :
  theconversation    The Conversation (CC BY-ND 4.0)
  wikipedia          Wikipédia (CC BY-SA 4.0)
  arxiv              ArXiv preprints (Various CC)
  hal                HAL Archives Ouvertes (CC BY)

Options :
  --limit=N          Nombre maximum d'articles (défaut: 10)
  --category=NAME    Catégorie à importer (défaut: all)
  --query=TEXT       Terme de recherche (Wikipedia, HAL)
  --publish          Publier automatiquement les articles
  --list-sources     Afficher les sources disponibles

Catégories The Conversation :
  all, science, environment, health, economy, politics, education, culture, international

Exemples :
  ./bin/console blog:import-cc theconversation --limit=5
  ./bin/console blog:import-cc theconversation --category=science --publish
  ./bin/console blog:import-cc wikipedia --query="intelligence artificielle"
  ./bin/console blog:import-cc arxiv --category=ai --limit=10
  ./bin/console blog:import-cc hal --query="deep learning"
  ./bin/console blog:import-cc --list-sources

IMPORTANT :
  Les articles importés sont VERROUILLÉS et ne peuvent pas être modifiés par une IA.
  L'attribution originale est automatiquement incluse conformément aux licences CC.

HELP;
    }
}
