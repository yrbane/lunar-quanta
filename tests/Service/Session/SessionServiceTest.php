<?php

declare(strict_types=1);

namespace Tests\Service\Session;

use Lunar\Service\Session\SessionService;
use PHPUnit\Framework\TestCase;

class SessionServiceTest extends TestCase
{
    private SessionService $session;

    protected function setUp(): void
    {
        // Use a mock session handler for testing
        $this->session = new SessionService(testMode: true);
    }

    public function testSetAndGet(): void
    {
        $this->session->set('user_id', 123);
        $this->assertSame(123, $this->session->get('user_id'));
    }

    public function testGetWithDefault(): void
    {
        $this->assertSame('default', $this->session->get('nonexistent', 'default'));
    }

    public function testGetReturnsNullByDefault(): void
    {
        $this->assertNull($this->session->get('nonexistent'));
    }

    public function testHas(): void
    {
        $this->assertFalse($this->session->has('key'));
        $this->session->set('key', 'value');
        $this->assertTrue($this->session->has('key'));
    }

    public function testRemove(): void
    {
        $this->session->set('key', 'value');
        $this->assertTrue($this->session->has('key'));

        $this->session->remove('key');
        $this->assertFalse($this->session->has('key'));
    }

    public function testFlashMessage(): void
    {
        $this->session->flash('success', 'Operation completed!');

        // First read should return the value
        $this->assertSame('Operation completed!', $this->session->getFlash('success'));

        // Second read should return default (flash is consumed)
        $this->assertNull($this->session->getFlash('success'));
    }

    public function testFlashWithDefault(): void
    {
        $this->assertSame('default', $this->session->getFlash('nonexistent', 'default'));
    }

    public function testMultipleFlashMessages(): void
    {
        $this->session->flash('success', 'Saved!');
        $this->session->flash('error', 'Failed!');

        $this->assertSame('Saved!', $this->session->getFlash('success'));
        $this->assertSame('Failed!', $this->session->getFlash('error'));
    }

    public function testAll(): void
    {
        $this->session->set('a', 1);
        $this->session->set('b', 2);

        $all = $this->session->all();
        $this->assertSame(1, $all['a']);
        $this->assertSame(2, $all['b']);
    }

    public function testDestroy(): void
    {
        $this->session->set('key', 'value');
        $this->session->destroy();

        $this->assertFalse($this->session->has('key'));
    }

    public function testSetOverwrites(): void
    {
        $this->session->set('key', 'first');
        $this->session->set('key', 'second');

        $this->assertSame('second', $this->session->get('key'));
    }

    public function testSetComplexValues(): void
    {
        $array = ['a' => 1, 'b' => [2, 3]];
        $this->session->set('data', $array);

        $this->assertSame($array, $this->session->get('data'));
    }

    public function testFlashDoesNotAffectRegularData(): void
    {
        $this->session->set('permanent', 'stays');
        $this->session->flash('temporary', 'goes');

        // Read flash
        $this->session->getFlash('temporary');

        // Permanent data should still exist
        $this->assertSame('stays', $this->session->get('permanent'));
    }
}
