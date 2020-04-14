<?php
/*
 * This file is part of the Dive ORM framework.
 * (c) Steffen Zeidler <sigma_z@sigma-scripts.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dive\Test\Collection;


use Dive\Collection\Collection;
use PHPUnit\Framework\TestCase;

/**
 * @author Steffen Zeidler <sigma_z@sigma-scripts.de>
 * Date: 11.02.13
 */
class CollectionTest extends TestCase
{


    /**
     * @var Collection
     */
    private $coll;


    protected function setUp()
    {
        parent::setUp();
        $coll = new Collection();
        $array = [
            'a' => ['name' => 'Item I'],
            'b' => ['name' => 'Item II'],
            'c' => ['name' => 'Item III']
        ];
        $coll->setItems($array);
        $this->coll = $coll;
    }


    public function testOffsetGetWithWithIntegerIndex()
    {
        $array = [
            ['name' => 'Item I'],
            ['name' => 'Item II'],
            ['name' => 'Item III']
        ];
        $this->coll->setItems($array);
        $this->assertSame('Item III', $this->coll[2]['name']);
    }


    public function testGetItems()
    {
        $array = [
            ['name' => 'Item I'],
            ['name' => 'Item II'],
            ['name' => 'Item III']
        ];
        $this->coll->setItems($array);
        $this->assertSame($array, $this->coll->getItems());
    }


    public function testOffsetGetWithStringIndex()
    {
        $this->assertSame('Item III', $this->coll['c']['name']);
    }


    public function testHasItem()
    {
        $this->assertTrue($this->coll->has('b'));
        $this->assertFalse($this->coll->has('d'));
    }


    public function testOffsetGetOnNoneExistingItem()
    {
        $this->assertNull($this->coll['d']);
    }


    public function testRemoveItem()
    {
        $this->coll->remove('b');
        $this->assertFalse($this->coll->has('b'));
    }


    public function testRemoveItemNotInCollection()
    {
        $this->assertFalse($this->coll->remove('d'));
    }


    public function testAddItem()
    {
        $item = ['name' => 'Item D'];
        $this->coll->add($item, 'd');
        $this->assertSame($item, $this->coll->get('d'));
    }


    public function testDoubleAddItem()
    {
        $item = ['name' => 'Item D'];
        $count = $this->coll->count();
        $this->coll->add($item, 'd');
        $count++;
        $this->assertSame($count, $this->coll->count());
        $this->coll->add($item, 'd');
        $this->assertSame($count, $this->coll->count());
        $this->assertSame($item, $this->coll->get('d'));
    }


    public function testCount()
    {
        $this->assertCount(3, $this->coll);
    }


    public function testKeys()
    {
        $this->assertSame(['a', 'b', 'c'], $this->coll->keys());
    }


    public function testGetIterator()
    {
        $this->assertInstanceOf(\Iterator::class, $this->coll->getIterator());
    }

}
