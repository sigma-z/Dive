<?php
/*
 * This file is part of the Dive ORM framework.
 * (c) Steffen Zeidler <sigma_z@sigma-scripts.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Dive\Test\Record;

use Dive\TestSuite\ChangeForCommitTestCase;
use Dive\TestSuite\TableRowsProvider;
use Dive\TestSuite\Model\Article;
use Dive\TestSuite\Model\Author;
use Dive\TestSuite\Model\User;

/**
 * @author  Steffen Zeidler <sigma_z@sigma-scripts.de>
 * @created 15.11.13
 */
class RecordOneToManyDeleteTest extends ChangeForCommitTestCase
{


    protected function setUp()
    {
        $this->tableRows = TableRowsProvider::provideTableRows();
        parent::setUp();
    }


    /**
     * @param string $side
     * @dataProvider provideRelationSides
     */
    public function testDeleteOnNonSavedRecordsOwningSide($side)
    {
        $this->markTestIncomplete('TODO implement!');
        $rm = $this->getRecordManagerWithOverWrittenConstraint(self::CONSTRAINT_TYPE_ON_DELETE, 'article.author_id');

        /** @var User $user */
        $user = $rm->getRecord('user', array());
        /** @var Author $author */
        $author = $rm->getRecord('author', array());
        /** @var Article $article */
        $article = $rm->getRecord('article', array());
        $author->Article[] = $article;
        $user->Author = $author;

        if ($side == self::RELATION_SIDE_REFERENCE) {
            $recordToDelete = $author;
        }
        else {
            $recordToDelete = $user;
        }
        $rm->delete($recordToDelete);
        $this->assertRecordIsNotScheduledForDelete($author);
        $this->assertRecordIsNotScheduledForDelete($user);
    }


    /**
     * @param string $side
     * @dataProvider provideRelationSides
     */
    public function testDeleteSetNullConstraintOwningSide($side)
    {
        $this->markTestIncomplete('TODO implement!');
    }


    /**
     * deleting the author, user stays untouched
     * both records have to be deleted
     */
    public function testDeleteCascadeConstraintReferencedSide()
    {
        $this->markTestIncomplete('TODO implement!');
    }


    public function testDeleteCascadeConstraintOwningSide()
    {
        $this->markTestIncomplete('TODO implement!');
    }


    /**
     * @param string $constraint
     * @dataProvider provideConstraints
     */
    public function testDeleteThrowsExceptionForScheduleSaveAfterDelete($constraint)
    {
        $this->markTestIncomplete('TODO implement!');
    }


    /**
     * @param string $side
     * @dataProvider provideRelationSides
     */
    public function testRecordWithModifiedReferenceOnReferenceSide($side = 'reference')
    {
        $this->markTestIncomplete('TODO implement!');
    }


    /**
     * @dataProvider provideConstraints
     */
    public function testRecordWithoutOwningSide($constraint)
    {
        $this->markTestIncomplete('TODO implement!');
    }

}