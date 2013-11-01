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
 * TODO refactor this class!
 */
class RecordOneToOneDeleteTest extends TestCase
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


    public function testDeleteOnNonSavedRecordsOwningSide()
    {
        $rm = $this->getRecordManagerWithOverWrittenConstraint();

        $user = $rm->getRecord('user', array());
        $author = $rm->getTable('author')->createRecord();
        /** @noinspection PhpUndefinedFieldInspection */
        $user->Author = $author;

        $rm->delete($author);
        $this->assertRecordIsNotScheduledForDelete($author);
        $this->assertRecordIsNotScheduledForDelete($user);
    }


    public function testDeleteOnNonSavedRecordsReferencedSide()
    {
        $rm = $this->getRecordManagerWithOverWrittenConstraint();

        $user = $rm->getTable('user')->createRecord();
        $author = $rm->getTable('author')->createRecord();
        /** @noinspection PhpUndefinedFieldInspection */
        $user->Author = $author;

        $rm->delete($user);
        $this->assertRecordIsNotScheduledForDelete($author);
        $this->assertRecordIsNotScheduledForDelete($user);
    }


    /**
     * deleting the author, user stays untouched
     */
    public function testOneToOneDeleteCascadeConstraintOwningSide()
    {
        $rm = $this->getRecordManagerWithOverWrittenConstraint(PlatformInterface::CASCADE);

        $user = $this->createUserWithAuthor($rm, 'JohnD');
        /** @noinspection PhpUndefinedFieldInspection */
        $author = $user->Author;

        $rm->delete($author);

        $this->assertRecordIsScheduledForDelete($author);
        $this->assertRecordIsNotScheduledForDelete($user);
    }


    /**
     * both records have to be deleted
     */
    public function testOneToOneDeleteCascadeConstraintReferencedSide()
    {
        $rm = $this->getRecordManagerWithOverWrittenConstraint(PlatformInterface::CASCADE);

        $user = $this->createUserWithAuthor($rm, 'JohnD');
        /** @noinspection PhpUndefinedFieldInspection */
        $author = $user->Author;

        $this->assertRecordIsNotScheduledForDelete($author);
        $this->assertRecordIsNotScheduledForDelete($user);
        $rm->delete($user);

        $this->assertFalse($author->isFieldModified('user_id'));
        $this->assertRecordIsScheduledForDelete($user);
        $this->assertRecordIsScheduledForDelete($author);

        $rm->delete($author);
        $this->assertRecordIsScheduledForDelete($author);
    }


    /**
     *
     */
    public function testOneToOneDeleteSetNullConstraintOwningSide()
    {
        $rm = $this->getRecordManagerWithOverWrittenConstraint(PlatformInterface::SET_NULL);

        $user = $this->createUserWithAuthor($rm, 'JohnD');
        /** @noinspection PhpUndefinedFieldInspection */
        $author = $user->Author;

        // does not touch user record
        $rm->delete($author);
        $this->assertRecordIsNotScheduledForDelete($user);
        $this->assertRecordIsScheduledForDelete($author);

        $rm->delete($user);
        $this->assertRecordIsScheduledForDelete($user);
        $this->assertRecordIsScheduledForDelete($author);
    }


    /**
     *
     */
    public function testOneToOneDeleteSetNullConstraintReferencedSide()
    {
        $rm = $this->getRecordManagerWithOverWrittenConstraint(PlatformInterface::SET_NULL);

        $user = $this->createUserWithAuthor($rm, 'JohnD');
        /** @noinspection PhpUndefinedFieldInspection */
        $author = $user->Author;

        $rm->delete($user);
        $this->assertRecordIsScheduledForDelete($user);
        $this->assertRecordIsScheduledForSave($author);
        $this->assertNull($author->user_id);

        $rm->delete($author);
        $this->assertRecordIsScheduledForDelete($user);
        $this->assertRecordIsScheduledForDelete($author);
    }


    /**
     * @dataProvider provideConstraints
     * @expectedException \Dive\UnitOfWork\UnitOfWorkException
     */
    public function testOneToOneDeleteThrowsExceptionForScheduleSave($constraint)
    {
        $rm = $this->getRecordManagerWithOverWrittenConstraint($constraint);
        $user = $this->createUserWithAuthor($rm, 'JohnD');
        /** @noinspection PhpUndefinedFieldInspection */
        $author = $user->Author;

        $rm->delete($author);
        $this->assertRecordIsScheduledForDelete($author);
        $rm->save($author);
    }


    /**
     * @dataProvider provideRestrictConstraints
     * @expectedException \Dive\UnitOfWork\UnitOfWorkException
     */
    public function testOneToOneDeleteThrowsRestrictException($constraint)
    {
        $rm = $this->getRecordManagerWithOverWrittenConstraint($constraint);
        $user = $this->createUserWithAuthor($rm, 'JohnD');
        /** @noinspection PhpUndefinedFieldInspection */
        $rm->delete($user);
    }


    public function testOneToOneRecordWithModifiedReferenceOnReferenceSide()
    {
        $rm = $this->getRecordManagerWithOverWrittenConstraint(PlatformInterface::CASCADE);

        $userJohn = $this->createUserWithAuthor($rm, 'JohnD');
        /** @noinspection PhpUndefinedFieldInspection */
        $authorJohn = $userJohn->Author;
        $userSally = $this->createUserWithAuthor($rm, 'SallyK');
        /** @noinspection PhpUndefinedFieldInspection */
        $authorSally = $userSally->Author;
        /** @noinspection PhpUndefinedFieldInspection */
        $userJohn->Author = $authorSally;

        $rm->delete($userJohn);

        $this->assertRecordIsScheduledForDelete($userJohn);

        $this->assertNull($authorJohn->get('user_id'));
        $this->assertRecordIsNotScheduledForDelete($authorJohn);

        $this->assertRecordIsNotScheduledForDelete($userSally);
        $this->assertRecordIsNotScheduledForDelete($authorSally);
    }


    public function testOneToOneRecordWithModifiedReferenceOnOwningSide()
    {
        $rm = $this->getRecordManagerWithOverWrittenConstraint(PlatformInterface::CASCADE);

        $userJohn = $this->createUserWithAuthor($rm, 'JohnD');
        /** @noinspection PhpUndefinedFieldInspection */
        $authorJohn = $userJohn->Author;
        $userSally = $this->createUserWithAuthor($rm, 'SallyK');
        /** @noinspection PhpUndefinedFieldInspection */
        $authorSally = $userSally->Author;

        $authorJohn->User = $userSally;

        $rm->delete($authorJohn);

        $this->assertRecordIsScheduledForDelete($authorJohn);
        $this->assertNull($authorSally->get('user_id'));

        $this->assertRecordIsNotScheduledForDelete($userJohn);

        $this->assertRecordIsNotScheduledForDelete($userSally);
        $this->assertRecordIsNotScheduledForDelete($authorSally);
    }


    /**
     * @dataProvider provideRestrictConstraints
     */
    public function testOneToOneRecordWithoutOwningSide($constraint)
    {
        $rm = $this->getRecordManagerWithOverWrittenConstraint($constraint);

        $userData = array('username' => 'JohnD', 'password' => 'secret');
        $user = $rm->getTable('user')->createRecord($userData);
        $rm->save($user);
        $rm->commit();

        $relation = $user->getTableRelation('Author');
        $this->assertFalse($relation->hasReferenceFor($user, 'Author'));

        $rm->delete($user);
        $this->assertRecordIsScheduledForDelete($user);
    }


    /**
     * @dataProvider provideRestrictConstraints
     */
    public function testOneToOneRecordRestrictConstraintOwningSide($constraint)
    {
        $rm = $this->getRecordManagerWithOverWrittenConstraint($constraint);
        $user = $this->createUserWithAuthor($rm, 'JohnD');
        /** @noinspection PhpUndefinedFieldInspection */
        $author = $user->Author;

        $rm->delete($author);
        $this->assertRecordIsScheduledForDelete($author);
        $this->assertRecordIsNotScheduledForDelete($user);
    }


    /**
     * @expectedException \Dive\UnitOfWork\UnitOfWorkException
     * @dataProvider provideRestrictConstraints
     */
    public function testOneToOneRecordRestrictConstraintReferencedSide($constraint)
    {
        $rm = $this->getRecordManagerWithOverWrittenConstraint($constraint);
        $user = $this->createUserWithAuthor($rm, 'JohnD');
        $rm->delete($user);
    }


    /**
     * @return array
     */
    public function provideConstraints()
    {
        return array(
            array(PlatformInterface::CASCADE),
            array(PlatformInterface::SET_NULL),
            array(PlatformInterface::NO_ACTION),
            array(PlatformInterface::RESTRICT)
        );
    }


    /**
     * @return array
     */
    public function provideRestrictConstraints()
    {
        return array(
            array(PlatformInterface::NO_ACTION),
            array(PlatformInterface::RESTRICT)
        );
    }


    /**
     * @param \Dive\RecordManager $rm
     * @param  string             $username
     * @return Record
     */
    private function createUserWithAuthor(RecordManager $rm,  $username)
    {
        $recordGenerator = $this->createRecordGenerator($rm);
        $recordGenerator
            ->setTablesMapField(array('user' => 'username'))
            ->setTablesRows($this->tableRows[$username])
            ->generate();

        $userId = $recordGenerator->getRecordIdFromMap('user', $username);
        $userTable = $rm->getTable('user');
        $user = $userTable->findByPk($userId);

        $this->assertInstanceOf('\Dive\Record', $user);
        /** @noinspection PhpUndefinedFieldInspection */
        $author = $user->Author;
        $this->assertInstanceOf('\Dive\Record', $author);

        $this->assertRecordIsNotScheduledForDelete($author);
        $this->assertRecordIsNotScheduledForDelete($user);
        $this->assertRecordIsNotScheduledForSave($author);
        $this->assertRecordIsNotScheduledForSave($user);

        return $user;
    }


    /**
     * @param  string $constraint
     * @return RecordManager
     */
    private function getRecordManagerWithOverWrittenConstraint($constraint = null)
    {
        $schemaDefinition = self::getSchemaDefinition();
        if ($constraint) {
            $schemaDefinition['relations']['author.user_id']['onDelete'] = $constraint;
        }
        $recordManager = self::createDefaultRecordManager($schemaDefinition);
        if ($constraint) {
            $userTable = $recordManager->getTable('user');
            $relation = $userTable->getRelation('Author');
            $this->assertEquals($constraint, $relation->getOnDelete());
        }
        return $recordManager;
    }


    /**
     * @param Record $record
     * @param string $message
     */
    private function assertRecordIsScheduledForDelete(Record $record, $message = '')
    {
        $rm = $record->getRecordManager();
        $tableName = $record->getTable()->getTableName();
        if (!$message) {
            $message = 'Record ' . $tableName . ' has to be scheduled for delete';
        }
        $this->assertTrue($rm->isRecordScheduledForDelete($record), $message);
    }


    /**
     * @param Record $record
     * @param string $message
     */
    private function assertRecordIsScheduledForSave(Record $record, $message = '')
    {
        $rm = $record->getRecordManager();
        $tableName = $record->getTable()->getTableName();
        if (!$message) {
            $message = 'Record ' . $tableName . ' has to be scheduled for save';
        }
        $this->assertTrue($rm->isRecordScheduledForSave($record), $message);
    }


    /**
     * @param Record $record
     * @param string $message
     */
    private function assertRecordIsNotScheduledForDelete(Record $record, $message = '')
    {
        $rm = $record->getRecordManager();
        $tableName = $record->getTable()->getTableName();
        if (!$message) {
            $message = 'Record ' . $tableName . ' has not to be scheduled for delete';
        }
        $this->assertFalse($rm->isRecordScheduledForDelete($record), $message);
    }


    /**
     * @param Record $record
     * @param string $message
     */
    private function assertRecordIsNotScheduledForSave(Record $record, $message = '')
    {
        $rm = $record->getRecordManager();
        $tableName = $record->getTable()->getTableName();
        if (!$message) {
            $message = 'Record ' . $tableName . ' has not to be scheduled for save';
        }
        $this->assertFalse($rm->isRecordScheduledForSave($record), $message);
    }

}
