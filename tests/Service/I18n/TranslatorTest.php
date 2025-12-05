<?php

declare(strict_types=1);

namespace Tests\Service\I18n;

use Lunar\Service\I18n\Translator;
use Lunar\Service\I18n\TranslatorInterface;
use Lunar\Service\I18n\Loader\ArrayLoader;
use Lunar\Service\I18n\Loader\PhpFileLoader;
use PHPUnit\Framework\TestCase;

/**
 * Tests pour le système d'internationalisation (i18n).
 *
 * L'i18n permet de traduire les messages de l'application dans
 * différentes langues. Notre implémentation supporte :
 * - Traductions par fichiers PHP ou tableaux
 * - Pluralisation
 * - Interpolation de paramètres
 * - Fallback vers une langue par défaut
 */
final class TranslatorTest extends TestCase
{
    private string $translationsDir;

    protected function setUp(): void
    {
        $this->translationsDir = sys_get_temp_dir() . '/lunar_i18n_test_' . uniqid();
        mkdir($this->translationsDir, 0755, true);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->translationsDir);
    }

    // =========================================================================
    // Tests de traduction basique
    // =========================================================================

    public function testTranslateSimpleMessage(): void
    {
        $translator = new Translator('fr', [
            'fr' => ['messages' => ['hello' => 'Bonjour']]
        ]);

        $this->assertSame('Bonjour', $translator->trans('messages.hello'));
    }

    public function testTranslateReturnsKeyIfNotFound(): void
    {
        $translator = new Translator('fr', [
            'fr' => ['messages' => ['hello' => 'Bonjour']]
        ]);

        $this->assertSame('messages.unknown', $translator->trans('messages.unknown'));
    }

    public function testTranslateWithDifferentLocale(): void
    {
        $translator = new Translator('fr', [
            'fr' => ['messages' => ['hello' => 'Bonjour']],
            'en' => ['messages' => ['hello' => 'Hello']]
        ]);

        $this->assertSame('Bonjour', $translator->trans('messages.hello'));
        $this->assertSame('Hello', $translator->trans('messages.hello', [], 'en'));
    }

    // =========================================================================
    // Tests d'interpolation de paramètres
    // =========================================================================

    public function testTranslateWithParameters(): void
    {
        $translator = new Translator('fr', [
            'fr' => ['messages' => ['welcome' => 'Bienvenue, {name}!']]
        ]);

        $this->assertSame(
            'Bienvenue, Jean!',
            $translator->trans('messages.welcome', ['name' => 'Jean'])
        );
    }

    public function testTranslateWithMultipleParameters(): void
    {
        $translator = new Translator('fr', [
            'fr' => ['messages' => ['greeting' => '{greeting}, {name}! Vous avez {count} messages.']]
        ]);

        $result = $translator->trans('messages.greeting', [
            'greeting' => 'Bonjour',
            'name' => 'Marie',
            'count' => 5
        ]);

        $this->assertSame('Bonjour, Marie! Vous avez 5 messages.', $result);
    }

    public function testParametersNotInMessageAreIgnored(): void
    {
        $translator = new Translator('fr', [
            'fr' => ['messages' => ['hello' => 'Bonjour']]
        ]);

        $this->assertSame(
            'Bonjour',
            $translator->trans('messages.hello', ['unused' => 'value'])
        );
    }

    // =========================================================================
    // Tests de pluralisation
    // =========================================================================

    public function testPluralizeWithZero(): void
    {
        $translator = new Translator('fr', [
            'fr' => ['messages' => [
                'items' => '{0}Aucun article|{1}Un article|[2,*]{count} articles'
            ]]
        ]);

        $this->assertSame('Aucun article', $translator->transChoice('messages.items', 0));
    }

    public function testPluralizeWithOne(): void
    {
        $translator = new Translator('fr', [
            'fr' => ['messages' => [
                'items' => '{0}Aucun article|{1}Un article|[2,*]{count} articles'
            ]]
        ]);

        $this->assertSame('Un article', $translator->transChoice('messages.items', 1));
    }

    public function testPluralizeWithMany(): void
    {
        $translator = new Translator('fr', [
            'fr' => ['messages' => [
                'items' => '{0}Aucun article|{1}Un article|[2,*]{count} articles'
            ]]
        ]);

        $this->assertSame('5 articles', $translator->transChoice('messages.items', 5, ['count' => 5]));
    }

    public function testPluralizeSimpleFormat(): void
    {
        $translator = new Translator('en', [
            'en' => ['messages' => [
                'apple' => 'one apple|many apples'
            ]]
        ]);

        $this->assertSame('one apple', $translator->transChoice('messages.apple', 1));
        $this->assertSame('many apples', $translator->transChoice('messages.apple', 5));
    }

    // =========================================================================
    // Tests du fallback
    // =========================================================================

    public function testFallbackToDefaultLocale(): void
    {
        $translator = new Translator('de', [
            'en' => ['messages' => ['hello' => 'Hello']],
            'de' => ['messages' => []]  // German has no translation
        ]);
        $translator->setFallbackLocale('en');

        $this->assertSame('Hello', $translator->trans('messages.hello'));
    }

    public function testFallbackChain(): void
    {
        $translator = new Translator('fr_CA', [
            'en' => ['messages' => ['hello' => 'Hello']],
            'fr' => ['messages' => ['hello' => 'Bonjour']],
            'fr_CA' => ['messages' => []]  // Canadian French has no translation
        ]);
        $translator->setFallbackLocales(['fr', 'en']);

        $this->assertSame('Bonjour', $translator->trans('messages.hello'));
    }

    // =========================================================================
    // Tests du changement de locale
    // =========================================================================

    public function testSetLocale(): void
    {
        $translator = new Translator('fr', [
            'fr' => ['messages' => ['hello' => 'Bonjour']],
            'en' => ['messages' => ['hello' => 'Hello']]
        ]);

        $this->assertSame('Bonjour', $translator->trans('messages.hello'));

        $translator->setLocale('en');

        $this->assertSame('Hello', $translator->trans('messages.hello'));
    }

    public function testGetLocale(): void
    {
        $translator = new Translator('fr');

        $this->assertSame('fr', $translator->getLocale());
    }

    // =========================================================================
    // Tests des domaines
    // =========================================================================

    public function testTranslateFromDifferentDomains(): void
    {
        $translator = new Translator('fr', [
            'fr' => [
                'messages' => ['hello' => 'Bonjour'],
                'errors' => ['not_found' => 'Non trouvé'],
                'admin' => ['dashboard' => 'Tableau de bord']
            ]
        ]);

        $this->assertSame('Bonjour', $translator->trans('messages.hello'));
        $this->assertSame('Non trouvé', $translator->trans('errors.not_found'));
        $this->assertSame('Tableau de bord', $translator->trans('admin.dashboard'));
    }

    // =========================================================================
    // Tests du PhpFileLoader
    // =========================================================================

    public function testPhpFileLoaderLoadsTranslations(): void
    {
        // Create translation file
        $content = "<?php\nreturn ['hello' => 'Bonjour', 'goodbye' => 'Au revoir'];";
        file_put_contents($this->translationsDir . '/messages.fr.php', $content);

        $loader = new PhpFileLoader($this->translationsDir);
        $translations = $loader->load('fr', 'messages');

        $this->assertSame('Bonjour', $translations['hello']);
        $this->assertSame('Au revoir', $translations['goodbye']);
    }

    public function testPhpFileLoaderReturnsEmptyArrayIfFileNotFound(): void
    {
        $loader = new PhpFileLoader($this->translationsDir);
        $translations = $loader->load('fr', 'nonexistent');

        $this->assertSame([], $translations);
    }

    // =========================================================================
    // Tests de l'ArrayLoader
    // =========================================================================

    public function testArrayLoaderLoadsTranslations(): void
    {
        $data = [
            'fr' => [
                'messages' => ['hello' => 'Bonjour']
            ]
        ];

        $loader = new ArrayLoader($data);
        $translations = $loader->load('fr', 'messages');

        $this->assertSame(['hello' => 'Bonjour'], $translations);
    }

    // =========================================================================
    // Tests de l'interface
    // =========================================================================

    public function testTranslatorImplementsInterface(): void
    {
        $translator = new Translator('fr');

        $this->assertInstanceOf(TranslatorInterface::class, $translator);
    }

    // =========================================================================
    // Tests avec clés imbriquées
    // =========================================================================

    public function testNestedKeys(): void
    {
        $translator = new Translator('fr', [
            'fr' => [
                'messages' => [
                    'user' => [
                        'profile' => [
                            'title' => 'Mon profil'
                        ]
                    ]
                ]
            ]
        ]);

        $this->assertSame('Mon profil', $translator->trans('messages.user.profile.title'));
    }

    // =========================================================================
    // Helper methods
    // =========================================================================

    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->removeDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }
}
