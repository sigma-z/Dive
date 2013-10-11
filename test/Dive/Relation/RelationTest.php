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
use Dive\Relation\ReferenceMap;
use Dive\Relation\Relation;
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

        $mapping = $this->getReferenceMap($relation);
        $this->assertEmpty($mapping['references']);
        $this->assertEmpty($mapping['owningFieldOidMapping']);
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
            $user->Author = $author;
            $this->assertTrue($relation->hasReferenceFor($user, 'Author'), 'Reference to user->Author is not set!');
        }
        else {
            $author->User = $user;
            $this->assertTrue($relation->hasReferenceFor($author, 'User', 'Reference to author->User is not set!'));
        }

        $expected = array($user->getInternalId() => $author->getInternalId());
        $mapping = $this->getReferenceMap($relation);
        $this->assertEquals($expected, $mapping['references']);

        $expected = array($author->getOid() => $user->getOid());
        $this->assertEquals($expected, $mapping['owningFieldOidMapping']);
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

        $expected = array($editor->getInternalId() => array($author->getInternalId()));
        $mapping = $this->getReferenceMap($relation);
        $this->assertEquals($expected, $mapping['references']);

        $expected = array($author->getOid() => $editor->getOid());
        $this->assertEquals($expected, $mapping['owningFieldOidMapping']);
    }


    public function provideIsOwningSideFlag()
    {
        return array(array(true), array(false));
    }


    /**
     * @param  Relation $relation
     * @return array
     */
    private function getReferenceMap(Relation $relation)
    {
        $reflRelation = new \ReflectionClass($relation);
        $property = $reflRelation->getProperty('map');
        $property->setAccessible(true);
        /** @var ReferenceMap $map */
        $map = $property->getValue($relation);
        $relfReferenceMap = new \ReflectionClass($map);
        $property = $relfReferenceMap->getProperty('owningFieldOidMapping');
        $property->setAccessible(true);
        $mapping = array(
            'references' => $map->getMapping(),
            'owningFieldOidMapping' => $property->getValue($map)
        );
        return $mapping;
    }

}
