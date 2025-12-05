<?php
/**
 * Tests unitaires pour la hiérarchie d'exceptions du framework.
 *
 * =============================================================================
 * PHILOSOPHIE DES TESTS D'EXCEPTION
 * =============================================================================
 *
 * Les tests d'exceptions vérifient :
 * 1. La HIÉRARCHIE : Chaque exception hérite correctement
 * 2. Le MESSAGE : Le message est bien transmis
 * 3. Le CODE : Le code d'erreur est correct
 * 4. Le CHAÎNAGE : Les exceptions peuvent encapsuler d'autres exceptions
 *
 * ```
 * POURQUOI TESTER LES EXCEPTIONS ?
 *
 *    try {
 *        riskyOperation();
 *    } catch (RouterException $e) {
 *        // Si l'exception n'hérite pas de LunarException,
 *        // ce catch ne fonctionnera pas comme prévu !
 *    }
 *
 * Les tests garantissent que la hiérarchie est respectée.
 * ```
 *
 * =============================================================================
 * COUVERTURE DE CODE
 * =============================================================================
 *
 * Ces tests augmentent la couverture des classes d'exception de 0% à 100%.
 * Même si les classes sont simples (juste extends), les tests valident
 * que l'héritage fonctionne correctement.
 *
 * @package Tests\Exception
 */
declare(strict_types=1);

namespace Tests\Exception;

use Lunar\Exception\ContainerException;
use Lunar\Exception\LunarException;
use Lunar\Exception\RouterException;
use Lunar\Exception\SecurityException;
use Lunar\Exception\StorageException;
use Lunar\Exception\TemplateException;
use PHPUnit\Framework\TestCase;

class ExceptionTest extends TestCase
{
    // =========================================================================
    // TESTS DE LunarException (CLASSE DE BASE)
    // =========================================================================

    public function testLunarExceptionExtendsException(): void
    {
        $exception = new LunarException('Test message');

        $this->assertInstanceOf(\Exception::class, $exception);
    }

    public function testLunarExceptionWithMessage(): void
    {
        $exception = new LunarException('Something went wrong');

        $this->assertSame('Something went wrong', $exception->getMessage());
    }

    public function testLunarExceptionWithCode(): void
    {
        $exception = new LunarException('Error', 500);

        $this->assertSame(500, $exception->getCode());
    }

    public function testLunarExceptionWithPreviousException(): void
    {
        $previous = new \RuntimeException('Original error');
        $exception = new LunarException('Wrapper error', 0, $previous);

        $this->assertSame($previous, $exception->getPrevious());
    }

    public function testLunarExceptionDefaultCode(): void
    {
        $exception = new LunarException('Test');

        $this->assertSame(0, $exception->getCode());
    }

    public function testLunarExceptionFileAndLine(): void
    {
        $exception = new LunarException('Test');

        $this->assertSame(__FILE__, $exception->getFile());
        $this->assertIsInt($exception->getLine());
    }

    // =========================================================================
    // TESTS DE RouterException
    // =========================================================================

    public function testRouterExceptionExtendsLunarException(): void
    {
        $exception = new RouterException('Route not found');

        $this->assertInstanceOf(LunarException::class, $exception);
        $this->assertInstanceOf(\Exception::class, $exception);
    }

    public function testRouterExceptionWith404Code(): void
    {
        $exception = new RouterException('Page not found', 404);

        $this->assertSame('Page not found', $exception->getMessage());
        $this->assertSame(404, $exception->getCode());
    }

    public function testRouterExceptionCatchableAsLunarException(): void
    {
        $caught = false;

        try {
            throw new RouterException('Test');
        } catch (LunarException $e) {
            $caught = true;
        }

        $this->assertTrue($caught);
    }

    // =========================================================================
    // TESTS DE ContainerException
    // =========================================================================

    public function testContainerExceptionExtendsLunarException(): void
    {
        $exception = new ContainerException('Service not found');

        $this->assertInstanceOf(LunarException::class, $exception);
    }

    public function testContainerExceptionWithCircularDependency(): void
    {
        $message = 'Circular dependency detected: A -> B -> A';
        $exception = new ContainerException($message);

        $this->assertSame($message, $exception->getMessage());
    }

    public function testContainerExceptionCatchableAsLunarException(): void
    {
        $caught = false;

        try {
            throw new ContainerException('Test');
        } catch (LunarException $e) {
            $caught = true;
        }

        $this->assertTrue($caught);
    }

    // =========================================================================
    // TESTS DE TemplateException
    // =========================================================================

    public function testTemplateExceptionExtendsLunarException(): void
    {
        $exception = new TemplateException('Template not found');

        $this->assertInstanceOf(LunarException::class, $exception);
    }

    public function testTemplateExceptionWithTemplatePath(): void
    {
        $message = 'Template not found: /path/to/missing.tpl';
        $exception = new TemplateException($message);

        $this->assertStringContainsString('missing.tpl', $exception->getMessage());
    }

    public function testTemplateExceptionCatchableAsLunarException(): void
    {
        $caught = false;

        try {
            throw new TemplateException('Test');
        } catch (LunarException $e) {
            $caught = true;
        }

        $this->assertTrue($caught);
    }

    // =========================================================================
    // TESTS DE SecurityException
    // =========================================================================

    public function testSecurityExceptionExtendsLunarException(): void
    {
        $exception = new SecurityException('Access denied');

        $this->assertInstanceOf(LunarException::class, $exception);
    }

    public function testSecurityExceptionWith403Code(): void
    {
        $exception = new SecurityException('Forbidden', 403);

        $this->assertSame(403, $exception->getCode());
    }

    public function testSecurityExceptionForCsrfFailure(): void
    {
        $message = 'CSRF token mismatch';
        $exception = new SecurityException($message);

        $this->assertSame($message, $exception->getMessage());
    }

    public function testSecurityExceptionCatchableAsLunarException(): void
    {
        $caught = false;

        try {
            throw new SecurityException('Test');
        } catch (LunarException $e) {
            $caught = true;
        }

        $this->assertTrue($caught);
    }

    // =========================================================================
    // TESTS DE StorageException
    // =========================================================================

    public function testStorageExceptionExtendsLunarException(): void
    {
        $exception = new StorageException('Write failed');

        $this->assertInstanceOf(LunarException::class, $exception);
    }

    public function testStorageExceptionForFileOperation(): void
    {
        $message = 'Cannot write to /path/to/file.json';
        $exception = new StorageException($message);

        $this->assertStringContainsString('file.json', $exception->getMessage());
    }

    public function testStorageExceptionCatchableAsLunarException(): void
    {
        $caught = false;

        try {
            throw new StorageException('Test');
        } catch (LunarException $e) {
            $caught = true;
        }

        $this->assertTrue($caught);
    }

    // =========================================================================
    // TESTS DE CHAÎNAGE D'EXCEPTIONS
    // =========================================================================

    public function testExceptionChaining(): void
    {
        $original = new \PDOException('Connection failed');
        $storage = new StorageException('Database error', 0, $original);
        $lunar = new LunarException('System error', 500, $storage);

        $this->assertSame($storage, $lunar->getPrevious());
        $this->assertSame($original, $storage->getPrevious());
    }

    public function testExceptionTraceContainsInfo(): void
    {
        $exception = new LunarException('Test');
        $trace = $exception->getTrace();

        $this->assertIsArray($trace);
    }

    public function testExceptionTraceAsString(): void
    {
        $exception = new LunarException('Test');
        $traceString = $exception->getTraceAsString();

        $this->assertIsString($traceString);
        $this->assertNotEmpty($traceString);
    }

    // =========================================================================
    // TESTS DE HIÉRARCHIE GLOBALE
    // =========================================================================

    public function testAllExceptionsCatchableWithGenericHandler(): void
    {
        $exceptions = [
            new RouterException('Router'),
            new ContainerException('Container'),
            new TemplateException('Template'),
            new SecurityException('Security'),
            new StorageException('Storage'),
        ];

        foreach ($exceptions as $exception) {
            $caught = false;
            try {
                throw $exception;
            } catch (LunarException $e) {
                $caught = true;
            }
            $this->assertTrue($caught, get_class($exception) . ' not catchable as LunarException');
        }
    }

    public function testSpecificExceptionNotCaughtByWrongHandler(): void
    {
        $caughtAsRouter = false;
        $caughtAsLunar = false;

        try {
            throw new SecurityException('Security issue');
        } catch (RouterException $e) {
            $caughtAsRouter = true;
        } catch (LunarException $e) {
            $caughtAsLunar = true;
        }

        $this->assertFalse($caughtAsRouter);
        $this->assertTrue($caughtAsLunar);
    }

    // =========================================================================
    // TESTS AVEC DONNÉES PROVIDER
    // =========================================================================

    #[\PHPUnit\Framework\Attributes\DataProvider('exceptionClassProvider')]
    public function testExceptionInheritance(string $exceptionClass): void
    {
        $exception = new $exceptionClass('Test message');

        $this->assertInstanceOf(LunarException::class, $exception);
        $this->assertInstanceOf(\Exception::class, $exception);
        $this->assertInstanceOf(\Throwable::class, $exception);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function exceptionClassProvider(): array
    {
        return [
            'LunarException' => [LunarException::class],
            'RouterException' => [RouterException::class],
            'ContainerException' => [ContainerException::class],
            'TemplateException' => [TemplateException::class],
            'SecurityException' => [SecurityException::class],
            'StorageException' => [StorageException::class],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('exceptionClassProvider')]
    public function testExceptionToString(string $exceptionClass): void
    {
        $exception = new $exceptionClass('Test message', 42);
        $string = (string) $exception;

        $this->assertIsString($string);
        $this->assertStringContainsString('Test message', $string);
    }
}
