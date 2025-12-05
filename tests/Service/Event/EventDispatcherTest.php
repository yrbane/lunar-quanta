<?php
/**
 * Tests du système d'événements.
 *
 * =============================================================================
 * TESTS DU DISPATCHER D'ÉVÉNEMENTS
 * =============================================================================
 *
 * Ces tests vérifient que :
 * - Les listeners sont correctement enregistrés
 * - Les événements sont dispatchés aux bons listeners
 * - Les priorités sont respectées
 * - Les événements stoppables fonctionnent
 * - Les données sont correctement transmises
 *
 * @package Tests\Service\Event
 */
declare(strict_types=1);

namespace Tests\Service\Event;

use Lunar\Service\Event\Event;
use Lunar\Service\Event\EventDispatcher;
use PHPUnit\Framework\TestCase;

class EventDispatcherTest extends TestCase
{
    private EventDispatcher $dispatcher;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dispatcher = new EventDispatcher();
    }

    // =========================================================================
    // TESTS DE L'ENREGISTREMENT DES LISTENERS
    // =========================================================================

    public function testAddListener(): void
    {
        $this->dispatcher->addListener('test.event', fn() => null);

        $this->assertTrue($this->dispatcher->hasListeners('test.event'));
        $this->assertSame(1, $this->dispatcher->countListeners('test.event'));
    }

    public function testAddMultipleListeners(): void
    {
        $this->dispatcher->addListener('test.event', fn() => null);
        $this->dispatcher->addListener('test.event', fn() => null);
        $this->dispatcher->addListener('test.event', fn() => null);

        $this->assertSame(3, $this->dispatcher->countListeners('test.event'));
    }

    public function testHasListenersReturnsFalseForUnknownEvent(): void
    {
        $this->assertFalse($this->dispatcher->hasListeners('unknown.event'));
    }

    public function testAddListenerIsChainable(): void
    {
        $result = $this->dispatcher
            ->addListener('event1', fn() => null)
            ->addListener('event2', fn() => null);

        $this->assertSame($this->dispatcher, $result);
    }

    // =========================================================================
    // TESTS DE LA SUPPRESSION DES LISTENERS
    // =========================================================================

    public function testRemoveListener(): void
    {
        $listener = fn() => null;

        $this->dispatcher->addListener('test.event', $listener);
        $this->assertTrue($this->dispatcher->hasListeners('test.event'));

        $this->dispatcher->removeListener('test.event', $listener);
        $this->assertFalse($this->dispatcher->hasListeners('test.event'));
    }

    public function testRemoveListenerDoesNotAffectOthers(): void
    {
        $listener1 = fn() => null;
        $listener2 = fn() => null;

        $this->dispatcher->addListener('test.event', $listener1);
        $this->dispatcher->addListener('test.event', $listener2);

        $this->dispatcher->removeListener('test.event', $listener1);

        $this->assertSame(1, $this->dispatcher->countListeners('test.event'));
    }

    public function testRemoveNonExistentListener(): void
    {
        $this->dispatcher->removeListener('unknown', fn() => null);

        // Pas d'exception, simplement rien ne se passe
        $this->assertFalse($this->dispatcher->hasListeners('unknown'));
    }

    public function testClearListenersForOneEvent(): void
    {
        $this->dispatcher->addListener('event1', fn() => null);
        $this->dispatcher->addListener('event2', fn() => null);

        $this->dispatcher->clearListeners('event1');

        $this->assertFalse($this->dispatcher->hasListeners('event1'));
        $this->assertTrue($this->dispatcher->hasListeners('event2'));
    }

    public function testClearAllListeners(): void
    {
        $this->dispatcher->addListener('event1', fn() => null);
        $this->dispatcher->addListener('event2', fn() => null);

        $this->dispatcher->clearListeners();

        $this->assertFalse($this->dispatcher->hasListeners('event1'));
        $this->assertFalse($this->dispatcher->hasListeners('event2'));
    }

    // =========================================================================
    // TESTS DU DISPATCH
    // =========================================================================

    public function testDispatchCallsListener(): void
    {
        $called = false;

        $this->dispatcher->addListener('test.event', function () use (&$called): void {
            $called = true;
        });

        $event = new Event('test.event');
        $this->dispatcher->dispatch($event);

        $this->assertTrue($called);
    }

    public function testDispatchCallsAllListeners(): void
    {
        $count = 0;

        $this->dispatcher->addListener('test.event', function () use (&$count): void {
            $count++;
        });
        $this->dispatcher->addListener('test.event', function () use (&$count): void {
            $count++;
        });

        $event = new Event('test.event');
        $this->dispatcher->dispatch($event);

        $this->assertSame(2, $count);
    }

    public function testDispatchPassesEventToListener(): void
    {
        $receivedEvent = null;

        $this->dispatcher->addListener('test.event', function (Event $event) use (&$receivedEvent): void {
            $receivedEvent = $event;
        });

        $event = new Event('test.event', ['key' => 'value']);
        $this->dispatcher->dispatch($event);

        $this->assertSame($event, $receivedEvent);
    }

    public function testDispatchReturnsEvent(): void
    {
        $event = new Event('test.event');
        $returned = $this->dispatcher->dispatch($event);

        $this->assertSame($event, $returned);
    }

    public function testDispatchWithNoListeners(): void
    {
        $event = new Event('no.listeners');
        $returned = $this->dispatcher->dispatch($event);

        $this->assertSame($event, $returned);
    }

    // =========================================================================
    // TESTS DES PRIORITÉS
    // =========================================================================

    public function testListenersAreCalledByPriority(): void
    {
        $order = [];

        $this->dispatcher->addListener('test.event', function () use (&$order): void {
            $order[] = 'normal';
        }, 0);

        $this->dispatcher->addListener('test.event', function () use (&$order): void {
            $order[] = 'first';
        }, 100);

        $this->dispatcher->addListener('test.event', function () use (&$order): void {
            $order[] = 'last';
        }, -100);

        $event = new Event('test.event');
        $this->dispatcher->dispatch($event);

        $this->assertSame(['first', 'normal', 'last'], $order);
    }

    public function testSamePriorityPreservesOrder(): void
    {
        $order = [];

        $this->dispatcher->addListener('test.event', function () use (&$order): void {
            $order[] = 'first';
        }, 0);

        $this->dispatcher->addListener('test.event', function () use (&$order): void {
            $order[] = 'second';
        }, 0);

        $event = new Event('test.event');
        $this->dispatcher->dispatch($event);

        $this->assertSame(['first', 'second'], $order);
    }

    // =========================================================================
    // TESTS DES ÉVÉNEMENTS STOPPABLES
    // =========================================================================

    public function testStopPropagation(): void
    {
        $called = [];

        $this->dispatcher->addListener('test.event', function (Event $event) use (&$called): void {
            $called[] = 'first';
            $event->stopPropagation();
        }, 100);

        $this->dispatcher->addListener('test.event', function () use (&$called): void {
            $called[] = 'second';
        }, 0);

        $event = new Event('test.event');
        $this->dispatcher->dispatch($event);

        $this->assertSame(['first'], $called);
        $this->assertTrue($event->isPropagationStopped());
    }

    // =========================================================================
    // TESTS DE LA CLASSE EVENT
    // =========================================================================

    public function testEventName(): void
    {
        $event = new Event('user.created');

        $this->assertSame('user.created', $event->getName());
    }

    public function testEventData(): void
    {
        $event = new Event('test', ['key' => 'value', 'count' => 42]);

        $this->assertSame('value', $event->get('key'));
        $this->assertSame(42, $event->get('count'));
        $this->assertNull($event->get('unknown'));
        $this->assertSame('default', $event->get('unknown', 'default'));
    }

    public function testEventSetData(): void
    {
        $event = new Event('test');
        $event->set('key', 'value');

        $this->assertSame('value', $event->get('key'));
    }

    public function testEventSetIsChainable(): void
    {
        $event = new Event('test');
        $result = $event->set('key1', 'value1')->set('key2', 'value2');

        $this->assertSame($event, $result);
    }

    public function testEventHas(): void
    {
        $event = new Event('test', ['key' => 'value']);

        $this->assertTrue($event->has('key'));
        $this->assertFalse($event->has('unknown'));
    }

    public function testEventGetData(): void
    {
        $data = ['key1' => 'value1', 'key2' => 'value2'];
        $event = new Event('test', $data);

        $this->assertSame($data, $event->getData());
    }

    public function testEventPropagation(): void
    {
        $event = new Event('test');

        $this->assertFalse($event->isPropagationStopped());

        $event->stopPropagation();

        $this->assertTrue($event->isPropagationStopped());
    }

    // =========================================================================
    // TESTS AVANCÉS
    // =========================================================================

    public function testAddListenerForMultipleEvents(): void
    {
        $count = 0;

        $this->dispatcher->addListenerForEvents(
            ['event1', 'event2', 'event3'],
            function () use (&$count): void {
                $count++;
            }
        );

        $this->dispatcher->dispatch(new Event('event1'));
        $this->dispatcher->dispatch(new Event('event2'));
        $this->dispatcher->dispatch(new Event('event3'));

        $this->assertSame(3, $count);
    }

    public function testListenerCanModifyEventData(): void
    {
        $this->dispatcher->addListener('test.event', function (Event $event): void {
            $current = $event->get('count', 0);
            $event->set('count', $current + 1);
        });

        $this->dispatcher->addListener('test.event', function (Event $event): void {
            $current = $event->get('count', 0);
            $event->set('count', $current + 1);
        });

        $event = new Event('test.event');
        $this->dispatcher->dispatch($event);

        $this->assertSame(2, $event->get('count'));
    }

    public function testGetListenersReturnsSortedListeners(): void
    {
        $listener1 = fn() => 'low';
        $listener2 = fn() => 'high';
        $listener3 = fn() => 'normal';

        $this->dispatcher->addListener('test', $listener1, -10);
        $this->dispatcher->addListener('test', $listener2, 10);
        $this->dispatcher->addListener('test', $listener3, 0);

        $listeners = $this->dispatcher->getListeners('test');

        $this->assertSame($listener2, $listeners[0]);
        $this->assertSame($listener3, $listeners[1]);
        $this->assertSame($listener1, $listeners[2]);
    }

    public function testGetListenersForUnknownEventReturnsEmptyArray(): void
    {
        $listeners = $this->dispatcher->getListeners('unknown');

        $this->assertSame([], $listeners);
    }

    // =========================================================================
    // TESTS DE SCÉNARIOS RÉELS
    // =========================================================================

    public function testUserRegistrationScenario(): void
    {
        $actions = [];

        // Listener de validation (priorité haute)
        $this->dispatcher->addListener('user.registering', function (Event $event) use (&$actions): void {
            $email = $event->get('email');
            if ($email === 'blocked@example.com') {
                $event->set('valid', false);
                $event->stopPropagation();
                $actions[] = 'blocked';
            } else {
                $event->set('valid', true);
                $actions[] = 'validated';
            }
        }, 100);

        // Listener d'enregistrement (priorité normale)
        $this->dispatcher->addListener('user.registering', function (Event $event) use (&$actions): void {
            if ($event->get('valid')) {
                $actions[] = 'registered';
            }
        }, 0);

        // Listener d'email (priorité basse)
        $this->dispatcher->addListener('user.registering', function (Event $event) use (&$actions): void {
            if ($event->get('valid')) {
                $actions[] = 'email_sent';
            }
        }, -100);

        // Test avec email valide
        $event1 = new Event('user.registering', ['email' => 'john@example.com']);
        $this->dispatcher->dispatch($event1);
        $this->assertSame(['validated', 'registered', 'email_sent'], $actions);

        // Reset et test avec email bloqué
        $actions = [];
        $event2 = new Event('user.registering', ['email' => 'blocked@example.com']);
        $this->dispatcher->dispatch($event2);
        $this->assertSame(['blocked'], $actions);
    }
}
