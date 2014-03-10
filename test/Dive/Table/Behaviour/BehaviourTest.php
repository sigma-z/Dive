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
        $behaviourConfig = array(
            'onSave' => array('a', 'b'),
            'onInsert' => 'c'
        );
        $behaviour->setTableConfig('test_table', $behaviourConfig);

        $this->behaviour = $behaviour;
    }


    public function testAddTableEventFields()
    {
        $behaviourConfig = $this->behaviour->getTableConfig('test_table');
        $behaviourConfig['onUpdate'] = 'abc';
        $this->behaviour->setTableConfigValue('test_table', 'onUpdate', 'abc');
        $actual = self::readAttribute($this->behaviour, 'tableConfigs');
        $expected = array('test_table' => array(
            'onSave' => array('a', 'b'),
            'onInsert' => 'c',
            'onUpdate' => 'abc'
        ));
        $this->assertEquals($expected, $actual);
    }


    public function testGetTableEventFields()
    {
        $this->assertEquals(array('a', 'b'), $this->behaviour->getTableConfigValue('test_table', 'onSave'));
        $this->assertEquals('c',             $this->behaviour->getTableConfigValue('test_table', 'onInsert'));
    }


    public function testUnsetTableConfigValue()
    {
        $this->behaviour->unsetTableConfigValue('test_table', 'onSave');

        $this->assertNull($this->behaviour->getTableConfigValue('test_table', 'onSave'));
        $this->assertEquals('c', $this->behaviour->getTableConfigValue('test_table', 'onInsert'));
    }


    public function testClearTableEvents()
    {
        $this->behaviour->setTableConfig('test_table', array());

        $this->assertNull($this->behaviour->getTableConfigValue('test_table', 'onSave'));
        $this->assertNull($this->behaviour->getTableConfigValue('test_table', 'onInsert'));
    }

}
