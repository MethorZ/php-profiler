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
 * Integration test simulating real-world usage patterns
 * based on the bank-salt project implementation.
 */
final class RealWorldUsageTest extends TestCase
{
    public function testDatabaseRepositoryPattern(): void
    {
        $repository = new MockDatabaseRepository();
        $repository->setProfilingMonitor(
            PerformanceMonitor::create()
                ->setSlowOperationThreshold(0.5)
                ->setHighRowCountThreshold(1000),
        );

        // Fetch data
        $result = $repository->fetchTransactionsByAccounts(['ACC001', 'ACC002']);

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
            ['ACC001', 'ACC002'],
            ['ACC003', 'ACC004', 'ACC005'],
            ['ACC006'],
        ];

        foreach ($batches as $batch) {
            $repository->fetchTransactionsByAccounts($batch);
            $metrics = $repository->getLastQueryMetrics();
            $collector->record('fetch_transactions', $metrics);
        }

        // Aggregate
        $aggregate = $collector->aggregate();

        $this->assertEquals(3, $aggregate['total_operations']);
        $this->assertGreaterThan(0, $aggregate['total_duration']);
        $this->assertArrayHasKey('fetch_transactions', $aggregate['by_operation']);
    }

    public function testConsoleFormatterOutput(): void
    {
        $repository = new MockDatabaseRepository();
        $repository->fetchTransactionsByAccounts(['ACC001']);

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
 * Mock repository simulating the bank-salt TransactionDetailRepository pattern.
 */
class MockDatabaseRepository
{
    use ProfilesOperations;

    /**
     * @param array<int, string> $accountNumbers
     *
     * @return array<int, array<string, mixed>>
     */
    public function fetchTransactionsByAccounts(array $accountNumbers): array
    {
        $profiler = $this->startProfiling('fetch_transactions');

        try {
            // Phase 1: Query preparation
            $profiler->checkpoint('prep');
            $this->simulateQueryPreparation($accountNumbers);

            // Phase 2: Query execution
            $profiler->checkpoint('exec');
            $rawData = $this->simulateQueryExecution($accountNumbers);

            // Phase 3: Fetch results
            $profiler->checkpoint('fetch');
            $rows = $this->simulateFetchResults($rawData);

            // Phase 4: Hydrate objects
            $profiler->checkpoint('hydrate');
            $transactions = $this->simulateHydration($rows);

            $profiler->addCount('accounts', count($accountNumbers));
            $profiler->addCount('rows', count($transactions));
            $profiler->addContext('query_type', 'SELECT');

            return $transactions;
        } finally {
            $profiler->end();
        }
    }

    public function getLastQueryMetrics(): array
    {
        return $this->getLastProfilingMetrics();
    }

    /**
     * @param array<int, string> $accountNumbers
     */
    private function simulateQueryPreparation(array $accountNumbers): void
    {
        // Simulate query building
        usleep(500);
    }

    /**
     * @param array<int, string> $accountNumbers
     *
     * @return array<string, mixed>
     */
    private function simulateQueryExecution(array $accountNumbers): array
    {
        // Simulate database query
        usleep(2000);
        return ['result' => 'data'];
    }

    /**
     * @param array<string, mixed> $rawData
     *
     * @return array<int, array<string, mixed>>
     */
    private function simulateFetchResults(array $rawData): array
    {
        // Simulate fetching rows
        usleep(1000);
        return array_fill(0, 10, ['id' => 1, 'amount' => 100.0]);
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

