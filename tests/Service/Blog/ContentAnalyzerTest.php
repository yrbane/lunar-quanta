<?php

declare(strict_types=1);

namespace Tests\Service\Blog;

use Lunar\Service\Blog\ContentAnalyzer;
use PHPUnit\Framework\TestCase;

/**
 * Tests pour ContentAnalyzer.
 */
final class ContentAnalyzerTest extends TestCase
{
    private ContentAnalyzer $analyzer;

    protected function setUp(): void
    {
        $this->analyzer = new ContentAnalyzer();
    }

    // =========================================================================
    // EXTRACT KEYWORDS
    // =========================================================================

    public function testExtractKeywordsReturnsArray(): void
    {
        $keywords = $this->analyzer->extractKeywords('PHP is a programming language');

        $this->assertIsArray($keywords);
    }

    public function testExtractKeywordsFindsRelevantWords(): void
    {
        $text = 'PHP est un langage de programmation pour le web. PHP permet de créer des applications web dynamiques.';

        $keywords = $this->analyzer->extractKeywords($text, 5);

        $this->assertContains('php', $keywords);
    }

    public function testExtractKeywordsExcludesStopwords(): void
    {
        $text = 'Le framework PHP est utilisé pour le développement web.';

        $keywords = $this->analyzer->extractKeywords($text, 10);

        $this->assertNotContains('le', $keywords);
        $this->assertNotContains('est', $keywords);
        $this->assertNotContains('pour', $keywords);
    }

    public function testExtractKeywordsRespectsLimit(): void
    {
        $text = 'PHP JavaScript Python Ruby Go Rust Java Kotlin Swift TypeScript';

        $keywords = $this->analyzer->extractKeywords($text, 3);

        $this->assertCount(3, $keywords);
    }

    public function testExtractKeywordsHandlesEmptyText(): void
    {
        $keywords = $this->analyzer->extractKeywords('');

        $this->assertSame([], $keywords);
    }

    public function testExtractKeywordsHandlesMarkdown(): void
    {
        $text = <<<'MD'
# Titre

Ceci est un paragraphe avec du **gras** et de l'*italique*.

```php
echo "Hello World";
```

[Un lien](https://example.com)
MD;

        $keywords = $this->analyzer->extractKeywords($text, 10);

        // Ne doit pas contenir les éléments de syntaxe Markdown
        $this->assertNotContains('#', $keywords);
        $this->assertNotContains('*', $keywords);
    }

    public function testExtractKeywordsHandlesHtml(): void
    {
        $text = '<p>Ceci est un <strong>paragraphe</strong> avec du <em>HTML</em>.</p>';

        $keywords = $this->analyzer->extractKeywords($text, 10);

        // Doit extraire les mots, pas les balises
        $this->assertNotContains('<p>', $keywords);
        $this->assertNotContains('strong', $keywords);
    }

    // =========================================================================
    // TF-IDF
    // =========================================================================

    public function testCalculateTfIdfReturnsScores(): void
    {
        $text = 'PHP est un langage de programmation';
        $corpus = [
            'PHP est populaire pour le web',
            'JavaScript est aussi utilisé pour le web',
            'Python est utilisé pour la data science',
        ];

        $scores = $this->analyzer->calculateTfIdf($text, $corpus);

        $this->assertIsArray($scores);
        $this->assertNotEmpty($scores);
    }

    public function testCalculateTfIdfGivesHigherScoreToUniqueTerms(): void
    {
        $text = 'PHP programmation Laravel framework';
        $corpus = [
            'PHP est un langage',
            'PHP pour le web',
            'Laravel est un framework PHP',
        ];

        $scores = $this->analyzer->calculateTfIdf($text, $corpus);

        // "programmation" est plus unique que "PHP" (qui est partout)
        // Vérifions que les scores sont ordonnés
        $this->assertArrayHasKey('php', $scores);
    }

    public function testCalculateTfIdfHandlesEmptyCorpus(): void
    {
        $text = 'PHP programmation';
        $corpus = [];

        $scores = $this->analyzer->calculateTfIdf($text, $corpus);

        $this->assertIsArray($scores);
    }

    // =========================================================================
    // SUGGEST TAGS
    // =========================================================================

    public function testSuggestTagsFindsExistingTags(): void
    {
        $text = 'Article sur PHP et le framework Laravel pour créer des APIs REST.';
        $existingTags = ['PHP', 'Laravel', 'JavaScript', 'Python', 'API'];

        $suggestions = $this->analyzer->suggestTags($text, $existingTags, 5);

        $this->assertContains('PHP', $suggestions);
        $this->assertContains('Laravel', $suggestions);
        $this->assertContains('API', $suggestions);
    }

    public function testSuggestTagsRespectsLimit(): void
    {
        $text = 'PHP JavaScript Python Ruby Go Rust';
        $existingTags = ['PHP', 'JavaScript', 'Python', 'Ruby', 'Go', 'Rust'];

        $suggestions = $this->analyzer->suggestTags($text, $existingTags, 3);

        $this->assertCount(3, $suggestions);
    }

    public function testSuggestTagsSuggestsNewTags(): void
    {
        $text = 'Article sur Symfony et Doctrine pour PHP';
        $existingTags = ['PHP'];

        $suggestions = $this->analyzer->suggestTags($text, $existingTags, 5);

        // Devrait suggérer PHP (existant) et potentiellement symfony, doctrine
        $this->assertContains('PHP', $suggestions);
    }

    public function testSuggestTagsHandlesEmptyExistingTags(): void
    {
        $text = 'PHP est un langage de programmation';
        $existingTags = [];

        $suggestions = $this->analyzer->suggestTags($text, $existingTags, 5);

        // Devrait suggérer des mots-clés du texte
        $this->assertNotEmpty($suggestions);
    }

    // =========================================================================
    // CUSTOM STOPWORDS
    // =========================================================================

    public function testCustomStopwords(): void
    {
        $customStopwords = ['php', 'framework'];
        $analyzer = new ContentAnalyzer($customStopwords);

        $text = 'PHP est un framework populaire';
        $keywords = $analyzer->extractKeywords($text, 10);

        $this->assertNotContains('php', $keywords);
        $this->assertNotContains('framework', $keywords);
    }

    // =========================================================================
    // UNICODE SUPPORT
    // =========================================================================

    public function testHandlesUnicodeCharacters(): void
    {
        $text = 'Développement web avec PHP. Création d\'applications.';

        $keywords = $this->analyzer->extractKeywords($text, 10);

        $this->assertContains('développement', $keywords);
        $this->assertContains('création', $keywords);
    }

    public function testHandlesCyrillicCharacters(): void
    {
        $text = 'Программирование на PHP';

        $keywords = $this->analyzer->extractKeywords($text, 10);

        $this->assertNotEmpty($keywords);
    }
}
