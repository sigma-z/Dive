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
use Dive\Record;
use Dive\TestSuite\Model\Author;

/**
 * @author  Steffen Zeidler <sigma_z@sigma-scripts.de>
 * @created 24.04.13
 *
 * TODO test set null reference!
 */
class SetOneToManyReferenceTest extends RelationSetReferenceTestCase
{

    /**
     * @dataProvider provideOneToMany
     *
     * @param bool $authorExists
     * @param bool $editorExists
     */
    public function testOneToManyReferencedSide($authorExists, $editorExists)
    {
        list($user, $userEditor) = $this->createAuthorEditorUsers($authorExists, $editorExists);
        /** @var Author $author */
        $author = $user->Author;
        /** @var Author $editor */
        $editor = $userEditor->Author;

        // setting reference
        $authorCollection = new RecordCollection($author->getTable());
        $authorCollection->add($author);
        $editor->Author = $authorCollection;

        // assertions
        $this->assertRelationReferences($editor, 'Author', $author);

        $this->assertEquals($authorCollection, $editor->Author);

        $user->Author = $author;
        $userEditor->Author = $editor;

        $this->assertEquals($user, $userEditor->Author->Author[$author->getInternalId()]->User);
    }


    /**
     * @dataProvider provideOneToMany
     *
     * @param bool $authorExists
     * @param bool $editorExists
     */
    public function testOneToManyOwningSide($authorExists, $editorExists)
    {
        list($user, $userEditor) = $this->createAuthorEditorUsers($authorExists, $editorExists);
        /** @var Author $author */
        $author = $user->Author;
        /** @var Author $editor */
        $editor = $userEditor->Author;

        // setting reference
        $author->Editor = $editor;

        // assertions
        $this->assertRelationReferences($editor, 'Author', $author);

        $this->assertEquals($editor, $author->Editor);  // fails when args: [true, true]
        $this->assertEquals($userEditor, $editor->User);

        $user->Author = $author;
        $userEditor->Author = $editor;
        $this->assertEquals($userEditor, $user->Author->Editor->User);
    }


    /**
     * @return array
     */
    public function provideOneToMany()
    {
        $testCases = array();

        // [authorExists, editorExists]
        $testCases[] = array(false, false);
        //$testCases[] = array(false, true); // will not work, because author cannot be saved for non-existing user
        $testCases[] = array(true, false);
        $testCases[] = array(true, true);

        return $testCases;
    }


    public function testOneToManyOwningSideSetForExistingRecords()
    {
        $editorOne = $this->createAuthorWithUser('EditorOne');
        $editorTwo = $this->createAuthorWithUser('EditorTwo');
        $editorTwoId = $editorTwo->id;

        $authorOne = $this->createAuthorWithUser('One');
        $this->assertNull($authorOne->Editor);
        $authorOne->Editor = $editorOne;
        $this->assertEquals($editorOne->id, $authorOne->Editor->id);
        $this->assertNull($authorOne->getModifiedFieldValue('editor_id'));

        $this->rm->save($authorOne)->commit();
        $this->assertRelationReferences($editorOne, 'Author', $authorOne);

        $authorOneId = $authorOne->id;

        $authorTwo = $this->createAuthorWithUser('Two');
        $authorTwo->Editor = $editorTwo;
        $this->rm->save($authorTwo)->commit();
        $this->assertRelationReferences($editorTwo, 'Author', $authorTwo);

        $this->rm->clearTables();

        $authorTable = $this->rm->getTable('author');
        $authors = $authorTable->createQuery()->fetchObjects();
        $authorOne = $authors[$authorOneId];
        $authorOne->Editor = $editorTwo;
        $this->assertRelationReferences($editorTwo, 'Author', $authorOne);

        $this->assertEquals($authorOne->editor_id, $editorTwoId);
    }


    public function testOneToManyReferencedSideSetForExistingRecords()
    {
        $editorOne = $this->createAuthorWithUser('EditorOne');
        $editorOneId = $editorOne->id;
        $editorTwo = $this->createAuthorWithUser('EditorTwo');
        $editorTwoId = $editorTwo->id;

        $this->assertNotEquals($editorOneId, $editorTwoId);

        $authorOne = $this->createAuthorWithUser('One');

        $this->assertNotEquals($authorOne->editor_id, $editorOne->id);
        $authorOne->Editor = $editorOne;

        $this->rm->save($authorOne)->commit();
        $this->assertRelationReferences($editorOne, 'Author', $authorOne);

        $this->assertNoRelationReferences($authorOne, 'Editor', array($authorOne, $editorTwo));

        $authorTwo = $this->createAuthorWithUser('Two');
        $authorTwo->Editor = $editorOne;
        $this->rm->save($authorTwo)->commit();
        $this->assertRelationReferences($editorOne, 'Author', array($authorOne, $authorTwo));

        $this->rm->clearTables();

        $authorTable = $this->rm->getTable('author');
        $authors = $authorTable->createQuery()->fetchObjects();
        $editorOne = $authors[$editorOneId];
        $editorTwo = $authors[$editorTwoId];

        $editorOne->Author[] = $editorTwo;
        $this->assertRelationReferences($editorOne, 'Author', array($authorOne, $authorTwo, $editorTwo));

        $this->assertEquals($editorTwo->editor_id, $editorOneId);
    }


    /**
     * @param  string $name
     *
     * @return Author
     */
    private function createAuthorWithUser($name)
    {
        $user = $this->createUser('User' . $name);
        $author  = $this->createAuthor('Author' . $name);
        $author->User = $user;
        $this->rm->save($author)->commit();
        return $author;
    }


    /**
     * @param bool $authorExists
     * @param bool $editorExists
     *
     * @return array
     */
    private function createAuthorEditorUsers($authorExists, $editorExists)
    {
        $user = $this->createUser('UserOne');
        $this->rm->save($user)->commit();
        $author = $this->createAuthor('Author');
        $user->Author = $author;
        $this->assertEquals($user->id, $author->user_id);

        $userEditor = $this->createUser('UserTwo');
        $this->rm->save($userEditor)->commit();
        $editor = $this->createAuthor('Editor');
        $userEditor->Author = $editor;
        $this->assertEquals($userEditor->id, $editor->user_id);

        if ($editorExists) {
            $this->rm->save($editor)->commit();
        }

        if ($authorExists) {
            $this->rm->save($author)->commit();
        }

        return array($user, $userEditor);
    }

}
