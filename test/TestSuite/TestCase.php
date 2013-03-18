<?php

namespace Dive\TestSuite;

use Dive\Connection\Connection;
use Dive\Event\Dispatcher;
use Dive\Event\Event;
use Dive\RecordManager;
use Dive\Schema\Schema;
use Dive\Table;

/**
 * @author Steffen Zeidler <sigma_z@sigma-scripts.de>
 * Date: 31.10.12
 */
abstract class TestCase extends \PHPUnit_Framework_TestCase
{

    /**
     * @var array
     */
    private static $databases = array();
    /**
     * @var \Dive\Schema\Schema
     */
    private static $schema = null;


    /**
     * Gets schema
     *
     * @return \Dive\Schema\Schema
     */
    public static function getSchema()
    {
        if (!self::$schema) {
            $schemaDefinition = include FIXTURE_DIR . '/schema.php';
            self::$schema = new Schema($schemaDefinition);
        }
        return self::$schema;
    }


    /**
     * Adds mock listener via closure to event dispatcher
     *
     * @param \Dive\Event\Dispatcher $eventDispatcher
     * @param \Dive\Event\Event|\Dive\Event\Event[] $events
     * @param array $expectedEventsCalled
     */
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


    /**
     * Gets database config arrays for unit tests
     *
     * @return array
     */
    public static function getDatabases()
    {
        if (empty(self::$databases)) {
            $dbConfigFile = __DIR__ . '/../../phpunit_db_config.php';
            $dbConfigDistFile = __DIR__ . '/../../phpunit_db_config.php.dist';
            $databases = array();
            if (is_file($dbConfigFile)) {
                /** @noinspection PhpIncludeInspection */
                $databases = require_once $dbConfigFile;
            }
            else if (is_file($dbConfigDistFile)) {
                /** @noinspection PhpIncludeInspection */
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


    /**
     * Gets database for given scheme
     *
     * @param  string $givenScheme
     * @return array|bool
     */
    public static function getDatabaseForScheme($givenScheme)
    {
        $databases = self::getDatabases();
        foreach ($databases as $database) {
            $scheme = self::getSchemeFromDsn($database['dsn']);
            if ($scheme === $givenScheme) {
                return $database;
            }
        }
        return false;
    }


    /**
     * Gets default record manager
     *
     * @return \Dive\RecordManager
     */
    public static function createDefaultRecordManager()
    {
        $databases = self::getDatabases();
        return self::createRecordManager($databases[0]);
    }


    /**
     * Gets scheme from dsn
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
     * Creates record manager by given database array
     *
     * @param  array $database
     * @return \Dive\RecordManager
     */
    protected static function createRecordManager($database)
    {
        $conn = self::createDatabaseConnection($database);
        $schema = self::getSchema();
        return new RecordManager($conn, $schema);
    }


    /**
     * Creates database connection instance
     *
     * @param   array   $database           must have keys: dsn, user, password
     * @return  \Dive\Connection\Connection
     */
    public static function createDatabaseConnection(array $database)
    {
        $scheme = self::getSchemeFromDsn($database['dsn']);
        /** @var \Dive\Connection\Driver\DriverInterface $driver */
        $driver = self::createInstance('Connection\Driver', 'Driver', $scheme);
        return new Connection($driver, $database['dsn'], $database['user'], $database['password']);
    }


    /**
     * Creates class instance
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
     * Creates class instance, or mark test as skipped, if instance could not be created
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
     * Data provider method for test cases that do not need other arguments like '$expected' and so on
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
