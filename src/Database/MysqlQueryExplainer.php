<?php
declare(strict_types=1);

namespace MethorZ\Profiler\Database;

use PDO;

/**
 * MySQL-specific query explainer.
 *
 * Provides EXPLAIN output and optimization suggestions for MySQL queries.
 */
final class MysqlQueryExplainer implements QueryExplainer
{
    public function __construct(
        private readonly PDO $pdo,
    ) {
    }

    public function explain(string $query): array
    {
        try {
            $stmt = $this->pdo->query('EXPLAIN ' . $query);

            if ($stmt === false) {
                return ['error' => 'Failed to execute EXPLAIN'];
            }

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            return ['error' => $e->getMessage()];
        }
    }

    public function needsOptimization(array $explainData): bool
    {
        if (isset($explainData['error'])) {
            return false;
        }

        foreach ($explainData as $row) {
            // Check for common issues
            if (isset($row['type']) && $row['type'] === 'ALL') {
                return true; // Full table scan
            }

            if (isset($row['Extra']) && str_contains($row['Extra'], 'Using filesort')) {
                return true; // Filesort detected
            }

            if (isset($row['Extra']) && str_contains($row['Extra'], 'Using temporary')) {
                return true; // Temporary table detected
            }

            if (isset($row['rows']) && $row['rows'] > 10000) {
                return true; // Scanning many rows
            }
        }

        return false;
    }

    public function getSuggestions(array $explainData): array
    {
        if (isset($explainData['error'])) {
            return [];
        }

        $suggestions = [];

        foreach ($explainData as $row) {
            $table = $row['table'] ?? 'unknown';

            if (isset($row['type']) && $row['type'] === 'ALL') {
                $suggestions[] = sprintf(
                    'Full table scan on "%s" - consider adding an index',
                    $table,
                );
            }

            if (isset($row['key']) && $row['key'] === null && isset($row['possible_keys'])) {
                $suggestions[] = sprintf(
                    'No index used on "%s" despite possible keys: %s',
                    $table,
                    $row['possible_keys'],
                );
            }

            if (isset($row['Extra']) && str_contains($row['Extra'], 'Using filesort')) {
                $suggestions[] = sprintf(
                    'Filesort on "%s" - consider adding an index that matches ORDER BY',
                    $table,
                );
            }

            if (isset($row['Extra']) && str_contains($row['Extra'], 'Using temporary')) {
                $suggestions[] = sprintf(
                    'Temporary table on "%s" - may need query optimization',
                    $table,
                );
            }

            if (isset($row['rows']) && $row['rows'] > 10000) {
                $suggestions[] = sprintf(
                    'High row count on "%s": %d rows examined - consider narrowing query scope',
                    $table,
                    $row['rows'],
                );
            }
        }

        return array_unique($suggestions);
    }
}

