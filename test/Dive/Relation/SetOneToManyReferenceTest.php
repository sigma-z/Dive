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
 * @created 24.04.13
 * TODO test set null reference!
 */

namespace Dive\Test\Relation;

require_once __DIR__ . '/AbstractRelationSetReferenceTestCase.php';

use Dive\Collection\RecordCollection;


class SetOneToManyReferenceTest extends AbstractRelationSetReferenceTestCase
{

    /**
     * @dataProvider provideOneToOne
     *
     * @param bool $userExists
     * @param bool $authorExists
     */
    public function testOneToOneReferencedSide($userExists, $authorExists)
    {
        list($user, $author) = $this->createUserAndAuthor($userExists, $authorExists);

        // setting reference
        $user->Author = $author;

        // assertions
        $this->assertEquals($author, $user->Author);
        $this->assertEquals($user, $user->Author->User);
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
    }


    public function testOneToOneReferencedSideViaField()
    {
        list($user, $author) = $this->createUserAndAuthor(true, true);

        // setting reference
        $author->user_id = $user->id;

        // assertions
        $this->assertEquals($author, $user->Author);
        $this->assertEquals($user, $user->Author->User);
    }


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


    private function createUserAndAuthor($userExists, $authorExists)
    {
        $user = $this->createUser('UserOne');
        if ($userExists) {
            $user->save();
        }
        $author = $this->createAuthor('AuthorOne');
        if ($userExists) {
            // reference must be set, to save the author record
            $author->user_id = $user->id;
        }
        if ($authorExists) {
            $author->save();
        }
        return array($user, $author);
    }


    /**
     * @dataProvider provideOneToMany
     *
     * @param bool $authorExists
     * @param bool $editorExists
     */
    public function testOneToManyReferencedSide($authorExists, $editorExists)
    {
        list($user, $userEditor) = $this->createAuthorEditorUsers($authorExists, $editorExists);
        /** @var \Dive\Record $author */
        $author = $user->Author;
        /** @var \Dive\Record $editor */
        $editor = $userEditor->Author;

        // setting reference
        $authorCollection = new RecordCollection($author->getTable());
        $authorCollection->add($author);
        $editor->Author = $authorCollection;

        // assertions
        $editorReferences = $author->getTable()->getRelation('Editor')->getReferences();
        $expectedReferenced = array($editor->getInternalIdentifier() => array($author->getInternalIdentifier()));
        $this->assertEquals($expectedReferenced, $editorReferences);

        $this->assertEquals($authorCollection, $editor->Author);

        $user->Author = $author;
        $userEditor->Author = $editor;
        $this->assertEquals($user, $userEditor->Author->Author[$author->getInternalIdentifier()]->User);
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
        /** @var \Dive\Record $author */
        $author = $user->Author;
        /** @var \Dive\Record $editor */
        $editor = $userEditor->Author;

        // setting reference
        $author->Editor = $editor;

        // assertions
        $editorReferences = $author->getTable()->getRelation('Editor')->getReferences();
        $expectedReferenced = array($editor->getInternalIdentifier() => array($author->getInternalIdentifier()));
        $this->assertEquals($expectedReferenced, $editorReferences);

        $this->assertEquals($editor, $author->Editor);  // fails when args: [true, true]
        $this->assertEquals($userEditor, $editor->User);

        $user->Author = $author;
        $userEditor->Author = $editor;
        $this->assertEquals($userEditor, $user->Author->Editor->User);
    }


    public function provideOneToMany()
    {
        $testCases = array();

        // [authorExists, editorExists]
        $testCases[] = array(false, false);
        //$testCases[] = array(false, true); // TODO will not work, because author cannot be saved for non-existing user!!
        $testCases[] = array(true, false);
        $testCases[] = array(true, true);

        return $testCases;
    }


    private function createAuthorEditorUsers($authorExists, $editorExists)
    {
        $user = $this->createUser('UserOne');
        $user->save();
        $author = $this->createAuthor('Author');
        $user->Author = $author;
        $this->assertEquals($user->id, $author->user_id);

        $userEditor = $this->createUser('UserTwo');
        $userEditor->save();
        $editor = $this->createAuthor('Editor');
        $userEditor->Author = $editor;
        $this->assertEquals($userEditor->id, $editor->user_id);

        if ($editorExists) {
            $editor->save();
        }

        if ($authorExists) {
            $author->save();
        }

        return array($user, $userEditor);
    }

}