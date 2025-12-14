<?php

declare(strict_types=1);

namespace MethorZ\Profiler\Tests\Unit\Analysis;

use MethorZ\Profiler\Analysis\MetricsComparator;
use PHPUnit\Framework\TestCase;

final class MetricsComparatorTest extends TestCase
{
    public function testCompareImprovedPerformance(): void
    {
        $comparator = new MetricsComparator();

        $before = ['total' => 1.0];
        $after = ['total' => 0.5];

        $result = $comparator->compare($before, $after);

        $this->assertTrue($result['duration']['improved']);
        $this->assertEquals(-0.5, $result['duration']['absolute_change']);
        $this->assertEquals(-50.0, $result['duration']['percentage_change']);
    }

    public function testCompareRegressedPerformance(): void
    {
        $comparator = new MetricsComparator();
        $comparator->setRegressionThreshold(0.1); // 10%

        $before = ['total' => 0.5];
        $after = ['total' => 0.7]; // 40% slower

        $result = $comparator->compare($before, $after);

        $this->assertFalse($result['duration']['improved']);
        $this->assertTrue($result['duration']['regressed']);
        $this->assertEqualsWithDelta(40.0, $result['duration']['percentage_change'], 0.01);
    }

    public function testCompareMemory(): void
    {
        $comparator = new MetricsComparator();

        $before = ['memory' => ['current' => 10485760]]; // 10MB
        $after = ['memory' => ['current' => 5242880]];   // 5MB

        $result = $comparator->compare($before, $after);

        $this->assertTrue($result['memory']['improved']);
        $this->assertEquals(10.0, $result['memory']['before_mb']);
        $this->assertEquals(5.0, $result['memory']['after_mb']);
        $this->assertEquals(-5.0, $result['memory']['absolute_change_mb']);
    }

    public function testComparePhases(): void
    {
        $comparator = new MetricsComparator();

        $before = [
            'total' => 1.0,
            'phases' => ['prep' => 0.1, 'exec' => 0.8, 'hydrate' => 0.1],
        ];

        $after = [
            'total' => 0.5,
            'phases' => ['prep' => 0.05, 'exec' => 0.4, 'hydrate' => 0.05],
        ];

        $result = $comparator->compare($before, $after);

        $this->assertArrayHasKey('prep', $result['phases']);
        $this->assertArrayHasKey('exec', $result['phases']);
        $this->assertTrue($result['phases']['prep']['improved']);
        $this->assertTrue($result['phases']['exec']['improved']);
    }

    public function testCompareSummary(): void
    {
        $comparator = new MetricsComparator();

        $before = ['total' => 1.0];
        $after = ['total' => 0.5];

        $result = $comparator->compare($before, $after);

        $this->assertEquals('improved', $result['summary']['status']);
        $this->assertNotEmpty($result['summary']['messages']);
        $this->assertStringContainsString('faster', $result['summary']['messages'][0]);
    }

    public function testSetRegressionThreshold(): void
    {
        $comparator = new MetricsComparator();
        $result = $comparator->setRegressionThreshold(0.2);

        $this->assertInstanceOf(MetricsComparator::class, $result);
    }
}
