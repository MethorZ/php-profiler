<?php

declare(strict_types=1);

namespace MethorZ\Profiler\Tests\Unit;

use MethorZ\Profiler\MetricsCollector;
use PHPUnit\Framework\TestCase;

final class MetricsCollectorTest extends TestCase
{
    public function testRecordStoresMetrics(): void
    {
        $collector = new MetricsCollector();
        $metrics = ['total' => 0.125, 'operation' => 'test'];

        $collector->record('test_operation', $metrics);

        $this->assertEquals(1, $collector->count());
    }

    public function testGetOperationsReturnsAllRecorded(): void
    {
        $collector = new MetricsCollector();
        $collector->record('op1', ['total' => 0.1]);
        $collector->record('op2', ['total' => 0.2]);

        $operations = $collector->getOperations();

        $this->assertCount(2, $operations);
        $this->assertEquals('op1', $operations[0]['operation']);
        $this->assertEquals('op2', $operations[1]['operation']);
    }

    public function testAggregateReturnsEmptyForNoMetrics(): void
    {
        $collector = new MetricsCollector();

        $aggregate = $collector->aggregate();

        $this->assertEquals(0, $aggregate['total_operations']);
        $this->assertEquals(0.0, $aggregate['total_duration']);
    }

    public function testAggregateCalculatesTotals(): void
    {
        $collector = new MetricsCollector();
        $collector->record('op1', ['total' => 0.1, 'memory' => ['current' => 1000000]]);
        $collector->record('op2', ['total' => 0.2, 'memory' => ['current' => 2000000]]);
        $collector->record('op3', ['total' => 0.3, 'memory' => ['current' => 3000000]]);

        $aggregate = $collector->aggregate();

        $this->assertEquals(3, $aggregate['total_operations']);
        $this->assertEqualsWithDelta(0.6, $aggregate['total_duration'], 0.01);
        $this->assertEqualsWithDelta(0.2, $aggregate['avg_duration'], 0.01);
    }

    public function testAggregateGroupsByOperation(): void
    {
        $collector = new MetricsCollector();
        $collector->record('db_query', ['total' => 0.1, 'memory' => ['current' => 1000000]]);
        $collector->record('db_query', ['total' => 0.2, 'memory' => ['current' => 2000000]]);
        $collector->record('api_call', ['total' => 0.5, 'memory' => ['current' => 3000000]]);

        $aggregate = $collector->aggregate();

        $this->assertArrayHasKey('by_operation', $aggregate);
        $this->assertArrayHasKey('db_query', $aggregate['by_operation']);
        $this->assertArrayHasKey('api_call', $aggregate['by_operation']);
        $this->assertEquals(2, $aggregate['by_operation']['db_query']['count']);
        $this->assertEquals(1, $aggregate['by_operation']['api_call']['count']);
    }

    public function testAggregateCalculatesAverages(): void
    {
        $collector = new MetricsCollector();
        $collector->record('op', ['total' => 0.1, 'memory' => ['current' => 10485760]]); // 10MB
        $collector->record('op', ['total' => 0.3, 'memory' => ['current' => 20971520]]); // 20MB

        $aggregate = $collector->aggregate();

        $opStats = $aggregate['by_operation']['op'];
        $this->assertEquals(0.2, $opStats['avg_duration']); // (0.1 + 0.3) / 2
        $this->assertEquals(15.0, $opStats['avg_memory_mb']); // (10 + 20) / 2
    }

    public function testAggregateCalculatesMinMax(): void
    {
        $collector = new MetricsCollector();
        $collector->record('op', ['total' => 0.1]);
        $collector->record('op', ['total' => 0.5]);
        $collector->record('op', ['total' => 0.2]);

        $aggregate = $collector->aggregate();

        $opStats = $aggregate['by_operation']['op'];
        $this->assertEquals(0.1, $opStats['min_duration']);
        $this->assertEquals(0.5, $opStats['max_duration']);
    }

    public function testGetByOperationFiltersOperations(): void
    {
        $collector = new MetricsCollector();
        $collector->record('op1', ['total' => 0.1]);
        $collector->record('op2', ['total' => 0.2]);
        $collector->record('op1', ['total' => 0.3]);

        $op1Metrics = $collector->getByOperation('op1');

        $this->assertCount(2, $op1Metrics);
        $this->assertEquals('op1', $op1Metrics[0]['operation']);
        $this->assertEquals('op1', $op1Metrics[1]['operation']);
    }

    public function testClearRemovesAllMetrics(): void
    {
        $collector = new MetricsCollector();
        $collector->record('op', ['total' => 0.1]);
        $collector->record('op', ['total' => 0.2]);

        $collector->clear();

        $this->assertEquals(0, $collector->count());
        $this->assertEmpty($collector->getOperations());
    }

    public function testCountReturnsNumberOfOperations(): void
    {
        $collector = new MetricsCollector();

        $this->assertEquals(0, $collector->count());

        $collector->record('op1', ['total' => 0.1]);
        $this->assertEquals(1, $collector->count());

        $collector->record('op2', ['total' => 0.2]);
        $this->assertEquals(2, $collector->count());
    }
}
