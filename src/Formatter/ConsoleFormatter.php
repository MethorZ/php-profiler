<?php
declare(strict_types=1);

namespace MethorZ\Profiler\Formatter;

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
        if (isset($metrics['operation']) && is_string($metrics['operation'])) {
            $lines[] = sprintf('Operation: %s', $metrics['operation']);
        }

        // Total duration
        $total = 1.0;
        if (isset($metrics['total']) && is_numeric($metrics['total'])) {
            $total = (float) $metrics['total'];
            $lines[] = sprintf('Total: %.2fms', $total * 1000);
        }

        // Phases
        if (isset($metrics['phases']) && is_array($metrics['phases']) && $metrics['phases'] !== []) {
            $lines[] = 'Phases:';

            foreach ($metrics['phases'] as $phase => $duration) {
                if (!is_numeric($duration)) {
                    continue;
                }

                $durationFloat = (float) $duration;
                $percentage = ($durationFloat / $total) * 100;
                $durationMs = $durationFloat * 1000;

                if ($this->showPercentages) {
                    $lines[] = sprintf(
                        '%s%s: %8.2fms (%5.1f%%)',
                        $this->indent(),
                        (string) $phase,
                        $durationMs,
                        $percentage,
                    );
                } else {
                    $lines[] = sprintf(
                        '%s%s: %.2fms',
                        $this->indent(),
                        (string) $phase,
                        $durationMs,
                    );
                }
            }
        }

        // Memory
        if (isset($metrics['memory']) && is_array($metrics['memory'])) {
            $memory = $metrics['memory'];
            $parts = [];

            if (isset($memory['current_mb']) && is_numeric($memory['current_mb'])) {
                $parts[] = sprintf('%.1fMB', (float) $memory['current_mb']);
            }

            if (isset($memory['peak_mb']) && is_numeric($memory['peak_mb'])) {
                $parts[] = sprintf('peak: %.1fMB', (float) $memory['peak_mb']);
            }

            if (isset($memory['delta_mb']) && is_numeric($memory['delta_mb'])) {
                $deltaMb = (float) $memory['delta_mb'];
                $sign = $deltaMb >= 0 ? '+' : '';
                $parts[] = sprintf('Δ%s%.1fMB', $sign, $deltaMb);
            }

            if ($parts !== []) {
                $lines[] = sprintf('Memory: %s', implode(', ', $parts));
            }
        }

        // Counts
        if (isset($metrics['counts']) && is_array($metrics['counts']) && $metrics['counts'] !== []) {
            $counts = $metrics['counts'];
            $parts = [];

            foreach ($counts as $name => $value) {
                if (is_numeric($value)) {
                    $parts[] = sprintf('%s: %d', (string) $name, (int) $value);
                }
            }

            if ($parts !== []) {
                $lines[] = sprintf('Counts: %s', implode(', ', $parts));
            }
        }

        // Warnings
        if (isset($metrics['context'])
            && is_array($metrics['context'])
            && isset($metrics['context']['warnings'])
            && is_array($metrics['context']['warnings'])
            && $metrics['context']['warnings'] !== []
        ) {
            $lines[] = 'Warnings:';
            foreach ($metrics['context']['warnings'] as $warning) {
                if (is_string($warning)) {
                    $lines[] = sprintf('%s⚠️  %s', $this->indent(), $warning);
                }
            }
        }

        return implode("\n", $lines);
    }

    private function indent(): string
    {
        return str_repeat(' ', $this->indentSize);
    }
}
