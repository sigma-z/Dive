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
 * @created 01.03.13
 */

namespace Dive\Test\Query;

use Dive\Collection\RecordCollection;
use Dive\Query\Query;
use Dive\Record;
use Dive\RecordManager;
use Dive\Table;
use Dive\TestSuite\TestCase;


class QueryTest extends TestCase
{

    /** @var array */
    private static $usersData = array(
        array(
            'username' => 'John Doe',
            'password' => 'my secret'
        ),
        array(
            'username' => 'Johanna Stuart',
            'password' => 'johanna secret'
        )
    );


    /**
     * @dataProvider provideSqlParts
     */
    public function testGetSql($operations, $expected)
    {
        $rm = self::createDefaultRecordManager();
        $query = $rm->createQuery('user', 'u');
        foreach ($operations as $operation) {
            $method = $operation[0];
            $args = $operation[1];
            call_user_func_array(array($query, $method), $args);
        }
        if ($rm->getConnection()->getScheme() == 'sqlite' && $query->getQueryPart('forUpdate') === true) {
            $this->markTestSkipped('FOR UPDATE clause is not supported for sqlite!');
        }
        $actual = $query->getSql();
        // removing identifier quotations
        $actual = str_replace($rm->getConnection()->getIdentifierQuote(), '', $actual);
        $actual = preg_replace('/\s+/', ' ', $actual);
        $this->assertEquals($expected, $actual);
    }


    public function provideSqlParts()
    {
        $testCases = array();

        $testCases[] = array(
            'operations' => array(),
            'expected' => 'SELECT u.id, u.username, u.password FROM user u'
        );

        // select tests
        $testCases[] = array(
            'operations' => array(
                array('select', array('id'))
            ),
            'expected' => 'SELECT id FROM user u'
        );
        $testCases[] = array(
            'operations' => array(
                array('addSelect', array('username'))
            ),
            'expected' => 'SELECT username FROM user u'
        );
        $testCases[] = array(
            'operations' => array(
                array('addSelect', array('username')),
                array('select', array('id'))
            ),
            'expected' => 'SELECT id FROM user u'
        );
        $testCases[] = array(
            'operations' => array(
                array('select', array('id')),
                array('addSelect', array('username'))
            ),
            'expected' => 'SELECT id, username FROM user u'
        );
        //-- select tests

        // where tests
        $testCases[] = array(
            'operations' => array(
                array('where', array('id = ?', 1))
            ),
            'expected' => 'SELECT u.id, u.username, u.password FROM user u WHERE id = ?'
        );
        $testCases[] = array(
            'operations' => array(
                array('andWhere', array('username = ?', 'Joe')),
            ),
            'expected' => 'SELECT u.id, u.username, u.password FROM user u WHERE username = ?'
        );
        $testCases[] = array(
            'operations' => array(
                array('orWhere', array('username = ?', 'Joe')),
            ),
            'expected' => 'SELECT u.id, u.username, u.password FROM user u WHERE username = ?'
        );
        $testCases[] = array(
            'operations' => array(
                array('andWhere', array('username = ?', 'Joe')),
                array('where', array('id = ?', 1))
            ),
            'expected' => 'SELECT u.id, u.username, u.password FROM user u WHERE id = ?'
        );
        $testCases[] = array(
            'operations' => array(
                array('orWhere', array('username = ?', 'Joe')),
                array('where', array('id = ?', 1))
            ),
            'expected' => 'SELECT u.id, u.username, u.password FROM user u WHERE id = ?'
        );
        $testCases[] = array(
            'operations' => array(
                array('where', array('id = ?', 1)),
                array('andWhere', array('username = ?', 'Joe')),
                array('orWhere', array('username = ?', 'Joe'))
            ),
            'expected' => 'SELECT u.id, u.username, u.password FROM user u WHERE id = ? AND username = ? OR username = ?'
        );
        $testCases[] = array(
            'operations' => array(
                array('where', array('id = ?', 1)),
                array('whereIn', array('username', array('John', 'Doe'))),
            ),
            'expected' => 'SELECT u.id, u.username, u.password FROM user u WHERE username IN (?,?)'
        );
        $testCases[] = array(
            'operations' => array(
                array('whereIn', array('username', array('John', 'Doe'))),
                array('where', array('id = ?', 1)),
            ),
            'expected' => 'SELECT u.id, u.username, u.password FROM user u WHERE id = ?'
        );
        $testCases[] = array(
            'operations' => array(
                array('whereIn', array('username', array('John', 'Doe')))
            ),
            'expected' => 'SELECT u.id, u.username, u.password FROM user u WHERE username IN (?,?)'
        );
        $testCases[] = array(
            'operations' => array(
                array('whereIn', array('username', array('John', 'Doe'))),
                array('orWhereNotIn', array('username', array('Jamie', 'McDonald')))
            ),
            'expected' => 'SELECT u.id, u.username, u.password FROM user u WHERE username IN (?,?) OR username NOT IN (?,?)'
        );
        $testCases[] = array(
            'operations' => array(
                array('whereIn', array('username', array('John', 'Doe'))),
                array('andWhereNotIn', array('username', array('Jamie', 'McDonald')))
            ),
            'expected' => 'SELECT u.id, u.username, u.password FROM user u WHERE username IN (?,?) AND username NOT IN (?,?)'
        );
        $testCases[] = array(
            'operations' => array(
                array('orWhereNotIn', array('username', array('Jamie', 'McDonald')))
            ),
            'expected' => 'SELECT u.id, u.username, u.password FROM user u WHERE username NOT IN (?,?)'
        );
        $testCases[] = array(
            'operations' => array(
                array('andWhereNotIn', array('username', array('Jamie', 'McDonald')))
            ),
            'expected' => 'SELECT u.id, u.username, u.password FROM user u WHERE username NOT IN (?,?)'
        );
        $testCases[] = array(
            'operations' => array(
                array('whereNotIn', array('username', array('John', 'Doe')))
            ),
            'expected' => 'SELECT u.id, u.username, u.password FROM user u WHERE username NOT IN (?,?)'
        );
        $testCases[] = array(
            'operations' => array(
                array('whereNotIn', array('username', array('John', 'Doe'))),
                array('orWhereIn', array('username', array('Jamie', 'McDonald')))
            ),
            'expected' => 'SELECT u.id, u.username, u.password FROM user u WHERE username NOT IN (?,?) OR username IN (?,?)'
        );
        $testCases[] = array(
            'operations' => array(
                array('whereNotIn', array('username', array('John', 'Doe'))),
                array('andWhereIn', array('username', array('Jamie', 'McDonald')))
            ),
            'expected' => 'SELECT u.id, u.username, u.password FROM user u WHERE username NOT IN (?,?) AND username IN (?,?)'
        );
        $testCases[] = array(
            'operations' => array(
                array('orWhereIn', array('username', array('Jamie', 'McDonald')))
            ),
            'expected' => 'SELECT u.id, u.username, u.password FROM user u WHERE username IN (?,?)'
        );
        $testCases[] = array(
            'operations' => array(
                array('andWhereIn', array('username', array('Jamie', 'McDonald')))
            ),
            'expected' => 'SELECT u.id, u.username, u.password FROM user u WHERE username IN (?,?)'
        );
        //-- where tests

        // from/left join tests
        $testCases[] = array(
            'operations' => array(
                array('from', array('author a'))
            ),
            'expected' => 'SELECT a.id, a.firstname, a.lastname, a.email, a.user_id, a.editor_id FROM author a'
        );
        $testCases[] = array(
            'operations' => array(
                array('leftJoin', array('u.Author a'))
            ),
            'expected' => 'SELECT u.id, u.username, u.password FROM user u LEFT JOIN author a ON a.user_id = u.id'
        );
        $testCases[] = array(
            'operations' => array(
                array('leftJoinOn', array('u.Author a', 'u.id > a.id'))
            ),
            'expected' => 'SELECT u.id, u.username, u.password FROM user u LEFT JOIN author a ON u.id > a.id'
        );
        $testCases[] = array(
            'operations' => array(
                array('leftJoinWith', array('u.Author a', 'u.id > a.id'))
            ),
            'expected' => 'SELECT u.id, u.username, u.password FROM user u LEFT JOIN author a ON a.user_id = u.id AND u.id > a.id'
        );
        $testCases[] = array(
            'operations' => array(
                array('leftJoin', array('u.Author a')), // left join will be overwritten by "from" in the next line
                array('from', array('tag t')),
                array('leftJoin', array('t.Article2tagHasMany art2t')),
                array('leftJoin', array('art2t.Article art'))
            ),
            'expected' => 'SELECT t.id, t.name FROM tag t '
                . 'LEFT JOIN article2tag art2t ON art2t.tag_id = t.id '
                . 'LEFT JOIN article art ON art2t.article_id = art.id'
        );
        //-- from/left join tests

        // group by
        $testCases[] = array(
            'operations' => array(
                array('groupBy', array('username'))
            ),
            'expected' => 'SELECT u.id, u.username, u.password FROM user u GROUP BY username'
        );
        $testCases[] = array(
            'operations' => array(
                array('addGroupBy', array('id'))
            ),
            'expected' => 'SELECT u.id, u.username, u.password FROM user u GROUP BY id'
        );
        $testCases[] = array(
            'operations' => array(
                array('addGroupBy', array('id')),
                array('groupBy', array('username'))
            ),
            'expected' => 'SELECT u.id, u.username, u.password FROM user u GROUP BY username'
        );
        $testCases[] = array(
            'operations' => array(
                array('groupBy', array('username')),
                array('addGroupBy', array('id'))
            ),
            'expected' => 'SELECT u.id, u.username, u.password FROM user u GROUP BY username, id'
        );
        //-- group by

        // having
        $testCases[] = array(
            'operations' => array(
                array('groupBy', array('username')),
                array('having', array('count(u.id) > 1'))
            ),
            'expected' => 'SELECT u.id, u.username, u.password FROM user u'
                . ' GROUP BY username'
                . ' HAVING count(u.id) > 1'
        );
        $testCases[] = array(
            'operations' => array(
                array('groupBy', array('username')),
                array('andHaving', array('length(u.username) > 5')),
                array('having', array('count(u.id) > 1'))
            ),
            'expected' => 'SELECT u.id, u.username, u.password FROM user u'
                . ' GROUP BY username'
                . ' HAVING count(u.id) > 1'
        );
        $testCases[] = array(
            'operations' => array(
                array('groupBy', array('username')),
                array('andHaving', array('length(u.username) > 5'))
            ),
            'expected' => 'SELECT u.id, u.username, u.password FROM user u'
                . ' GROUP BY username'
                . ' HAVING length(u.username) > 5'
        );
        $testCases[] = array(
            'operations' => array(
                array('groupBy', array('username')),
                array('orHaving', array('length(u.username) = 4'))
            ),
            'expected' => 'SELECT u.id, u.username, u.password FROM user u'
                . ' GROUP BY username'
                . ' HAVING length(u.username) = 4'
        );
        $testCases[] = array(
            'operations' => array(
                array('groupBy', array('username')),
                array('orHaving', array('length(u.username) = 4')),
                array('having', array('count(u.id) > 1'))
            ),
            'expected' => 'SELECT u.id, u.username, u.password FROM user u'
                . ' GROUP BY username'
                . ' HAVING count(u.id) > 1'
        );
        $testCases[] = array(
            'operations' => array(
                array('groupBy', array('username')),
                array('having', array('count(u.id) > 1')),
                array('andHaving', array('length(u.username) > 5')),
                array('orHaving', array('length(u.username) = 4'))
            ),
            'expected' => 'SELECT u.id, u.username, u.password FROM user u'
                . ' GROUP BY username'
                . ' HAVING count(u.id) > 1 AND length(u.username) > 5 OR length(u.username) = 4'
        );
        //-- having

        // order by
        $testCases[] = array(
            'operations' => array(
                array('orderBy', array('LENGTH(u.username) DESC'))
            ),
            'expected' => 'SELECT u.id, u.username, u.password FROM user u ORDER BY LENGTH(u.username) DESC'
        );
        $testCases[] = array(
            'operations' => array(
                array('addOrderBy', array('u.username ASC'))
            ),
            'expected' => 'SELECT u.id, u.username, u.password FROM user u ORDER BY u.username ASC'
        );
        $testCases[] = array(
            'operations' => array(
                array('addOrderBy', array('u.username ASC')),
                array('orderBy', array('LENGTH(u.username) DESC'))
            ),
            'expected' => 'SELECT u.id, u.username, u.password FROM user u ORDER BY LENGTH(u.username) DESC'
        );
        $testCases[] = array(
            'operations' => array(
                array('orderBy', array('LENGTH(u.username) DESC')),
                array('addOrderBy', array('u.username ASC'))
            ),
            'expected' => 'SELECT u.id, u.username, u.password FROM user u ORDER BY LENGTH(u.username) DESC, u.username ASC'
        );
        //-- order by

        // limit/offset
        $testCases[] = array(
            'operations' => array(
                array('limit', array(10)),
            ),
            'expected' => 'SELECT u.id, u.username, u.password FROM user u LIMIT 10'
        );
        $testCases[] = array(
            'operations' => array(
                array('offset', array(10)),
            ),
            'expected' => 'SELECT u.id, u.username, u.password FROM user u'
        );
        $testCases[] = array(
            'operations' => array(
                array('limit', array(10)),
                array('offset', array(5))
            ),
            'expected' => 'SELECT u.id, u.username, u.password FROM user u LIMIT 10 OFFSET 5'
        );
        $testCases[] = array(
            'operations' => array(
                array('limitOffset', array(10, 5)),
            ),
            'expected' => 'SELECT u.id, u.username, u.password FROM user u LIMIT 10 OFFSET 5'
        );
        //-- limit/offset

        // distinct
        $testCases[] = array(
            'operations' => array(
                array('distinct', array(true)),
            ),
            'expected' => 'SELECT DISTINCT u.id, u.username, u.password FROM user u'
        );
        $testCases[] = array(
            'operations' => array(
                array('distinct', array(true)),
                array('distinct', array(false)),
            ),
            'expected' => 'SELECT u.id, u.username, u.password FROM user u'
        );
        //-- distinct

        // forUpdate
        $testCases[] = array(
            'operations' => array(
                array('forUpdate', array(true)),
            ),
            'expected' => 'SELECT u.id, u.username, u.password FROM user u FOR UPDATE'
        );
        $testCases[] = array(
            'operations' => array(
                array('forUpdate', array(true)),
                array('forUpdate', array(false)),
            ),
            'expected' => 'SELECT u.id, u.username, u.password FROM user u'
        );
        //-- forUpdate

        return $testCases;
    }


    /**
     * @dataProvider provideGetSqlThrowsAliasException
     */
    public function testGetSqlThrowsAliasException($method, array $args, $expectedMessage)
    {
        $this->setExpectedException('\Dive\Query\QueryException', $expectedMessage);
        $rm = self::createDefaultRecordManager();
        $query = $rm->createQuery('user', 'u');
        call_user_func_array(array($query, $method), $args);
        $query->getSql();
    }


    public function provideGetSqlThrowsAliasException()
    {
        $testCases = array();

        $testCases[] = array(
            'method' => 'from',
            'args' => array('user'),
            'expectedMessage' => "Missing alias in query from 'user'!"
        );
        $testCases[] = array(
            'method' => 'leftJoin',
            'args' => array('u.author u'),
            'expectedMessage' => "Duplicate alias 'u' in query!"
        );
        $testCases[] = array(
            'method' => 'leftJoin',
            'args' => array('a.author u'),
            'expectedMessage' => "Parent alias 'a' is not defined in query!"
        );
        $testCases[] = array(
            'method' => 'leftJoin',
            'args' => array('u.author'),
            'expectedMessage' => "Left join 'u.author' misses alias!"
        );
        $testCases[] = array(
            'method' => 'leftJoin',
            'args' => array('author a'),
            'expectedMessage' => "Left join 'author a' misses parent alias!"
        );

        return $testCases;
    }


    /**
     * @dataProvider provideSqlPartsDatabaseAware
     */
    public function testFetchArray($database, array $operations)
    {
        // prepare
        $rm = self::createRecordManager($database);
        $query = $rm->createQuery('user', 'u');
        foreach ($operations as $operation) {
            $method = $operation[0];
            $args = $operation[1];
            call_user_func_array(array($query, $method), $args);
        }
        if ($rm->getConnection()->getScheme() == 'sqlite' && $query->getQueryPart('forUpdate') === true) {
            $this->markTestSkipped('FOR UPDATE clause is not supported for sqlite!');
        }
        $result = $query->fetchArray();
        $this->assertInternalType('array', $result, 'Expected query result to be an array!');
        $this->assertEmpty($result, 'Expected query result to be empty!');
    }


    public function provideSqlPartsDatabaseAware()
    {
        $testCases = $this->provideSqlParts();
        return $this->getDatabaseAwareTestCases($testCases);
    }


    private function saveUserRecords(RecordManager $rm)
    {
        $table = $rm->getTable('user');
        $userIds = array();
        foreach (self::$usersData as &$userData) {
            $userIds[] = self::insertDataset($table, $userData);
        }
        return $userIds;
    }


    /**
     * @dataProvider provideExecute
     */
    public function testExecute(array $database, $fetchMode, $method, $expected)
    {
        // prepare
        $rm = self::createRecordManager($database);
        $userIds = $this->saveUserRecords($rm);

        $recordFetchModes = array(RecordManager::FETCH_RECORD, RecordManager::FETCH_RECORD_COLLECTION);
        $isRecordFetchMode = in_array($fetchMode, $recordFetchModes);

        $table = $rm->getTable('user');
        $query = $table->createQuery();
        if ($isRecordFetchMode) {
            if ($fetchMode == RecordManager::FETCH_RECORD) {
                $expected['id'] = $userIds[0];
            }
            else {
                $expectedTmp = $expected;
                $expected = array();
                foreach ($userIds as $index => $id) {
                    $expected[$id] = array('id' => $id) + $expectedTmp[$index];
                }
            }
        }
        else {
            $query->select('username, password');
        }

        // execute unit
        $this->assertTrue(method_exists($query, $method));
        $executedResult = $query->execute($fetchMode);
        $methodResult = call_user_func(array($query, $method));

        // assert
        $this->assertQueryExecute($executedResult, $expected);
        $this->assertQueryExecute($methodResult, $expected);
    }


    /**
     * @param RecordCollection|Record|array|string|bool $result
     * @param array|string                              $expected
     */
    private function assertQueryExecute($result, $expected)
    {
        if (is_object($result)) {
            $this->assertTrue(method_exists($result, 'toArray'));
            $this->assertEquals($expected, $result->toArray());
        }
        else {
            $this->assertEquals($expected, $result);
        }
    }


    public function provideExecute()
    {
        $testCases = array();

        $testCases[] = array(
            'fetchMode' => RecordManager::FETCH_ARRAY,
            'method' => 'fetchArray',
            'expected' => self::$usersData
        );

        $testCases[] = array(
            'fetchMode' => RecordManager::FETCH_SINGLE_ARRAY,
            'method' => 'fetchOneAsArray',
            'expected' => self::$usersData[0]
        );

        $testCases[] = array(
            'fetchMode' => RecordManager::FETCH_SINGLE_SCALAR,
            'method' => 'fetchSingleScalar',
            'expected' => self::$usersData[0]['username']
        );

        $expected = array();
        foreach (self::$usersData as $userData) {
            $expected[] = $userData['username'];
        }
        $testCases[] = array(
            'fetchMode' => RecordManager::FETCH_SCALARS,
            'method' => 'fetchScalars',
            'expected' => $expected
        );

        $usersDataWithExistsFlag = array();
        foreach (self::$usersData as $userData) {
            $userData[Record::FROM_ARRAY_EXISTS_KEY] = true;
            $usersDataWithExistsFlag[] = $userData;
        }

        $testCases[] = array(
            'fetchMode' => RecordManager::FETCH_RECORD,
            'method' => 'fetchOneAsObject',
            'expected' => $usersDataWithExistsFlag[0]
        );

        $testCases[] = array(
            'fetchMode' => RecordManager::FETCH_RECORD_COLLECTION,
            'method' => 'fetchObjects',
            'expected' => $usersDataWithExistsFlag
        );

        return self::getDatabaseAwareTestCases($testCases);
    }


    /**
     * @dataProvider provideDatabaseAwareTestCases
     */
    public function testFindByFkOnNonExistingRecord($database)
    {
        $rm = self::createRecordManager($database);
        $table = $rm->getTable('user');
        $query = $table->createQuery();
        $query->where('id = ?', 10);
        $record = $query->fetchOneAsObject();
        $this->assertFalse($record);
    }


    public function testGetRootTable()
    {
        $rm = self::createDefaultRecordManager();
        $query = new Query($rm);
        $query->from('user u');
        $this->assertEquals('user', $query->getRootTable()->getTableName());
    }


    public function testGetRootTableThrowsMissingFromException()
    {
        $this->setExpectedException('\Dive\Query\QueryException', 'Root table is not defined, yet!');
        $rm = self::createDefaultRecordManager();
        $query = new Query($rm);
        $query->getRootTable();
    }

}