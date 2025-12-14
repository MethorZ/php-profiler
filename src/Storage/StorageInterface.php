<?php

declare(strict_types=1);

namespace MethorZ\Profiler\Storage;

/**
 * Interface for persisting profiling metrics.
 *
 * Allows storing metrics for later analysis, trend detection,
 * or comparison across runs.
 */
interface StorageInterface
{
    /**
     * Store metrics with a unique key.
     *
     * @param array<string, mixed> $metrics
     */
    public function store(string $key, array $metrics): void;

    /**
     * Retrieve stored metrics by key.
     *
     * @return array<string, mixed>|null
     */
    public function retrieve(string $key): ?array;

    /**
     * Retrieve multiple metrics by keys.
     *
     * @param array<int, string> $keys
     *
     * @return array<string, array<string, mixed>>
     */
    public function retrieveMultiple(array $keys): array;

    /**
     * Check if metrics exist for a key.
     */
    public function has(string $key): bool;

    /**
     * Delete metrics by key.
     */
    public function delete(string $key): void;

    /**
     * Clear all stored metrics.
     */
    public function clear(): void;
}
