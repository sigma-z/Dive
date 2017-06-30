<?php
 /*
 * This file is part of the Dive ORM framework.
 * (c) Steffen Zeidler <sigma_z@sigma-scripts.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dive\Test\Relation;

use Dive\Collection\RecordCollection;
use Dive\Log\SqlLogger;
use Dive\Record\Generator\RecordGenerator;
use Dive\Record;
use Dive\RecordManager;
use Dive\TestSuite\Model\User;
use Dive\TestSuite\TableRowsProvider;
use Dive\TestSuite\TestCase;


/**
 * @author  Steffen Zeidler <sigma_z@sigma-scripts.de>
 * @created 05.04.13
 */
class RelationLoadingTest extends TestCase
{

    /** @var RecordManager */
    private static $rm;

    /** @var array */
    private static $tableRows = array();

    /** @var RecordGenerator */
    private static $recordGenerator;


    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();

        self::$tableRows = TableRowsProvider::provideTableRows();
        self::$rm = self::createDefaultRecordManager();
        self::$recordGenerator = self::saveTableRows(self::$rm, self::$tableRows);
    }


    protected function setUp()
    {
        parent::setUp();
        self::$rm->clearTables();
    }


    public function testLoadingUserArticles()
    {
        $expectedArticles = array(
            'JohnD' => 2,
            'JamieTK' => 1
        );
        $userTable = self::$rm->getTable('user');
        $users = $userTable->createQuery('u')->execute();
        $this->assertEquals(5, $users->count());

        foreach ($users as $user) {
            if (isset($expectedArticles[$user->username])) {
                $expected = $expectedArticles[$user->username];
                $author = $user->Author;
                $message = "Expected an author record for user '" . $user->username . "'!";
                $this->assertInstanceOf('\Dive\Record', $author, $message);
                /** @var \Dive\Collection\RecordCollection $articleColl */
                $articleColl = $author->Article;
                $this->assertInstanceOf('\Dive\Collection\RecordCollection', $articleColl);
                $this->assertEquals($expected, $articleColl->count());
            }
        }
    }


    public function testNumberOfQueriesForLoadingUserArticles()
    {
        $sqlLogger = new SqlLogger();
        //$sqlLogger->setEchoOutput(true);
        self::$rm->getConnection()->setSqlLogger($sqlLogger);

        $userTable = self::$rm->getTable('user');
        /** @var \Dive\Collection\RecordCollection|User[] $users */
        $users = $userTable->createQuery('u')
            ->leftJoin('u.Author au')
            ->where('au.id IS NOT NULL')
            ->execute();
        $this->assertCount(count(self::$tableRows['author']), $users);

        foreach ($users as $user) {
            if (($author = $user->Author)) {
                $author->Article;
            }
        }
        foreach ($users as $user) {
            if (($author = $user->Author)) {
                $author->Article;
            }
        }

        $sqlLogger->setEchoOutput(false);
        $this->assertEquals(3, $sqlLogger->getCount());
    }


    public function testLoadArticleComments()
    {
        $articleId = self::$recordGenerator->getRecordIdFromMap('article', 'tableSupport');
        $rm = self::createDefaultRecordManager();
        $article = $rm->getTable('article')->findByPk($articleId);
        $comments = $article->get('Comment');
        $this->assertCount(4, $comments);
    }


    public function testLoadReferencesToSelf()
    {
        $commentId = self::$recordGenerator->getRecordIdFromMap('comment', 'tableSupport#1');
        $rm = self::createDefaultRecordManager();
        $comment = $rm->getTable('comment')->findByPk($commentId);
        $article = $comment->get('Article');
        $comments = $article->get('Comment');
        $this->assertCount(4, $comments);
    }


    public function testLoadReferenceBySettingOwningField()
    {
        $this->markTestSkipped('Not supported, yes');
        $articleId = self::$recordGenerator->getRecordIdFromMap('article', 'DiveORM released');
        $rm = self::createDefaultRecordManager();
        $article = $rm->getTable('article')->findByPk($articleId);
        $comment = $rm->getTable('comment')->createRecord();
        $comment->article_id = $articleId;
        $comment->user_id = self::$recordGenerator->getRecordIdFromMap('user', 'JohnD');

        $this->assertCount(2, $article->Comment);
    }


    /**
     * @dataProvider provideLoadReferences
     * @param string $tableName
     * @param string $recordKey
     * @param array  $references
     * @param array  $expectedReferencesLoaded
     */
    public function testLoadReferences($tableName, $recordKey, array $references, array $expectedReferencesLoaded)
    {
        $table = self::$rm->getTable($tableName);
        $record = $this->getGeneratedRecord(self::$recordGenerator, $table, $recordKey);
        $record->loadReferences($references);

        $actualReferencesLoaded = $this->getLoadedReferences($record);
        $this->assertEquals($expectedReferencesLoaded, $actualReferencesLoaded);
    }


    /**
     * @return array[]
     */
    public function provideLoadReferences()
    {
        $testCases = array();

        $testCases[] = array(
            'tableName' => 'user',
            'recordKey' => 'JohnD',
            'references' => array(),
            'expectedReferencesLoaded' => array()
        );
        $testCases[] = array(
            'tableName' => 'user',
            'recordKey' => 'JohnD',
            'references' => array(
                'Comment' => array(),
                'Author' => array(
                    'Article' => array('Comment' => array())
                ),
            ),
            'expectedReferencesLoaded' => array(
                'Author' => array(
                    'Article' => array('Comment' => array())
                )
            )
        );
        $testCases[] = array(
            'tableName' => 'user',
            'recordKey' => 'JamieTK',
            'references' => array(
                'Author' => array(
                    'Article' => array(
                        'Article2tagHasMany' => array(),
                        'Comment' => array()
                    )
                ),
                'Comment' => array()
            ),
            'expectedReferencesLoaded' => array(
                'Comment' => array(
                    // loaded through Article relation
                    'Article' => array(
                        'Author' => array(),
                        'Article2tagHasMany' => array(),
                        'Comment' => array()
                    )
                ),
                'Author' => array(
                    'Article' => array(
                        'Article2tagHasMany' => array(),
                        'Comment' => array()
                    )
                )
            )
        );
        $testCases[] = array(
            'tableName' => 'user',
            'recordKey' => 'BartS',
            'references' => array(
                'Comment' => array(),
                'Author' => array()
            ),
            'expectedReferencesLoaded' => array(
                'Author' => array(),
                'Comment' => array()
            )
        );

        return $testCases;
    }


    /**
     * @param  Record $record
     * @param  array  $visited
     * @return array|bool
     */
    private function getLoadedReferences(Record $record, array $visited = array())
    {
        $oid = $record->getOid();
        if (in_array($oid, $visited)) {
            return false;
        }
        $visited[] = $oid;

        $actualReferences = array();
        $table = $record->getTable();
        $relations = $table->getRelations();
        foreach ($relations as $relationName => $relation) {
            if ($relation->hasReferenceLoadedFor($record, $relationName)) {
                $related = $relation->getReferenceFor($record, $relationName);
                if ($related instanceof RecordCollection) {
                    foreach ($related as $relatedRecord) {
                        $loadedReferences = $this->getLoadedReferences($relatedRecord, $visited);
                        if ($loadedReferences !== false) {
                            $actualReferences[$relationName] = $loadedReferences;
                        }
                    }
                }
                else if ($related instanceof Record) {
                    $loadedReferences = $this->getLoadedReferences($related, $visited);
                    if ($loadedReferences !== false) {
                        $actualReferences[$relationName] = $loadedReferences;
                    }
                }
            }
        }
        return $actualReferences;
    }


}
