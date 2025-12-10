<?php
declare(strict_types=1);

namespace MethorZ\Profiler\Exception;

use RuntimeException;

class ProfilingException extends RuntimeException
{
    public static function alreadyStarted(string $operation): self
    {
        return new self(sprintf('Profiler for operation "%s" is already running', $operation));
    }

    public static function notStarted(string $operation): self
    {
        return new self(sprintf('Profiler for operation "%s" was not started', $operation));
    }

    public static function invalidCheckpoint(string $checkpoint): self
    {
        return new self(sprintf('Invalid checkpoint name: "%s"', $checkpoint));
    }

    public static function invalidThreshold(string $name, float $value): self
    {
        return new self(sprintf('Invalid threshold "%s": %.2f', $name, $value));
    }
}
