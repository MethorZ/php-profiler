<?php

declare(strict_types=1);

namespace MethorZ\Profiler\Tests\Unit;

use MethorZ\Profiler\MetricBag;
use PHPUnit\Framework\TestCase;

final class MetricBagTest extends TestCase
{
    public function testConstructorSetsProperties(): void
    {
        $bag = new MetricBag(
            'test_operation',
            0.125,
            ['prep' => 0.010, 'exec' => 0.100],
            ['current' => 1024000, 'peak' => 2048000, 'delta' => 512000],
            ['rows' => 100],
            ['context_key' => 'context_value'],
        );

        $this->assertEquals('test_operation', $bag->getOperation());
        $this->assertEquals(0.125, $bag->getTotal());
        $this->assertEquals(['prep' => 0.010, 'exec' => 0.100], $bag->getPhases());
    }

    public function testToArrayReturnsStandardizedFormat(): void
    {
        $bag = new MetricBag(
            'test_operation',
            0.125,
            ['prep' => 0.010, 'exec' => 0.100],
            ['current' => 1024000, 'peak' => 2048000, 'delta' => 512000],
            ['rows' => 100],
        );

        $array = $bag->toArray();

        $this->assertArrayHasKey('operation', $array);
        $this->assertArrayHasKey('total', $array);
        $this->assertArrayHasKey('phases', $array);
        $this->assertArrayHasKey('memory', $array);
        $this->assertArrayHasKey('counts', $array);
    }

    public function testToArrayIncludesMemoryInMB(): void
    {
        $bag = new MetricBag(
            'test_operation',
            0.125,
            [],
            ['current' => 47185920, 'peak' => 52428800, 'delta' => 5242880],
        );

        $array = $bag->toArray();

        $this->assertArrayHasKey('current_mb', $array['memory']);
        $this->assertArrayHasKey('peak_mb', $array['memory']);
        $this->assertArrayHasKey('delta_mb', $array['memory']);
        $this->assertEquals(45.0, $array['memory']['current_mb']);
        $this->assertEquals(50.0, $array['memory']['peak_mb']);
        $this->assertEquals(5.0, $array['memory']['delta_mb']);
    }

    public function testGetPhaseReturnsSpecificPhase(): void
    {
        $bag = new MetricBag(
            'test_operation',
            0.125,
            ['prep' => 0.010, 'exec' => 0.100],
        );

        $this->assertEquals(0.010, $bag->getPhase('prep'));
        $this->assertEquals(0.100, $bag->getPhase('exec'));
        $this->assertNull($bag->getPhase('nonexistent'));
    }

    public function testGetCountReturnsSpecificCount(): void
    {
        $bag = new MetricBag(
            'test_operation',
            0.125,
            [],
            [],
            ['rows' => 100, 'accounts' => 50],
        );

        $this->assertEquals(100, $bag->getCount('rows'));
        $this->assertEquals(50, $bag->getCount('accounts'));
        $this->assertNull($bag->getCount('nonexistent'));
    }

    public function testGetMemoryInMBCalculatesCorrectly(): void
    {
        $bag = new MetricBag(
            'test_operation',
            0.125,
            [],
            ['current' => 52428800],
        );

        $this->assertEquals(50.0, $bag->getMemoryInMB());
    }

    public function testGetMemoryInMBReturnsZeroWhenNoMemory(): void
    {
        $bag = new MetricBag('test_operation', 0.125);

        $this->assertEquals(0.0, $bag->getMemoryInMB());
    }

    public function testToArrayOmitsEmptyArrays(): void
    {
        $bag = new MetricBag('test_operation', 0.125);

        $array = $bag->toArray();

        $this->assertArrayNotHasKey('phases', $array);
        $this->assertArrayNotHasKey('memory', $array);
        $this->assertArrayNotHasKey('counts', $array);
        $this->assertArrayNotHasKey('context', $array);
    }
}
