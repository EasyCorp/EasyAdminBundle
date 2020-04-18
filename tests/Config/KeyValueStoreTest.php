<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Config;

use EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore;
use PHPUnit\Framework\TestCase;

class KeyValueStoreTest extends TestCase
{
    public function testHas()
    {
        $store = KeyValueStore::new([
            'k1' => 'v1',
            'k2' => [
                'k1' => 'v1',
                'k2' => [
                    'k1' => 'v1',
                ],
            ],
        ]);

        $this->assertTrue($store->has('k1'));
        $this->assertTrue($store->has('k2.k1'));
        $this->assertTrue($store->has('k2.k2'));
        $this->assertTrue($store->has('k2.k2.k1'));

        $this->assertFalse($store->has('v1'));
        $this->assertFalse($store->has('k1.v1'));
        $this->assertFalse($store->has('k2.k2.v1'));
    }

    public function testIsEmpty()
    {
        $this->assertTrue(KeyValueStore::new()->isEmpty());
        $this->assertTrue(KeyValueStore::new([])->isEmpty());

        $this->assertFalse(KeyValueStore::new(['foo'])->isEmpty());
        $this->assertFalse(KeyValueStore::new([false])->isEmpty());
        $this->assertFalse(KeyValueStore::new([null])->isEmpty());
    }

    public function testAll()
    {
        $this->assertSame([], KeyValueStore::new()->all());
        $this->assertSame(['foo'], KeyValueStore::new(['foo'])->all());
        $this->assertSame(['foo' => 'bar'], KeyValueStore::new(['foo' => 'bar'])->all());
        $this->assertSame(['foo' => ['bar' => 'baz']], KeyValueStore::new(['foo' => ['bar' => 'baz']])->all());
        $this->assertSame([null, false, 0, ''], KeyValueStore::new([null, false, 0, ''])->all());
    }

    public function testGet()
    {
        $store = KeyValueStore::new([
            'k1' => 'v1',
            'k2' => [
                'k1' => 'v1',
                'k2' => [
                    'k1' => 'v1',
                ],
            ],
        ]);

        $this->assertSame('v1', $store->get('k1'));
        $this->assertSame('v1', $store->get('k1', 'default1'));
        $this->assertNull($store->get('k3'));
        $this->assertSame('default1', $store->get('k3', 'default1'));

        $this->assertSame('v1', $store->get('k2.k1'));
        $this->assertSame('v1', $store->get('k2.k1', 'default1'));
        $this->assertNull($store->get('k2.k3'));
        $this->assertSame('default1', $store->get('k2.k3', 'default1'));

        $this->assertSame('v1', $store->get('k2.k2.k1'));
        $this->assertSame('v1', $store->get('k2.k2.k1', 'default1'));
        $this->assertNull($store->get('k2.k2.k3'));
        $this->assertSame('default1', $store->get('k2.k2.k3', 'default1'));
    }

    public function testSet()
    {
        $store = KeyValueStore::new([
            'k1' => 'v1',
            'k2' => [
                'k1' => 'v1',
                'k2' => [
                    'k1' => 'v1',
                ],
            ],
        ]);

        $store->set('k1', '*v1');
        $this->assertSame('*v1', $store->get('k1'));

        $store->set('k2.k1', '*v1');
        $this->assertSame('*v1', $store->get('k2.k1'));

        $store->set('k2.k2.k1', '*v1');
        $this->assertSame('*v1', $store->get('k2.k2.k1'));

        $store->set('k3', 'v3');
        $this->assertSame('v3', $store->get('k3'));

        $store->set('k1', null);
        $this->assertNull($store->get('k1'));
    }

    public function testSetIfNotSet()
    {
        $store = KeyValueStore::new([
            'k1' => 'v1',
            'k2' => [
                'k1' => 'v1',
                'k2' => [
                    'k1' => 'v1',
                ],
            ],
        ]);

        $store->setIfNotSet('k1', '*v1');
        $this->assertSame('v1', $store->get('k1'));

        $store->setIfNotSet('k2.k1', '*v1');
        $this->assertSame('v1', $store->get('k2.k1'));

        $store->setIfNotSet('k2.k2.k1', '*v1');
        $this->assertSame('v1', $store->get('k2.k2.k1'));

        $store->setIfNotSet('k3', 'v3');
        $this->assertSame('v3', $store->get('k3'));

        $store->setIfNotSet('k1', null);
        $this->assertSame('v1', $store->get('k1'));
    }

    public function testSetAll()
    {
        $store = KeyValueStore::new([
            'k1' => 'v1',
            'k2' => [
                'k1' => 'v1',
                'k2' => [
                    'k1' => 'v1',
                ],
            ],
        ]);

        $store->setAll(['k1' => '*v1']);
        $this->assertSame('*v1', $store->get('k1'));

        $store->setAll(['k1' => '**v1', 'k2.k1' => '*v1']);
        $this->assertSame('**v1', $store->get('k1'));
        $this->assertSame('*v1', $store->get('k2.k1'));

        $store->setAll(['k1' => '***v1', 'k2.k1' => '**v1', 'k2.k2.k1' => '*v1']);
        $this->assertSame('***v1', $store->get('k1'));
        $this->assertSame('**v1', $store->get('k2.k1'));
        $this->assertSame('*v1', $store->get('k2.k2.k1'));

        $store->setAll(['k1' => '****v1', 'k2.k1' => '***v1', 'k2.k2.k1' => '**v1', 'k3.k1' => 'v1']);
        $this->assertSame('****v1', $store->get('k1'));
        $this->assertSame('***v1', $store->get('k2.k1'));
        $this->assertSame('**v1', $store->get('k2.k2.k1'));
        $this->assertSame('v1', $store->get('k3.k1'));

        $store->setAll(['k4' => 'v4', 'k4.k1' => 'v1']);
        $this->assertSame(['k1' => 'v1'], $store->get('k4'));
        $this->assertSame('v1', $store->get('k4.k1'));
    }

    public function testDelete()
    {
        $store = KeyValueStore::new([
            'k1' => 'v1',
            'k2' => [
                'k1' => 'v1',
                'k2' => [
                    'k1' => 'v1',
                ],
            ],
        ]);

        $store->delete('k3');
        $this->assertSame('v1', $store->get('k1'));

        $store->delete('k2.k3');
        $this->assertSame('v1', $store->get('k2.k1'));

        $store->delete('k1');
        $this->assertNull($store->get('k1'));

        $store->delete('k2.k2.k1');
        $this->assertNull($store->get('k2.k2.k1'));
        $this->assertSame([], $store->get('k2.k2'));
    }
}
