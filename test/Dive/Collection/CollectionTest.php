<?php
/*
 * This file is part of the Dive ORM framework.
 * (c) Steffen Zeidler <sigma_z@sigma-scripts.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
/**
 * @author Steffen Zeidler <sigma_z@sigma-scripts.de>
 * Date: 11.02.13
 */

namespace Dive\Test\Collection;


class CollectionTest extends \PHPUnit_Framework_TestCase
{


    /**
     * @var \Dive\Collection\Collection
     */
    private $coll;


    protected function setUp()
    {
        parent::setUp();
        /** @var \Dive\Collection\Collection $coll */
        $coll = $this->getMockForAbstractClass('\Dive\Collection\Collection');
        $array = array(
            'a' => array('name' => 'Item I'),
            'b' => array('name' => 'Item II'),
            'c' => array('name' => 'Item III')
        );
        $coll->setItems($array);
        $this->coll = $coll;
    }


    public function testOffsetGetWithWithIntegerIndex()
    {
        $array = array(
            array('name' => 'Item I'),
            array('name' => 'Item II'),
            array('name' => 'Item III')
        );

        $this->coll->setItems($array);
        $this->assertEquals('Item III', $this->coll[2]['name']);
    }


    public function testOffsetGetWithStringIndex()
    {
        $this->assertEquals('Item III', $this->coll['c']['name']);
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
        $item = array('name' => 'Item D');
        $this->coll->add($item, 'd');
        $this->assertEquals($item, $this->coll->get('d'));
    }


    public function testCount()
    {
        $this->assertEquals(3, $this->coll->count());
    }


    public function testKeys()
    {
        $this->assertEquals(array('a', 'b', 'c'), $this->coll->keys());
    }


    public function testGetIterator()
    {
        $this->assertInstanceOf('\Iterator', $this->coll->getIterator());
    }

}
