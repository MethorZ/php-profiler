<?php
declare(strict_types=1);

namespace MethorZ\Profiler\Analysis;

use function count;

/**
 * Compare two sets of metrics to analyze performance changes.
 *
 * Useful for measuring optimization impact or detecting regressions.
 */
final class MetricsComparator
{
    private float $regressionThreshold = 0.1; // 10% slower is regression

    public function setRegressionThreshold(float $threshold): self
    {
        $this->regressionThreshold = $threshold;
        return $this;
    }

    /**
     * Compare two metric sets and return analysis.
     *
     * @param array<string, mixed> $before Baseline metrics
     * @param array<string, mixed> $after Current metrics
     *
     * @return array<string, mixed>
     */
    public function compare(array $before, array $after): array
    {
        $comparison = [
            'duration' => $this->compareDuration($before, $after),
            'memory' => $this->compareMemory($before, $after),
            'phases' => $this->comparePhases($before, $after),
            'counts' => $this->compareCounts($before, $after),
            'summary' => [],
        ];

        // Generate summary
        $comparison['summary'] = $this->generateSummary($comparison);

        return $comparison;
    }

    /**
     * @param array<string, mixed> $before
     * @param array<string, mixed> $after
     *
     * @return array<string, mixed>
     */
    private function compareDuration(array $before, array $after): array
    {
        $beforeTotal = $before['total'] ?? 0.0;
        $afterTotal = $after['total'] ?? 0.0;

        $absolute = $afterTotal - $beforeTotal;
        $percentage = $beforeTotal > 0 ? ($absolute / $beforeTotal) * 100 : 0.0;

        return [
            'before' => $beforeTotal,
            'after' => $afterTotal,
            'absolute_change' => $absolute,
            'percentage_change' => $percentage,
            'improved' => $absolute < 0,
            'regressed' => $percentage > ($this->regressionThreshold * 100),
        ];
    }

    /**
     * @param array<string, mixed> $before
     * @param array<string, mixed> $after
     *
     * @return array<string, mixed>
     */
    private function compareMemory(array $before, array $after): array
    {
        $beforeMemory = $before['memory']['current'] ?? 0;
        $afterMemory = $after['memory']['current'] ?? 0;

        $absolute = $afterMemory - $beforeMemory;
        $percentage = $beforeMemory > 0 ? ($absolute / $beforeMemory) * 100 : 0.0;

        return [
            'before_mb' => round($beforeMemory / 1024 / 1024, 2),
            'after_mb' => round($afterMemory / 1024 / 1024, 2),
            'absolute_change_mb' => round($absolute / 1024 / 1024, 2),
            'percentage_change' => $percentage,
            'improved' => $absolute < 0,
        ];
    }

    /**
     * @param array<string, mixed> $before
     * @param array<string, mixed> $after
     *
     * @return array<string, array<string, mixed>>
     */
    private function comparePhases(array $before, array $after): array
    {
        $beforePhases = $before['phases'] ?? [];
        $afterPhases = $after['phases'] ?? [];

        $allPhases = array_unique(array_merge(array_keys($beforePhases), array_keys($afterPhases)));
        $comparison = [];

        foreach ($allPhases as $phase) {
            $beforeDuration = $beforePhases[$phase] ?? 0.0;
            $afterDuration = $afterPhases[$phase] ?? 0.0;

            $absolute = $afterDuration - $beforeDuration;
            $percentage = $beforeDuration > 0 ? ($absolute / $beforeDuration) * 100 : 0.0;

            $comparison[$phase] = [
                'before' => $beforeDuration,
                'after' => $afterDuration,
                'absolute_change' => $absolute,
                'percentage_change' => $percentage,
                'improved' => $absolute < 0,
            ];
        }

        return $comparison;
    }

    /**
     * @param array<string, mixed> $before
     * @param array<string, mixed> $after
     *
     * @return array<string, array<string, mixed>>
     */
    private function compareCounts(array $before, array $after): array
    {
        $beforeCounts = $before['counts'] ?? [];
        $afterCounts = $after['counts'] ?? [];

        $allCounts = array_unique(array_merge(array_keys($beforeCounts), array_keys($afterCounts)));
        $comparison = [];

        foreach ($allCounts as $name) {
            $beforeValue = $beforeCounts[$name] ?? 0;
            $afterValue = $afterCounts[$name] ?? 0;

            $comparison[$name] = [
                'before' => $beforeValue,
                'after' => $afterValue,
                'change' => $afterValue - $beforeValue,
            ];
        }

        return $comparison;
    }

    /**
     * @param array<string, mixed> $comparison
     *
     * @return array<string, mixed>
     */
    private function generateSummary(array $comparison): array
    {
        $messages = [];
        $status = 'unchanged';

        // Duration summary
        $duration = $comparison['duration'];
        if ($duration['regressed']) {
            $status = 'regressed';
            $messages[] = sprintf(
                'Performance regression: %.1f%% slower (%.2fs → %.2fs)',
                $duration['percentage_change'],
                $duration['before'],
                $duration['after'],
            );
        } elseif ($duration['improved']) {
            $status = 'improved';
            $messages[] = sprintf(
                'Performance improvement: %.1f%% faster (%.2fs → %.2fs)',
                abs($duration['percentage_change']),
                $duration['before'],
                $duration['after'],
            );
        }

        // Memory summary
        $memory = $comparison['memory'];
        if (abs($memory['percentage_change']) > 10) {
            $verb = $memory['improved'] ? 'reduced' : 'increased';
            $messages[] = sprintf(
                'Memory %s: %.1f%% (%.1fMB → %.1fMB)',
                $verb,
                abs($memory['percentage_change']),
                $memory['before_mb'],
                $memory['after_mb'],
            );
        }

        // Phase highlights
        $significantPhases = array_filter(
            $comparison['phases'],
            static fn(array $phase): bool => abs($phase['percentage_change']) > 20,
        );

        if (count($significantPhases) > 0) {
            $messages[] = sprintf('%d phases changed significantly', count($significantPhases));
        }

        return [
            'status' => $status,
            'messages' => $messages,
            'overall_change_percent' => $duration['percentage_change'],
        ];
    }
}

