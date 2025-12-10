<?php
declare(strict_types=1);

namespace MethorZ\Profiler;

/**
 * Container for operation metrics with standardized format.
 *
 * Stores timing, memory, counts, and contextual information about
 * a profiled operation with consistent structure and units.
 */
final class MetricBag
{
    /**
     * @param array<string, float> $phases Phase timings in seconds
     * @param array<string, int> $memory Memory metrics in bytes
     * @param array<string, int> $counts Arbitrary count metrics
     * @param array<string, mixed> $context Additional context
     */
    public function __construct(
        private readonly string $operation,
        private readonly float $total,
        private readonly array $phases = [],
        private readonly array $memory = [],
        private readonly array $counts = [],
        private readonly array $context = [],
    ) {
    }

    /**
     * Convert metrics to standardized array format.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $data = [
            'operation' => $this->operation,
            'total' => $this->total,
        ];

        if ($this->phases !== []) {
            $data['phases'] = $this->phases;
        }

        if ($this->memory !== []) {
            $data['memory'] = array_merge(
                $this->memory,
                [
                    'current_mb' => round($this->memory['current'] / 1024 / 1024, 2),
                    'peak_mb' => round($this->memory['peak'] / 1024 / 1024, 2),
                    'delta_mb' => round($this->memory['delta'] / 1024 / 1024, 2),
                ],
            );
        }

        if ($this->counts !== []) {
            $data['counts'] = $this->counts;
        }

        if ($this->context !== []) {
            $data['context'] = $this->context;
        }

        return $data;
    }

    public function getOperation(): string
    {
        return $this->operation;
    }

    public function getTotal(): float
    {
        return $this->total;
    }

    /**
     * @return array<string, float>
     */
    public function getPhases(): array
    {
        return $this->phases;
    }

    /**
     * @return array<string, int>
     */
    public function getMemory(): array
    {
        return $this->memory;
    }

    /**
     * @return array<string, int>
     */
    public function getCounts(): array
    {
        return $this->counts;
    }

    /**
     * @return array<string, mixed>
     */
    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * Get a specific phase timing.
     */
    public function getPhase(string $name): ?float
    {
        return $this->phases[$name] ?? null;
    }

    /**
     * Get a specific count.
     */
    public function getCount(string $name): ?int
    {
        return $this->counts[$name] ?? null;
    }

    /**
     * Get memory usage in megabytes.
     */
    public function getMemoryInMB(): float
    {
        return isset($this->memory['current'])
            ? round($this->memory['current'] / 1024 / 1024, 2)
            : 0.0;
    }
}
