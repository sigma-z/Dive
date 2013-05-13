<?php
 /*
 * This file is part of the Dive ORM framework.
 * (c) Steffen Zeidler <sigma_z@sigma-scripts.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dive\Test\Relation;

require_once __DIR__ . '/AbstractRelationSetReferenceTestCase.php';

use Dive\Collection\RecordCollection;


/**
 * @author  Steffen Zeidler <sigma_z@sigma-scripts.de>
 * @created 13.05.13
 */
class SetOneToManyReferenceByRecordCollectionTest extends AbstractRelationSetReferenceTestCase
{

    public function testOneToManyReferencedSideNullReference()
    {
        $this->markTestIncomplete();
    }


    public function testOneToManyOwningSideNullReference()
    {
        $editor = $this->createAuthor('Editor');
        $authorOne = $this->createAuthor('AuthorOne');
        $authorTwo = $this->createAuthor('AuthorTwo');

        $message = 'Relation should be an instance of RecordCollection';
        $this->assertInstanceOf('\Dive\Collection\RecordCollection', $editor->Author, $message);
        $this->assertEquals(0, $editor->Author->count());

        $editor->Author[] = $authorOne;
        $editor->Author[] = $authorTwo;

        $this->assertEquals(2, $editor->Author->count());

        $this->markTestIncomplete('Keep record collection up-to-date by setting the owning side to NULL-Reference');
        $authorOne->Editor = null;
        $authorTwo->Editor = null;

        $this->assertEquals(0, $editor->Author->count());
    }


    public function testOneToManyReferencedSide()
    {
        $this->markTestIncomplete();
    }


    public function testOneToManyOwningSide()
    {
        $editor = $this->createAuthor('Editor');
        $authorOne = $this->createAuthor('AuthorOne');
        $authorTwo = $this->createAuthor('AuthorTwo');

        $message = 'Relation should be an instance of RecordCollection';
        $this->assertInstanceOf('\Dive\Collection\RecordCollection', $editor->Author, $message);
        $this->assertEquals(0, $editor->Author->count());

        $editor->Author[] = $authorOne;

        $this->assertEquals(1, $editor->Author->count());

        $authorTwo->Editor = $editor;

        $this->markTestIncomplete('Keep record collection up-to-date by setting the owning side reference');
        $this->assertEquals(2, $editor->Author->count());
    }

}