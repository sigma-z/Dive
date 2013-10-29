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
use Dive\Record;

/**
 * @author  Steffen Zeidler <sigma_z@sigma-scripts.de>
 * @created 30.08.13
 */
class RecordDeleteGraphTest extends TestCase
{

    private $tableRows = array(
        'JohnD' => array(
            'user' => array('JohnD'),
            'author' => array(
                'John' => array(
                    'firstname' => 'John',
                    'lastname' => 'Doe',
                    'email' => 'jdo@example.com',
                    'User' => 'JohnD'
                )
            )
        ),
        'SallyK' => array(
            'user' => array('SallyK'),
            'author' => array(
                'SallyK' => array(
                    'firstname' => 'Sally',
                    'lastname' => 'Kingston',
                    'email' => 'ski@example.com',
                    'User' => 'SallyK'
                )
            )
        )
    );

    /** @var RecordManager */
    private $rm = null;


    protected function setUp()
    {
        parent::setUp();

        $this->rm = self::createDefaultRecordManager();
    }


    /**
     * @param string $constraint
     */
    private function changeDeleteConstraint($constraint)
    {
        $userTable = $this->rm->getTable('user');
        $relation = $userTable->getRelation('Author');
        $class = new \ReflectionClass($relation);
        $property = $class->getProperty('onDelete');
        $property->setAccessible(true);
        $property->setValue($class, $constraint);

        $this->assertEquals($constraint, $relation->getOnDelete());
    }


    public function testDeleteOnNonSavedRecordsOwningSide()
    {
        $user = $this->rm->getRecord('user', array());
        $author = $this->rm->getTable('author')->createRecord();
        $user->Author = $author;

        $this->rm->delete($author);
        $this->assertFalse($this->rm->isRecordScheduledForDelete($author));
        $this->assertFalse($this->rm->isRecordScheduledForDelete($user));
    }


    public function testDeleteOnNonSavedRecordsReferencedSide()
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

        $user = $this->createUserWithAuthor('JohnD');

        $author = $user->Author;

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

        $user = $this->createUserWithAuthor('JohnD');
        $author = $user->Author;

        $this->assertFalse($this->rm->isRecordScheduledForDelete($author), 'author must not be scheduled for delete');
        $this->assertFalse($this->rm->isRecordScheduledForDelete($user), 'user must not be scheduled for delete');

        $relation = $user->getTableRelation('Author');

        $expectedOriginalIds = array($author->getInternalId());
        $originalIds = $relation->getOriginalReferencedIds($user, 'Author');
        $this->assertEquals($expectedOriginalIds, $originalIds);

        $this->rm->delete($user);

        $originalIds = $relation->getOriginalReferencedIds($user, 'Author');
        $this->assertEquals($expectedOriginalIds, $originalIds);

        $this->assertFalse($author->isFieldModified('user_id'));

        $this->assertTrue($this->rm->isRecordScheduledForDelete($user), 'user must be scheduled for delete');
        $this->assertTrue($this->rm->isRecordScheduledForDelete($author), 'author must be scheduled for delete');
    }


    public function testOneToOneRecordWithModifiedReferenceOnReferenceSide()
    {
        $this->changeDeleteConstraint(PlatformInterface::CASCADE);

        $userJohn = $this->createUserWithAuthor('JohnD');
        $authorJohn = $userJohn->Author;
        $userSally = $this->createUserWithAuthor('SallyK');
        $authorSally = $userSally->Author;

        $userJohn->Author = $authorSally;

        $this->rm->delete($userJohn);

        $this->assertTrue($this->rm->isRecordScheduledForDelete($userJohn));

        $this->assertNull($authorJohn->get('user_id'));
        $this->assertFalse($this->rm->isRecordScheduledForDelete($authorJohn));

        $this->assertFalse($this->rm->isRecordScheduledForDelete($userSally));
        $this->assertFalse($this->rm->isRecordScheduledForDelete($authorSally));
    }


    public function testOneToOneRecordWithModifiedReferenceOnOwningSide()
    {
        $this->changeDeleteConstraint(PlatformInterface::CASCADE);

        $userJohn = $this->createUserWithAuthor('JohnD');
        $authorJohn = $userJohn->Author;
        $userSally = $this->createUserWithAuthor('SallyK');
        $authorSally = $userSally->Author;

        $authorJohn->User = $userSally;

        $this->rm->delete($authorJohn);

        $this->assertTrue($this->rm->isRecordScheduledForDelete($authorJohn));
        $this->assertNull($authorSally->get('user_id'));

        $this->assertFalse($this->rm->isRecordScheduledForDelete($userJohn));

        $this->assertFalse($this->rm->isRecordScheduledForDelete($userSally));
        $this->assertFalse($this->rm->isRecordScheduledForDelete($authorSally));
    }


    /**
     * @param  string $username
     * @return Record
     */
    private function createUserWithAuthor($username)
    {
        $recordGenerator = $this->createRecordGenerator($this->rm);
        $recordGenerator
            ->setTablesMapField(array('user' => 'username'))
            ->setTablesRows($this->tableRows[$username])
            ->generate();

        $userId = $recordGenerator->getRecordIdFromMap('user', $username);
        $userTable = $this->rm->getTable('user');
        $user = $userTable->findByPk($userId);

        $this->assertInstanceOf('\Dive\Record', $user);
        $this->assertInstanceOf('\Dive\Record', $user->Author);

        return $user;
    }

}
