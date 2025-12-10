<?php
declare(strict_types=1);

namespace MethorZ\Profiler;

use MethorZ\Profiler\Exception\ProfilingException;

use function ini_get;

/**
 * Monitors performance metrics and generates warnings based on thresholds.
 *
 * Helps identify performance issues by comparing metrics against
 * configurable thresholds.
 */
final class PerformanceMonitor
{
    private float $slowOperationThreshold = 1.0;  // seconds
    private float $slowPhaseThreshold = 0.5;      // seconds
    private float $highMemoryThreshold = 0.7;     // 70% of memory limit
    private ?int $highRowCountThreshold = null;

    private function __construct()
    {
    }

    /**
     * Create a new performance monitor with default thresholds.
     */
    public static function create(): self
    {
        return new self();
    }

    /**
     * Set threshold for slow operations (in seconds).
     */
    public function setSlowOperationThreshold(float $seconds): self
    {
        if ($seconds <= 0) {
            throw ProfilingException::invalidThreshold('slow_operation', $seconds);
        }

        $this->slowOperationThreshold = $seconds;
        return $this;
    }

    /**
     * Set threshold for slow individual phases (in seconds).
     */
    public function setSlowPhaseThreshold(float $seconds): self
    {
        if ($seconds <= 0) {
            throw ProfilingException::invalidThreshold('slow_phase', $seconds);
        }

        $this->slowPhaseThreshold = $seconds;
        return $this;
    }

    /**
     * Set threshold for high memory usage (as fraction of memory limit: 0.0-1.0).
     */
    public function setHighMemoryThreshold(float $fraction): self
    {
        if ($fraction <= 0 || $fraction > 1.0) {
            throw ProfilingException::invalidThreshold('high_memory', $fraction);
        }

        $this->highMemoryThreshold = $fraction;
        return $this;
    }

    /**
     * Set threshold for high row count.
     */
    public function setHighRowCountThreshold(?int $rows): self
    {
        if ($rows !== null && $rows <= 0) {
            throw ProfilingException::invalidThreshold('high_row_count', (float) $rows);
        }

        $this->highRowCountThreshold = $rows;
        return $this;
    }

    /**
     * Check metrics against thresholds and return warnings.
     *
     * @return array<int, string>
     */
    public function checkThresholds(MetricBag $metrics): array
    {
        $warnings = [];

        // Check operation duration
        if ($metrics->getTotal() > $this->slowOperationThreshold) {
            $warnings[] = sprintf(
                'Slow operation: %.2fs (threshold: %.2fs)',
                $metrics->getTotal(),
                $this->slowOperationThreshold,
            );
        }

        // Check phase durations
        foreach ($metrics->getPhases() as $phase => $duration) {
            if ($duration > $this->slowPhaseThreshold) {
                $warnings[] = sprintf(
                    'Slow phase "%s": %.2fs (threshold: %.2fs)',
                    $phase,
                    $duration,
                    $this->slowPhaseThreshold,
                );
            }
        }

        // Check memory usage
        $memory = $metrics->getMemory();
        if (isset($memory['peak'])) {
            $memoryLimit = $this->getMemoryLimit();
            $memoryUsagePercent = $memory['peak'] / $memoryLimit;

            if ($memoryUsagePercent > $this->highMemoryThreshold) {
                $warnings[] = sprintf(
                    'High memory usage: %.1fMB (%.1f%% of limit, threshold: %.0f%%)',
                    $memory['peak'] / 1024 / 1024,
                    $memoryUsagePercent * 100,
                    $this->highMemoryThreshold * 100,
                );
            }
        }

        // Check row count if threshold is set
        if ($this->highRowCountThreshold !== null) {
            $rowCount = $metrics->getCount('rows');
            if ($rowCount !== null && $rowCount > $this->highRowCountThreshold) {
                $warnings[] = sprintf(
                    'High row count: %d (threshold: %d)',
                    $rowCount,
                    $this->highRowCountThreshold,
                );
            }
        }

        return $warnings;
    }

    /**
     * Get PHP memory limit in bytes.
     */
    private function getMemoryLimit(): int
    {
        $memoryLimit = ini_get('memory_limit');

        // PHPStan doesn't understand that ini_get can return string after false check
        /** @phpstan-ignore-next-line identical.alwaysFalse */
        if ($memoryLimit === false || $memoryLimit === '' || $memoryLimit === '-1') {
            return PHP_INT_MAX;
        }

        return $this->parseMemoryLimit($memoryLimit);
    }

    /**
     * Parse memory limit string to bytes.
     */
    private function parseMemoryLimit(string $memoryLimit): int
    {
        $unit = strtolower(substr($memoryLimit, -1));
        $value = (int) substr($memoryLimit, 0, -1);

        return match ($unit) {
            'g' => $value * 1024 * 1024 * 1024,
            'm' => $value * 1024 * 1024,
            'k' => $value * 1024,
            default => (int) $memoryLimit,
        };
    }
}
