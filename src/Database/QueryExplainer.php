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
     * @param string $query SQL query to explain
     *
     * @return array<string, mixed>
     */
    public function explain(string $query): array;

    /**
     * Check if query needs optimization based on EXPLAIN output.
     *
     * @param array<string, mixed> $explainData EXPLAIN output data
     */
    public function needsOptimization(array $explainData): bool;

    /**
     * Get suggestions for query optimization.
     *
     * @param array<string, mixed> $explainData EXPLAIN output data
     *
     * @return array<int, string>
     */
    public function getSuggestions(array $explainData): array;
}
