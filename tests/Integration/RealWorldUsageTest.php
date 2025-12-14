<?php

declare(strict_types=1);

namespace MethorZ\Profiler\Tests\Integration;

use MethorZ\Profiler\Concern\ProfilesOperations;
use MethorZ\Profiler\Formatter\ConsoleFormatter;
use MethorZ\Profiler\MetricsCollector;
use MethorZ\Profiler\OperationProfiler;
use MethorZ\Profiler\PerformanceMonitor;
use PHPUnit\Framework\TestCase;

/**
 * Integration test simulating real-world usage patterns.
 */
final class RealWorldUsageTest extends TestCase
{
    public function testDatabaseRepositoryPattern(): void
    {
        $repository = new MockDatabaseRepository();
        $repository->setMonitor(
            PerformanceMonitor::create()
                ->setSlowOperationThreshold(0.5)
                ->setHighRowCountThreshold(1000),
        );

        // Fetch data
        $result = $repository->fetchRecordsByIds(['ID001', 'ID002']);

        // Verify results
        $this->assertNotEmpty($result);

        // Check metrics were captured
        $metrics = $repository->getLastQueryMetrics();
        $this->assertArrayHasKey('operation', $metrics);
        $this->assertArrayHasKey('total', $metrics);
        $this->assertArrayHasKey('phases', $metrics);
        $this->assertArrayHasKey('prep', $metrics['phases']);
        $this->assertArrayHasKey('exec', $metrics['phases']);
        $this->assertArrayHasKey('fetch', $metrics['phases']);
        $this->assertArrayHasKey('hydrate', $metrics['phases']);
    }

    public function testBatchProcessingWithAggregation(): void
    {
        $collector = new MetricsCollector();
        $repository = new MockDatabaseRepository();

        // Process multiple batches
        $batches = [
            ['ID001', 'ID002'],
            ['ID003', 'ID004', 'ID005'],
            ['ID006'],
        ];

        foreach ($batches as $batch) {
            $repository->fetchRecordsByIds($batch);
            $metrics = $repository->getLastQueryMetrics();
            $collector->record('fetch_records', $metrics);
        }

        // Aggregate
        $aggregate = $collector->aggregate();

        $this->assertEquals(3, $aggregate['total_operations']);
        $this->assertGreaterThan(0, $aggregate['total_duration']);
        $this->assertArrayHasKey('fetch_records', $aggregate['by_operation']);
    }

    public function testConsoleFormatterOutput(): void
    {
        $repository = new MockDatabaseRepository();
        $repository->fetchRecordsByIds(['ID001']);

        $metrics = $repository->getLastQueryMetrics();
        $formatter = new ConsoleFormatter();
        $output = $formatter->format($metrics);

        $this->assertStringContainsString('Operation:', $output);
        $this->assertStringContainsString('Total:', $output);
        $this->assertStringContainsString('Phases:', $output);
        $this->assertStringContainsString('Memory:', $output);
    }

    public function testThresholdWarnings(): void
    {
        $monitor = PerformanceMonitor::create()
            ->setSlowOperationThreshold(0.001) // Very low - should trigger
            ->setHighRowCountThreshold(5);      // Low - should trigger

        $profiler = OperationProfiler::start('slow_query', $monitor);
        usleep(5000); // 5ms
        $profiler->checkpoint('query');
        $profiler->addCount('rows', 100);

        $metrics = $profiler->end();

        $this->assertArrayHasKey('warnings', $metrics['context']);
        $this->assertNotEmpty($metrics['context']['warnings']);
        $this->assertGreaterThanOrEqual(2, count($metrics['context']['warnings']));
    }
}

/**
 * Mock repository demonstrating profiling integration pattern.
 *
 * phpcs:disable PSR1.Classes.ClassDeclaration.MultipleClasses
 */
class MockDatabaseRepository
{
    use ProfilesOperations;

    public function setMonitor(?PerformanceMonitor $monitor): void
    {
        $this->setProfilingMonitor($monitor);
    }

    /**
     * @param array<int, string> $ids
     *
     * @return array<int, array<string, mixed>>
     */
    public function fetchRecordsByIds(array $ids): array
    {
        $profiler = $this->startProfiling('fetch_records');

        try {
            // Phase 1: Query preparation
            $profiler->checkpoint('prep');
            $this->simulateQueryPreparation($ids);

            // Phase 2: Query execution
            $profiler->checkpoint('exec');
            $rawData = $this->simulateQueryExecution($ids);

            // Phase 3: Fetch results
            $profiler->checkpoint('fetch');
            $rows = $this->simulateFetchResults($rawData);

            // Phase 4: Hydrate objects
            $profiler->checkpoint('hydrate');
            $records = $this->simulateHydration($rows);

            $profiler->addCount('ids', count($ids));
            $profiler->addCount('rows', count($records));
            $profiler->addContext('query_type', 'SELECT');

            return $records;
        } finally {
            $profiler->end();
        }
    }

    public function getLastQueryMetrics(): array
    {
        return $this->getLastProfilingMetrics();
    }

    /**
     * @param array<int, string> $_ids
     */
    private function simulateQueryPreparation(array $_ids): void
    {
        // Simulate query building
        usleep(500);
    }

    /**
     * @param array<int, string> $_ids
     *
     * @return array<string, mixed>
     */
    private function simulateQueryExecution(array $_ids): array
    {
        // Simulate database query
        usleep(2000);
        return ['result' => 'data'];
    }

    /**
     * @param array<string, mixed> $_rawData
     *
     * @return array<int, array<string, mixed>>
     */
    private function simulateFetchResults(array $_rawData): array
    {
        // Simulate fetching rows
        usleep(1000);
        return array_fill(0, 10, ['id' => 1, 'value' => 100.0]);
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     *
     * @return array<int, array<string, mixed>>
     */
    private function simulateHydration(array $rows): array
    {
        // Simulate object creation
        usleep(1500);
        return $rows;
    }
}
