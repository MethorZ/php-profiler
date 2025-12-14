<?php

declare(strict_types=1);

namespace MethorZ\Profiler;

/**
 * Main profiler for operation performance measurement.
 *
 * Tracks timing, memory usage, and custom metrics for operations
 * with checkpoint support. Can be globally disabled for zero overhead.
 *
 * Note: This class is not final to allow the NullProfiler to extend it.
 */
class OperationProfiler
{
    private Timer $timer;
    private int $memoryStart;

    /**
     * @var array<string, int>
     */
    private array $counts = [];

    /**
     * @var array<string, mixed>
     */
    private array $context = [];

    private ?PerformanceMonitor $monitor = null;

    private static bool $enabled = true;

    private function __construct(
        private readonly string $operation,
    ) {
        $this->timer = Timer::start();
        $this->memoryStart = memory_get_usage(true);
    }

    /**
     * Start profiling an operation.
     *
     * If profiling is globally disabled, returns a no-op profiler.
     */
    public static function start(string $operation, ?PerformanceMonitor $monitor = null): self
    {
        if (!self::$enabled) {
            return new NullProfiler($operation);
        }

        $profiler = new self($operation);

        if ($monitor !== null) {
            $profiler->monitor = $monitor;
        }

        return $profiler;
    }

    /**
     * Enable or disable profiling globally.
     *
     * When disabled, all profiler instances become no-ops with zero overhead.
     */
    public static function setEnabled(bool $enabled): void
    {
        self::$enabled = $enabled;
    }

    /**
     * Check if profiling is globally enabled.
     */
    public static function isEnabled(): bool
    {
        return self::$enabled;
    }

    /**
     * Mark a checkpoint in the operation.
     */
    public function checkpoint(string $name): void
    {
        $this->timer->checkpoint($name);
    }

    /**
     * Add a count metric.
     */
    public function addCount(string $name, int $value): void
    {
        $this->counts[$name] = $value;
    }

    /**
     * Increment a count metric.
     */
    public function incrementCount(string $name, int $increment = 1): void
    {
        $this->counts[$name] = ($this->counts[$name] ?? 0) + $increment;
    }

    /**
     * Add context information.
     */
    public function addContext(string $key, mixed $value): void
    {
        $this->context[$key] = $value;
    }

    /**
     * End profiling and return metrics.
     *
     * @return array<string, mixed>
     */
    public function end(): array
    {
        $timing = $this->timer->end();
        $memoryEnd = memory_get_usage(true);
        $memoryPeakEnd = memory_get_peak_usage(true);

        $memory = [
            'current' => $memoryEnd,
            'peak' => $memoryPeakEnd,
            'delta' => $memoryEnd - $this->memoryStart,
        ];

        $context = array_merge(
            $this->context,
            ['timestamp' => time()],
        );

        // Add warnings if monitor is present
        if ($this->monitor !== null) {
            $metricBag = new MetricBag(
                $this->operation,
                $timing['total'],
                $timing['checkpoints'],
                $memory,
                $this->counts,
                $context,
            );
            $warnings = $this->monitor->checkThresholds($metricBag);
            if ($warnings !== []) {
                $context['warnings'] = $warnings;
            }
        }

        $bag = new MetricBag(
            $this->operation,
            $timing['total'],
            $timing['checkpoints'],
            $memory,
            $this->counts,
            $context,
        );

        return $bag->toArray();
    }

    /**
     * Get elapsed time without ending profiling.
     */
    public function elapsed(): float
    {
        return $this->timer->elapsed();
    }

    /**
     * Get current memory usage.
     */
    public function currentMemory(): int
    {
        return memory_get_usage(true);
    }
}

/**
 * Null object pattern for disabled profiler.
 *
 * All operations are no-ops to ensure zero overhead when profiling is disabled.
 *
 * @internal
 *
 * phpcs:disable PSR1.Classes.ClassDeclaration.MultipleClasses
 * phpcs:disable Squiz.Classes.ClassFileName.NoMatch
 */
final class NullProfiler extends OperationProfiler
{
    /**
     * @param string $_operation Operation name (unused in null profiler)
     *
     * @phpstan-ignore-next-line constructor.unusedParameter
     */
    public function __construct(string $_operation)
    {
        // Don't call parent constructor (no initialization)
    }

    public function checkpoint(string $_name): void
    {
        // No-op
    }

    public function addCount(string $_name, int $_value): void
    {
        // No-op
    }

    public function incrementCount(string $_name, int $_increment = 1): void
    {
        // No-op
    }

    public function addContext(string $_key, mixed $_value): void
    {
        // No-op
    }

    public function end(): array
    {
        return [];
    }

    public function elapsed(): float
    {
        return 0.0;
    }

    public function currentMemory(): int
    {
        return 0;
    }
}
