<?php
 /*
 * This file is part of the Dive ORM framework.
 * (c) Steffen Zeidler <sigma_z@sigma-scripts.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


namespace Dive\Test\Table\Behaviour;

use Dive\Table\Behaviour\Behaviour;

/**
 * Class BehaviourTest
 *
 * @author  Steffen Zeidler <sigma_z@sigma-scripts.de>
 */
class BehaviourTest extends \PHPUnit_Framework_TestCase
{

    /** @var Behaviour */
    private $behaviour;


    protected function setUp()
    {
        parent::setUp();

        /** @var Behaviour $behaviour */
        $behaviour = $this->getMockForAbstractClass('\Dive\Table\Behaviour\Behaviour');
        $behaviour->addTableEventFields('test_table', 'onSave', array('a', 'b'));
        $behaviour->addTableEventFields('test_table', 'onInsert', 'c');
        $this->behaviour = $behaviour;
    }


    public function testAddTableEventFields()
    {
        $this->behaviour->addTableEventFields('test_table', 'onUpdate', 'abc');
        $actual = self::readAttribute($this->behaviour, 'tableEventFields');
        $expected = array('test_table' => array(
            'onSave' => array('a', 'b'),
            'onInsert' => array('c'),
            'onUpdate' => array('abc')
        ));
        $this->assertEquals($expected, $actual);
    }


    public function testGetTableEventFields()
    {
        $this->assertEquals(array('a', 'b'), $this->behaviour->getTableEventFields('test_table', 'onSave'));
        $this->assertEquals(array('c'),      $this->behaviour->getTableEventFields('test_table', 'onInsert'));
    }


    public function testClearTableEventFieldsByEventName()
    {
        $this->behaviour->clearTableEventFields('test_table', 'onSave');

        $this->assertEmpty($this->behaviour->getTableEventFields('test_table', 'onSave'));
        $this->assertEquals(array('c'), $this->behaviour->getTableEventFields('test_table', 'onInsert'));
    }


    public function testClearTableEvents()
    {
        $this->behaviour->clearTableEventFields('test_table');

        $this->assertEmpty($this->behaviour->getTableEventFields('test_table', 'onSave'));
        $this->assertEmpty($this->behaviour->getTableEventFields('test_table', 'onInsert'));
    }

}
