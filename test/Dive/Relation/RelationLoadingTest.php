<?php
 /*
 * This file is part of the Dive ORM framework.
 * (c) Steffen Zeidler <sigma_z@sigma-scripts.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dive\Test\Relation;

use Dive\Log\SqlLogger;
use Dive\RecordManager;
use Dive\TestSuite\TableRowsProvider;
use Dive\TestSuite\TestCase;


/**
 * @author  Steffen Zeidler <sigma_z@sigma-scripts.de>
 * @created 05.04.13
 */
class RelationLoadingTest extends TestCase
{

    /**
     * @var RecordManager
     */
    private static $rm = null;
    /**
     * @var array
     */
    private static $tableRows = array();


    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();

        self::$tableRows = TableRowsProvider::provideTableRows();
        self::$rm = self::createDefaultRecordManager();
        self::saveTableRows(self::$rm, self::$tableRows);
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
        /** @var \Dive\Collection\RecordCollection|\Dive\Record[] $users */
        $users = $userTable->createQuery('u')
            ->leftJoin('u.Author au')
            ->where('au.id IS NOT NULL')
            ->execute();
        $this->assertEquals(count(self::$tableRows['author']), $users->count());

        /** @var \Iterator $iterator */
        $iterator = $users->getIterator();
        /** @var \Dive\Record $user */
        $user = $iterator->current();
        $coll = $user->getResultCollection();
        $this->assertInstanceOf('\Dive\Collection\RecordCollection', $coll);

        foreach ($users as $user) {
            if (($author = $user->Author)) {
                $author->Article;
            }
        }

        $sqlLogger->setEchoOutput(false);
        $this->assertEquals(3, $sqlLogger->getCount());
    }

}
