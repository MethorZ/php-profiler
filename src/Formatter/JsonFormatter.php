<?php
declare(strict_types=1);

namespace MethorZ\Profiler\Formatter;

use function json_encode;

use const JSON_PRETTY_PRINT;
use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;

/**
 * Format metrics as JSON for export and analysis.
 */
final class JsonFormatter implements FormatterInterface
{
    private bool $prettyPrint = true;

    public function __construct(bool $prettyPrint = true)
    {
        $this->prettyPrint = $prettyPrint;
    }

    public function format(array $metrics): string
    {
        $flags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;

        if ($this->prettyPrint) {
            $flags |= JSON_PRETTY_PRINT;
        }

        $result = json_encode($metrics, $flags);

        return $result !== false ? $result : '{}';
    }
}
