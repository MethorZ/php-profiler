<?php
declare(strict_types=1);

namespace MethorZ\Profiler\Concern;

use MethorZ\Profiler\OperationProfiler;
use MethorZ\Profiler\PerformanceMonitor;

/**
 * Trait for easy profiling integration into repositories and services.
 *
 * Provides convenient methods to start profiling and retrieve metrics
 * using a consistent pattern across classes.
 *
 * Usage:
 * ```php
 * class MyRepository
 * {
 *     use ProfilesOperations;
 *
 *     public function fetchData(): array
 *     {
 *         $profiler = $this->startProfiling('fetch_data');
 *         try {
 *             // ... operation
 *             return $result;
 *         } finally {
 *             $profiler->end();
 *         }
 *     }
 *
 *     public function getLastQueryMetrics(): array
 *     {
 *         return $this->getLastProfilingMetrics();
 *     }
 * }
 * ```
 */
trait ProfilesOperations
{
    /**
     * Last operation metrics for performance analysis.
     *
     * @var array<string, mixed>
     */
    private array $lastProfilingMetrics = [];

    private ?PerformanceMonitor $profilingMonitor = null;

    /**
     * Start profiling an operation.
     *
     * The profiler should be ended via ->end() when the operation completes.
     * Use try/finally to ensure profiling ends even on exceptions.
     */
    protected function startProfiling(string $operation): OperationProfiler
    {
        $profiler = OperationProfiler::start($operation, $this->profilingMonitor);

        // Wrap profiler to capture metrics automatically
        return new class($profiler, $this) extends OperationProfiler {
            public function __construct(
                private readonly OperationProfiler $wrapped,
                private readonly ProfilesOperations $trait,
            ) {
            }

            public function checkpoint(string $name): void
            {
                $this->wrapped->checkpoint($name);
            }

            public function addCount(string $name, int $value): void
            {
                $this->wrapped->addCount($name, $value);
            }

            public function incrementCount(string $name, int $increment = 1): void
            {
                $this->wrapped->incrementCount($name, $increment);
            }

            /**
             * @param mixed $value
             */
            public function addContext(string $key, $value): void
            {
                $this->wrapped->addContext($key, $value);
            }

            public function end(): array
            {
                $metrics = $this->wrapped->end();
                $this->trait->lastProfilingMetrics = $metrics;
                return $metrics;
            }

            public function elapsed(): float
            {
                return $this->wrapped->elapsed();
            }

            public function currentMemory(): int
            {
                return $this->wrapped->currentMemory();
            }
        };
    }

    /**
     * Get the last operation's profiling metrics.
     *
     * @return array<string, mixed>
     */
    protected function getLastProfilingMetrics(): array
    {
        return $this->lastProfilingMetrics;
    }

    /**
     * Set a performance monitor for automatic threshold checking.
     */
    protected function setProfilingMonitor(?PerformanceMonitor $monitor): void
    {
        $this->profilingMonitor = $monitor;
    }

    /**
     * Get the current performance monitor.
     */
    protected function getProfilingMonitor(): ?PerformanceMonitor
    {
        return $this->profilingMonitor;
    }

    /**
     * Clear stored profiling metrics.
     */
    protected function clearProfilingMetrics(): void
    {
        $this->lastProfilingMetrics = [];
    }
}

