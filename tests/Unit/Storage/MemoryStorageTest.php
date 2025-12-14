<?php

declare(strict_types=1);

namespace MethorZ\Profiler\Tests\Unit\Storage;

use MethorZ\Profiler\Storage\MemoryStorage;
use PHPUnit\Framework\TestCase;

final class MemoryStorageTest extends TestCase
{
    public function testStoreAndRetrieve(): void
    {
        $storage = new MemoryStorage();
        $metrics = ['operation' => 'test', 'total' => 0.5];

        $storage->store('key1', $metrics);
        $retrieved = $storage->retrieve('key1');

        $this->assertEquals($metrics, $retrieved);
    }

    public function testRetrieveNonExistent(): void
    {
        $storage = new MemoryStorage();

        $this->assertNull($storage->retrieve('nonexistent'));
    }

    public function testRetrieveMultiple(): void
    {
        $storage = new MemoryStorage();
        $storage->store('key1', ['total' => 0.1]);
        $storage->store('key2', ['total' => 0.2]);
        $storage->store('key3', ['total' => 0.3]);

        $result = $storage->retrieveMultiple(['key1', 'key3', 'nonexistent']);

        $this->assertCount(2, $result);
        $this->assertArrayHasKey('key1', $result);
        $this->assertArrayHasKey('key3', $result);
        $this->assertArrayNotHasKey('nonexistent', $result);
    }

    public function testHas(): void
    {
        $storage = new MemoryStorage();
        $storage->store('key1', ['total' => 0.1]);

        $this->assertTrue($storage->has('key1'));
        $this->assertFalse($storage->has('key2'));
    }

    public function testDelete(): void
    {
        $storage = new MemoryStorage();
        $storage->store('key1', ['total' => 0.1]);

        $this->assertTrue($storage->has('key1'));

        $storage->delete('key1');
        $this->assertFalse($storage->has('key1'));
    }

    public function testClear(): void
    {
        $storage = new MemoryStorage();
        $storage->store('key1', ['total' => 0.1]);
        $storage->store('key2', ['total' => 0.2]);

        $storage->clear();

        $this->assertFalse($storage->has('key1'));
        $this->assertFalse($storage->has('key2'));
    }

    public function testAll(): void
    {
        $storage = new MemoryStorage();
        $storage->store('key1', ['total' => 0.1]);
        $storage->store('key2', ['total' => 0.2]);

        $all = $storage->all();

        $this->assertCount(2, $all);
        $this->assertArrayHasKey('key1', $all);
        $this->assertArrayHasKey('key2', $all);
    }
}
