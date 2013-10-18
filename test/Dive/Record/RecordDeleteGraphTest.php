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
use Dive\Record\Generator\RecordGenerator;
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
            'John' => array(
                'firstname' => 'John',
                'lastname' => 'Doe',
                'email' => 'jdo@example.com',
                'User' => 'JohnD'
            ),
        )
    );

    /** @var RecordManager */
    private $rm = null;

    /** @var RecordGenerator */
    private $recordGenerator = null;


    protected function setUp()
    {
        parent::setUp();

        $this->rm = self::createDefaultRecordManager();
        $this->recordGenerator = $this->createRecords($this->rm, $this->tableRows, array('user' => 'username'));
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


    public function testDeleteOnNonExistingRecordsOwningSide()
    {
        $user = $this->rm->getTable('user')->createRecord();
        $author = $this->rm->getTable('author')->createRecord();
        $user->Author = $author;

        $this->rm->delete($author);
        $this->assertFalse($this->rm->isRecordScheduledForDelete($author));
        $this->assertFalse($this->rm->isRecordScheduledForDelete($user));
    }


    public function testDeleteOnNonExistingRecordsReferencedSide()
    {
        $user = $this->rm->getTable('user')->createRecord();
        $author = $this->rm->getTable('author')->createRecord();
        $user->Author = $author;

        $this->rm->delete($user);
        $this->assertFalse($this->rm->isRecordScheduledForDelete($author));
        $this->assertFalse($this->rm->isRecordScheduledForDelete($user));
    }


    /**
     * deleting the author, user stays untouched
     */
    public function testOneToOneDeleteCascadeConstraintOwningSide()
    {
        $this->changeDeleteConstraint(PlatformInterface::CASCADE);

        $author = $this->getAuthor();
        $user = $this->getUser();

        $this->rm->delete($author);

        $this->assertTrue($this->rm->isRecordScheduledForDelete($author));
        $this->assertFalse($this->rm->isRecordScheduledForDelete($user));
    }


    /**
     * both records have to be deleted
     */
    public function testOneToOneDeleteCascadeConstraintReferencedSide()
    {
        $this->changeDeleteConstraint(PlatformInterface::CASCADE);

        $author = $this->getAuthor();
        $user = $this->getUser();

        $this->rm->delete($user);

        $this->assertTrue($this->rm->isRecordScheduledForDelete($author));
        $this->assertTrue($this->rm->isRecordScheduledForDelete($user));
    }


    /**
     * @return \Dive\Record
     */
    private function getUser()
    {
        $id = $this->recordGenerator->getRecordIdFromMap('user', 'JohnD');
        $userTable = $this->rm->getTable('user');
        $user = $userTable->findByPk($id);
        $this->assertInstanceOf('\Dive\Record', $user);
        return $user;
    }


    /**
     * @return \Dive\Record
     */
    private function getAuthor()
    {
        $id = $this->recordGenerator->getRecordIdFromMap('author', 'John');
        $authorTable = $this->rm->getTable('author');
        $author = $authorTable->findByPk($id);
        $this->assertInstanceOf('\Dive\Record', $author);
        return $author;
    }

}
