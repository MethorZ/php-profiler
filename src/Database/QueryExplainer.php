<?php
declare(strict_types=1);

namespace MethorZ\Profiler\Database;

/**
 * Interface for database query analysis and explanation.
 *
 * Implementations can provide EXPLAIN output for slow queries
 * to help identify performance issues.
 */
interface QueryExplainer
{
    /**
     * Explain a query and return analysis data.
     *
     * @return array<string, mixed>
     */
    public function explain(string $query): array;

    /**
     * Check if query needs optimization based on EXPLAIN output.
     *
     * @param array<string, mixed> $explainData
     */
    public function needsOptimization(array $explainData): bool;

    /**
     * Get suggestions for query optimization.
     *
     * @param array<string, mixed> $explainData
     *
     * @return array<int, string>
     */
    public function getSuggestions(array $explainData): array;
}

