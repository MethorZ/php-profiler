<?php
declare(strict_types=1);

namespace MethorZ\Profiler\Formatter;

/**
 * Interface for metric formatters.
 */
interface FormatterInterface
{
    /**
     * Format metrics for output.
     *
     * @param array<string, mixed> $metrics Metrics to format
     */
    public function format(array $metrics): string;
}
