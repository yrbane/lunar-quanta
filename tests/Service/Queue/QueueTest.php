<?php

declare(strict_types=1);

namespace Tests\Service\Queue;

use Lunar\Service\Queue\Job;
use Lunar\Service\Queue\JobInterface;
use Lunar\Service\Queue\Queue;
use Lunar\Service\Queue\QueueInterface;
use Lunar\Service\Queue\Driver\SyncDriver;
use Lunar\Service\Queue\Driver\FileDriver;
use Lunar\Service\Queue\Worker;
use PHPUnit\Framework\TestCase;
use Tests\Service\Queue\Fixtures\TestCounterJob;
use Tests\Service\Queue\Fixtures\TestJobWithPayload;
use Tests\Service\Queue\Fixtures\TestFailingJob;

/**
 * Tests pour le système de queue et jobs.
 *
 * Le système de queue permet d'exécuter des tâches de manière asynchrone :
 * - SyncDriver : exécution immédiate (pour les tests)
 * - FileDriver : stockage dans des fichiers JSON (sans dépendance Redis/etc)
 */
final class QueueTest extends TestCase
{
    private string $queuePath;

    protected function setUp(): void
    {
        $this->queuePath = sys_get_temp_dir() . '/lunar_queue_test_' . uniqid();
        mkdir($this->queuePath, 0755, true);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->queuePath);
    }

    // =========================================================================
    // Tests de l'interface Job
    // =========================================================================

    public function testJobHasHandleMethod(): void
    {
        $job = new class implements JobInterface {
            public bool $handled = false;

            public function handle(): void
            {
                $this->handled = true;
            }

            public function getPayload(): array
            {
                return [];
            }
        };

        $job->handle();

        $this->assertTrue($job->handled);
    }

    public function testJobCanHavePayload(): void
    {
        $job = new TestJobWithPayload(['email' => 'test@example.com']);

        $this->assertSame('test@example.com', $job->getPayload()['email']);
    }

    // =========================================================================
    // Tests du SyncDriver (exécution synchrone)
    // =========================================================================

    public function testSyncDriverExecutesImmediately(): void
    {
        $driver = new SyncDriver();
        $queue = new Queue($driver);

        $job = new TestCounterJob();
        TestCounterJob::$counter = 0;

        $queue->push($job);

        // Avec SyncDriver, le job est exécuté immédiatement
        $this->assertSame(1, TestCounterJob::$counter);
    }

    public function testSyncDriverReturnsJobId(): void
    {
        $driver = new SyncDriver();
        $queue = new Queue($driver);

        $jobId = $queue->push(new TestCounterJob());

        $this->assertNotEmpty($jobId);
    }

    // =========================================================================
    // Tests du FileDriver
    // =========================================================================

    public function testFileDriverPushesToQueue(): void
    {
        $driver = new FileDriver($this->queuePath);
        $queue = new Queue($driver);

        $jobId = $queue->push(new TestCounterJob());

        $this->assertNotEmpty($jobId);
        $this->assertTrue($driver->hasJobs());
    }

    public function testFileDriverPopReturnsJob(): void
    {
        $driver = new FileDriver($this->queuePath);
        $queue = new Queue($driver);

        $queue->push(new TestJobWithPayload(['key' => 'value']));

        $job = $driver->pop();

        $this->assertInstanceOf(JobInterface::class, $job);
        $this->assertSame('value', $job->getPayload()['key']);
    }

    public function testFileDriverPopReturnsNullWhenEmpty(): void
    {
        $driver = new FileDriver($this->queuePath);

        $job = $driver->pop();

        $this->assertNull($job);
    }

    public function testFileDriverRemovesJobAfterPop(): void
    {
        $driver = new FileDriver($this->queuePath);
        $queue = new Queue($driver);

        $queue->push(new TestCounterJob());

        $this->assertTrue($driver->hasJobs());

        $driver->pop();

        $this->assertFalse($driver->hasJobs());
    }

    public function testFileDriverHandlesMultipleJobs(): void
    {
        $driver = new FileDriver($this->queuePath);
        $queue = new Queue($driver);

        $queue->push(new TestJobWithPayload(['order' => 1]));
        $queue->push(new TestJobWithPayload(['order' => 2]));
        $queue->push(new TestJobWithPayload(['order' => 3]));

        $this->assertSame(3, $driver->count());

        // FIFO order
        $job1 = $driver->pop();
        $this->assertSame(1, $job1->getPayload()['order']);

        $job2 = $driver->pop();
        $this->assertSame(2, $job2->getPayload()['order']);
    }

    // =========================================================================
    // Tests du Worker
    // =========================================================================

    public function testWorkerProcessesSingleJob(): void
    {
        $driver = new FileDriver($this->queuePath);
        $queue = new Queue($driver);
        $worker = new Worker($driver);

        TestCounterJob::$counter = 0;
        $queue->push(new TestCounterJob());

        $processed = $worker->processNext();

        $this->assertTrue($processed);
        $this->assertSame(1, TestCounterJob::$counter);
    }

    public function testWorkerReturnsFalseWhenNoJobs(): void
    {
        $driver = new FileDriver($this->queuePath);
        $worker = new Worker($driver);

        $processed = $worker->processNext();

        $this->assertFalse($processed);
    }

    public function testWorkerProcessesMultipleJobs(): void
    {
        $driver = new FileDriver($this->queuePath);
        $queue = new Queue($driver);
        $worker = new Worker($driver);

        TestCounterJob::$counter = 0;
        $queue->push(new TestCounterJob());
        $queue->push(new TestCounterJob());
        $queue->push(new TestCounterJob());

        $count = $worker->processAll();

        $this->assertSame(3, $count);
        $this->assertSame(3, TestCounterJob::$counter);
    }

    // =========================================================================
    // Tests de gestion des erreurs
    // =========================================================================

    public function testWorkerHandlesJobException(): void
    {
        $driver = new FileDriver($this->queuePath);
        $queue = new Queue($driver);
        $worker = new Worker($driver);

        // Utiliser un failure handler silencieux pour éviter error_log
        $worker->setFailureHandler(function () {});

        $queue->push(new TestFailingJob());

        // Le worker doit gérer l'exception sans la propager
        $processed = $worker->processNext();

        $this->assertTrue($processed);
    }

    public function testFailedJobIsRecorded(): void
    {
        $driver = new FileDriver($this->queuePath);
        $queue = new Queue($driver);
        $worker = new Worker($driver);

        // Utiliser un failure handler silencieux pour éviter error_log
        $worker->setFailureHandler(function () {});

        $queue->push(new TestFailingJob());
        $worker->processNext();

        $this->assertSame(1, $worker->getFailedCount());
    }

    public function testCustomFailureHandlerReceivesJobAndException(): void
    {
        $driver = new FileDriver($this->queuePath);
        $queue = new Queue($driver);
        $worker = new Worker($driver);

        $capturedJob = null;
        $capturedException = null;

        $worker->setFailureHandler(function (JobInterface $job, \Throwable $e) use (&$capturedJob, &$capturedException) {
            $capturedJob = $job;
            $capturedException = $e;
        });

        $queue->push(new TestFailingJob());
        $worker->processNext();

        $this->assertInstanceOf(TestFailingJob::class, $capturedJob);
        $this->assertSame('Job failed intentionally', $capturedException->getMessage());
    }

    // =========================================================================
    // Tests des queues nommées
    // =========================================================================

    public function testPushToNamedQueue(): void
    {
        $driver = new FileDriver($this->queuePath);
        $queue = new Queue($driver);

        $queue->pushOn('emails', new TestCounterJob());
        $queue->pushOn('notifications', new TestCounterJob());

        $this->assertSame(1, $driver->count('emails'));
        $this->assertSame(1, $driver->count('notifications'));
    }

    public function testPopFromNamedQueue(): void
    {
        $driver = new FileDriver($this->queuePath);
        $queue = new Queue($driver);

        $queue->pushOn('emails', new TestJobWithPayload(['type' => 'email']));
        $queue->pushOn('notifications', new TestJobWithPayload(['type' => 'notification']));

        $job = $driver->pop('emails');

        $this->assertSame('email', $job->getPayload()['type']);
    }

    // =========================================================================
    // Tests de délai
    // =========================================================================

    public function testDelayedJob(): void
    {
        $driver = new FileDriver($this->queuePath);
        $queue = new Queue($driver);

        $queue->later(10, new TestCounterJob()); // 10 secondes de délai

        // Le job ne devrait pas être disponible immédiatement
        $this->assertFalse($driver->hasAvailableJobs());
    }

    // =========================================================================
    // Tests de l'interface Queue
    // =========================================================================

    public function testQueueImplementsInterface(): void
    {
        $driver = new SyncDriver();
        $queue = new Queue($driver);

        $this->assertInstanceOf(QueueInterface::class, $queue);
    }

    // =========================================================================
    // Tests additionnels
    // =========================================================================

    public function testLaterOnPushesToNamedQueueWithDelay(): void
    {
        $driver = new FileDriver($this->queuePath);
        $queue = new Queue($driver);

        $queue->laterOn('emails', 10, new TestCounterJob());

        $this->assertTrue($driver->hasJobs('emails'));
        $this->assertFalse($driver->hasAvailableJobs('emails'));
    }

    public function testGetDriverReturnsTheDriver(): void
    {
        $driver = new FileDriver($this->queuePath);
        $queue = new Queue($driver);

        $this->assertSame($driver, $queue->getDriver());
    }

    public function testWorkerProcessedCountIncrementsOnSuccess(): void
    {
        $driver = new FileDriver($this->queuePath);
        $queue = new Queue($driver);
        $worker = new Worker($driver);

        TestCounterJob::$counter = 0;
        $queue->push(new TestCounterJob());
        $queue->push(new TestCounterJob());

        $worker->processNext();
        $this->assertSame(1, $worker->getProcessedCount());

        $worker->processNext();
        $this->assertSame(2, $worker->getProcessedCount());
    }

    public function testWorkerResetCounters(): void
    {
        $driver = new FileDriver($this->queuePath);
        $queue = new Queue($driver);
        $worker = new Worker($driver);

        $worker->setFailureHandler(function () {});

        $queue->push(new TestCounterJob());
        $queue->push(new TestFailingJob());

        $worker->processAll();

        $this->assertSame(1, $worker->getProcessedCount());
        $this->assertSame(1, $worker->getFailedCount());

        $worker->resetCounters();

        $this->assertSame(0, $worker->getProcessedCount());
        $this->assertSame(0, $worker->getFailedCount());
    }

    public function testAbstractJobClass(): void
    {
        $job = new class(['key' => 'value']) extends Job {
            public function handle(): void
            {
                // Do nothing
            }
        };

        $this->assertSame(['key' => 'value'], $job->getPayload());
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
