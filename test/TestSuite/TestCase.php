<?php

namespace Dive\TestSuite;

use Dive\Connection\Connection;
use Dive\Connection\ConnectionRowChangeEvent;
use Dive\Event\Dispatcher;
use Dive\Event\Event;
use Dive\Record;
use Dive\Record\Generator\RecordGenerator;
use Dive\RecordManager;
use Dive\Relation\Relation;
use Dive\Schema\Schema;
use Dive\Table;
use Dive\TestSuite\Constraint\OwningFieldMappingConstraint;
use Dive\TestSuite\Constraint\ReferenceMapIsEmptyConstraint;
use Dive\TestSuite\Constraint\RelationReferenceMapConstraint;
use Dive\Util\FieldValuesGenerator;

/**
 * @author Steffen Zeidler <sigma_z@sigma-scripts.de>
 * Date: 31.10.12
 */
abstract class TestCase extends \PHPUnit_Framework_TestCase
{

    public static $debug = false;
    /**
     * @var array
     */
    private static $databases = array();
    /**
     * @var array
     */
    private static $schemaDefinition = null;
    /**
     * @var DatasetRegistry
     */
    private static $datasetRegistryTestCase = null;
    /**
     * @var DatasetRegistry
     */
    protected static $datasetRegistryTestClass = null;
    /**
     * @var FieldValuesGenerator
     */
    protected $randomRecordDataGenerator = null;
    /**
     * @var Connection[]
     */
    private static $connectionPoolTestClass = array();
    /**
     * @var bool
     */
    private static $isTestCase = false;


    public static function setUpBeforeClass()
    {
        self::$debug = false;
        self::checkTablesAreEmpty();
        self::$datasetRegistryTestClass = new DatasetRegistry();

        parent::setUpBeforeClass();
    }


    public static function tearDownAfterClass()
    {
        parent::tearDownAfterClass();
        self::removeDatasets(self::$datasetRegistryTestClass);

        foreach (self::$connectionPoolTestClass as $conn) {
            $conn->disconnect();
        }
        self::$connectionPoolTestClass = array();
        self::$datasetRegistryTestClass = null;
    }


    protected function setUp()
    {
        parent::setUp();
        self::$datasetRegistryTestCase = new DatasetRegistry();
        self::$isTestCase = true;
    }


    protected function tearDown()
    {
        parent::tearDown();
        self::removeDatasets(self::$datasetRegistryTestCase);

        self::$datasetRegistryTestCase = null;
        self::$isTestCase = false;
    }


    protected static function checkTablesAreEmpty()
    {
        $databases = self::getDatabases();
        $schema = self::getSchema();
        $tableNames = $schema->getTableNames();
        foreach ($databases as $database) {
            $conn = self::createDatabaseConnection($database);
            foreach ($tableNames as $tableName) {
                $sql = "SELECT COUNT(*) FROM " . $conn->quoteIdentifier($tableName);
                $count = $conn->queryOne($sql, array(), \PDO::FETCH_COLUMN);
                if ($count > 0) {
                    throw new \Exception("Table '$tableName' is supposed to be empty, but has $count rows!");
                }
            }
        }
    }


    /**
     * @return array
     */
    protected static function getSchemaDefinition()
    {
        if (self::$schemaDefinition === null) {
            self::$schemaDefinition = include FIXTURE_DIR . '/schema.php';
        }
        return self::$schemaDefinition;
    }


    /**
     * Gets schema
     *
     * @param  array $definition
     * @return \Dive\Schema\Schema
     */
    public static function getSchema(array $definition = null)
    {
        if ($definition === null) {
            $definition = self::getSchemaDefinition();
        }
        return new Schema($definition);
    }


    /**
     * Adds mock listener via closure to event dispatcher
     *
     * @param Dispatcher $eventDispatcher
     * @param Event[] $events
     * @param array $expectedEventsCalled
     */
    protected function addMockListenerToEventDispatcher(
        Dispatcher $eventDispatcher,
        array $events,
        array &$expectedEventsCalled
    ) {
        $callOnEvent = function(Event $event) use (&$expectedEventsCalled) {
            // TODO Event::getName() will be deprecated in symfony/event-dispatcher 3
            /** @noinspection PhpDeprecationInspection */
            $expectedEventsCalled[] = $event->getName();
        };
        foreach ($events as $eventName) {
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
     * TODO could we use getRecordWithRandomData() instead?
     * @return FieldValuesGenerator
     */
    public function getRandomRecordDataGenerator()
    {
        if (null === $this->randomRecordDataGenerator) {
            $this->randomRecordDataGenerator = new FieldValuesGenerator();
        }
        return $this->randomRecordDataGenerator;
    }


    /**
     * @param  Table $table
     * @param  array $defaultFieldValues
     * @return Record
     */
    protected function getRecordWithRandomData(Table $table, array $defaultFieldValues = array())
    {
        $fieldValueGenerator = new FieldValuesGenerator();
        $recordData = $fieldValueGenerator->getRandomRecordData($table->getFields(), $defaultFieldValues);
        return $table->createRecord($recordData);
    }


    /**
     * Gets default record manager
     *
     * @param  array $schemaDefinition
     * @return \Dive\RecordManager
     */
    public static function createDefaultRecordManager(array $schemaDefinition = null)
    {
        $databases = self::getDatabases();
        return self::createRecordManager($databases[0], $schemaDefinition);
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
    protected function createDatabaseConnectionOrMarkTestSkipped(array $database)
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
     * @param  array $schemaDefinition
     * @return \Dive\RecordManager
     */
    protected static function createRecordManager(array $database, array $schemaDefinition = null)
    {
        $conn = self::createDatabaseConnection($database);
        if ($schemaDefinition === null) {
            $schemaDefinition = self::getSchemaDefinition();
        }
        $schema = self::getSchema($schemaDefinition);
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
        $dsn = $database['dsn'];
        if (!isset(self::$connectionPoolTestClass[$dsn])) {
            $scheme = self::getSchemeFromDsn($dsn);
            /** @var \Dive\Connection\Driver\DriverInterface $driver */
            $driver = self::createInstance('Connection\Driver', 'Driver', $scheme);
            $conn = new Connection($driver, $dsn, $database['user'], $database['password']);
            $eventDispatcher = $conn->getEventDispatcher();
            $eventDispatcher->addListener(Connection::EVENT_POST_INSERT, array(__CLASS__, 'addToDatasetRegistry'));
            self::$connectionPoolTestClass[$dsn] = $conn;
        }
        return self::$connectionPoolTestClass[$dsn];
    }


    /**
     * @param ConnectionRowChangeEvent $event
     */
    public static function addToDatasetRegistry(ConnectionRowChangeEvent $event)
    {
        $datasetRegistry = self::$isTestCase ? self::$datasetRegistryTestCase : self::$datasetRegistryTestClass;
        $identifier = $event->getIdentifier();
        if (self::$debug) {
            echo 'add record to registry ' . $event->getTable()->getTableName() . ' ' . implode(',', $identifier) . "\n";
        }
        $datasetRegistry->add($event->getTable(), $identifier);
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
     * @param Relation $relation
     */
    protected function assertRelationReferenceMapIsEmpty(Relation $relation)
    {
        $constraint = new ReferenceMapIsEmptyConstraint();
        $this->assertThat($relation, $constraint);
    }


    /**
     * @param Record $record
     * @param string $relationName
     * @param Record $otherRecord
     * @param string $message
     */
    protected function assertOwningFieldMapping(Record $record, $relationName, Record $otherRecord, $message = '')
    {
        $constraint = new OwningFieldMappingConstraint($record, $relationName);
        $this->assertThat($otherRecord, $constraint, $message);
    }


    /**
     * @param Record $record
     * @param string $relationName
     * @param Record $otherRecord
     * @param string $message
     */
    protected function assertNoOwningFieldMapping(Record $record, $relationName, Record $otherRecord, $message = '')
    {
        $constraint = new OwningFieldMappingConstraint($record, $relationName);
        $this->assertThat($otherRecord, self::logicalNot($constraint), $message);
    }


    /**
     * @param Record $record
     * @param string $relationName
     * @param Record $otherRecord
     * @param string $message
     */
    protected function assertRelationReference(Record $record, $relationName, Record $otherRecord, $message = '')
    {
        $constraint = new RelationReferenceMapConstraint($record, $relationName);
        $this->assertThat($otherRecord, $constraint, $message);
    }


    /**
     * @param Record $record
     * @param string $relationName
     * @param Record $otherRecord
     * @param string $message
     */
    protected function assertNoRelationReference(Record $record, $relationName, Record $otherRecord, $message = '')
    {
        $constraint = new RelationReferenceMapConstraint($record, $relationName);
        $this->assertThat($otherRecord, self::logicalNot($constraint), $message);
    }


    /**
     * @param Record $record
     * @param string $relationName
     * @param array  $otherRecords
     * @param string $message
     */
    protected function assertOwningFieldMappingOneToMany(
        Record $record,
        $relationName,
        array $otherRecords,
        $message = ''
    ) {
        $constraint = new OwningFieldMappingConstraint($record, $relationName);
        foreach ($otherRecords as $otherRecord) {
            $this->assertThat($otherRecord, $constraint, $message);
        }
    }


    /**
     * @param Record          $record
     * @param string          $relationName
     * @param Record|Record[] $otherRecords
     * @param string          $message
     */
    protected function assertRelationReferences(Record $record, $relationName, $otherRecords, $message = '')
    {
        if (!is_array($otherRecords)) {
            $otherRecords = array($otherRecords);
        }
        $owningFieldMappingConstraint = new OwningFieldMappingConstraint($record, $relationName);
        $relationReferenceMapConstraint = new RelationReferenceMapConstraint($record, $relationName);
        $constraint = self::logicalAnd($relationReferenceMapConstraint, $owningFieldMappingConstraint);
        foreach ($otherRecords as $otherRecord) {
            $this->assertThat($otherRecord, $constraint, $message);
        }
    }


    /**
     * @param Record          $record
     * @param string          $relationName
     * @param Record|Record[] $otherRecords
     * @param string          $message
     */
    protected function assertNoRelationReferences(Record $record, $relationName, $otherRecords, $message = '')
    {
        if (!is_array($otherRecords)) {
            $otherRecords = array($otherRecords);
        }
        $owningFieldMappingConstraint = new OwningFieldMappingConstraint($record, $relationName);
        $relationReferenceMapConstraint = new RelationReferenceMapConstraint($record, $relationName);
        $constraint = self::logicalAnd($relationReferenceMapConstraint, $owningFieldMappingConstraint);
        $invertedConstraint = self::logicalNot($constraint);
        foreach ($otherRecords as $otherRecord) {
            $this->assertThat($otherRecord, $invertedConstraint, $message);
        }
    }


    /**
     * @param  RecordManager $rm
     * @return RecordGenerator
     */
    protected function createRecordGenerator(RecordManager $rm)
    {
        $fvGenerator = new FieldValuesGenerator();
        return new RecordGenerator($rm, $fvGenerator);
    }


    /**
     * @param  RecordManager $rm
     * @param  array         $tableRows
     * @param  array         $tableMapFields
     * @return RecordGenerator
     */
    protected static function saveTableRows(RecordManager $rm, array $tableRows = null, array $tableMapFields = null)
    {
        if ($tableRows === null) {
            $tableRows = TableRowsProvider::provideTableRows();
        }
        if ($tableMapFields === null) {
            $tableMapFields = TableRowsProvider::provideTableMapFields();
        }
        $fvGenerator = new FieldValuesGenerator();
        $recordGenerator = new RecordGenerator($rm, $fvGenerator);
        $recordGenerator->setTablesRows($tableRows);
        $recordGenerator->setTablesMapField($tableMapFields);
        $recordGenerator->generate();
        return $recordGenerator;
    }


    /**
     * NOTE Table should use a different record manager than generator to use different table repositories
     *
     * @param \Dive\Record\Generator\RecordGenerator $generator
     * @param  Table            $table
     * @param  string           $recordKey
     * @return Record
     */
    protected function getGeneratedRecord(RecordGenerator $generator, Table $table, $recordKey)
    {
        $tableName = $table->getTableName();
        $pk = $generator->getRecordIdFromMap($tableName, $recordKey);
        if ($table->hasCompositePrimaryKey()) {
            $pk = explode(Record::COMPOSITE_ID_SEPARATOR, $pk);
        }
        $record = $table->findByPk($pk);
        $message = "Could not load record for '$recordKey' in table '$tableName'";
        $this->assertInstanceOf('\Dive\Record', $record, $message);
        return $record;
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


    /**
     * @param bool $testFullSchema = true
     * @return array
     */
    public static function provideTableNameTestCases($testFullSchema = true)
    {
        $schema = self::getSchema();
        $tableNames = $testFullSchema ? $schema->getTableNames() : array('author'); // TODO: workaround until all cleanups working...
        $tableNameTestCases = array();
        foreach ($tableNames as $tableName) {
            $tableNameTestCases[] = array($tableName);
        }

        return $tableNameTestCases;
    }


    /**
     * Inserts new data record to database
     *
     * @param  \Dive\Table      $table
     * @param  array            $data
     * @return string
     */
    protected static function insertDataset(Table $table, array $data)
    {
        $conn = $table->getConnection();
        $affectedRows = $conn->insert($table, $data);
        return $affectedRows == 1 ? $conn->getLastInsertId() : false;
    }


    /**
     * Removes datasets from registry
     *
     * @param DatasetRegistry $registry
     */
    protected static function removeDatasets(DatasetRegistry $registry)
    {
        $connections = $registry->getConnections();

        foreach ($connections as $conn) {
            $tables = $registry->getTables($conn);
            if (empty($tables)) {
                continue;
            }

            if (self::$debug) {
                echo "\ncleaning up data records\n";
            }
            /** @var Table[] $tables */
            $tables = array_reverse($tables);
            foreach ($tables as $table) {
                $datasetIds = $registry->getByTable($table);
                foreach ($datasetIds as $id) {
                    if (self::$debug) {
                        echo 'remove record from registry '
                            . $conn->getDatabaseName() . '.' . $table->getTableName() . ' ' . implode(',', $id) . "\n";
                    }
                    $conn->delete($table, $id);
                }
            }
        }
    }


    // TODO: helper - move to record-data-generator?
    /**
     * @param RecordManager $rm
     * @param Relation      $relation
     * @return string
     */
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
        $owningRelations = $refTable->getReferencedRelationsIndexedByOwningField();
        foreach ($owningRelations as $owningField => $owningRelation) {
            // check if field is required (default of matchType) and insert required related data
            if ($randomGenerator->matchType($refFields[$owningField])) {
                $data[$owningField] = $this->insertRequiredLocalRelationGraph($rm, $owningRelation);
            }
        }

        // insert a record and return its id
        $data = $randomGenerator->getRandomRecordData($refFields, $data);
        $conn->insert($refTable, $data);
        return $conn->getLastInsertId($refTableName);
    }


    // TODO: helper function - right place?
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
     * @param Record   $record
     * @param string   $relationName
     * @param string[] $expectedOriginalIds
     */
    protected function assertOriginalReference(Record $record, $relationName, array $expectedOriginalIds)
    {
        $relation = $record->getTable()->getRelation($relationName);
        $originalReferencedIds = $relation->getOriginalReferencedIds($record, $relationName);
        $this->assertEquals($expectedOriginalIds, $originalReferencedIds);
    }

}
