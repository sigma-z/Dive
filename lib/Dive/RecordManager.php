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

/**
 * @author Steffen Zeidler <sigma_z@sigma-scripts.de>
 * Date: 24.11.12
 */
class RecordManager
{

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

    /** @var \Dive\Hydrator\HydratorInterface[] */
    private $hydrators = array();

    /** @var UnitOfWork */
    private $unitOfWork = null;

    /** @var string */
    private $queryClass = '\Dive\Query\Query';


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
     * @return Event\DispatcherInterface
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
            if (!$this->schema->hasTable($tableName)) {
                throw new SchemaException("Table '$tableName' not found!");
            }

            $this->tables[$tableName] = $this->createTable($tableName);
        }
    }


    /**
     * @param  string $tableName
     * @param  bool   $autoLoadClass
     * @return Table
     */
    private function createTable($tableName, $autoLoadClass = true)
    {
        $tableClass = $this->schema->getTableClass($tableName, $autoLoadClass);
        $recordClass = $this->schema->getRecordClass($tableName);
        $fields = $this->schema->getTableFields($tableName);

        $relationsData = $this->schema->getTableRelations($tableName);
        $relations = $this->instantiateRelations($relationsData);

        $indexes = $this->schema->getTableIndexes($tableName);

        return new $tableClass($this, $tableName, $recordClass, $fields, $relations, $indexes);
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
    public function save(Record $record)
    {
        $this->unitOfWork->scheduleSave($record);
        return $this;
    }


    /**
     * @param  Record $record
     * @return $this
     */
    public function delete(Record $record)
    {
        $this->unitOfWork->scheduleDelete($record);
        return $this;
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
     * Gets record
     *
     * @param  string $tableName
     * @param  array $data
     * @param  bool  $exists
     * @return Record
     */
    public function getRecord($tableName, array $data, $exists = false)
    {
        $table = $this->getTable($tableName);
        return $this->unitOfWork->getRecord($table, $data, $exists);
    }

}
