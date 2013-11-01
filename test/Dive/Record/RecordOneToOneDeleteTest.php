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
use Dive\TestSuite\ChangeForCommitTestCase;
use Dive\Record;

/**
 * @author  Steffen Zeidler <sigma_z@sigma-scripts.de>
 * @created 30.08.13
 * TODO refactor this class!
 */
class RecordOneToOneDeleteTest extends ChangeForCommitTestCase
{

    /**
     * @var array
     */
    protected $tableRows = array(
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
        $rm = $this->getRecordManagerWithOverWrittenConstraint(self::CONSTRAINT_TYPE_ON_DELETE);

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
        $rm = $this->getRecordManagerWithOverWrittenConstraint(self::CONSTRAINT_TYPE_ON_DELETE);

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
    public function testDeleteCascadeConstraintOwningSide()
    {
        $rm = $this->getRecordManagerWithOverWrittenConstraint(self::CONSTRAINT_TYPE_ON_DELETE, PlatformInterface::CASCADE);

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
    public function testDeleteCascadeConstraintReferencedSide()
    {
        $rm = $this->getRecordManagerWithOverWrittenConstraint(self::CONSTRAINT_TYPE_ON_DELETE, PlatformInterface::CASCADE);

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
    public function testDeleteSetNullConstraintOwningSide()
    {
        $rm = $this->getRecordManagerWithOverWrittenConstraint(self::CONSTRAINT_TYPE_ON_DELETE, PlatformInterface::SET_NULL);

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
    public function testDeleteSetNullConstraintReferencedSide()
    {
        $rm = $this->getRecordManagerWithOverWrittenConstraint(self::CONSTRAINT_TYPE_ON_DELETE, PlatformInterface::SET_NULL);

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
    public function testDeleteThrowsExceptionForScheduleSave($constraint)
    {
        $rm = $this->getRecordManagerWithOverWrittenConstraint(self::CONSTRAINT_TYPE_ON_DELETE, $constraint);
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
    public function testDeleteThrowsRestrictException($constraint)
    {
        $rm = $this->getRecordManagerWithOverWrittenConstraint(self::CONSTRAINT_TYPE_ON_DELETE, $constraint);
        $user = $this->createUserWithAuthor($rm, 'JohnD');
        /** @noinspection PhpUndefinedFieldInspection */
        $rm->delete($user);
    }


    public function testRecordWithModifiedReferenceOnReferenceSide()
    {
        $rm = $this->getRecordManagerWithOverWrittenConstraint(self::CONSTRAINT_TYPE_ON_DELETE, PlatformInterface::CASCADE);

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


    public function testRecordWithModifiedReferenceOnOwningSide()
    {
        $rm = $this->getRecordManagerWithOverWrittenConstraint(self::CONSTRAINT_TYPE_ON_DELETE, PlatformInterface::CASCADE);

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
    public function testRecordWithoutOwningSide($constraint)
    {
        $rm = $this->getRecordManagerWithOverWrittenConstraint(self::CONSTRAINT_TYPE_ON_DELETE, $constraint);

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
    public function testRecordRestrictConstraintOwningSide($constraint)
    {
        $rm = $this->getRecordManagerWithOverWrittenConstraint(self::CONSTRAINT_TYPE_ON_DELETE, $constraint);
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
    public function testRecordRestrictConstraintReferencedSide($constraint)
    {
        $rm = $this->getRecordManagerWithOverWrittenConstraint(self::CONSTRAINT_TYPE_ON_DELETE, $constraint);
        $user = $this->createUserWithAuthor($rm, 'JohnD');
        $rm->delete($user);
    }

}
