<?php

declare(strict_types=1);

namespace MethorZ\Profiler\Tests\Unit;

use MethorZ\Profiler\ContextAwareProfiler;
use PHPUnit\Framework\TestCase;

final class ContextAwareProfilerTest extends TestCase
{
    protected function setUp(): void
    {
        // Clear environment variable
        putenv('APP_ENV');
    }

    public function testCreate(): void
    {
        $profiler = ContextAwareProfiler::create();

        $this->assertInstanceOf(ContextAwareProfiler::class, $profiler);
    }

    public function testForceEnable(): void
    {
        $profiler = ContextAwareProfiler::create()->forceEnable();
        $config = $profiler->getConfiguration();

        $this->assertTrue($config['enabled']);
        $this->assertEquals('Forced enabled', $config['reason']);
    }

    public function testForceSamplingRate(): void
    {
        $profiler = ContextAwareProfiler::create()
            ->forceEnable()
            ->forceSamplingRate(0.3);

        $config = $profiler->getConfiguration();

        $this->assertEquals(0.3, $config['sampling_rate']);
    }

    public function testConfigurationForDevelopment(): void
    {
        putenv('APP_ENV=development');

        $profiler = ContextAwareProfiler::create();
        $config = $profiler->getConfiguration();

        $this->assertTrue($config['enabled']);
        $this->assertEquals(1.0, $config['sampling_rate']);
        $this->assertStringContainsString('Development', $config['reason']);
    }

    public function testConfigurationForTesting(): void
    {
        putenv('APP_ENV=testing');

        $profiler = ContextAwareProfiler::create();
        $config = $profiler->getConfiguration();

        $this->assertTrue($config['enabled']);
        $this->assertEquals(1.0, $config['sampling_rate']);
        $this->assertStringContainsString('Testing', $config['reason']);
    }

    public function testIsProduction(): void
    {
        putenv('APP_ENV=production');

        $profiler = ContextAwareProfiler::create();

        $this->assertTrue($profiler->isProduction());
    }

    public function testIsCLI(): void
    {
        $profiler = ContextAwareProfiler::create();

        // We're running tests in CLI
        $this->assertTrue($profiler->isCLI());
    }

    public function testStartReturnsProfiler(): void
    {
        $profiler = ContextAwareProfiler::create()->forceEnable();

        $opProfiler = $profiler->start('test_operation');

        $this->assertInstanceOf(\MethorZ\Profiler\OperationProfiler::class, $opProfiler);
    }
}
