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
use Dive\TestSuite\Model\Article;
use Dive\TestSuite\Model\Author;
use Dive\TestSuite\Model\User;
use Dive\TestSuite\TestCase;

/**
 * @author  Steffen Zeidler <sigma_z@sigma-scripts.de>
 * @created 15.09.2014
 */
class RecordCollectionDoesNotUnlinkRecordOnChangingRecordIdentifierTest extends TestCase
{

    /** @var RecordManager */
    private $rm;

    /** @var Author */
    private $author;

    /** @var Article */
    private $recordCollectionRecord;


    public function testRecordCollectionDoesNotUnlinkRecordOnChangingRelatedRecordIdentifier()
    {
        $this->givenIHaveRecordManager();
        $this->givenIHaveAStoredRecordWithARelatedRecordCollection();

        $this->whenIChangeTheRecordIdentifierOfTheRelatedRecordInTheRecordCollection();

        $this->thenTheRecordCollectionRecordReferenceShouldNotHaveChanged();
        $this->thenTheOwningFieldMappingShouldMapTheReferencedRecord();
    }


    private function givenIHaveRecordManager()
    {
        $this->rm = self::createDefaultRecordManager();
    }


    private function givenIHaveAStoredRecordWithARelatedRecordCollection()
    {
        $user = $this->createUser();
        $author = $this->createAuthor();
        $article = $this->createArticle();

        $article->Author = $author;
        $author->User = $user;

        $this->rm->scheduleSave($author);
        $this->rm->commit();

        $this->author = $author;
        $this->recordCollectionRecord = $article;
    }


    /**
     * @return User
     */
    private function createUser()
    {
        $table = $this->rm->getTable('user');
        return self::getRecordWithRandomData($table, array('id' => '1', 'username' => 'Hugo'));
    }


    /**
     * @return Author
     */
    private function createAuthor()
    {
        $table = $this->rm->getTable('author');
        $recordData = array('id' => '1', 'lastname' => 'Smith', 'email' => 'smith@example.com');
        return self::getRecordWithRandomData($table, $recordData);
    }


    /**
     * @return Article
     */
    private function createArticle()
    {
        $table = $this->rm->getTable('article');
        $recordData = array(
            'id' => '1',
            'title' => 'Release announcement',
            'teaser' => 'Dive release with some brand new features',
            'text' => 'Dive into Dive'
        );
        return self::getRecordWithRandomData($table, $recordData);
    }


    private function whenIChangeTheRecordIdentifierOfTheRelatedRecordInTheRecordCollection()
    {
        $this->recordCollectionRecord->id = '345';
    }


    private function thenTheRecordCollectionRecordReferenceShouldNotHaveChanged()
    {
        $this->assertSame($this->recordCollectionRecord, $this->author->Article->first());
    }


    private function thenTheOwningFieldMappingShouldMapTheReferencedRecord()
    {
        $relation = $this->recordCollectionRecord->getTableRelation('Author');
        /** @var ReferenceMap $referenceMap */
        $referenceMap = self::readAttribute($relation, 'map');
        $owningOid = $this->recordCollectionRecord->getOid();
        $this->assertTrue($referenceMap->hasFieldMapping($owningOid));
        $this->assertEquals($this->author->getOid(), $referenceMap->getFieldMapping($owningOid));
    }

}
