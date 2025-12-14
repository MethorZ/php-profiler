<?php

declare(strict_types=1);

namespace MethorZ\Profiler\Tests\Unit;

use MethorZ\Profiler\Exception\ProfilingException;
use MethorZ\Profiler\MetricBag;
use MethorZ\Profiler\PerformanceMonitor;
use PHPUnit\Framework\TestCase;

final class PerformanceMonitorTest extends TestCase
{
    public function testCreateReturnsMonitor(): void
    {
        $monitor = PerformanceMonitor::create();

        $this->assertInstanceOf(PerformanceMonitor::class, $monitor);
    }

    public function testSetSlowOperationThreshold(): void
    {
        $monitor = PerformanceMonitor::create()
            ->setSlowOperationThreshold(2.0);

        $this->assertInstanceOf(PerformanceMonitor::class, $monitor);
    }

    public function testSetSlowOperationThresholdThrowsForInvalidValue(): void
    {
        $this->expectException(ProfilingException::class);

        PerformanceMonitor::create()->setSlowOperationThreshold(0.0);
    }

    public function testSetSlowPhaseThreshold(): void
    {
        $monitor = PerformanceMonitor::create()
            ->setSlowPhaseThreshold(0.75);

        $this->assertInstanceOf(PerformanceMonitor::class, $monitor);
    }

    public function testSetSlowPhaseThresholdThrowsForInvalidValue(): void
    {
        $this->expectException(ProfilingException::class);

        PerformanceMonitor::create()->setSlowPhaseThreshold(-1.0);
    }

    public function testSetHighMemoryThreshold(): void
    {
        $monitor = PerformanceMonitor::create()
            ->setHighMemoryThreshold(0.8);

        $this->assertInstanceOf(PerformanceMonitor::class, $monitor);
    }

    public function testSetHighMemoryThresholdThrowsForInvalidValue(): void
    {
        $this->expectException(ProfilingException::class);

        PerformanceMonitor::create()->setHighMemoryThreshold(1.5);
    }

    public function testSetHighRowCountThreshold(): void
    {
        $monitor = PerformanceMonitor::create()
            ->setHighRowCountThreshold(5000);

        $this->assertInstanceOf(PerformanceMonitor::class, $monitor);
    }

    public function testSetHighRowCountThresholdThrowsForInvalidValue(): void
    {
        $this->expectException(ProfilingException::class);

        PerformanceMonitor::create()->setHighRowCountThreshold(-100);
    }

    public function testCheckThresholdsReturnsEmptyForGoodMetrics(): void
    {
        $monitor = PerformanceMonitor::create()
            ->setSlowOperationThreshold(10.0);

        $metrics = new MetricBag('test', 0.5);
        $warnings = $monitor->checkThresholds($metrics);

        $this->assertEmpty($warnings);
    }

    public function testCheckThresholdsDetectsSlowOperation(): void
    {
        $monitor = PerformanceMonitor::create()
            ->setSlowOperationThreshold(0.1);

        $metrics = new MetricBag('test', 0.5);
        $warnings = $monitor->checkThresholds($metrics);

        $this->assertNotEmpty($warnings);
        $this->assertStringContainsString('Slow operation', $warnings[0]);
    }

    public function testCheckThresholdsDetectsSlowPhase(): void
    {
        $monitor = PerformanceMonitor::create()
            ->setSlowPhaseThreshold(0.1);

        $metrics = new MetricBag('test', 0.5, ['exec' => 0.4]);
        $warnings = $monitor->checkThresholds($metrics);

        $this->assertNotEmpty($warnings);
        $this->assertStringContainsString('Slow phase "exec"', $warnings[0]);
    }

    public function testCheckThresholdsDetectsHighMemory(): void
    {
        // Save original memory_limit and set a known limit for testing
        $originalLimit = ini_get('memory_limit');
        ini_set('memory_limit', '128M'); // Set a known limit

        try {
            $monitor = PerformanceMonitor::create()
                ->setHighMemoryThreshold(0.50); // 50% threshold

            // Use 100MB which is 78% of 128MB limit
            $memory = [
                'current' => 104857600, // 100MB
                'peak' => 104857600,
                'delta' => 0,
            ];

            $metrics = new MetricBag('test', 0.1, [], $memory);
            $warnings = $monitor->checkThresholds($metrics);

            $this->assertNotEmpty($warnings);
            $this->assertStringContainsString('High memory usage', $warnings[0]);
        } finally {
            // Restore original memory_limit
            ini_set('memory_limit', $originalLimit);
        }
    }

    public function testCheckThresholdsDetectsHighRowCount(): void
    {
        $monitor = PerformanceMonitor::create()
            ->setHighRowCountThreshold(100);

        $metrics = new MetricBag('test', 0.1, [], [], ['rows' => 500]);
        $warnings = $monitor->checkThresholds($metrics);

        $this->assertNotEmpty($warnings);
        $this->assertStringContainsString('High row count', $warnings[0]);
    }

    public function testCheckThresholdsReturnsMultipleWarnings(): void
    {
        $monitor = PerformanceMonitor::create()
            ->setSlowOperationThreshold(0.1)
            ->setSlowPhaseThreshold(0.05);

        $metrics = new MetricBag('test', 0.5, ['phase1' => 0.3, 'phase2' => 0.2]);
        $warnings = $monitor->checkThresholds($metrics);

        $this->assertGreaterThanOrEqual(3, count($warnings)); // Operation + 2 phases
    }

    public function testFluentInterface(): void
    {
        $monitor = PerformanceMonitor::create()
            ->setSlowOperationThreshold(2.0)
            ->setSlowPhaseThreshold(0.5)
            ->setHighMemoryThreshold(0.8)
            ->setHighRowCountThreshold(10000);

        $this->assertInstanceOf(PerformanceMonitor::class, $monitor);
    }
}
