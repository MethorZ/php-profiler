<?php
declare(strict_types=1);

/**
 * Basic Usage Examples for Performance Profiler
 *
 * Run with: php examples/basic-usage.php
 */

require_once __DIR__ . '/../vendor/autoload.php';

use MethorZ\Profiler\Formatter\ConsoleFormatter;
use MethorZ\Profiler\Formatter\JsonFormatter;
use MethorZ\Profiler\MetricsCollector;
use MethorZ\Profiler\OperationProfiler;
use MethorZ\Profiler\PerformanceMonitor;

echo "=== Basic Usage Examples ===\n\n";

// ============================================================================
// Example 1: Simple Operation Profiling
// ============================================================================
echo "1. Simple Operation Profiling\n";
echo str_repeat('-', 50) . "\n";

$profiler = OperationProfiler::start('data_processing');

// Simulate work
$data = range(1, 10000);
$profiler->checkpoint('data_loaded');

$result = array_map(fn($n) => $n * 2, $data);
$profiler->checkpoint('data_transformed');

$sum = array_sum($result);
$profiler->checkpoint('data_aggregated');

$profiler->addCount('records', count($data));
$profiler->addContext('sum', $sum);

$metrics = $profiler->end();

$formatter = new ConsoleFormatter();
echo $formatter->format($metrics);
echo "\n\n";

// ============================================================================
// Example 2: Repository Pattern with Trait
// ============================================================================
echo "2. Repository Pattern Integration\n";
echo str_repeat('-', 50) . "\n";

class UserRepository
{
    use MethorZ\Profiler\Concern\ProfilesOperations;

    public function fetchUsers(array $ids): array
    {
        $profiler = $this->startProfiling('fetch_users');

        try {
            $profiler->checkpoint('query_prep');
            // Simulate query preparation
            usleep(1000);

            $profiler->checkpoint('query_exec');
            // Simulate database query
            usleep(5000);

            $profiler->checkpoint('hydrate');
            // Simulate object hydration
            $users = array_map(fn($id) => ['id' => $id, 'name' => 'User ' . $id], $ids);
            usleep(2000);

            $profiler->addCount('users', count($users));
            return $users;
        } finally {
            $profiler->end();
        }
    }

    public function getLastQueryMetrics(): array
    {
        return $this->getLastProfilingMetrics();
    }
}

$repository = new UserRepository();
$users = $repository->fetchUsers([1, 2, 3, 4, 5]);
$metrics = $repository->getLastQueryMetrics();

echo $formatter->format($metrics);
echo "\n\n";

// ============================================================================
// Example 3: Performance Monitoring with Thresholds
// ============================================================================
echo "3. Performance Monitoring with Thresholds\n";
echo str_repeat('-', 50) . "\n";

$monitor = PerformanceMonitor::create()
    ->setSlowOperationThreshold(0.005)  // 5ms
    ->setSlowPhaseThreshold(0.003)      // 3ms
    ->setHighMemoryThreshold(0.7);      // 70% of memory limit

$profiler = OperationProfiler::start('slow_operation', $monitor);

$profiler->checkpoint('phase1');
usleep(4000); // 4ms - exceeds phase threshold

$profiler->checkpoint('phase2');
usleep(3000); // 3ms - at threshold

$metrics = $profiler->end();

echo $formatter->format($metrics);
echo "\n\n";

// ============================================================================
// Example 4: Batch Operations with Metrics Collector
// ============================================================================
echo "4. Batch Operations with Aggregation\n";
echo str_repeat('-', 50) . "\n";

$collector = new MetricsCollector();

// Simulate processing multiple batches
for ($i = 1; $i <= 5; $i++) {
    $profiler = OperationProfiler::start('batch_process');

    // Simulate varying workload
    usleep(rand(1000, 5000));
    $profiler->checkpoint('process');

    $records = rand(100, 500);
    $profiler->addCount('records', $records);

    $metrics = $profiler->end();
    $collector->record('batch_process', $metrics);
}

$aggregate = $collector->aggregate();

echo "Aggregate Summary:\n";
echo sprintf("  Total operations: %d\n", $aggregate['total_operations']);
echo sprintf("  Total duration: %.2fms\n", $aggregate['total_duration'] * 1000);
echo sprintf("  Average duration: %.2fms\n", $aggregate['avg_duration'] * 1000);
echo sprintf("  Total memory: %.2fMB\n", $aggregate['total_memory_mb']);
echo "\n";

echo "Per-operation breakdown:\n";
foreach ($aggregate['by_operation'] as $operation => $stats) {
    echo sprintf(
        "  %s: %d ops, avg %.2fms (min: %.2fms, max: %.2fms)\n",
        $operation,
        $stats['count'],
        $stats['avg_duration'] * 1000,
        $stats['min_duration'] * 1000,
        $stats['max_duration'] * 1000,
    );
}
echo "\n";

// ============================================================================
// Example 5: JSON Export for Analysis
// ============================================================================
echo "5. JSON Export\n";
echo str_repeat('-', 50) . "\n";

$profiler = OperationProfiler::start('data_export');
$profiler->checkpoint('prepare');
usleep(2000);
$profiler->checkpoint('export');
usleep(3000);
$profiler->addCount('records', 1000);
$metrics = $profiler->end();

$jsonFormatter = new JsonFormatter(prettyPrint: true);
echo $jsonFormatter->format($metrics);
echo "\n\n";

// ============================================================================
// Example 6: Disabling Profiling (Production Mode)
// ============================================================================
echo "6. Disabled Profiling (Zero Overhead)\n";
echo str_repeat('-', 50) . "\n";

OperationProfiler::setEnabled(false);

$profiler = OperationProfiler::start('production_operation');
$profiler->checkpoint('phase1');
$profiler->addCount('records', 1000);
$metrics = $profiler->end();

echo "Profiling disabled - metrics returned: " . (empty($metrics) ? 'empty (expected)' : 'not empty') . "\n";

// Re-enable for other examples
OperationProfiler::setEnabled(true);
echo "\n";

echo "=== Examples Complete ===\n";

