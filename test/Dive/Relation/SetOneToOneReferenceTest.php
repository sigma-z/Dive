<?php
 /*
 * This file is part of the Dive ORM framework.
 * (c) Steffen Zeidler <sigma_z@sigma-scripts.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dive\Test\Relation;

use Dive\Record;
use Dive\TestSuite\Model\Author;
use Dive\TestSuite\Model\User;

require_once __DIR__ . '/RelationSetReferenceTestCase.php';

/**
 * @author  Steffen Zeidler <sigma_z@sigma-scripts.de>
 * @created 22.04.13
 */
class SetOneToOneReferenceTest extends RelationSetReferenceTestCase
{

    /**
     * @dataProvider provideOneToOne
     *
     * @param bool $userExists
     * @param bool $authorExists
     */
    public function testOneToOneReferencedSide($userExists, $authorExists)
    {
        /**
         * @var User   $user
         * @var Author $author
         */
        list($user, $author) = $this->createUserAndAuthor($userExists, $authorExists);

        // setting reference
        $user->Author = $author;

        // assertions
        $this->assertEquals($userExists, $user->exists());
        $this->assertEquals($authorExists, $author->exists());

        $this->assertEquals($author, $user->Author);
        $this->assertEquals($user, $user->Author->User);

        $this->assertRelationReferences($user, 'Author', $author);
    }


    /**
     * @dataProvider provideOneToOne
     *
     * @param bool $authorExists
     * @param bool $userExists
     */
    public function testOneToOneOwningSide($userExists, $authorExists)
    {
        list($user, $author) = $this->createUserAndAuthor($userExists, $authorExists);

        // setting reference
        $author->User = $user;

        // assertions
        $this->assertEquals($user, $author->User);
        $this->assertEquals($author, $author->User->Author);

        $this->assertRelationReferences($user, 'Author', $author);
    }


    public function testOneToOneReferencedSideViaField()
    {
        list($user, $author) = $this->createUserAndAuthor(true, true);

        // assertions
        $this->assertEquals($author, $user->Author);
        $this->assertEquals($user, $user->Author->User);

        $this->assertRelationReferences($user, 'Author', $author);
    }


    /**
     * @return array[]
     */
    public function provideOneToOne()
    {
        $testCases = array();

        // [userExists, authorExists]
        $testCases[] = array(false, false);
        //$testCases[] = array(false, true); // should not work, because author cannot be saved for non-existing user!!
        $testCases[] = array(true, false);
        $testCases[] = array(true, true);

        return $testCases;
    }


    public function testOneToOneReferencedSideNullReference()
    {
        $user = $this->createUser('UserOne');
        $this->assertNull($user->Author, 'Expected reference $user->Author to be NULL!');

        $author = $this->createAuthor('AuthorOne');
        $this->assertNull($author->User, 'Expected reference $author->User to be NULL!');

        $user->Author = $author;
        $this->assertEquals($author, $user->Author, 'Expected reference $user->Author to be the Author record!');
        $this->assertEquals($user, $author->User, 'Expected reference $author->User to be the User record!');

        // setting reference to NULL
        $user->Author = null;

        $expectedReferences = array($user->getInternalId() => null);
        $actualReferences = self::getRelationReferences($user->getTable()->getRelation('Author'));
        $this->assertEquals($expectedReferences, $actualReferences);

        $this->assertNull($user->Author, 'Expected reference $user->Author to be NULL!');
        $this->assertNull($author->User, 'Expected reference $author->User to be NULL!');
    }


    public function testOneToOneOwningSideNullReference()
    {
        $user = $this->createUser('UserOne');
        $this->assertNull($user->Author, 'Expected reference $user->Author to be NULL!');

        $author = $this->createAuthor('AuthorOne');
        $this->assertNull($author->User, 'Expected reference $author->User to be NULL!');

        $author->User = $user;
        $this->assertEquals($user, $author->User, 'Expected reference $author->User to be the User record!');
        $this->assertEquals($author, $user->Author, 'Expected reference $user->Author to be the Author record!');

        // setting reference to NULL
        $author->User = null;

        $expectedReferences = array($user->getInternalId() => null);
        $actualReferences = self::getRelationReferences($author->getTable()->getRelation('User'));
        $this->assertEquals($expectedReferences, $actualReferences);

        $this->assertNull($author->User, 'Expected reference $author->User to be NULL!');
        $this->assertNull($user->Author, 'Expected reference $user->Author to be NULL!');
    }


    /**
     */
    public function testOneToOneOwningSideSetForExistingRecords()
    {
        $userTable = $this->rm->getTable('user');
        $authorTable = $this->rm->getTable('author');
        $userOneId = self::insertDataset($userTable, array('username' => 'userOne', 'password' => 'my-secret'));
        $userTwoId = self::insertDataset($userTable, array('username' => 'userTwo', 'password' => 'my-secret'));
        $authorOneData = array('lastname' => 'authorOne', 'email' => '', 'user_id' => $userOneId);
        $authorTwoData = array('lastname' => 'authorTwo', 'email' => '', 'user_id' => $userTwoId);
        $authorOneId = self::insertDataset($authorTable, $authorOneData);
        $authorTwoId = self::insertDataset($authorTable, $authorTwoData);

        $relation = $authorTable->getRelation('User');
        $this->assertRelationReferenceMapIsEmpty($relation);

        $authors = $authorTable->createQuery()->fetchObjects();
        /** @var Author $authorOne */
        $authorOne = $authors->getById($authorOneId);
        /** @var Author $authorTwo */
        $authorTwo = $authors->getById($authorTwoId);

        // loading references for 'User' (for the the other author records, too)
        $authorOne->User;

        $this->assertInstanceOf('\Dive\Record', $authorOne->User);
        $this->assertInstanceOf('\Dive\Record', $authorTwo->User);

        $this->assertNotEquals($authorOne->User, $authorTwo->User);

        $this->assertNotNull($authorOne->User);
        $this->assertNotNull($authorTwo->User);

        $users = $userTable->createQuery()->fetchObjects();
        /** @var User $userOne */
        $userOne = $users->getById($userOneId);
        $this->assertRelationReference($authorOne, 'User', $userOne);
        /** @var User $userTwo */
        $userTwo = $users->getById($userTwoId);
        $this->assertRelationReference($authorTwo, 'User', $userTwo);

        // user of author two gets lost when setting it to user of author one
        $authorOne->User = $authorTwo->User;
        $this->assertNotNull($authorOne->User);
        $this->assertNull($authorTwo->User);
        $this->assertNotEquals($authorOne->User, $authorTwo->User);

        $userTwo = $users->getById($userTwoId);
        $this->assertRelationReference($authorOne, 'User', $userTwo);
    }


    public function testOneToOneOwningSideSetNonExistingRecordOnExistingRecord()
    {
        $userTable = $this->rm->getTable('user');
        $authorTable = $this->rm->getTable('author');
        $userOneId = self::insertDataset($userTable, array('username' => 'userOne', 'password' => 'my-secret'));
        $authorOneData = array('lastname' => 'authorOne', 'email' => '', 'user_id' => $userOneId);
        self::insertDataset($authorTable, $authorOneData);

        $users = $userTable->createQuery()->fetchObjects();
        $userOne = $users->getIterator()->current();

        $this->assertInstanceOf('\Dive\Record', $userOne->Author);

        /** @var Author $newAuthor */
        $newAuthor = $authorTable->createRecord();
        $userOne->Author = $newAuthor;

        $this->assertInstanceOf('\Dive\Record', $userOne->Author);
        $this->assertOwningFieldMapping($userOne, 'Author', $newAuthor);
        $this->assertRelationReference($userOne, 'Author', $newAuthor);
        $this->assertEquals($newAuthor, $userOne->Author);
    }


    /**
     */
    public function testOneToOneReferencingSideSetForExistingRecords()
    {
        $userTable = $this->rm->getTable('user');
        $authorTable = $this->rm->getTable('author');
        $userOneId = self::insertDataset($userTable, array('username' => 'userOne', 'password' => 'my-secret'));
        $userTwoId = self::insertDataset($userTable, array('username' => 'userTwo', 'password' => 'my-secret'));
        $authorOneData = array('lastname' => 'authorOne', 'email' => '', 'user_id' => $userOneId);
        $authorTwoData = array('lastname' => 'authorTwo', 'email' => '', 'user_id' => $userTwoId);
        $authorOneId = self::insertDataset($authorTable, $authorOneData);
        $authorTwoId = self::insertDataset($authorTable, $authorTwoData);

        $relation = $authorTable->getRelation('User');
        $this->assertRelationReferenceMapIsEmpty($relation);

        $users = $userTable->createQuery()->fetchObjects();
        /** @var User $userOne */
        $userOne = $users->getById($userOneId);
        /** @var User $userTwo */
        $userTwo = $users->getById($userTwoId);

        // loading references for 'Author' (for the whole collection)
        $authorOne = $userOne->Author;
        $authorTwo = $userTwo->Author;

        $references = self::getRelationReferences($relation);
        $expectedReferences = array(
            $userOneId => $authorOneId,
            $userTwoId => $authorTwoId
        );
        $this->assertEquals($expectedReferences, $references);

        $userOne->Author = $userTwo->Author;

        $references = self::getRelationReferences($relation);
        $expectedReferences = array(
            $userOneId => $authorTwoId,
            $userTwoId => null
        );
        $this->assertEquals($expectedReferences, $references);
        $this->assertEquals($authorTwo->get($relation->getOwningField()), $userOneId);
        $this->assertNull($authorOne->get($relation->getOwningField()));
    }


    public function testOneToOneReferencedSideSetNonExistingRecordOnExistingRecord()
    {
        $userTable = $this->rm->getTable('user');
        $authorTable = $this->rm->getTable('author');
        $userOneId = self::insertDataset($userTable, array('username' => 'userOne', 'password' => 'my-secret'));
        $authorOneData = array('lastname' => 'authorOne', 'email' => '', 'user_id' => $userOneId);
        self::insertDataset($authorTable, $authorOneData);

        $authors = $authorTable->createQuery()->fetchObjects();
        $authorOne = $authors->getIterator()->current();

        $this->assertInstanceOf('\Dive\Record', $authorOne->User);

        /** @var User $newUser */
        $newUser = $userTable->createRecord();
        $authorOne->User = $newUser;
        $this->assertNull($authorOne->user_id);

        $this->assertInstanceOf('\Dive\Record', $authorOne->User);
        $this->assertOwningFieldMapping($authorOne, 'User', $newUser);
        $this->assertRelationReference($authorOne, 'User', $newUser);
        $this->assertEquals($newUser, $authorOne->User);
    }


    /**
     * @param  bool $userExists
     * @param  bool $authorExists
     * @return Record[]
     */
    private function createUserAndAuthor($userExists, $authorExists)
    {
        $user = $this->createUser('UserOne');
        if ($userExists) {
            $this->rm->scheduleSave($user)->commit();
        }
        $author = $this->createAuthor('AuthorOne');
        if ($userExists) {
            $author->User = $user;
        }
        if ($authorExists) {
            $this->rm->scheduleSave($author)->commit();
        }
        return array($user, $author);
    }

}
