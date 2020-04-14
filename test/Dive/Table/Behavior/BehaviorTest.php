<?php
 /*
 * This file is part of the Dive ORM framework.
 * (c) Steffen Zeidler <sigma_z@sigma-scripts.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


namespace Dive\Test\Table\Behavior;

use Dive\Table\Behavior\Behavior;
use PHPUnit\Framework\TestCase;

/**
 * Class BehaviorTest
 *
 * @author  Steffen Zeidler <sigma_z@sigma-scripts.de>
 */
class BehaviorTest extends TestCase
{

    /** @var Behavior */
    private $behavior;


    protected function setUp()
    {
        parent::setUp();

        /** @var Behavior $behavior */
        $behavior = $this->getMockForAbstractClass('\Dive\Table\Behavior\Behavior');
        $behaviorConfig = array(
            'onSave' => array('a', 'b'),
            'onInsert' => 'c'
        );
        $behavior->setTableConfig('test_table', $behaviorConfig);

        $this->behavior = $behavior;
    }


    public function testAddTableEventFields()
    {
        $behaviorConfig = $this->behavior->getTableConfig('test_table');
        $behaviorConfig['onUpdate'] = 'abc';
        $this->behavior->setTableConfigValue('test_table', 'onUpdate', 'abc');
        $actual = self::readAttribute($this->behavior, 'tableConfigs');
        $expected = array('test_table' => array(
            'onSave' => array('a', 'b'),
            'onInsert' => 'c',
            'onUpdate' => 'abc'
        ));
        $this->assertEquals($expected, $actual);
    }


    public function testGetTableEventFields()
    {
        $this->assertEquals(array('a', 'b'), $this->behavior->getTableConfigValue('test_table', 'onSave'));
        $this->assertEquals('c',             $this->behavior->getTableConfigValue('test_table', 'onInsert'));
    }


    public function testUnsetTableConfigValue()
    {
        $this->behavior->unsetTableConfigValue('test_table', 'onSave');

        $this->assertNull($this->behavior->getTableConfigValue('test_table', 'onSave'));
        $this->assertEquals('c', $this->behavior->getTableConfigValue('test_table', 'onInsert'));
    }


    public function testClearTableEvents()
    {
        $this->behavior->setTableConfig('test_table', array());

        $this->assertNull($this->behavior->getTableConfigValue('test_table', 'onSave'));
        $this->assertNull($this->behavior->getTableConfigValue('test_table', 'onInsert'));
    }

}
