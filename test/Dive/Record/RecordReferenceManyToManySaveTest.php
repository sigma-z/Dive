<?php
/*
 * This file is part of the Dive ORM framework.
 * (c) Steffen Zeidler <sigma_z@sigma-scripts.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dive\Test\Record;

use Dive\Record\Generator\RecordGenerator;
use Dive\RecordManager;
use Dive\TestSuite\Model\Article;
use Dive\TestSuite\Model\Tag;
use Dive\TestSuite\TestCase;

/**
 * @author Steffen Zeidler <sigma_z@sigma-scripts.de>
 * @date   09.08.2017
 */
class RecordReferenceManyToManySaveTest extends TestCase
{

    /** @var RecordManager */
    private $rm;

    /** @var RecordGenerator */
    private $recordGenerator;


    public function testSaveReferenceForNewSavedRecords()
    {
        $this->givenIHaveARecordManager();
        $article = $this->givenIHaveACreatedASavedArticle('Post #1');
        $tag = $this->givenIHaveACreatedASavedTag('Tag #1');

        $this->whenISaveANewAssociationArticle2TagRecord($article, $tag);

        $this->thenArticle_andTag_areRelated($article, $tag);
    }


    public function testSaveReferenceForStoredRecords()
    {
        $this->givenIHaveARecordManager();
        $article = $this->givenIHaveAStoredArticle('Post #1');
        $tag = $this->givenIHaveACreatedAStoredTag('Tag #1');

        $this->whenISaveANewAssociationArticle2TagRecord($article, $tag);

        $this->thenArticle_andTag_areRelated($article, $tag);
    }


    public function testSaveReferenceForANewSavedRecordAndAStoredRecord()
    {
        $this->givenIHaveARecordManager();
        $article = $this->givenIHaveAStoredArticle('Post #1');
        $tag = $this->givenIHaveACreatedASavedTag('Tag #1');

        $this->whenISaveANewAssociationArticle2TagRecord($article, $tag);

        $this->thenArticle_andTag_areRelated($article, $tag);
    }


    // #####  given / when / then methods  #####
    private function givenIHaveARecordManager()
    {
        $this->rm = self::createDefaultRecordManager();
    }


    private function initRecordGenerator()
    {
        if (!$this->recordGenerator) {
            $recordManager = self::createDefaultRecordManager();
            $this->recordGenerator = self::createRecordGenerator($recordManager);
        }
    }


    /**
     * @param string $articleTitle
     * @return Article
     */
    private function givenIHaveAStoredArticle($articleTitle)
    {
        $this->initRecordGenerator();
        $id = $this->recordGenerator->generateRecord('article', [['title' => $articleTitle]]);
        return $this->rm->getTable('article')->findByPk($id);
    }


    /**
     * @param string $articleTitle
     * @return Article
     */
    private function givenIHaveACreatedASavedArticle($articleTitle)
    {
        $recordGenerator = self::createRecordGenerator($this->rm);
        $id = $recordGenerator->generateRecord('article', [['title' => $articleTitle]]);
        return $this->rm->getTable('article')->findByPk($id);
    }


    /**
     * @param string $tagName
     * @return Tag
     */
    private function givenIHaveACreatedAStoredTag($tagName)
    {
        $this->initRecordGenerator();
        $id = $this->recordGenerator->generateRecord('tag', [['name' => $tagName]]);
        return $this->rm->getTable('tag')->findByPk($id);
    }


    /**
     * @param string $tagName
     * @return Tag
     */
    private function givenIHaveACreatedASavedTag($tagName)
    {
        $tag = $this->rm->getTable('tag')->createRecord(['name' => $tagName]);
        $this->rm->scheduleSave($tag)->commit();
        return $tag;
    }


    /**
     * @param Article $article
     * @param Tag     $tag
     */
    private function whenISaveANewAssociationArticle2TagRecord(Article $article, Tag $tag)
    {
        $recordData = [
            'article_id' => $article->getIdentifier(),
            'tag_id' => $tag->getIdentifier()
        ];
        $article2tag = $this->rm->getTable('article2tag')->createRecord($recordData);
        $this->rm->scheduleSave($article2tag)->commit();
    }

    /**
     * @param Article $article
     * @param Tag     $tag
     */
    private function thenArticle_andTag_areRelated(Article $article, Tag $tag): void
    {
        $this->rm->clearTables();
        self::assertCount(1, $tag->Article2tagHasMany);
        self::assertCount(1, $article->Article2tagHasMany);
    }

}
