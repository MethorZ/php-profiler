<?php

declare(strict_types=1);

namespace MethorZ\Profiler\Storage;

/**
 * In-memory storage for profiling metrics.
 *
 * Useful for testing or temporary storage within a request.
 */
final class MemoryStorage implements StorageInterface
{
    /**
     * @var array<string, array<string, mixed>>
     */
    private array $storage = [];

    public function store(string $key, array $metrics): void
    {
        $this->storage[$key] = $metrics;
    }

    public function retrieve(string $key): ?array
    {
        return $this->storage[$key] ?? null;
    }

    public function retrieveMultiple(array $keys): array
    {
        $result = [];

        foreach ($keys as $key) {
            if (isset($this->storage[$key])) {
                $result[$key] = $this->storage[$key];
            }
        }

        return $result;
    }

    public function has(string $key): bool
    {
        return isset($this->storage[$key]);
    }

    public function delete(string $key): void
    {
        unset($this->storage[$key]);
    }

    public function clear(): void
    {
        $this->storage = [];
    }

    /**
     * Get all stored metrics.
     *
     * @return array<string, array<string, mixed>>
     */
    public function all(): array
    {
        return $this->storage;
    }
}
