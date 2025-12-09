<?php
declare(strict_types=1);

namespace MethorZ\Profiler;

use MethorZ\Profiler\Exception\ProfilingException;

/**
 * Precise timing utility with checkpoint support.
 *
 * Provides microsecond-precision timing measurements for operations
 * and allows marking checkpoints to identify bottlenecks.
 */
final class Timer
{
    private float $startTime;
    private ?float $endTime = null;

    /**
     * @var array<string, float>
     */
    private array $checkpoints = [];

    private function __construct()
    {
        $this->startTime = microtime(true);
    }

    /**
     * Start a new timer.
     */
    public static function start(): self
    {
        return new self();
    }

    /**
     * Mark a checkpoint with the given name.
     *
     * Checkpoints record elapsed time since timer start.
     */
    public function checkpoint(string $name): void
    {
        if ($this->endTime !== null) {
            throw ProfilingException::invalidCheckpoint($name . ' (timer already ended)');
        }

        if (trim($name) === '') {
            throw ProfilingException::invalidCheckpoint('empty checkpoint name');
        }

        $this->checkpoints[$name] = microtime(true) - $this->startTime;
    }

    /**
     * End the timer and return timing data.
     *
     * @return array{total: float, checkpoints: array<string, float>}
     */
    public function end(): array
    {
        if ($this->endTime !== null) {
            return $this->buildResult();
        }

        $this->endTime = microtime(true);

        return $this->buildResult();
    }

    /**
     * Get elapsed time without ending the timer.
     */
    public function elapsed(): float
    {
        $endTime = $this->endTime ?? microtime(true);
        return $endTime - $this->startTime;
    }

    /**
     * Check if timer has ended.
     */
    public function isEnded(): bool
    {
        return $this->endTime !== null;
    }

    /**
     * @return array{total: float, checkpoints: array<string, float>}
     */
    private function buildResult(): array
    {
        return [
            'total' => $this->elapsed(),
            'checkpoints' => $this->checkpoints,
        ];
    }
}

