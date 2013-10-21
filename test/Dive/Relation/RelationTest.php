<?php
/*
 * This file is part of the Dive ORM framework.
 * (c) Steffen Zeidler <sigma_z@sigma-scripts.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dive\Test\Relation;

use Dive\RecordManager;
use Dive\TestSuite\TestCase;

/**
 * @author Steffen Zeidler <sigma_z@sigma-scripts.de>
 * Date: 30.10.12
 */
class RelationTest extends TestCase
{

    /** @var RecordManager */
    private $rm = null;


    protected function setUp()
    {
        parent::setUp();

        $this->rm = self::createDefaultRecordManager();
    }


    public function testGetJoinTable()
    {
        $user = $this->rm->getTable('user');
        $relation = $user->getRelation('Author');

        $this->assertEquals('author', $relation->getJoinTableName('Author'));
        $this->assertEquals('user', $relation->getJoinTableName('User'));
    }


    public function testIsOwningSide()
    {
        $user = $this->rm->getTable('user');
        $relation = $user->getRelation('Author');

        $this->assertTrue($relation->isOwningSide('Author'));
        $this->assertFalse($relation->isOwningSide('User'));
    }


    public function testIsReferencedSide()
    {
        $user = $this->rm->getTable('user');
        $relation = $user->getRelation('Author');

        $this->assertFalse($relation->isReferencedSide('Author'));
        $this->assertTrue($relation->isReferencedSide('User'));
    }


    public function testRelationInstanceIsTheSameOnBothSides()
    {
        $userTable = $this->rm->getTable('user');
        $authorTable = $this->rm->getTable('author');
        $this->assertSame($userTable->getRelation('Author'), $authorTable->getRelation('User'));
    }


    public function testReferenceMapIsEmpty()
    {
        $userTable = $this->rm->getTable('user');
        $relation = $userTable->getRelation('Author');
        $this->assertRelationReferenceMapIsEmpty($relation);
    }


    /**
     * @dataProvider provideIsOwningSideFlag
     */
    public function testReferenceMapOneToOneSet($isOwningSide)
    {
        $userTable = $this->rm->getTable('user');
        $authorTable = $this->rm->getTable('author');
        $user = $userTable->createRecord();
        $author = $authorTable->createRecord();
        $relation = $userTable->getRelation('Author');

        if ($isOwningSide) {
            $referenceMessage = 'Reference to user->Author';
            $this->assertFalse($relation->hasReferenceFor($user, 'Author'), $referenceMessage . ' is not set, yet!');
            $user->Author = $author;
            $this->assertTrue($relation->hasReferenceFor($user, 'Author'), $referenceMessage . ' should be set!');
        }
        else {
            $referenceMessage = 'Reference to author->User';
            $this->assertFalse($relation->hasReferenceFor($author, 'User'), $referenceMessage . ' is not set, yet!');
            $author->User = $user;
            $this->assertTrue($relation->hasReferenceFor($author, 'User'), $referenceMessage . ' should be set!');
        }

        $this->assertRelationReferences($user, 'Author', $author);
    }


    /**
     * @dataProvider provideIsOwningSideFlag
     */
    public function testReferenceMapOneToManySet($isOwningSide)
    {
        $authorTable = $this->rm->getTable('author');
        $author = $authorTable->createRecord();
        $editor = $authorTable->createRecord();
        $relation = $authorTable->getRelation('Author');

        if ($isOwningSide) {
            $author->Editor = $editor;
            $this->assertTrue($relation->hasReferenceFor($author, 'Editor'), 'Reference to author->Editor is not set!');
        }
        else {
            $this->assertInstanceOf('\Dive\Collection\RecordCollection', $editor->Author);
            $editor->Author[] = $author;
            $this->assertTrue($relation->hasReferenceFor($editor, 'Author'), 'Reference to editor->Author is not set!');
        }

        $this->assertRelationReferences($editor, 'Author', $author);
    }


    public function provideIsOwningSideFlag()
    {
        return array(array(true), array(false));
    }

}
