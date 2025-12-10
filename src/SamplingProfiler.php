<?php
declare(strict_types=1);

namespace MethorZ\Profiler;

use function mt_getrandmax;
use function mt_rand;

/**
 * Profiler with sampling capability to reduce overhead.
 *
 * Only profiles a configurable percentage of operations,
 * making it suitable for production use.
 */
final class SamplingProfiler
{
    private float $samplingRate;
    private ?PerformanceMonitor $monitor = null;

    /**
     * @param float $samplingRate Sampling rate between 0.0 and 1.0 (e.g., 0.1 = 10%)
     */
    public function __construct(float $samplingRate = 0.1)
    {
        if ($samplingRate < 0.0 || $samplingRate > 1.0) {
            throw new \InvalidArgumentException('Sampling rate must be between 0.0 and 1.0');
        }

        $this->samplingRate = $samplingRate;
    }

    /**
     * Set performance monitor for sampled operations.
     */
    public function setMonitor(PerformanceMonitor $monitor): self
    {
        $this->monitor = $monitor;
        return $this;
    }

    /**
     * Start profiling with sampling.
     *
     * Returns a profiler if operation is sampled, otherwise returns a no-op profiler.
     */
    public function start(string $operation): OperationProfiler
    {
        if (!$this->shouldSample()) {
            // Return no-op profiler
            OperationProfiler::setEnabled(false);
            $profiler = OperationProfiler::start($operation);
            OperationProfiler::setEnabled(true);
            return $profiler;
        }

        return OperationProfiler::start($operation, $this->monitor);
    }

    /**
     * Check if current operation should be sampled.
     */
    public function shouldSample(): bool
    {
        if ($this->samplingRate === 0.0) {
            return false;
        }

        if ($this->samplingRate === 1.0) {
            return true;
        }

        return (mt_rand() / mt_getrandmax()) < $this->samplingRate;
    }

    /**
     * Get current sampling rate.
     */
    public function getSamplingRate(): float
    {
        return $this->samplingRate;
    }
}
