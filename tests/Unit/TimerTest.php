<?php

declare(strict_types=1);

namespace MethorZ\Profiler\Tests\Unit;

use MethorZ\Profiler\Exception\ProfilingException;
use MethorZ\Profiler\Timer;
use PHPUnit\Framework\TestCase;

final class TimerTest extends TestCase
{
    public function testStartCreatesTimer(): void
    {
        $timer = Timer::start();

        $this->assertInstanceOf(Timer::class, $timer);
        $this->assertFalse($timer->isEnded());
    }

    public function testElapsedReturnsTimeInSeconds(): void
    {
        $timer = Timer::start();
        usleep(10000); // 10ms

        $elapsed = $timer->elapsed();

        $this->assertGreaterThanOrEqual(0.01, $elapsed);
        $this->assertLessThan(0.1, $elapsed);
    }

    public function testCheckpointRecordsElapsedTime(): void
    {
        $timer = Timer::start();
        usleep(5000); // 5ms
        $timer->checkpoint('phase1');
        usleep(5000); // 5ms
        $timer->checkpoint('phase2');

        $result = $timer->end();

        $this->assertArrayHasKey('checkpoints', $result);
        $this->assertArrayHasKey('phase1', $result['checkpoints']);
        $this->assertArrayHasKey('phase2', $result['checkpoints']);
        $this->assertGreaterThan($result['checkpoints']['phase1'], $result['checkpoints']['phase2']);
    }

    public function testEndReturnsTimingData(): void
    {
        $timer = Timer::start();
        $timer->checkpoint('prep');
        $timer->checkpoint('exec');

        $result = $timer->end();

        $this->assertArrayHasKey('total', $result);
        $this->assertArrayHasKey('checkpoints', $result);
        $this->assertIsFloat($result['total']);
        $this->assertIsArray($result['checkpoints']);
    }

    public function testEndMarksTimerAsEnded(): void
    {
        $timer = Timer::start();
        $timer->end();

        $this->assertTrue($timer->isEnded());
    }

    public function testMultipleEndCallsReturnSameResult(): void
    {
        $timer = Timer::start();
        $result1 = $timer->end();
        $result2 = $timer->end();

        $this->assertEquals($result1['total'], $result2['total']);
    }

    public function testCheckpointAfterEndThrowsException(): void
    {
        $timer = Timer::start();
        $timer->end();

        $this->expectException(ProfilingException::class);
        $this->expectExceptionMessage('timer already ended');

        $timer->checkpoint('invalid');
    }

    public function testEmptyCheckpointNameThrowsException(): void
    {
        $timer = Timer::start();

        $this->expectException(ProfilingException::class);
        $this->expectExceptionMessage('empty checkpoint name');

        $timer->checkpoint('');
    }

    public function testElapsedAfterEndReturnsFixedValue(): void
    {
        $timer = Timer::start();
        $timer->end();
        $elapsed1 = $timer->elapsed();
        usleep(10000);
        $elapsed2 = $timer->elapsed();

        $this->assertEquals($elapsed1, $elapsed2);
    }
}
