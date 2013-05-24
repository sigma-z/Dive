<?php
 /*
 * This file is part of the Dive ORM framework.
 * (c) Steffen Zeidler <sigma_z@sigma-scripts.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
/**
 * @author  Steffen Zeidler <sigma_z@sigma-scripts.de>
 * @created 23.05.13
 */


namespace Dive\Test\Relation;

require_once __DIR__ . '/AbstractRelationSetReferenceTestCase.php';
use Dive\Collection\RecordCollection;
use Dive\Record;
use Dive\TestSuite\TestCase;

class UnlinkReferenceTest extends AbstractRelationSetReferenceTestCase
{


    public function testUnlinkOwningSideOneToOne()
    {
        $author = $this->createAuthor('Author');
        $user = $this->createUser('User');
        $user->Author = $author;
        $relation = $user->getTable()->getRelation('Author');

        // assert test data setup was correct
        $this->assertEquals($author, $user->Author);
        $expectedReferences = array($user->getIntId() => $author->getIntId());
        $references = $relation->getReferences();
        $this->assertEquals($expectedReferences, $references);

        // perform test
        $author->User = null;

        // assert unlink of reference
        $this->assertNull($user->Author);
        $references = $relation->getReferences();
        $expectedReferences = array($user->getIntId() => null);
        $this->assertEquals($expectedReferences, $references);
    }


    public function testUnlinkReferencedSideOneToOne()
    {
        $author = $this->createAuthor('Author');
        $user = $this->createUser('User');
        $author->User = $user;
        $relation = $author->getTable()->getRelation('User');

        // assert test data setup was correct
        $this->assertEquals($user, $author->User);
        $expectedReferences = array($user->getIntId() => $author->getIntId());
        $references = $relation->getReferences();
        $this->assertEquals($expectedReferences, $references);

        // perform test
        $user->Author = null;

        // assert unlink of reference
        $this->assertNull($user->Author);
        $references = $relation->getReferences();
        $expectedReferences = array($user->getIntId() => null);
        $this->assertEquals($expectedReferences, $references);
    }


    public function testUnlinkOwningSideOneToMany()
    {
        $authorOne = $this->createAuthor('Author One');
        $authorTwo = $this->createAuthor('Author Two');
        $editor = $this->createAuthor('Editor');
        $relation = $authorOne->getTable()->getRelation('Editor');

        $authorOne->Editor = $editor;
        $authorTwo->Editor = $editor;

        // assert test data setup was correct
        $this->assertEquals($editor, $authorOne->Editor);
        $this->assertEquals($editor, $authorTwo->Editor);
        $expectedReferences = array($editor->getIntId() => array($authorOne->getIntId(), $authorTwo->getIntId()));
        $references = $relation->getReferences();
        $this->assertEquals($expectedReferences, $references);

        // perform test
        $authorOne->Editor = null;

        // assert unlink of reference
        $this->assertNull($authorOne->Editor);
        $this->assertNotNull($authorTwo->Editor);
        $references = $relation->getReferences();
        $expectedReferences = array($editor->getIntId() => array($authorTwo->getIntId()));
        $this->assertEquals($expectedReferences, $references);
    }


    public function testUnlinkReferencedSideOneToMany()
    {
        $authorOne = $this->createAuthor('Author One');
        $authorTwo = $this->createAuthor('Author Two');
        $editor = $this->createAuthor('Editor');

        $editor->Author[] = $authorOne;
        $editor->Author[] = $authorTwo;
        $table = $authorOne->getTable();
        $relation = $table->getRelation('Editor');

        // assert test data setup was correct
        $this->assertInstanceOf('\Dive\Collection\RecordCollection', $editor->Author);
        $expectedReferences = array($editor->getIntId() => array($authorOne->getIntId(), $authorTwo->getIntId()));
        /** @noinspection PhpUndefinedMethodInspection */
        $this->assertEquals($expectedReferences[$editor->getIntId()], $editor->Author->getIdentifiers());
        $references = $relation->getReferences();
        $this->assertEquals($expectedReferences, $references);

        // perform test
        $editor->Author = new RecordCollection($table);

        // assert unlink of reference
        $this->assertNull($authorOne->Editor);
        $this->assertNull($authorTwo->Editor);
        $references = $relation->getReferences();
        $expectedReferences = array($editor->getIntId() => array());
        $this->assertEquals($expectedReferences, $references);
    }

}