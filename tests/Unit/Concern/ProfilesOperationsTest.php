<?php
declare(strict_types=1);

namespace MethorZ\Profiler\Tests\Unit\Concern;

use MethorZ\Profiler\Concern\ProfilesOperations;
use MethorZ\Profiler\PerformanceMonitor;
use PHPUnit\Framework\TestCase;

final class ProfilesOperationsTest extends TestCase
{
    public function testStartProfilingReturnsProfiler(): void
    {
        $object = new class {
            use ProfilesOperations;

            public function testMethod(): void
            {
                $profiler = $this->startProfiling('test_operation');
                $profiler->end();
            }
        };

        $object->testMethod();

        $this->assertTrue(true); // No exception thrown
    }

    public function testGetLastProfilingMetricsReturnsStoredMetrics(): void
    {
        $object = new class {
            use ProfilesOperations;

            public function performOperation(): void
            {
                $profiler = $this->startProfiling('test_operation');
                $profiler->checkpoint('phase1');
                $profiler->addCount('rows', 100);
                $profiler->end();
            }

            public function getMetrics(): array
            {
                return $this->getLastProfilingMetrics();
            }
        };

        $object->performOperation();
        $metrics = $object->getMetrics();

        $this->assertArrayHasKey('operation', $metrics);
        $this->assertArrayHasKey('total', $metrics);
        $this->assertEquals('test_operation', $metrics['operation']);
        $this->assertEquals(100, $metrics['counts']['rows']);
    }

    public function testSetProfilingMonitorStoresMonitor(): void
    {
        $object = new class {
            use ProfilesOperations;

            public function setMonitor(PerformanceMonitor $monitor): void
            {
                $this->setProfilingMonitor($monitor);
            }

            public function getMonitor(): ?PerformanceMonitor
            {
                return $this->getProfilingMonitor();
            }
        };

        $monitor = PerformanceMonitor::create();
        $object->setMonitor($monitor);

        $this->assertSame($monitor, $object->getMonitor());
    }

    public function testClearProfilingMetricsRemovesMetrics(): void
    {
        $object = new class {
            use ProfilesOperations;

            public function performOperation(): void
            {
                $profiler = $this->startProfiling('test_operation');
                $profiler->end();
            }

            public function clear(): void
            {
                $this->clearProfilingMetrics();
            }

            public function getMetrics(): array
            {
                return $this->getLastProfilingMetrics();
            }
        };

        $object->performOperation();
        $this->assertNotEmpty($object->getMetrics());

        $object->clear();
        $this->assertEmpty($object->getMetrics());
    }

    public function testAutomaticMetricsCaptureOnEnd(): void
    {
        $object = new class {
            use ProfilesOperations;

            public function operation1(): void
            {
                $profiler = $this->startProfiling('operation1');
                $profiler->addCount('rows', 50);
                $profiler->end();
            }

            public function operation2(): void
            {
                $profiler = $this->startProfiling('operation2');
                $profiler->addCount('rows', 100);
                $profiler->end();
            }

            public function getMetrics(): array
            {
                return $this->getLastProfilingMetrics();
            }
        };

        $object->operation1();
        $metrics1 = $object->getMetrics();
        $this->assertEquals('operation1', $metrics1['operation']);
        $this->assertEquals(50, $metrics1['counts']['rows']);

        $object->operation2();
        $metrics2 = $object->getMetrics();
        $this->assertEquals('operation2', $metrics2['operation']);
        $this->assertEquals(100, $metrics2['counts']['rows']);
    }
}

