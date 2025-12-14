<?php

declare(strict_types=1);

namespace MethorZ\Profiler\Tests\Unit;

use MethorZ\Profiler\OperationProfiler;
use MethorZ\Profiler\SamplingProfiler;
use PHPUnit\Framework\TestCase;

final class SamplingProfilerTest extends TestCase
{
    public function testConstructorWithValidRate(): void
    {
        $profiler = new SamplingProfiler(0.5);

        $this->assertEquals(0.5, $profiler->getSamplingRate());
    }

    public function testConstructorWithInvalidRateThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new SamplingProfiler(1.5);
    }

    public function testShouldSampleWithZeroRate(): void
    {
        $profiler = new SamplingProfiler(0.0);

        $this->assertFalse($profiler->shouldSample());
    }

    public function testShouldSampleWithFullRate(): void
    {
        $profiler = new SamplingProfiler(1.0);

        $this->assertTrue($profiler->shouldSample());
    }

    public function testShouldSampleWithPartialRate(): void
    {
        $profiler = new SamplingProfiler(0.5);

        // Run multiple times to check probabilistic behavior
        $sampled = 0;
        $iterations = 1000;

        for ($i = 0; $i < $iterations; $i++) {
            if ($profiler->shouldSample()) {
                $sampled++;
            }
        }

        // Should be approximately 50% (allow 40-60% range due to randomness)
        $percentage = ($sampled / $iterations) * 100;
        $this->assertGreaterThan(40, $percentage);
        $this->assertLessThan(60, $percentage);
    }

    public function testStartReturnsProfiler(): void
    {
        $profiler = new SamplingProfiler(1.0);

        $opProfiler = $profiler->start('test_operation');

        $this->assertInstanceOf(OperationProfiler::class, $opProfiler);
    }

    public function testGetSamplingRate(): void
    {
        $profiler = new SamplingProfiler(0.25);

        $this->assertEquals(0.25, $profiler->getSamplingRate());
    }
}
