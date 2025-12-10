<?php
declare(strict_types=1);

namespace MethorZ\Profiler;

/**
 * Collects and aggregates metrics across multiple operations.
 *
 * Useful for analyzing performance patterns across batch operations
 * or request lifecycles.
 */
final class MetricsCollector
{
    /**
     * @var array<int, array{operation: string, metrics: array<string, mixed>, timestamp: int}>
     */
    private array $operations = [];

    /**
     * Record an operation's metrics.
     *
     * @param string $operation Operation name
     * @param array<string, mixed> $metrics Metrics data
     */
    public function record(string $operation, array $metrics): void
    {
        $this->operations[] = [
            'operation' => $operation,
            'metrics' => $metrics,
            'timestamp' => time(),
        ];
    }

    /**
     * Get all recorded operations.
     *
     * @return array<int, array{operation: string, metrics: array<string, mixed>, timestamp: int}>
     */
    public function getOperations(): array
    {
        return $this->operations;
    }

    /**
     * Aggregate metrics across all operations.
     *
     * @return array<string, mixed>
     */
    public function aggregate(): array
    {
        if ($this->operations === []) {
            return [
                'total_operations' => 0,
                'total_duration' => 0.0,
                'total_memory_mb' => 0.0,
                'percentiles' => [],
                'by_operation' => [],
            ];
        }

        $totalDuration = 0.0;
        $totalMemory = 0;
        $byOperation = [];
        $allDurations = [];

        foreach ($this->operations as $entry) {
            $operation = $entry['operation'];
            $metrics = $entry['metrics'];

            $duration = isset($metrics['total']) && is_numeric($metrics['total']) ? (float) $metrics['total'] : 0.0;

            $memory = 0;
            if (isset($metrics['memory']) && is_array($metrics['memory'])) {
                $memory = isset($metrics['memory']['current']) && is_numeric($metrics['memory']['current'])
                    ? (int) $metrics['memory']['current']
                    : 0;
            }

            $totalDuration += $duration;
            $totalMemory += $memory;
            $allDurations[] = $duration;

            if (!isset($byOperation[$operation])) {
                $byOperation[$operation] = [
                    'count' => 0,
                    'total_duration' => 0.0,
                    'avg_duration' => 0.0,
                    'min_duration' => PHP_FLOAT_MAX,
                    'max_duration' => 0.0,
                    'total_memory' => 0,
                    'durations' => [],
                ];
            }

            $byOperation[$operation]['count']++;
            $byOperation[$operation]['total_duration'] = (float) $byOperation[$operation]['total_duration'] + $duration;
            $byOperation[$operation]['total_memory'] = (int) $byOperation[$operation]['total_memory'] + $memory;
            $byOperation[$operation]['min_duration'] = min((float) $byOperation[$operation]['min_duration'], $duration);
            $byOperation[$operation]['max_duration'] = max((float) $byOperation[$operation]['max_duration'], $duration);
            $byOperation[$operation]['durations'][] = $duration;
        }

        // Calculate averages and percentiles
        foreach ($byOperation as $operation => $stats) {
            $byOperation[$operation]['avg_duration'] = $stats['total_duration'] / $stats['count'];
            $byOperation[$operation]['avg_memory_mb'] = round($stats['total_memory'] / $stats['count'] / 1024 / 1024, 2);
            $byOperation[$operation]['percentiles'] = $this->calculatePercentiles($stats['durations']);

            // Remove raw durations array from output (was just for calculation)
            unset($byOperation[$operation]['durations']);
        }

        return [
            'total_operations' => count($this->operations),
            'total_duration' => $totalDuration,
            'avg_duration' => $totalDuration / count($this->operations),
            'total_memory_mb' => round($totalMemory / 1024 / 1024, 2),
            'percentiles' => $this->calculatePercentiles($allDurations),
            'by_operation' => $byOperation,
        ];
    }

    /**
     * Get metrics for a specific operation type.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getByOperation(string $operation): array
    {
        return array_values(
            array_filter(
                $this->operations,
                static fn(array $entry): bool => $entry['operation'] === $operation,
            ),
        );
    }

    /**
     * Clear all recorded metrics.
     */
    public function clear(): void
    {
        $this->operations = [];
    }

    /**
     * Get total number of recorded operations.
     */
    public function count(): int
    {
        return count($this->operations);
    }

    /**
     * Calculate percentiles from an array of durations.
     *
     * @param array<int, float> $durations Array of duration values
     *
     * @return array<string, float>
     */
    private function calculatePercentiles(array $durations): array
    {
        if ($durations === []) {
            return [
                'p50' => 0.0,
                'p75' => 0.0,
                'p90' => 0.0,
                'p95' => 0.0,
                'p99' => 0.0,
            ];
        }

        sort($durations);
        $count = count($durations);

        return [
            'p50' => $this->getPercentileValue($durations, $count, 50),
            'p75' => $this->getPercentileValue($durations, $count, 75),
            'p90' => $this->getPercentileValue($durations, $count, 90),
            'p95' => $this->getPercentileValue($durations, $count, 95),
            'p99' => $this->getPercentileValue($durations, $count, 99),
        ];
    }

    /**
     * Get percentile value from sorted array.
     *
     * @param array<int, float> $sorted Sorted durations
     * @param int $count Total count of durations
     * @param int $percentile Percentile to calculate (0-100)
     */
    private function getPercentileValue(array $sorted, int $count, int $percentile): float
    {
        $index = (int) ceil(($percentile / 100) * $count) - 1;
        $index = max(0, min($index, $count - 1));

        return $sorted[$index];
    }
}
