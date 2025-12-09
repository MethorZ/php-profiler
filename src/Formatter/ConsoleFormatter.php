<?php
declare(strict_types=1);

namespace MethorZ\Profiler\Formatter;

use function count;
use function sprintf;

/**
 * Format metrics for console output with human-readable structure.
 */
final class ConsoleFormatter implements FormatterInterface
{
    private bool $showPercentages = true;
    private int $indentSize = 2;

    public function __construct(
        bool $showPercentages = true,
        int $indentSize = 2,
    ) {
        $this->showPercentages = $showPercentages;
        $this->indentSize = $indentSize;
    }

    public function format(array $metrics): string
    {
        $lines = [];

        // Operation name
        if (isset($metrics['operation'])) {
            $lines[] = sprintf('Operation: %s', $metrics['operation']);
        }

        // Total duration
        if (isset($metrics['total'])) {
            $lines[] = sprintf('Total: %.2fms', $metrics['total'] * 1000);
        }

        // Phases
        if (isset($metrics['phases']) && $metrics['phases'] !== []) {
            $lines[] = 'Phases:';
            $total = $metrics['total'] ?? 1.0;

            foreach ($metrics['phases'] as $phase => $duration) {
                $percentage = ($duration / $total) * 100;
                $durationMs = $duration * 1000;

                if ($this->showPercentages) {
                    $lines[] = sprintf(
                        '%s%s: %8.2fms (%5.1f%%)',
                        $this->indent(),
                        $phase,
                        $durationMs,
                        $percentage,
                    );
                } else {
                    $lines[] = sprintf(
                        '%s%s: %.2fms',
                        $this->indent(),
                        $phase,
                        $durationMs,
                    );
                }
            }
        }

        // Memory
        if (isset($metrics['memory'])) {
            $memory = $metrics['memory'];
            $parts = [];

            if (isset($memory['current_mb'])) {
                $parts[] = sprintf('%.1fMB', $memory['current_mb']);
            }

            if (isset($memory['peak_mb'])) {
                $parts[] = sprintf('peak: %.1fMB', $memory['peak_mb']);
            }

            if (isset($memory['delta_mb'])) {
                $sign = $memory['delta_mb'] >= 0 ? '+' : '';
                $parts[] = sprintf('Δ%s%.1fMB', $sign, $memory['delta_mb']);
            }

            if ($parts !== []) {
                $lines[] = sprintf('Memory: %s', implode(', ', $parts));
            }
        }

        // Counts
        if (isset($metrics['counts']) && $metrics['counts'] !== []) {
            $counts = $metrics['counts'];
            $parts = [];

            foreach ($counts as $name => $value) {
                $parts[] = sprintf('%s: %d', $name, $value);
            }

            $lines[] = sprintf('Counts: %s', implode(', ', $parts));
        }

        // Warnings
        if (isset($metrics['context']['warnings']) && $metrics['context']['warnings'] !== []) {
            $lines[] = 'Warnings:';
            foreach ($metrics['context']['warnings'] as $warning) {
                $lines[] = sprintf('%s⚠️  %s', $this->indent(), $warning);
            }
        }

        return implode("\n", $lines);
    }

    private function indent(): string
    {
        return str_repeat(' ', $this->indentSize);
    }
}

