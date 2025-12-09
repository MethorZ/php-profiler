<?php
declare(strict_types=1);

namespace MethorZ\Profiler\Tests\Unit\Formatter;

use MethorZ\Profiler\Formatter\ConsoleFormatter;
use PHPUnit\Framework\TestCase;

final class ConsoleFormatterTest extends TestCase
{
    public function testFormatBasicMetrics(): void
    {
        $formatter = new ConsoleFormatter();
        $metrics = [
            'operation' => 'test_operation',
            'total' => 0.125,
        ];

        $output = $formatter->format($metrics);

        $this->assertStringContainsString('Operation: test_operation', $output);
        $this->assertStringContainsString('Total: 125.00ms', $output);
    }

    public function testFormatWithPhases(): void
    {
        $formatter = new ConsoleFormatter();
        $metrics = [
            'operation' => 'test_operation',
            'total' => 0.100,
            'phases' => [
                'prep' => 0.010,
                'exec' => 0.080,
                'hydrate' => 0.010,
            ],
        ];

        $output = $formatter->format($metrics);

        $this->assertStringContainsString('Phases:', $output);
        $this->assertStringContainsString('prep:', $output);
        $this->assertStringContainsString('exec:', $output);
        $this->assertStringContainsString('hydrate:', $output);
    }

    public function testFormatWithPercentages(): void
    {
        $formatter = new ConsoleFormatter(showPercentages: true);
        $metrics = [
            'total' => 0.100,
            'phases' => ['prep' => 0.010, 'exec' => 0.090],
        ];

        $output = $formatter->format($metrics);

        $this->assertStringContainsString('10.0%', $output);
        $this->assertStringContainsString('90.0%', $output);
    }

    public function testFormatWithoutPercentages(): void
    {
        $formatter = new ConsoleFormatter(showPercentages: false);
        $metrics = [
            'total' => 0.100,
            'phases' => ['prep' => 0.010],
        ];

        $output = $formatter->format($metrics);

        $this->assertStringNotContainsString('%', $output);
    }

    public function testFormatWithMemory(): void
    {
        $formatter = new ConsoleFormatter();
        $metrics = [
            'total' => 0.100,
            'memory' => [
                'current_mb' => 45.5,
                'peak_mb' => 48.2,
                'delta_mb' => 2.7,
            ],
        ];

        $output = $formatter->format($metrics);

        $this->assertStringContainsString('Memory:', $output);
        $this->assertStringContainsString('45.5MB', $output);
        $this->assertStringContainsString('peak: 48.2MB', $output);
        $this->assertStringContainsString('Δ+2.7MB', $output);
    }

    public function testFormatWithCounts(): void
    {
        $formatter = new ConsoleFormatter();
        $metrics = [
            'total' => 0.100,
            'counts' => [
                'rows' => 2340,
                'accounts' => 150,
            ],
        ];

        $output = $formatter->format($metrics);

        $this->assertStringContainsString('Counts:', $output);
        $this->assertStringContainsString('rows: 2340', $output);
        $this->assertStringContainsString('accounts: 150', $output);
    }

    public function testFormatWithWarnings(): void
    {
        $formatter = new ConsoleFormatter();
        $metrics = [
            'total' => 0.100,
            'context' => [
                'warnings' => [
                    'Slow operation: 1.25s',
                    'High memory usage: 512MB',
                ],
            ],
        ];

        $output = $formatter->format($metrics);

        $this->assertStringContainsString('Warnings:', $output);
        $this->assertStringContainsString('Slow operation: 1.25s', $output);
        $this->assertStringContainsString('High memory usage: 512MB', $output);
    }

    public function testFormatWithCustomIndent(): void
    {
        $formatter = new ConsoleFormatter(indentSize: 4);
        $metrics = [
            'total' => 0.100,
            'phases' => ['prep' => 0.010],
        ];

        $output = $formatter->format($metrics);

        // Should have 4 spaces of indentation
        $this->assertStringContainsString('    prep:', $output);
    }
}

