<?php
declare(strict_types=1);

namespace MethorZ\Profiler;

use function getenv;

use const PHP_SAPI;

/**
 * Context-aware profiler that automatically adjusts behavior based on environment.
 *
 * Provides smart defaults:
 * - Production web: disabled
 * - Production CLI: sampled (10%)
 * - Development: full profiling
 * - Testing: full profiling
 */
final class ContextAwareProfiler
{
    private bool $forceEnabled = false;
    private ?float $forceSamplingRate = null;
    private ?PerformanceMonitor $monitor = null;
    private ?SamplingProfiler $samplingProfiler = null;

    /**
     * Create profiler with auto-detected context.
     */
    public static function create(): self
    {
        return new self();
    }

    /**
     * Force profiling to be enabled regardless of environment.
     */
    public function forceEnable(bool $enabled = true): self
    {
        $this->forceEnabled = $enabled;
        return $this;
    }

    /**
     * Force a specific sampling rate.
     */
    public function forceSamplingRate(float $rate): self
    {
        $this->forceSamplingRate = $rate;
        return $this;
    }

    /**
     * Set performance monitor for threshold checking.
     */
    public function setMonitor(PerformanceMonitor $monitor): self
    {
        $this->monitor = $monitor;
        return $this;
    }

    /**
     * Start profiling with context-aware configuration.
     */
    public function start(string $operation): OperationProfiler
    {
        $config = $this->getConfiguration();

        // Apply configuration
        OperationProfiler::setEnabled($config['enabled']);

        if (!$config['enabled']) {
            return OperationProfiler::start($operation);
        }

        // Use sampling if configured
        if ($config['sampling_rate'] < 1.0) {
            if ($this->samplingProfiler === null) {
                $this->samplingProfiler = new SamplingProfiler($config['sampling_rate']);

                if ($this->monitor !== null) {
                    $this->samplingProfiler->setMonitor($this->monitor);
                }
            }

            return $this->samplingProfiler->start($operation);
        }

        // Full profiling
        return OperationProfiler::start($operation, $this->monitor);
    }

    /**
     * Get configuration based on current context.
     *
     * @return array{enabled: bool, sampling_rate: float, reason: string}
     */
    public function getConfiguration(): array
    {
        // Force overrides
        if ($this->forceEnabled) {
            return [
                'enabled' => true,
                'sampling_rate' => $this->forceSamplingRate ?? 1.0,
                'reason' => 'Forced enabled',
            ];
        }

        $env = $this->detectEnvironment();
        $isCLI = PHP_SAPI === 'cli';

        return match (true) {
            $env === 'development' => [
                'enabled' => true,
                'sampling_rate' => 1.0,
                'reason' => 'Development environment',
            ],
            $env === 'testing' => [
                'enabled' => true,
                'sampling_rate' => 1.0,
                'reason' => 'Testing environment',
            ],
            $env === 'production' && $isCLI => [
                'enabled' => true,
                'sampling_rate' => $this->forceSamplingRate ?? 0.1, // 10% sampling
                'reason' => 'Production CLI with sampling',
            ],
            $env === 'production' && !$isCLI => [
                'enabled' => false,
                'sampling_rate' => 0.0,
                'reason' => 'Production web (disabled)',
            ],
            default => [
                'enabled' => true,
                'sampling_rate' => 1.0,
                'reason' => 'Unknown environment (default)',
            ],
        };
    }

    /**
     * Check if currently in production environment.
     */
    public function isProduction(): bool
    {
        return $this->detectEnvironment() === 'production';
    }

    /**
     * Check if currently in CLI mode.
     */
    public function isCLI(): bool
    {
        return PHP_SAPI === 'cli';
    }

    /**
     * Detect current environment from common environment variables.
     */
    private function detectEnvironment(): string
    {
        // Check common environment variables
        $env = getenv('APP_ENV')
            ?: getenv('ENVIRONMENT')
            ?: getenv('ENV')
            ?: 'production'; // Safe default

        return strtolower($env);
    }
}
