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
use Dive\TestSuite\TestCase;

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

}
