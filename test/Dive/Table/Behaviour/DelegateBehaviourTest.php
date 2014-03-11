<?php
/*
 * This file is part of the Dive ORM framework.
 * (c) Steffen Zeidler <sigma_z@sigma-scripts.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Dive\Test\Table\Behaviour;

require_once __DIR__ . '/../../../TestSuite/Record/Record.php';

use Dive\RecordManager;
use Dive\TestSuite\TestCase;

/**
 * Class DelegateBehaviourTest
 * @author  Steffen Zeidler <sigma_z@sigma-scripts.de>
 * @created 10.03.14
 */
class DelegateBehaviourTest extends TestCase
{

    /**
     * class table inheritance by delegate behaviour
     *          ----------
     *          | person |
     *          ----------
     *               |
     *         ------------
     *         | employee |
     *         ------------
     *          |        |
     * ------------    -----------
     * | engineer |    | manager |
     * ------------    -----------
     *
     * @var array
     */
    private static $schemaDefinition = array(
        'tables' => array(
            'person' => array(
                'fields' => array(
                    'id'    => array(
                        'primary'   => true,
                        'type'      => 'integer',
                        'length'    => 10,
                        'unsigned'  => true,
                        'autoIncrement' => true
                    ),
                    'firstname'  => array(
                        'type'      => 'string',
                        'length'    => 64,
                        'nullable'  => true
                    ),
                    'lastname'  => array(
                        'type'      => 'string',
                        'length'    => 64
                    )
                )
            ),
            'employee' => array(
                'fields' => array(
                    'id'    => array(
                        'primary'   => true,
                        'type'      => 'integer',
                        'length'    => 10,
                        'unsigned'  => true,
                        'autoIncrement' => true,
                        'foreign'   => 'person.id'
                    ),
                    'salary' => array(
                        'type'      => 'integer',
                        'length'    => 7,
                        'unsigned'  => true
                    )
                ),
                'behaviours' => array(
                    array(
                        'class' => 'DelegateBehaviour',
                        'config' => array(
                            'delegateToRelation' => 'Person'
                        ),
                        'instanceShared' => true
                    )
                )
            ),
            'engineer' => array(
                'fields' => array(
                    'id'    => array(
                        'primary'   => true,
                        'type'      => 'integer',
                        'length'    => 10,
                        'unsigned'  => true,
                        'autoIncrement' => true,
                        'foreign'   => 'employee.id'
                    ),
                    'special_qualification' => array(
                        'type'      => 'string',
                        'length'    => 255,
                        'nullable'  => true
                    )
                ),
                'behaviours' => array(
                    array(
                        'class' => 'DelegateBehaviour',
                        'config' => array(
                            'delegateToRelation' => 'Employee'
                        ),
                        'instanceShared' => true
                    )
                )
            ),
            'manager' => array(
                'fields' => array(
                    'id'    => array(
                        'primary'   => true,
                        'type'      => 'integer',
                        'length'    => 10,
                        'unsigned'  => true,
                        'autoIncrement' => true,
                        'foreign'   => 'employee.id'
                    ),
                    'position' => array(
                        'type'      => 'string',
                        'length'    => 255,
                        'nullable'  => true
                    )
                ),
                'behaviours' => array(
                    array(
                        'class' => 'DelegateBehaviour',
                        'config' => array(
                            'delegateToRelation' => 'Employee'
                        ),
                        'instanceShared' => true
                    )
                )
            )
        ),
        'relations' => array(
            'employee.id' => array(
                'owningAlias' => 'Employee',
                'owningField' => 'id',
                'owningTable' => 'employee',
                'refAlias' => 'Person',
                'refField' => 'id',
                'refTable' => 'person',
                'type' => '1-1',
                'onUpdate' => 'CASCADE',
                'onDelete' => 'CASCADE'
            ),
            'engineer.id' => array(
                'owningAlias' => 'Engineer',
                'owningField' => 'id',
                'owningTable' => 'engineer',
                'refAlias' => 'Employee',
                'refField' => 'id',
                'refTable' => 'employee',
                'type' => '1-1',
                'onUpdate' => 'CASCADE',
                'onDelete' => 'CASCADE'
            ),
            'manager.id' => array(
                'owningAlias' => 'Manager',
                'owningField' => 'id',
                'owningTable' => 'manager',
                'refAlias' => 'Employee',
                'refField' => 'id',
                'refTable' => 'employee',
                'type' => '1-1',
                'onUpdate' => 'CASCADE',
                'onDelete' => 'CASCADE'
            ),
        )
    );

    /** @var RecordManager */
    private $rm;


    public function setUp()
    {
        parent::setUp();
        $this->rm = self::createDefaultRecordManager(self::$schemaDefinition);
    }


    public function testSetDelegatedFieldsSingleInheritanceDepth()
    {
        $employee = $this->rm->getTable('employee')->createRecord();
        $employee->firstname = 'Otto';
        $employee->lastname = 'Normal';

        $this->assertEquals('Otto', $employee->Person->firstname);
        $this->assertEquals('Normal', $employee->Person->lastname);
    }


    public function testGetDelegatedFieldsSingleInheritanceDepth()
    {
        $employee = $this->rm->getTable('employee')->createRecord();
        $person = $this->rm->getTable('person')->createRecord(array('firstname' => 'Otto', 'lastname' => 'Normal'));
        $employee->Person = $person;

        $this->assertEquals('Otto', $employee->firstname);
        $this->assertEquals('Normal', $employee->lastname);
    }


    public function testSetDelegatedFieldsDeepInheritance()
    {
        $manager = $this->rm->getTable('manager')->createRecord();
        $manager->firstname = 'Otto';
        $manager->lastname = 'Normal';
        $manager->salary = '165000';

        $this->assertEquals('Otto', $manager->Employee->Person->firstname);
        $this->assertEquals('Normal', $manager->Employee->Person->lastname);
        $this->assertEquals('165000', $manager->Employee->salary);
    }


    public function testGetDelegatedFieldsDeepInheritance()
    {
        $manager = $this->rm->getTable('manager')->createRecord();
        $person = $this->rm->getTable('person')->createRecord(array('firstname' => 'Otto', 'lastname' => 'Normal'));
        $employee = $this->rm->getTable('employee')->createRecord(array('salary' => '150000'));
        $employee->Person = $person;
        $manager->Employee = $employee;

        $this->assertEquals('Otto', $manager->firstname);
        $this->assertEquals('Normal', $manager->lastname);
        $this->assertEquals('150000', $manager->salary);
    }


    public function testGetDelegatedFieldsOnNotInitializedParents()
    {
        $manager = $this->rm->getTable('manager')->createRecord();
        $this->assertFalse($manager->firstname);
        $this->assertFalse($manager->lastname);
        $this->assertFalse($manager->salary);
    }

}
