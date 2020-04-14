<?php
 /*
 * This file is part of the Dive ORM framework.
 * (c) Steffen Zeidler <sigma_z@sigma-scripts.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dive\Test\Relation;

require_once __DIR__ . '/RelationSetReferenceTestCase.php';

use Dive\Collection\RecordCollection;
use Dive\TestSuite\Model\Author;

/**
 * @author  Steffen Zeidler <sigma_z@sigma-scripts.de>
 * @created 13.05.13
 */
class SetOneToManyReferenceByRecordCollectionTest extends RelationSetReferenceTestCase
{

    /**
     * @var Author
     */
    private $editor = null;
    /**
     * @var Author
     */
    private $authorOne = null;
    /**
     * @var Author
     */
    private $authorTwo = null;


    protected function setUp()
    {
        parent::setUp();
        $this->editor = $this->createAuthor('Editor');
        $this->authorOne = $this->createAuthor('AuthorOne');
        $this->authorTwo = $this->createAuthor('AuthorTwo');
    }


    public function testRecordCollectionAdd()
    {
        $message = 'Relation should be an instance of RecordCollection';
        $this->assertInstanceOf(RecordCollection::class, $this->editor->Author, $message);
        $this->assertEquals(0, $this->editor->Author->count());

        $this->editor->Author[] = $this->authorOne;
        $this->editor->Author[] = $this->authorTwo;
        $this->assertEquals(2, $this->editor->Author->count());

        $this->assertRelationReferences($this->editor, 'Author', array($this->authorOne, $this->authorTwo));
    }


    public function testRecordCollectionRemove()
    {
        $this->editor->Author[] = $this->authorOne;
        $this->editor->Author[] = $this->authorTwo;

        $this->editor->Author->unlinkRecord($this->authorOne);

        $this->assertNoRelationReferences($this->editor, 'Author', $this->authorOne);
        $this->assertRelationReferences($this->editor, 'Author', $this->authorTwo);
        $this->assertEquals(1, $this->editor->Author->count());
    }


    public function testOneToManyOwningSideNullReference()
    {
        $this->editor->Author[] = $this->authorOne;
        $this->editor->Author[] = $this->authorTwo;

        $this->assertEquals(2, $this->editor->Author->count());

        $this->authorOne->Editor = null;
        $this->authorTwo->Editor = null;

        $this->assertNoRelationReferences($this->editor, 'Author', array($this->authorOne, $this->authorTwo));
        $this->assertEquals(0, $this->editor->Author->count());
    }


    public function testOneToManyOwningSide()
    {
        $message = 'Relation should be an instance of RecordCollection';
        $this->assertInstanceOf(RecordCollection::class, $this->editor->Author, $message);
        $this->assertEquals(0, $this->editor->Author->count());

        $this->editor->Author[] = $this->authorOne;
        $this->assertRelationReferences($this->editor, 'Author', $this->authorOne);
        $this->assertEquals(1, $this->editor->Author->count());

        $this->authorTwo->Editor = $this->editor;
        $this->assertRelationReferences($this->editor, 'Author', array($this->authorOne, $this->authorTwo));
        $this->assertEquals(2, $this->editor->Author->count());
    }


    /**
     * @expectedException \Dive\Relation\RelationException
     */
    public function testOneToManyReferencedSideNullReferenceThrowsException()
    {
        $this->editor->Author[] = $this->authorOne;
        $this->editor->Author[] = $this->authorTwo;
        $this->editor->Author = null;
    }


    public function testOneToManySetByRecordCollection()
    {
        $authorTable = $this->rm->getTable('author');

        $this->assertEquals(0, $this->editor->Author->count());

        $collection = new RecordCollection($authorTable);
        $collection[] = $this->authorOne;
        $collection[] = $this->authorTwo;
        $this->editor->Author = $collection;

        $this->assertRelationReferences($this->editor, 'Author', array($this->authorOne, $this->authorTwo));
        $this->assertEquals(2, $this->editor->Author->count());
    }


    public function testOneToManySetEmptyByRecordCollection()
    {
        $authorTable = $this->rm->getTable('author');
        $this->editor->Author[] = $this->authorOne;
        $this->editor->Author[] = $this->authorTwo;
        $this->editor->Author = new RecordCollection($authorTable);

        $this->assertNoRelationReferences($this->editor, 'Author', array($this->authorOne, $this->authorTwo));
        $this->assertEquals(0, $this->editor->Author->count());
    }

}
