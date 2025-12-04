<?php

declare(strict_types=1);

namespace Tests\Service\Core;

use Lunar\Service\Core\BaseController;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class ConcreteController extends BaseController
{
    public function testRender(string $template, array $variables = []): string
    {
        return $this->render($template, $variables);
    }
}

class BaseControllerTest extends TestCase
{
    public function testConstructorCreatesInstance(): void
    {
        $controller = new ConcreteController();

        $this->assertInstanceOf(BaseController::class, $controller);
    }

    public function testRenderReturnsString(): void
    {
        $controller = new ConcreteController();

        $result = $controller->testRender('error.html', [
            'title' => 'Test',
            'errorCode' => 404,
            'errorMessage' => 'Not Found',
        ]);

        $this->assertIsString($result);
        $this->assertStringContainsString('Test', $result);
    }

    public function testRenderWithEmptyVariables(): void
    {
        $controller = new ConcreteController();

        $result = $controller->testRender('error.html', [
            'title' => '',
            'errorCode' => 0,
            'errorMessage' => '',
        ]);

        $this->assertIsString($result);
    }

    public function testRenderWithVariables(): void
    {
        $controller = new ConcreteController();

        $result = $controller->testRender('error.html', [
            'title' => 'Error Page',
            'errorCode' => 500,
            'errorMessage' => 'Internal Server Error',
        ]);

        $this->assertStringContainsString('Error Page', $result);
        $this->assertStringContainsString('500', $result);
    }
}
