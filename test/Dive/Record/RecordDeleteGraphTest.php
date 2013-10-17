<?php
/*
 * This file is part of the Dive ORM framework.
 * (c) Steffen Zeidler <sigma_z@sigma-scripts.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dive\Test\Record;

use Dive\Platform\PlatformInterface;
use Dive\RecordManager;
use Dive\TestSuite\TestCase;

/**
 * @author  Steffen Zeidler <sigma_z@sigma-scripts.de>
 * @created 30.08.13
 */
class RecordDeleteGraphTest extends TestCase
{

    private $tableRows = array(
        'user' => array('JohnD'),
        'author' => array(
            array(
                'firstname' => 'John',
                'lastname' => 'Doe',
                'email' => 'jdo@example.com',
                'User' => 'JohnD'
            ),
        )
    );

    /** @var RecordManager */
    private $rm = null;


    protected function setUp()
    {
        $this->markTestIncomplete("Missing constraint handling implementation!");
        parent::setUp();

        $this->rm = self::createDefaultRecordManager();
        $this->createRecords($this->rm, $this->tableRows, array('user' => 'username'));
    }


    private function changeDeleteConstraint($constraint)
    {
        $userTable = $this->rm->getTable('user');
        $relation = $userTable->getRelation('Author');
        $class = new \ReflectionClass($relation);
        $property = $class->getProperty('onDelete');
        $property->setAccessible(true);
        $property->setValue($class, $constraint);
    }


    /**
     * @dataProvider provide
     */
    public function testOneToOneRelationReferencedSide($constraint, $expected)
    {
        $this->changeDeleteConstraint($constraint);

        $userTable = $this->rm->getTable('user');
        $userJohn = $userTable->createQuery()
            ->where('username = ?', 'JohnD')
            ->fetchOneAsObject();
        $this->assertInstanceOf('\Dive\Record', $userJohn);
        $id = $userJohn->id;

        $this->rm->delete($userJohn);
        $this->assertEquals($userJohn, $userTable->findByPk($id));

        if (is_bool($expected)) {
            $this->rm->commit();
            $this->assertFalse($userTable->findByPk($id));
        }
        else {
            $this->setExpectedException($expected);
        }
    }


    /**
     * @dataProvider provide
     */
    public function testOneToOneRelationOwningSide($constraint, $expected)
    {
        $this->changeDeleteConstraint($constraint);

        $authorTable = $this->rm->getTable('author');
        $authorJohn = $authorTable->createQuery()
            ->where('email = ?', 'jdo@example.com')
            ->fetchOneAsObject();
        $this->assertInstanceOf('\Dive\Record', $authorJohn);
        $id = $authorJohn->id;

        $this->rm->delete($authorJohn);
        $this->assertEquals($authorJohn, $authorTable->findByPk($id));

        if (is_bool($expected)) {
            $this->rm->commit();
            $this->assertFalse($authorTable->findByPk($id));
        }
        else {
            $this->setExpectedException($expected);
        }
    }


    public function provide()
    {
        return array(
            array(PlatformInterface::CASCADE, true),
            array(PlatformInterface::SET_NULL, '\\Dive\\Exception'),
            array(PlatformInterface::RESTRICT, '\\Dive\\Exception'),
            array(PlatformInterface::NO_ACTION, '\\Dive\\Exception'),
            array(PlatformInterface::SET_DEFAULT, '\\Dive\\Exception')
        );
    }

}
