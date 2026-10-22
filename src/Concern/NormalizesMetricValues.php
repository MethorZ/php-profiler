<?php

declare(strict_types=1);

namespace MethorZ\Profiler\Concern;

use MethorZ\Profiler\Exception\ProfilingException;

/**
 * @internal
 */
trait NormalizesMetricValues
{
    /**
     * @throws ProfilingException When the value is not numeric
     */
    private function toNumber(mixed $value, string $metric): int|float
    {
        if (!is_numeric($value)) {
            throw ProfilingException::invalidMetric($metric, $value);
        }

        return $value + 0;
    }
}
