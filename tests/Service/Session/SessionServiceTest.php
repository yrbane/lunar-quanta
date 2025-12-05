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

    public function testStartCanBeCalledMultipleTimes(): void
    {
        $this->session->start();
        $this->session->set('key', 'value');
        $this->session->start(); // Should not reset data
        $this->session->start(); // Still should not reset

        $this->assertSame('value', $this->session->get('key'));
    }

    public function testRegenerate(): void
    {
        $this->session->set('user_id', 42);
        $this->session->regenerate();

        // Data should still exist after regenerate
        $this->assertSame(42, $this->session->get('user_id'));
    }

    public function testRegenerateWithoutActiveSession(): void
    {
        // Should not throw even when no session is active
        $session = new SessionService(testMode: true);
        $session->regenerate();
        $this->assertTrue(true); // If we get here, no exception was thrown
    }

    public function testAllExcludesFlashKeys(): void
    {
        $this->session->set('regular', 'data');
        $this->session->flash('flash_msg', 'temporary');

        $all = $this->session->all();

        $this->assertArrayHasKey('regular', $all);
        $this->assertArrayNotHasKey('_flash', $all);
        $this->assertArrayNotHasKey('_flash_new', $all);
    }

    public function testDestroyResetsStartedState(): void
    {
        $this->session->set('key', 'value');
        $this->session->destroy();

        // After destroy, setting a new value should work (starts fresh)
        $this->session->set('new_key', 'new_value');
        $this->assertSame('new_value', $this->session->get('new_key'));
    }

    public function testRemoveNonExistentKey(): void
    {
        // Should not throw when removing non-existent key
        $this->session->remove('nonexistent');
        $this->assertFalse($this->session->has('nonexistent'));
    }

    public function testSetNullValue(): void
    {
        $this->session->set('nullable', null);
        $this->assertTrue($this->session->has('nullable'));
        $this->assertNull($this->session->get('nullable'));
    }

    public function testSetFalseValue(): void
    {
        $this->session->set('boolean', false);
        $this->assertTrue($this->session->has('boolean'));
        $this->assertFalse($this->session->get('boolean'));
    }

    public function testSetZeroValue(): void
    {
        $this->session->set('zero', 0);
        $this->assertTrue($this->session->has('zero'));
        $this->assertSame(0, $this->session->get('zero'));
    }

    public function testSetEmptyStringValue(): void
    {
        $this->session->set('empty', '');
        $this->assertTrue($this->session->has('empty'));
        $this->assertSame('', $this->session->get('empty'));
    }

    public function testFlashWithNullValue(): void
    {
        $this->session->flash('nullable_flash', null);
        // Note: PHP's ?? operator returns the default when value is null
        // So storing null and getting with default returns the default
        $this->assertSame('default', $this->session->getFlash('nullable_flash', 'default'));
    }

    public function testFlashOverwritesSameKey(): void
    {
        $this->session->flash('msg', 'first');
        $this->session->flash('msg', 'second');

        $this->assertSame('second', $this->session->getFlash('msg'));
    }

    public function testAllReturnsEmptyArrayWhenNoData(): void
    {
        $session = new SessionService(testMode: true);
        $all = $session->all();

        $this->assertIsArray($all);
        $this->assertEmpty($all);
    }

    public function testHasReturnsFalseAfterRemove(): void
    {
        $this->session->set('key', 'value');
        $this->assertTrue($this->session->has('key'));

        $this->session->remove('key');
        $this->assertFalse($this->session->has('key'));
    }

    public function testHasReturnsFalseAfterDestroy(): void
    {
        $this->session->set('key', 'value');
        $this->session->destroy();

        $this->assertFalse($this->session->has('key'));
    }

    public function testGetDefaultNotUsedWhenKeyExists(): void
    {
        $this->session->set('key', 'actual');
        $this->assertSame('actual', $this->session->get('key', 'default'));
    }

    public function testFlashGetDefaultNotUsedWhenKeyExists(): void
    {
        $this->session->flash('msg', 'actual');
        $this->assertSame('actual', $this->session->getFlash('msg', 'default'));
    }

    public function testAllWithMultipleTypes(): void
    {
        $this->session->set('string', 'text');
        $this->session->set('int', 42);
        $this->session->set('float', 3.14);
        $this->session->set('bool', true);
        $this->session->set('array', [1, 2, 3]);
        $this->session->set('object', (object) ['foo' => 'bar']);

        $all = $this->session->all();

        $this->assertSame('text', $all['string']);
        $this->assertSame(42, $all['int']);
        $this->assertSame(3.14, $all['float']);
        $this->assertTrue($all['bool']);
        $this->assertSame([1, 2, 3], $all['array']);
        $this->assertEquals((object) ['foo' => 'bar'], $all['object']);
    }

    public function testDestroyAndSetNewData(): void
    {
        $this->session->set('old', 'data');
        $this->session->destroy();

        $this->session->set('new', 'data');
        $this->assertSame('data', $this->session->get('new'));
        $this->assertNull($this->session->get('old'));
    }
}
