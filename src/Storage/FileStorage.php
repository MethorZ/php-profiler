<?php
declare(strict_types=1);

namespace MethorZ\Profiler\Storage;

use RuntimeException;

use function file_exists;
use function file_get_contents;
use function file_put_contents;
use function is_dir;
use function json_decode;
use function json_encode;
use function mkdir;
use function sprintf;
use function unlink;

use const JSON_PRETTY_PRINT;
use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;

/**
 * File-based storage for profiling metrics.
 *
 * Stores metrics as JSON files in a directory.
 * Useful for local development and debugging.
 */
final class FileStorage implements StorageInterface
{
    private string $storagePath;

    /**
     * In-memory cache to avoid repeated file reads.
     *
     * @var array<string, array<string, mixed>>
     */
    private array $cache = [];

    public function __construct(string $storagePath)
    {
        $this->storagePath = rtrim($storagePath, '/');
        $this->ensureDirectoryExists();
    }

    public function store(string $key, array $metrics): void
    {
        $filePath = $this->getFilePath($key);
        $json = json_encode($metrics, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if ($json === false) {
            throw new RuntimeException('Failed to encode metrics to JSON');
        }

        $result = file_put_contents($filePath, $json);

        if ($result === false) {
            throw new RuntimeException(sprintf('Failed to write metrics to file: %s', $filePath));
        }

        // Update cache
        $this->cache[$key] = $metrics;
    }

    public function retrieve(string $key): ?array
    {
        // Check cache first
        if (isset($this->cache[$key])) {
            return $this->cache[$key];
        }

        $filePath = $this->getFilePath($key);

        if (!file_exists($filePath)) {
            return null;
        }

        $json = file_get_contents($filePath);

        if ($json === false) {
            throw new RuntimeException(sprintf('Failed to read metrics from file: %s', $filePath));
        }

        $metrics = json_decode($json, true);

        if (!is_array($metrics)) {
            throw new RuntimeException(sprintf('Invalid JSON in metrics file: %s', $filePath));
        }

        // Cache for future reads
        $this->cache[$key] = $metrics;

        return $metrics;
    }

    public function retrieveMultiple(array $keys): array
    {
        $result = [];

        foreach ($keys as $key) {
            $metrics = $this->retrieve($key);
            if ($metrics !== null) {
                $result[$key] = $metrics;
            }
        }

        return $result;
    }

    public function has(string $key): bool
    {
        return file_exists($this->getFilePath($key));
    }

    public function delete(string $key): void
    {
        $filePath = $this->getFilePath($key);

        if (file_exists($filePath)) {
            unlink($filePath);
        }

        // Remove from cache
        unset($this->cache[$key]);
    }

    public function clear(): void
    {
        $files = glob($this->storagePath . '/*.json');

        if ($files === false) {
            return;
        }

        foreach ($files as $file) {
            unlink($file);
        }

        // Clear cache
        $this->cache = [];
    }

    private function getFilePath(string $key): string
    {
        // Sanitize key for filesystem
        $safeKey = preg_replace('/[^a-zA-Z0-9_-]/', '_', $key);
        return sprintf('%s/%s.json', $this->storagePath, $safeKey);
    }

    private function ensureDirectoryExists(): void
    {
        if (!is_dir($this->storagePath)) {
            $result = mkdir($this->storagePath, 0755, true);

            if (!$result) {
                throw new RuntimeException(sprintf('Failed to create storage directory: %s', $this->storagePath));
            }
        }
    }
}

