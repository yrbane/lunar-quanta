<?php

declare(strict_types=1);

namespace Tests\Service\Blog;

use Lunar\Service\Blog\SlugGenerator;
use PHPUnit\Framework\TestCase;

/**
 * Tests pour le générateur de slugs.
 *
 * Un slug est une version URL-friendly d'un texte :
 * - minuscules
 * - caractères alphanumériques uniquement
 * - tirets comme séparateurs
 * - pas de caractères spéciaux ou accents
 */
final class SlugGeneratorTest extends TestCase
{
    private SlugGenerator $generator;

    protected function setUp(): void
    {
        $this->generator = new SlugGenerator();
    }

    public function testGenerateConvertsToLowercase(): void
    {
        $slug = $this->generator->generate('HELLO WORLD');

        $this->assertSame('hello-world', $slug);
    }

    public function testGenerateReplacesSpacesWithDashes(): void
    {
        $slug = $this->generator->generate('hello world');

        $this->assertSame('hello-world', $slug);
    }

    public function testGenerateRemovesAccents(): void
    {
        $slug = $this->generator->generate('Développement éco-responsable');

        $this->assertSame('developpement-eco-responsable', $slug);
    }

    public function testGenerateHandlesFrenchAccents(): void
    {
        $testCases = [
            'café' => 'cafe',
            'naïf' => 'naif',
            'Noël' => 'noel',
            'français' => 'francais',
            'Ça marche' => 'ca-marche',
            'où êtes-vous' => 'ou-etes-vous',
        ];

        foreach ($testCases as $input => $expected) {
            $this->assertSame($expected, $this->generator->generate($input), "Failed for: $input");
        }
    }

    public function testGenerateRemovesSpecialCharacters(): void
    {
        $slug = $this->generator->generate('Hello! How are you?');

        $this->assertSame('hello-how-are-you', $slug);
    }

    public function testGenerateHandlesMultipleSpaces(): void
    {
        $slug = $this->generator->generate('hello    world');

        $this->assertSame('hello-world', $slug);
    }

    public function testGenerateHandlesLeadingAndTrailingSpaces(): void
    {
        $slug = $this->generator->generate('  hello world  ');

        $this->assertSame('hello-world', $slug);
    }

    public function testGenerateHandlesMultipleDashes(): void
    {
        $slug = $this->generator->generate('hello---world');

        $this->assertSame('hello-world', $slug);
    }

    public function testGenerateRemovesLeadingAndTrailingDashes(): void
    {
        $slug = $this->generator->generate('-hello world-');

        $this->assertSame('hello-world', $slug);
    }

    public function testGeneratePreservesNumbers(): void
    {
        $slug = $this->generator->generate('PHP 8.3 Features');

        $this->assertSame('php-8-3-features', $slug);
    }

    public function testGenerateHandlesEmptyString(): void
    {
        $slug = $this->generator->generate('');

        $this->assertSame('', $slug);
    }

    public function testGenerateHandlesOnlySpecialCharacters(): void
    {
        $slug = $this->generator->generate('!!!???');

        $this->assertSame('', $slug);
    }

    public function testGenerateHandlesApostrophes(): void
    {
        $slug = $this->generator->generate("L'heure d'été");

        $this->assertSame('l-heure-d-ete', $slug);
    }

    public function testGenerateHandlesUnicodeCharacters(): void
    {
        $slug = $this->generator->generate('日本語テスト');

        // Les caractères non-ASCII sont supprimés
        $this->assertSame('', $slug);
    }

    public function testGenerateHandlesMixedContent(): void
    {
        $slug = $this->generator->generate('Article #1: Introduction à PHP!');

        $this->assertSame('article-1-introduction-a-php', $slug);
    }

    public function testGenerateWithMaxLength(): void
    {
        $slug = $this->generator->generate('This is a very long title that should be truncated', 20);

        $this->assertLessThanOrEqual(20, strlen($slug));
        $this->assertStringNotContainsString('truncated', $slug);
    }

    public function testGenerateWithMaxLengthDoesNotCutInMiddleOfWord(): void
    {
        $slug = $this->generator->generate('hello beautiful world', 16);

        // Doit couper sur un mot entier (hello-beautiful = 15 chars)
        $this->assertSame('hello-beautiful', $slug);
    }

    public function testGenerateWithMaxLengthRemovesTrailingDash(): void
    {
        $slug = $this->generator->generate('hello world test', 11);

        // Ne doit pas terminer par un tiret
        $this->assertStringEndsNotWith('-', $slug);
    }

    public function testGenerateUnique(): void
    {
        $existingSlugs = ['hello-world', 'hello-world-1', 'hello-world-2'];

        $slug = $this->generator->generateUnique('Hello World', $existingSlugs);

        $this->assertSame('hello-world-3', $slug);
    }

    public function testGenerateUniqueWithNoConflict(): void
    {
        $existingSlugs = ['other-slug'];

        $slug = $this->generator->generateUnique('Hello World', $existingSlugs);

        $this->assertSame('hello-world', $slug);
    }

    public function testGenerateUniqueWithEmptyExistingSlugs(): void
    {
        $slug = $this->generator->generateUnique('Hello World', []);

        $this->assertSame('hello-world', $slug);
    }

    public function testSlugify(): void
    {
        // Test static method shortcut
        $slug = SlugGenerator::slugify('Hello World');

        $this->assertSame('hello-world', $slug);
    }

    public function testCommonBlogTitles(): void
    {
        $testCases = [
            'Comment débuter avec PHP 8' => 'comment-debuter-avec-php-8',
            '10 astuces pour améliorer vos performances' => '10-astuces-pour-ameliorer-vos-performances',
            'API REST vs GraphQL : quel choix ?' => 'api-rest-vs-graphql-quel-choix',
            "Guide complet de l'architecture MVC" => 'guide-complet-de-l-architecture-mvc',
            'Tests unitaires : pourquoi et comment ?' => 'tests-unitaires-pourquoi-et-comment',
        ];

        foreach ($testCases as $title => $expected) {
            $this->assertSame($expected, $this->generator->generate($title), "Failed for: $title");
        }
    }
}
