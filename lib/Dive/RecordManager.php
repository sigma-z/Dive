<?php
/*
 * This file is part of the Dive ORM framework.
 * (c) Steffen Zeidler <sigma_z@sigma-scripts.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Dive;

use Dive\Schema\Schema;
use Dive\Schema\SchemaException;
use Dive\Connection\Connection;
use Dive\Relation\Relation;
use Dive\Table;
use Dive\Platform\PlatformInterface;
use Dive\UnitOfWork\UnitOfWork;
use Dive\Validation\FieldValidator\FieldValidator;
use Dive\Validation\UniqueValidator\UniqueRecordValidator;
use Dive\Validation\ValidationContainer;

/**
 * @author Steffen Zeidler <sigma_z@sigma-scripts.de>
 * Date: 24.11.12
 */
class RecordManager
{

    const ORM_VERSION = '1.1.1';

    const FETCH_RECORD_COLLECTION = 'record-collection';
    const FETCH_RECORD = 'record';
    const FETCH_ARRAY = 'array';
    const FETCH_SINGLE_ARRAY = 'single-array';
    const FETCH_SCALARS = 'scalars';
    const FETCH_SINGLE_SCALAR = 'single-scalar';


    /** @var Table[] */
    private $tables = array();

    /** @var Connection */
    private $conn = null;

    /** @var Schema */
    private $schema = null;

    /** @var \Dive\Relation\Relation[] */
    private $relations = array();

    /** @var Table\Behavior\Behavior[] */
    private $tableBehaviors = array();

    /** @var \Dive\Hydrator\HydratorInterface[] */
    private $hydrators = array();

    /** @var UnitOfWork */
    private $unitOfWork = null;

    /** @var string */
    private $queryClass = '\Dive\Query\Query';

    /** @var ValidationContainer */
    private $recordValidationContainer;


    /**
     * @param Connection $conn
     * @param Schema     $schema
     */
    public function __construct(Connection $conn, Schema $schema)
    {
        $this->conn = $conn;
        $this->schema = $schema;
        $this->unitOfWork = new UnitOfWork($this);
    }


    /**
     * @return Connection
     */
    public function getConnection()
    {
        return $this->conn;
    }


    /**
     * @return \Dive\Connection\Driver\DriverInterface
     */
    public function getDriver()
    {
        return $this->getConnection()->getDriver();
    }


    /**
     * @return Event\EventDispatcherInterface
     */
    public function getEventDispatcher()
    {
        return $this->conn->getEventDispatcher();
    }


    /**
     * @return \Dive\Schema\Schema
     */
    public function getSchema()
    {
        return $this->schema;
    }


    /**
     * @param  string $name
     * @return Table
     */
    public function getTable($name)
    {
        $this->initTable($name);
        return $this->tables[$name];
    }


    /**
     * @param string $name
     * @return Table
     */
    public function getView($name)
    {
        return $this->getTable($name);
    }


    /**
     * @param  string $name
     * @return \Dive\Table\Repository
     */
    public function getTableRepository($name)
    {
        $table = $this->getTable($name);
        return $table->getRepository();
    }


    public function clearTables()
    {
        foreach ($this->tables as $table) {
            $table->clearRepository();
        }
        $this->tables = array();
    }


    /**
     * initializes table instance
     *
     * @param  string $tableName
     * @throws SchemaException
     */
    private function initTable($tableName)
    {
        if (!isset($this->tables[$tableName])) {
            if ($this->schema->hasTable($tableName)) {
                $this->tables[$tableName] = $this->createTable($tableName);
                return;
            }

            if ($this->schema->hasView($tableName)) {
                $this->tables[$tableName] = $this->createView($tableName);
                return;
            }

            throw new SchemaException("Table/View '$tableName' not found!");
        }
    }


    /**
     * @param  string $tableName
     * @return \Dive\Table
     * @throws SchemaException
     */
    private function createTable($tableName)
    {
        $tableClass = $this->schema->getTableClass($tableName, true);
        $recordClass = $this->schema->getRecordClass($tableName);
        $fields = $this->schema->getTableFields($tableName);

        $relationsData = $this->schema->getTableRelations($tableName);
        $relations = $this->instantiateRelations($relationsData);

        $indexes = $this->schema->getTableIndexes($tableName);
        $this->initTableBehaviors($tableName);

        return new $tableClass($this, $tableName, $recordClass, $fields, $relations, $indexes);
    }


    /**
     * @param string $viewName
     * @return \Dive\Table
     * @throws SchemaException
     */
    private function createView($viewName)
    {
        $viewClass = $this->schema->getViewClass($viewName, true);
        $recordClass = $this->schema->getRecordClass($viewName);
        $fields = $this->schema->getViewFields($viewName);

        $relationsData = $this->schema->getTableRelations($viewName);
        $relations = $this->instantiateRelations($relationsData);

        return new $viewClass($this, $viewName, $recordClass, $fields, $relations);
    }


    /**
     * @param $relationsData
     * @return array
     */
    private function instantiateRelations($relationsData)
    {
        $relations = array();
        foreach ($relationsData['owning'] as $relName => $relData) {
            $relations[$relData['refAlias']] = $this->getRelationInstance($relName, $relData);
        }
        foreach ($relationsData['referenced'] as $relName => $relData) {
            $relations[$relData['owningAlias']] = $this->getRelationInstance($relName, $relData);
        }
        return $relations;
    }


    /**
     * gets relation instance
     *
     * @param   string $name
     * @param   array  $relationData
     * @return  \Dive\Relation\Relation
     */
    private function getRelationInstance($name, array $relationData)
    {
        if (!isset($this->relations[$name])) {
            $owningTable = $relationData['owningTable'];
            $owningAlias = $relationData['owningAlias'];
            $owningField = $relationData['owningField'];
            $referencedTable = $relationData['refTable'];
            $referencedAlias = $relationData['refAlias'];
            $referencedField = $relationData['refField'];
            $type = $relationData['type'];
            $onDelete = !empty($relationData['onDelete']) ? $relationData['onDelete'] : PlatformInterface::RESTRICT;
            $onUpdate = !empty($relationData['onUpdate']) ? $relationData['onUpdate'] : PlatformInterface::RESTRICT;

            $relation = new Relation(
                $owningAlias, $owningTable, $owningField,
                $referencedAlias, $referencedTable, $referencedField,
                $type, $onDelete, $onUpdate
            );

            if (isset($relationData['orderBy'])) {
                $relation->setOrderBy($relationData['orderBy']);
            }

            $this->relations[$name] = $relation;
        }

        return $this->relations[$name];
    }


    /**
     * @param string $tableName
     */
    private function initTableBehaviors($tableName)
    {
        $behaviors = $this->schema->getTableBehaviors($tableName);
        foreach ($behaviors as $behaviorDefinition) {
            $behavior = $this->getBehaviorInstance($behaviorDefinition);
            $this->getEventDispatcher()->addSubscriber($behavior);
            if (!empty($behaviorDefinition['config'])) {
                $behavior->setTableConfig($tableName, $behaviorDefinition['config']);
            }
        }
    }


    /**
     * @param  array $definition
     * @return Table\Behavior\Behavior
     * @throws Exception
     */
    private function getBehaviorInstance(array $definition)
    {
        if (!isset($definition['class'])) {
            throw new Exception("Missing table behavior class in schema definition!");
        }

        $sharedInstance = isset($definition['instanceShared']) && $definition['instanceShared'] === true;
        $className = $definition['class'];
        if ($className[0] != '\\') {
            $className = "\\Dive\\Table\\Behavior\\$className";
        }
        if ($sharedInstance && isset($this->tableBehaviors[$className])) {
            return $this->tableBehaviors[$className];
        }

        $behavior = new $className;
        if ($sharedInstance) {
            $this->tableBehaviors[$className] = $behavior;
        }
        return $behavior;
    }


    /**
     * Sets query class
     *
     * @param string $queryClass
     */
    public function setQueryClass($queryClass)
    {
        $this->queryClass = $queryClass;
    }


    /**
     * Gets query class
     *
     * @return string
     */
    public function getQueryClass()
    {
        return $this->queryClass;
    }


    /**
     * Creates query for given table name
     *
     * @param  string $tableName
     * @param  string $alias
     * @return Query\Query
     */
    public function createQuery($tableName, $alias = 'a')
    {
        return $this->getTable($tableName)->createQuery($alias);
    }


    /**
     * @param  Record $record
     * @return $this
     */
    public function scheduleSave(Record $record)
    {
        $this->unitOfWork->scheduleSave($record, true);
        return $this;
    }


    /**
     * @param  Record $record
     * @return $this
     */
    public function scheduleDelete(Record $record)
    {
        $this->unitOfWork->scheduleDelete($record);
        return $this;
    }


    /**
     * @deprecated
     * @param  Record $record
     * @return $this
     */
    public function save(Record $record)
    {
        return $this->scheduleSave($record);
    }


    /**
     * @deprecated
     * @param  Record $record
     * @return $this
     */
    public function delete(Record $record)
    {
        return $this->scheduleDelete($record);
    }


    /**
     * @param Record $record
     * @param string $operation
     * @return bool
     */
    public function isRecordScheduledForCommit(Record $record, $operation)
    {
        return $this->unitOfWork->isRecordScheduledForCommit($record, $operation);
    }


    public function commit()
    {
        $this->unitOfWork->commitChanges();
    }


    /**
     * TODO should rollback relation references and their record collections as well
     */
    public function rollback()
    {
        $this->unitOfWork->resetScheduled();
    }


    /**
     * Gets hydrator
     *
     * @param  string $name
     * @return Hydrator\HydratorInterface
     */
    public function getHydrator($name)
    {
        if (!isset($this->hydrators[$name])) {
            $this->hydrators[$name] = $this->createHydrator($name);
        }
        return $this->hydrators[$name];
    }


    /**
     * Sets custom hydrator
     *
     * @param string $name
     * @param Hydrator\HydratorInterface $hydrator
     */
    public function setHydrator($name, Hydrator\HydratorInterface $hydrator)
    {
        $this->hydrators[$name] = $hydrator;
    }


    /**
     * Creates hydrator
     *
     * @param  string $name
     * @return Hydrator\HydratorInterface
     * @throws Exception
     */
    private function createHydrator($name)
    {
        switch ($name) {
            case self::FETCH_RECORD_COLLECTION:
                return new Hydrator\RecordCollectionHydrator($this);

            case self::FETCH_RECORD:
                return new Hydrator\RecordHydrator($this);

            case self::FETCH_ARRAY:
                return new Hydrator\ArrayHydrator($this);

            case self::FETCH_SINGLE_ARRAY:
                return new Hydrator\SingleArrayHydrator($this);

            case self::FETCH_SCALARS:
                return new Hydrator\ScalarHydrator($this);

            case self::FETCH_SINGLE_SCALAR:
                return new Hydrator\SingleScalarHydrator($this);
        }

        throw new Exception("Hydrator '$name' is not defined!");
    }


    /**
     * Gets the record from the repository or creates a new instance
     *
     * @param  string $tableName
     * @param  array $data
     * @param  bool  $exists
     * @return Record
     */
    public function getOrCreateRecord($tableName, array $data, $exists = false)
    {
        $table = $this->getTable($tableName);
        return $this->unitOfWork->getOrCreateRecord($table, $data, $exists);
    }


    /**
     * Finds the record from the repository or creates a new instance
     *
     * @param  string $tableName
     * @param  array  $data
     * @return Record
     */
    public function findOrCreateRecord($tableName, array $data)
    {
        $table = $this->getTable($tableName);
        return $table->findOrCreateRecord($data);
    }


    /**
     * @return ValidationContainer
     */
    public function getRecordValidationContainer()
    {
        if (!$this->recordValidationContainer) {
            $this->recordValidationContainer = $this->createValidationContainer();
        }
        return $this->recordValidationContainer;
    }


    /**
     * @return ValidationContainer
     */
    protected function createValidationContainer()
    {
        $recordValidationContainer = new ValidationContainer();
        $fieldTypeValidator = $this->createConfiguredFieldTypeValidator();
        $recordValidationContainer->addValidator(ValidationContainer::VALIDATOR_FIELD, $fieldTypeValidator);
        $recordValidationContainer->addValidator(ValidationContainer::VALIDATOR_UNIQUE_CONSTRAINT, new UniqueRecordValidator());
        return $recordValidationContainer;
    }


    /**
     * @return FieldValidator
     */
    protected function createConfiguredFieldTypeValidator()
    {
        $dataTypeMapper = $this->getDriver()->getDataTypeMapper();
        $fieldTypeValidator = new FieldValidator($dataTypeMapper);
        return $fieldTypeValidator;
    }

}
