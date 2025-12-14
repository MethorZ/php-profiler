<?php

declare(strict_types=1);

namespace MethorZ\Profiler\Tests\Unit;

use MethorZ\Profiler\OperationProfiler;
use MethorZ\Profiler\PerformanceMonitor;
use PHPUnit\Framework\TestCase;

final class OperationProfilerTest extends TestCase
{
    protected function setUp(): void
    {
        // Ensure profiling is enabled for tests
        OperationProfiler::setEnabled(true);
    }

    public function testStartCreatesProfiler(): void
    {
        $profiler = OperationProfiler::start('test_operation');

        $this->assertInstanceOf(OperationProfiler::class, $profiler);
    }

    public function testEndReturnsMetrics(): void
    {
        $profiler = OperationProfiler::start('test_operation');
        $profiler->checkpoint('phase1');
        $profiler->addCount('rows', 100);

        $metrics = $profiler->end();

        $this->assertArrayHasKey('operation', $metrics);
        $this->assertArrayHasKey('total', $metrics);
        $this->assertArrayHasKey('phases', $metrics);
        $this->assertArrayHasKey('memory', $metrics);
        $this->assertArrayHasKey('counts', $metrics);
        $this->assertEquals('test_operation', $metrics['operation']);
        $this->assertEquals(100, $metrics['counts']['rows']);
    }

    public function testCheckpointRecordsPhase(): void
    {
        $profiler = OperationProfiler::start('test_operation');
        $profiler->checkpoint('prep');
        usleep(5000);
        $profiler->checkpoint('exec');

        $metrics = $profiler->end();

        $this->assertArrayHasKey('prep', $metrics['phases']);
        $this->assertArrayHasKey('exec', $metrics['phases']);
        $this->assertGreaterThan($metrics['phases']['prep'], $metrics['phases']['exec']);
    }

    public function testAddCountStoresCount(): void
    {
        $profiler = OperationProfiler::start('test_operation');
        $profiler->addCount('rows', 50);
        $profiler->addCount('accounts', 10);

        $metrics = $profiler->end();

        $this->assertEquals(50, $metrics['counts']['rows']);
        $this->assertEquals(10, $metrics['counts']['accounts']);
    }

    public function testIncrementCountIncrementsValue(): void
    {
        $profiler = OperationProfiler::start('test_operation');
        $profiler->incrementCount('operations');
        $profiler->incrementCount('operations');
        $profiler->incrementCount('operations', 3);

        $metrics = $profiler->end();

        $this->assertEquals(5, $metrics['counts']['operations']);
    }

    public function testAddContextStoresContext(): void
    {
        $profiler = OperationProfiler::start('test_operation');
        $profiler->addContext('query_type', 'SELECT');
        $profiler->addContext('table', 'users');

        $metrics = $profiler->end();

        $this->assertEquals('SELECT', $metrics['context']['query_type']);
        $this->assertEquals('users', $metrics['context']['table']);
    }

    public function testMemoryTrackingRecordsUsage(): void
    {
        $profiler = OperationProfiler::start('test_operation');
        // Allocate some memory
        $data = array_fill(0, 10000, 'test'); // phpcs:ignore Squiz.PHP.DisallowMultipleAssignments.Found
        unset($data); // Force memory to be used
        $profiler->checkpoint('after_allocation');

        $metrics = $profiler->end();

        $this->assertArrayHasKey('current', $metrics['memory']);
        $this->assertArrayHasKey('peak', $metrics['memory']);
        $this->assertArrayHasKey('delta', $metrics['memory']);
        $this->assertGreaterThan(0, $metrics['memory']['current']);
    }

    public function testElapsedReturnsCurrentDuration(): void
    {
        $profiler = OperationProfiler::start('test_operation');
        usleep(10000); // 10ms

        $elapsed = $profiler->elapsed();

        $this->assertGreaterThanOrEqual(0.01, $elapsed);
    }

    public function testCurrentMemoryReturnsMemoryUsage(): void
    {
        $profiler = OperationProfiler::start('test_operation');

        $memory = $profiler->currentMemory();

        $this->assertGreaterThan(0, $memory);
    }

    public function testWithPerformanceMonitorAddsWarnings(): void
    {
        $monitor = PerformanceMonitor::create()
            ->setSlowOperationThreshold(0.001); // Very low threshold

        $profiler = OperationProfiler::start('test_operation', $monitor);
        usleep(5000); // 5ms - will exceed threshold

        $metrics = $profiler->end();

        $this->assertArrayHasKey('warnings', $metrics['context']);
        $this->assertNotEmpty($metrics['context']['warnings']);
    }

    public function testDisabledProfilerReturnsNullProfiler(): void
    {
        OperationProfiler::setEnabled(false);

        $profiler = OperationProfiler::start('test_operation');
        $profiler->checkpoint('phase');
        $profiler->addCount('rows', 100);
        $metrics = $profiler->end();

        $this->assertEmpty($metrics);

        // Re-enable for other tests
        OperationProfiler::setEnabled(true);
    }

    public function testIsEnabledReflectsGlobalState(): void
    {
        $this->assertTrue(OperationProfiler::isEnabled());

        OperationProfiler::setEnabled(false);
        $this->assertFalse(OperationProfiler::isEnabled());

        OperationProfiler::setEnabled(true);
        $this->assertTrue(OperationProfiler::isEnabled());
    }
}
