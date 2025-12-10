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
     * @param array<string, mixed> $before Baseline metrics
     * @param array<string, mixed> $after Current metrics
     *
     * @return array<string, mixed>
     */
    private function compareDuration(array $before, array $after): array
    {
        $beforeTotal = isset($before['total']) && is_numeric($before['total']) ? (float) $before['total'] : 0.0;
        $afterTotal = isset($after['total']) && is_numeric($after['total']) ? (float) $after['total'] : 0.0;

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
     * @param array<string, mixed> $before Baseline metrics
     * @param array<string, mixed> $after Current metrics
     *
     * @return array<string, mixed>
     */
    private function compareMemory(array $before, array $after): array
    {
        $beforeMemory = 0;
        $afterMemory = 0;

        if (isset($before['memory']) && is_array($before['memory'])) {
            $beforeMemory = $before['memory']['current'] ?? 0;
        }

        if (isset($after['memory']) && is_array($after['memory'])) {
            $afterMemory = $after['memory']['current'] ?? 0;
        }

        $beforeMemory = is_numeric($beforeMemory) ? (int) $beforeMemory : 0;
        $afterMemory = is_numeric($afterMemory) ? (int) $afterMemory : 0;

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
     * @param array<string, mixed> $before Baseline metrics
     * @param array<string, mixed> $after Current metrics
     *
     * @return array<string, array<string, mixed>>
     */
    private function comparePhases(array $before, array $after): array
    {
        $beforePhases = isset($before['phases']) && is_array($before['phases']) ? $before['phases'] : [];
        $afterPhases = isset($after['phases']) && is_array($after['phases']) ? $after['phases'] : [];

        $allPhases = array_unique(array_merge(array_keys($beforePhases), array_keys($afterPhases)));
        $comparison = [];

        foreach ($allPhases as $phase) {
            $beforeDuration = isset($beforePhases[$phase]) && is_numeric($beforePhases[$phase])
                ? (float) $beforePhases[$phase]
                : 0.0;
            $afterDuration = isset($afterPhases[$phase]) && is_numeric($afterPhases[$phase])
                ? (float) $afterPhases[$phase]
                : 0.0;

            $absolute = $afterDuration - $beforeDuration;
            $percentage = $beforeDuration > 0 ? ($absolute / $beforeDuration) * 100 : 0.0;

            $comparison[(string) $phase] = [
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
     * @param array<string, mixed> $before Baseline metrics
     * @param array<string, mixed> $after Current metrics
     *
     * @return array<string, array<string, mixed>>
     */
    private function compareCounts(array $before, array $after): array
    {
        $beforeCounts = isset($before['counts']) && is_array($before['counts']) ? $before['counts'] : [];
        $afterCounts = isset($after['counts']) && is_array($after['counts']) ? $after['counts'] : [];

        $allCounts = array_unique(array_merge(array_keys($beforeCounts), array_keys($afterCounts)));
        $comparison = [];

        foreach ($allCounts as $name) {
            $beforeValue = isset($beforeCounts[$name]) && is_numeric($beforeCounts[$name])
                ? (int) $beforeCounts[$name]
                : 0;
            $afterValue = isset($afterCounts[$name]) && is_numeric($afterCounts[$name])
                ? (int) $afterCounts[$name]
                : 0;

            $comparison[(string) $name] = [
                'before' => $beforeValue,
                'after' => $afterValue,
                'change' => $afterValue - $beforeValue,
            ];
        }

        return $comparison;
    }

    /**
     * @param array<string, mixed> $comparison Comparison results
     *
     * @return array<string, mixed>
     */
    private function generateSummary(array $comparison): array
    {
        $messages = [];
        $status = 'unchanged';

        // Duration summary
        if (!isset($comparison['duration']) || !is_array($comparison['duration'])) {
            return ['status' => $status, 'messages' => $messages, 'overall_change_percent' => 0.0];
        }

        $duration = $comparison['duration'];
        $regressed = $duration['regressed'] ?? false;
        $improved = $duration['improved'] ?? false;
        $percentageChange = isset($duration['percentage_change']) && is_numeric($duration['percentage_change'])
            ? (float) $duration['percentage_change']
            : 0.0;
        $before = isset($duration['before']) && is_numeric($duration['before']) ? (float) $duration['before'] : 0.0;
        $after = isset($duration['after']) && is_numeric($duration['after']) ? (float) $duration['after'] : 0.0;

        if ($regressed) {
            $status = 'regressed';
            $messages[] = sprintf(
                'Performance regression: %.1f%% slower (%.2fs → %.2fs)',
                $percentageChange,
                $before,
                $after,
            );
        } elseif ($improved) {
            $status = 'improved';
            $messages[] = sprintf(
                'Performance improvement: %.1f%% faster (%.2fs → %.2fs)',
                abs($percentageChange),
                $before,
                $after,
            );
        }

        // Memory summary
        if (isset($comparison['memory']) && is_array($comparison['memory'])) {
            $memory = $comparison['memory'];
            $memoryPercentageChange = isset($memory['percentage_change']) && is_numeric($memory['percentage_change'])
                ? (float) $memory['percentage_change']
                : 0.0;

            if (abs($memoryPercentageChange) > 10) {
                $memoryImproved = $memory['improved'] ?? false;
                $verb = $memoryImproved ? 'reduced' : 'increased';
                $beforeMb = isset($memory['before_mb']) && is_numeric($memory['before_mb'])
                    ? (float) $memory['before_mb']
                    : 0.0;
                $afterMb = isset($memory['after_mb']) && is_numeric($memory['after_mb'])
                    ? (float) $memory['after_mb']
                    : 0.0;

                $messages[] = sprintf(
                    'Memory %s: %.1f%% (%.1fMB → %.1fMB)',
                    $verb,
                    abs($memoryPercentageChange),
                    $beforeMb,
                    $afterMb,
                );
            }
        }

        // Phase highlights
        if (isset($comparison['phases']) && is_array($comparison['phases'])) {
            $significantPhases = array_filter(
                $comparison['phases'],
                static function (mixed $phase): bool {
                    if (!is_array($phase)) {
                        return false;
                    }
                    $percentageChange = $phase['percentage_change'] ?? 0;
                    return is_numeric($percentageChange) && abs((float) $percentageChange) > 20;
                },
            );

            if (count($significantPhases) > 0) {
                $messages[] = sprintf('%d phases changed significantly', count($significantPhases));
            }
        }

        return [
            'status' => $status,
            'messages' => $messages,
            'overall_change_percent' => $percentageChange,
        ];
    }
}
