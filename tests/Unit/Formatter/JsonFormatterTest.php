<?php

declare(strict_types=1);

namespace MethorZ\Profiler\Tests\Unit\Formatter;

use MethorZ\Profiler\Formatter\JsonFormatter;
use PHPUnit\Framework\TestCase;

use function json_decode;

final class JsonFormatterTest extends TestCase
{
    public function testFormatReturnsValidJson(): void
    {
        $formatter = new JsonFormatter();
        $metrics = [
            'operation' => 'test_operation',
            'total' => 0.125,
        ];

        $output = $formatter->format($metrics);
        $decoded = json_decode($output, true);

        $this->assertIsArray($decoded);
        $this->assertEquals('test_operation', $decoded['operation']);
        $this->assertEquals(0.125, $decoded['total']);
    }

    public function testFormatWithPrettyPrint(): void
    {
        $formatter = new JsonFormatter(prettyPrint: true);
        $metrics = ['operation' => 'test', 'total' => 0.1];

        $output = $formatter->format($metrics);

        $this->assertStringContainsString("\n", $output);
        $this->assertStringContainsString('    ', $output);
    }

    public function testFormatWithoutPrettyPrint(): void
    {
        $formatter = new JsonFormatter(prettyPrint: false);
        $metrics = ['operation' => 'test', 'total' => 0.1];

        $output = $formatter->format($metrics);

        $this->assertStringNotContainsString("\n", $output);
    }

    public function testFormatPreservesStructure(): void
    {
        $formatter = new JsonFormatter();
        $metrics = [
            'operation' => 'test_operation',
            'total' => 0.125,
            'phases' => ['prep' => 0.010, 'exec' => 0.100],
            'memory' => ['current' => 1024000, 'peak' => 2048000],
            'counts' => ['rows' => 100],
        ];

        $output = $formatter->format($metrics);
        $decoded = json_decode($output, true);

        $this->assertEquals($metrics, $decoded);
    }

    public function testFormatHandlesUnicode(): void
    {
        $formatter = new JsonFormatter();
        $metrics = [
            'operation' => 'test_üöä',
            'total' => 0.1,
        ];

        $output = $formatter->format($metrics);

        $this->assertStringContainsString('test_üöä', $output);
    }
}
