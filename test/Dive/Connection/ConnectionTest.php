<?php
/*
 * This file is part of the Dive ORM framework.
 * (c) Steffen Zeidler <sigma_z@sigma-scripts.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dive\Test\Connection;

use Dive\Connection\Connection;
use Dive\Logging\SqlLogger;
use Dive\RecordManager;
use Dive\Relation\Relation;
use Dive\TestSuite\TestCase;
use Dive\Util\RandomRecordDataGenerator;

/**
 * @author Steffen Zeidler <sigma_z@sigma-scripts.de>
 * Date: 31.10.12
 */
class ConnectionTest extends TestCase
{

    /**
     * @dataProvider provideGetScheme
     *
     * @param string $dsn
     * @param string $expected
     */
    public function testGetScheme($dsn, $expected)
    {
        /** @var \Dive\Connection\Driver\DriverInterface $driver */
        $driver = $this->getMockForAbstractClass('\Dive\Connection\Driver\DriverInterface');
        $conn = new Connection($driver, $dsn);
        $this->assertEquals($expected, $conn->getScheme());
    }


    public function provideGetScheme()
    {
        return array(
            array('mysql:', 'mysql'),
            array('sqlite:', 'sqlite'),
            array('mssql::', 'mssql'),
        );
    }


    /**
     * @dataProvider provideTestConnectDisconnect
     */
    public function testConnect(array $database)
    {
        $events = array(Connection::EVENT_PRE_CONNECT, Connection::EVENT_POST_CONNECT);

        $conn = $this->createDatabaseConnection($database);

        // expected events will be gathered through closure calls
        $expectedEventsCalled = array();
        $eventDispatcher = $conn->getEventDispatcher();
        $this->addMockListenerToEventDispatcher($eventDispatcher, $events, $expectedEventsCalled);

        // connect database (events will be fired)
        $conn->connect();

        $this->assertTrue($conn->isConnected());
        $this->assertEquals($events, $expectedEventsCalled);
    }


    public function provideTestConnectDisconnect()
    {
        return $this->getDatabaseAwareTestCases();
    }


    /**
     * @dataProvider provideTestConnectDisconnect
     */
    public function testDisconnect(array $database)
    {
        $events = array(Connection::EVENT_PRE_DISCONNECT, Connection::EVENT_POST_DISCONNECT);

        $conn = $this->createDatabaseConnection($database);

        // expected events will be gathered through closure calls
        $expectedEventsCalled = array();
        $eventDispatcher = $conn->getEventDispatcher();
        $this->addMockListenerToEventDispatcher($eventDispatcher, $events, $expectedEventsCalled);

        // disconnect will be only processed, if connection has been established before
        $conn->connect();
        // disconnect database (events will be fired)
        $conn->disconnect();

        $this->assertFalse($conn->isConnected());
        $this->assertEquals($events, $expectedEventsCalled);
    }


    /**
     * @dataProvider provideGetDatabaseName
     */
    public function testGetDatabaseName($database)
    {
        $dsn = $database['dsn'];
        $search = 'dbname=';
        if (false === ($pos = strpos($dsn, $search))) {
            $this->markTestSkipped("No database name found in data source: $dsn!");
        }

        $pos += strlen($search);
        $posEnd = strpos($dsn, ';', $pos);
        $expectedDatabaseName = $posEnd !== false
            ? substr($dsn, $pos, $posEnd - $pos)
            : substr($dsn, $pos);

        $conn = $this->createDatabaseConnection($database);
        $this->assertEquals($expectedDatabaseName, $conn->getDatabaseName());
    }


    public function provideGetDatabaseName()
    {
        return $this->getDatabaseAwareTestCases();
    }


    /**
     * @dataProvider provideGetStatement
     * @param $database
     * @param $sql
     * @param $params
     * @param $sqlWithoutParams
     */
    public function testGetStatement($database, $sql, $params, $sqlWithoutParams)
    {
        $this->markTestSkipped('Skipped, because of sql statement inconsistencies across database platforms!');
        // create connection
        $conn = $this->createDatabaseConnection($database);

        $events = array(Connection::EVENT_PRE_QUERY, Connection::EVENT_POST_QUERY);

        // expected events will be gathered through closure calls
        $expectedEventsCalled = array();
        $eventDispatcher = $conn->getEventDispatcher();
        $this->addMockListenerToEventDispatcher($eventDispatcher, $events, $expectedEventsCalled);

        $sqlLogger = new SqlLogger();
        $conn->setSqlLogger($sqlLogger);

        // get statement from sql with placeholders and params
        $stmt = $conn->getStatement($sql, $params);
        $this->assertInstanceOf('\PDOStatement', $stmt);
        $this->assertEquals($sql, $stmt->queryString);

        // get statement from (same) sql without placeholders
        $stmt2 = $conn->getStatement($sqlWithoutParams);
        $this->assertInstanceOf('\PDOStatement', $stmt2);
        $this->assertEquals($sqlWithoutParams, $stmt2->queryString);

        $this->assertEquals($stmt->fetchAll(), $stmt2->fetchAll());

        $this->assertEquals(2, $conn->getSqlLogger()->count());

        $this->assertEquals(array_merge($events, $events), $expectedEventsCalled);
    }


    public function provideGetStatement()
    {
        $dbTestCases    = $this->getDatabaseAwareTestCases();
        $sqlTestCases   = $this->getSqlTestCases('query');

        return $this->combineTestCases($dbTestCases, $sqlTestCases);
    }


    public function testQuery()
    {
        $this->markTestSkipped('is this nearly the same as getStatement? do we need to test \PDOStatement->fetchAll(*)?');
    }


    /**
     * @dataProvider provideExec
     * @param $database
     * @param $sql
     * @param $params
     * @param $sqlWithoutParams
     */
    public function testExec($database, $sql, $params, $sqlWithoutParams)
    {
        // create connection
        $conn = $this->createDatabaseConnection($database);

        $events = array(Connection::EVENT_PRE_EXEC, Connection::EVENT_POST_EXEC);

        // expected events will be gathered through closure calls
        $expectedEventsCalled = array();
        $eventDispatcher = $conn->getEventDispatcher();
        $this->addMockListenerToEventDispatcher($eventDispatcher, $events, $expectedEventsCalled);

        $return1 = $conn->exec($sql, $params);
        $return2 = $conn->exec($sqlWithoutParams);

        $this->assertEquals($return1, $return2, 'Connection->exec() does not deliver same return value for calling with'
            . ' params and same with parsed params directly into sql and call exec without params');

        $this->assertEquals(array_merge($events, $events), $expectedEventsCalled);
    }


    public function provideExec()
    {
        $dbTestCases    = $this->getDatabaseAwareTestCases();
        $sqlTestCases   = $this->getSqlTestCases('exec');

        return $this->combineTestCases($dbTestCases, $sqlTestCases);
    }


    /**
     * returns array of testCases with array($sql, $params, $sqlWithoutParams)
     * @param   string  $type
     * @return  array
     */
    private function getSqlTestCases($type)
    {
        $testCases = array();

        // independent sql's
        // TODO: this query returns different values on exec()! Why?
//        $testCases[] = array(
//            'SELECT ?;',    // $sql
//            array(1),       // $params
//            'SELECT 1;'     // $sqlWithoutParams
//        );

        if ('exec' === $type) {
            // insert a user
            $testCases[] = array(
                "INSERT INTO user (username, password) VALUES (?, ?);",    // $sql
                array('test', 'test'),       // $params
                "INSERT INTO user (username, password) VALUES ('test2', 'test2');"
            );
            // update a user
            $testCases[] = array(
                "UPDATE user SET password = ? WHERE username = ?;",    // $sql
                array('test_up', 'test'),       // $params
                "UPDATE user SET password = 'test2_up' WHERE username = 'test2';"
            );
            // update a user without change...
            $testCases[] = array(
                "UPDATE user SET password = ? WHERE username = ?;",    // $sql
                array('test_up', 'test'),       // $params
                "UPDATE user SET password = 'test2_up' WHERE username = 'test2';"
            );
            // update a non existing user...
            $testCases[] = array(
                "UPDATE user SET password = ? WHERE username = ?;",    // $sql
                array('test_up_not_exists', 'test_not_exists'),       // $params
                "UPDATE user SET password = 'test2_up_not_exists' WHERE username = 'test2_not_exists';"
            );
            // delete a user
            $testCases[] = array(
                "DELETE FROM user WHERE username = ?;",    // $sql
                array('test'),       // $params
                "DELETE FROM user WHERE username = 'test2';"
            );
        }
        else if ('query' === $type) {
            // TODO inconsistent behavior across database platform, how should we define our test cases for that?
            // SELECT '' FROM DUAL: Oracle, MySQL, and DB2
            // SELECT '':           SQL Server, MySQL, PostgreSQL, and SQLite
            $testCases[] = array(
                'SELECT 1 FROM DUAL WHERE ?;',  // $sql
                array(1),                       // $params
                'SELECT 1 FROM DUAL WHERE 1'    // $sqlWithoutParams
            );
            $testCases[] = array(
                'SELECT ? AS id;',      // $sql
                array(1),               // $params
                'SELECT 1 AS id'        // $sqlWithoutParams
            );
        }

        return $testCases;
    }


    // TODO: helper function - move to parent?
    /**
     * iterates through both test-case arrays and combines with each-to-each
     * @example input:  [['a'],['b']]    and    [['1'],['2']]
     *          output: [['a','1'],['a','2'],['b','1'],['b','2']]
     *
     * @param array $testCases1
     * @param array $testCases2
     * @return array
     */
    protected static function combineTestCases(array $testCases1, array $testCases2)
    {
        $testCases = array();

        foreach ($testCases1 as $case1) {
            foreach ($testCases2 as $case2) {
                $testCases[] = array_merge($case1, $case2);
            }
        }

        return $testCases;
    }


    /**
     * @dataProvider provideDatabaseAwareTestCases
     * @param $database
     */
    public function testInsert($database)
    {
        $randomGenerator = $this->getRandomRecordDataGenerator();
        $insertTypes = $randomGenerator->getTypes();

        // prepare
        $rm = self::createRecordManager($database);
        $conn = $rm->getConnection();

        $testFullSchema = false; // <<<--- use true when foreign keys are supported
        $tableNames = $testFullSchema ? $rm->getSchema()->getTableNames() : array('author');
        foreach ($tableNames as $tableName) {
            $table = $rm->getTable($tableName);
            $owningRelations = $table->getOwningRelations();
            /** @var $localRelationFields Relation[] */
            $localRelationFields = array();
            foreach ($owningRelations as $relation) {
                $localRelationFields[$relation->getOwnerField()] = $relation;
            }

            $fields = $table->getFields();
            foreach ($insertTypes as $type) {
                $data = array();
                foreach ($localRelationFields as $fieldName => $relation) {
                    if ($randomGenerator->matchType($fields[$fieldName], $type)) {
                        $data[$fieldName] = $this->insertRequiredLocalRelationGraph($rm, $relation);
                    }
                }
                $data = $randomGenerator->getRandomRecordData($fields, $data, $type);
                $rowCount = $conn->insert($table, $data);
                $msg = "insert into $tableName with data: ";
                $this->assertEquals(1, $rowCount, $msg . print_r($data, true));
            }
        }
    }


    // TODO!
    /**
     * @dataProvider provideDatabaseAwareTestCases
     * @param $database
     */
    public function testUpdate($database)
    {
        $this->markTestIncomplete();
    }


    // TODO!
    /**
     * @dataProvider provideDatabaseAwareTestCases
     * @param $database
     */
    public function testDelete($database)
    {
        $this->markTestIncomplete();
    }


    // TODO: helper - move to parent? move to record-data-generator?
    protected function insertRequiredLocalRelationGraph(RecordManager $rm, Relation $relation)
    {
        $randomGenerator    = $this->getRandomRecordDataGenerator();
        $conn               = $rm->getConnection();

        $refTableName       = $relation->getReferencedTable();
        $refTable           = $rm->getTable($refTableName);
        $refFields          = $refTable->getFields();

        $data = array();
        // recursion: walk through all local relations that are required and handle this by calling this method with
        //  next relation
        $owningRelations = $refTable->getOwningRelations();
        foreach ($owningRelations as $owningRelation) {
            // field to fill
            $ownerField = $owningRelation->getOwnerField();
            // check if field is required (default of matchType) and insert required related data
            if ($randomGenerator->matchType($refFields[$ownerField])) {
                $data[$ownerField] = $this->insertRequiredLocalRelationGraph($rm, $owningRelation);
            }
        }

        // insert a record and return its id
        $data = $randomGenerator->getRandomRecordData($refFields, $data);
        $conn->insert($refTable, $data);
        return $conn->getLastInsertId($refTableName);
    }

}
