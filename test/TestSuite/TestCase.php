<?php

namespace Dive\TestSuite;

use Dive\Connection\Connection;
use Dive\Event\Dispatcher;
use Dive\Event\Event;
use Dive\RecordManager;
use Dive\Schema\Schema;

/**
 * @author Steffen Zeidler <sigma_z@sigma-scripts.de>
 * Date: 31.10.12
 */
abstract class TestCase extends \PHPUnit_Framework_TestCase
{

    private static $databases = array();

    /**
     * @var \Dive\Schema\Schema
     */
    private static $schema;


    public static function getSchema()
    {
        if (!self::$schema) {
            $schemaDefinition = include FIXTURE_DIR . '/schema.php';
            self::$schema = new Schema($schemaDefinition);
        }
        return self::$schema;
    }


    protected function addMockListenerToEventDispatcher(
        Dispatcher $eventDispatcher,
        $events,
        array &$expectedEventsCalled
    ) {
        $callOnEvent = function(Event $event) use (&$expectedEventsCalled) {
            $expectedEventsCalled[] = $event->getName();
        };
        foreach ((array)$events as $eventName) {
            $eventDispatcher->addListener($eventName, $callOnEvent);
        }
    }


    public static function getDatabases()
    {
        if (empty(self::$databases)) {
            $dbConfigFile = __DIR__ . '/../../phpunit_db_config.php';
            $dbConfigDistFile = __DIR__ . '/../../phpunit_db_config.php.dist';
            $databases = array();
            if (is_file($dbConfigFile)) {
                $databases = require_once $dbConfigFile;
            }
            else if (is_file($dbConfigDistFile)) {
                $databases = require_once $dbConfigDistFile;
            }
            foreach ($databases as $database) {
                self::$databases[] = is_string($database)
                    ? array('dsn' => $database, 'user' => '', 'password' => '')
                    : $database;
            }
        }
        return self::$databases;
    }


    public static function getDefaultRecordManager()
    {
        $databases = self::getDatabases();
        $conn = self::createDatabaseConnection($databases[0]);
        $schema = self::getSchema();
        return new RecordManager($conn, $schema);
    }


    /**
     * gets scheme from dsn
     *
     * @param  string|array $dsn
     * @return string
     * @throws \InvalidArgumentException
     */
    public static function getSchemeFromDsn($dsn)
    {
        $dsn = is_array($dsn) ? $dsn['dsn'] : $dsn;
        $pos = strpos($dsn, ':');
        if (false === $pos) {
            throw new \InvalidArgumentException(
                "Data source name '$dsn' must define a database scheme: ie. 'mysql:host=localhost;'!"
            );
        }
        return strtolower(substr($dsn, 0, $pos));
    }


    /**
     * Gets database connection or mark test as skipped.
     *
     * @param  array $database
     * @return \Dive\Connection\Connection
     * @throws \PHPUnit_Framework_SkippedTestError
     */
    protected function createDatabaseConnectionOrMarkTestSkipped($database)
    {
        $conn = self::createDatabaseConnection($database);
        if (!$conn) {
            $this->markTestSkipped('Test skipped for ' .  $database['scheme'] . '!');
        }
        return $conn;
    }


    /**
     * creates database connection instance
     *
     * @param   array   $database           must have keys: dsn, user, password
     * @return  \Dive\Connection\Connection
     */
    public static function createDatabaseConnection(array $database)
    {
        $scheme = self::getSchemeFromDsn($database['dsn']);
        /** @var \Dive\Connection\Driver\DriverInterface $driver */
        $driver = self::createInstance('Connection\Driver', 'Driver', $scheme);
        return new Connection(
            $driver,
            $database['dsn'],
            $database['user'],
            $database['password']
        );
    }


    /**
     * creates class instance
     *
     * @param   string $namespace
     * @param   string $className
     * @param   string $scheme
     * @param   array  $arguments
     * @return  object
     */
    protected static function createInstance($namespace, $className, $scheme, array $arguments = array())
    {
        if (substr($namespace, 0, 6) !== '\Dive\\') {
            $namespace = '\Dive\\' . $namespace;
        }
        $class = $namespace . '\\' . ucfirst($scheme) . $className;
        if (class_exists($class)) {
            $class = new \ReflectionClass($class);
            if (!empty($arguments)) {
                return $class->newInstanceArgs($arguments);
            }
            return $class->newInstance();
        }
        return false;
    }


    /**
     * creates class instance, or mark test as skipped, if instance could not be created
     *
     * @param  string $namespace
     * @param  string $className
     * @param  string $scheme
     * @param  array  $arguments
     * @return object
     * @throws \PHPUnit_Framework_SkippedTestError
     */
    protected function createInstanceOrMarkTestSkipped($namespace, $className, $scheme, array $arguments = array())
    {
        $instance = self::createInstance($namespace, $className, $scheme, $arguments);
        if (!$instance) {
            $this->markTestSkipped("Skipping test, because $className could not be created for '$scheme'!");
        }
        return $instance;
    }


    /**
     * Gets expected result, if defined for scheme, otherwise mark test as incomplete
     *
     * @param   array $expected
     * @param   array $database
     * @return  mixed
     * @throws  \PHPUnit_Framework_IncompleteTestError
     */
    protected function getExpectedOrMarkTestIncomplete(array $expected, $database)
    {
        $dsn = $database['dsn'];
        $scheme = self::getSchemeFromDsn($dsn);
        if (!isset($expected[$scheme])) {
            $this->markTestIncomplete('Test is not implemented for: ' . $dsn . ' using scheme: ' . $scheme . '!');
        }
        return $expected[$scheme];
    }


    /**
     * Adds for each test case the database array (from config) as first test method argument
     * NOTE: 3 test cases * 2 databases = 6 test cases
     *
     * @param  array $testCases
     * @return array
     */
    protected static function getDatabaseAwareTestCases($testCases = array())
    {
        if (empty($testCases)) {
            $testCases = array(array());
        }
        $databases = self::getDatabases();
        $testCasesWithDatabases = array();
        foreach ($testCases as $testCase) {
            foreach ($databases as $database) {
                $testCasesWithDatabases[] = array_merge(array('database' => $database), $testCase);
            }
        }
        return $testCasesWithDatabases;
    }


    /**
     * data provider method for test cases that do not need other arguments like '$expected' and so on
     *
     * @return array
     */
    public static function provideDatabaseAwareTestCases()
    {
        $databases = self::getDatabases();
        $testCasesWithDatabases = array();
        foreach ($databases as $database) {
            $testCasesWithDatabases[] = array('database' => $database);
        }
        return $testCasesWithDatabases;
    }

}
