<?php
/*
 * This file is part of the Dive ORM framework.
 * (c) Steffen Zeidler <sigma_z@sigma-scripts.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
/**
 * @author Steffen Zeidler <sigma_z@sigma-scripts.de>
 * Date: 24.11.12
 */
namespace Dive;

use Dive\Schema\Schema;
use Dive\Schema\SchemaException;
use Dive\Connection\Connection;
use Dive\Relation\Relation;
use Dive\Table;


class RecordManager
{

    const FETCH_RECORD_COLLECTION = 'record-collection';
    const FETCH_RECORD = 'record';
    const FETCH_ARRAY = 'array';
    const FETCH_SINGLE_ARRAY = 'single-array';
    const FETCH_SCALARS = 'scalars';
    const FETCH_SINGLE_SCALAR = 'single-scalar';


    /**
     * @var Table[]
     */
    private $tables = array();
    /**
     * @var Connection
     */
    private $conn;
    /**
     * @var Schema
     */
    private $schema;
    /**
     * @var \Dive\Relation\Relation[]
     */
    private $relations = array();
    /**
     * @var \Dive\Hydrator\HydratorInterface[]
     */
    private $hydrators = array();
    /**
     * @var UnitOfWork\UnitOfWork
     */
    private $unitOfWork;


    /**
     * @param Connection $conn
     * @param Schema     $schema
     */
    public function __construct(Connection $conn, Schema $schema)
    {
        $this->conn = $conn;
        $this->schema = $schema;
        $this->unitOfWork = new UnitOfWork\UnitOfWork($this);
    }


    /**
     * @return Connection
     */
    public function getConnection()
    {
        return $this->conn;
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
     * @return \Dive\Table
     */
    public function getTable($name)
    {
        $this->initTable($name);
        return $this->tables[$name];
    }


    /**
     * @param  string $name
     * @return Table\Repository
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
     * @throws \Dive\Schema\SchemaException
     */
    private function initTable($tableName)
    {
        if (isset($this->tables[$tableName])) {
            return;
        }

        if (!$this->schema->hasTable($tableName)) {
            throw new SchemaException("Table '$tableName' not found!");
        }

        $autoLoadClass = true;
        $tableClass = $this->schema->getTableClass($tableName, $autoLoadClass);
        $recordClass = $this->schema->getRecordClass($tableName);
        $fields = $this->schema->getTableFields($tableName);

        // relations
        $relationsData = $this->schema->getTableRelations($tableName);
        $relations = array();
        foreach ($relationsData['owning'] as $relName => $relData) {
            $relations[$relData['owningAlias']] = $this->getRelationInstance($relName, $relData);
        }
        foreach ($relationsData['referenced'] as $relName => $relData) {
            $relations[$relData['refAlias']] = $this->getRelationInstance($relName, $relData);
        }
        //-- relations

        $indexes = $this->schema->getTableIndexes($tableName);
        /**
         * @var \Dive\Table $table
         */
        $table = new $tableClass($this, $tableName, $recordClass, $fields, $relations, $indexes);

        $this->tables[$tableName] = $table;
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

            $relation = new Relation(
                $owningAlias,
                $owningTable,
                $owningField,
                $referencedAlias,
                $referencedTable,
                $referencedField,
                $type
            );

            if (isset($relationData['orderBy'])) {
                $relation->setOrderBy($relationData['orderBy']);
            }

            $this->relations[$name] = $relation;
        }

        return $this->relations[$name];
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
     * @param Record $record
     * @param UnitOfWork\ChangeSet $changeSet
     */
    public function saveRecord(Record $record, UnitOfWork\ChangeSet $changeSet = null)
    {
        if ($changeSet === null) {
            $changeSet = new UnitOfWork\ChangeSet();
        }
        $this->unitOfWork->saveGraph($record, $changeSet);
    }


    /**
     * @param Record $record
     * @param UnitOfWork\ChangeSet $changeSet
     */
    public function deleteRecord(Record $record, UnitOfWork\ChangeSet $changeSet = null)
    {
        if ($changeSet === null) {
            $changeSet = new UnitOfWork\ChangeSet();
        }
        $this->unitOfWork->deleteGraph($record, $changeSet);
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
     * @param  Table $table
     * @param  array $data
     * @param  bool  $exists
     * @return Record
     */
    public function getRecord(Table $table, array $data, $exists = false)
    {
        return $this->unitOfWork->getRecord($table, $data, $exists);
    }

}
