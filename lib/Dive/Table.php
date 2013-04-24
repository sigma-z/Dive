<?php
/*
 * This file is part of the Dive ORM framework.
 * (c) Steffen Zeidler <sigma_z@sigma-scripts.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dive;

use Dive\Connection\Connection;
use Dive\Query\Query;
use Dive\RecordManager;
use Dive\Table\Repository;
use Dive\Table\TableException;

/**
 * @author Steffen Zeidler <sigma_z@sigma-scripts.de>
 * Date: 24.11.12
 */
class Table
{

    /**
     * @var RecordManager
     */
    protected $rm;
    /**
     * @var string
     */
    protected $tableName;
    /**
     * @var string
     */
    protected $recordClass;
    /**
     * @var array
     */
    protected $identifier = array();
    /**
     * @var array associative (keys: field names, values: array with field structure)
     */
    protected $fields = array();
    /**
     * @var \Dive\Relation\Relation[]
     */
    protected $relations = array();
    /**
     * @var \Dive\Relation\Relation[]
     */
    protected $owningRelations = null;
    /**
     * @var \Dive\Relation\Relation[]
     */
    protected $referencedRelations = null;
    /**
     * @var array
     */
    protected $indexes = array();
    /**
     * @var Repository
     */
    private $repository;


    /**
     * TODO should repository be a obligated argument, given by the record manager?
     *
     * constructor
     *
     * @param RecordManager    $recordManager
     * @param string           $tableName
     * @param string           $recordClass
     * @param array            $fields
     * @param array            $relations
     * @param array            $indexes
     * @param Table\Repository $repository
     */
    public function __construct(
        RecordManager $recordManager,
        $tableName,
        $recordClass,
        array $fields,
        array $relations = array(),
        array $indexes = array(),
        Repository $repository = null
    )
    {
        $this->rm = $recordManager;
        $this->tableName = $tableName;
        $this->recordClass = $recordClass;
        $this->fields = $fields;
        foreach ($fields as $fieldName => $definition) {
            if (isset($definition['primary']) && $definition['primary'] === true) {
                $this->identifier[] = $fieldName;
            }
        }
        $this->relations = $relations;
        $this->indexes = $indexes;

        if (null === $repository) {
            $repository = new Repository($this);
        }
        $this->repository = $repository;
    }


    /**
     * Gets record manager
     *
     * @return RecordManager
     */
    public function getRecordManager()
    {
        return $this->rm;
    }


    /**
     * Gets table name
     *
     * @return string
     */
    public function getTableName()
    {
        return $this->tableName;
    }


    /**
     * toString
     */
    public function __toString()
    {
        return get_class($this) . ' ' . $this->getTableName();
    }


    /**
     * Creates record
     *
     * @param  array $data
     * @param  bool  $exists
     * @return Record
     */
    public function createRecord(array $data = array(), $exists = false)
    {
        $recordClass = $this->recordClass;
        return new $recordClass($this, $data, $exists);
    }


    /**
     * Gets record class
     *
     * @return string
     */
    public function getRecordClass()
    {
        return $this->recordClass;
    }


    /**
     * Gets connection belonging to the table
     *
     * @return Connection
     */
    public function getConnection()
    {
        return $this->rm->getConnection();
    }


    /**
     * Gets fields (keys: field names, values: field definition as array)
     *
     * @return array
     */
    public function getFields()
    {
        return $this->fields;
    }


    /**
     * Gets field names
     *
     * @return array
     */
    public function getFieldNames()
    {
        return array_keys($this->fields);
    }


    /**
     * Gets default value for field
     *
     * @param  string $name field name
     * @return mixed
     */
    public function getFieldDefaultValue($name)
    {
        if (isset($this->fields[$name]['default'])) {
            return $this->fields[$name]['default'];
        }
        return null;
    }


    /**
     * Gets index
     *
     * @param  string $name
     * @return array
     *   keys: type<string>, fields<array>
     */
    public function getIndex($name)
    {
        return isset($this->indexes[$name]) ? $this->indexes[$name] : null;
    }


    /**
     * Gets indexes (keys: index names, values: index definition as array)
     *
     * @return array
     */
    public function getIndexes()
    {
        return $this->indexes;
    }


    /**
     * TODO unit test it!
     */
    public function getUniqueIndexes()
    {
        $uniqueIndexes = array();
        foreach ($this->indexes as $name => $definition) {
            if ($definition['unique'] === true) {
                $uniqueIndexes[$name] = $definition;
            }
        }
        return $uniqueIndexes;
    }


    /**
     * Sets table repository
     *
     * @param Repository $repository
     */
    public function setRepository(Repository $repository)
    {
        $this->repository = $repository;
    }


    /**
     * Gets table repository
     *
     * @return Repository
     */
    public function getRepository()
    {
        return $this->repository;
    }


    /**
     * Returns true, if repository contains record
     *
     * @param  string $id
     * @return bool
     */
    public function isInRepository($id)
    {
        return $this->repository->hasByInternalId($id);
    }


    /**
     * Gets record from repository
     *
     * @param  string $id
     * @return bool|Record
     */
    public function getFromRepository($id)
    {
        return $this->repository->getByInternalId($id);
    }


    public function refreshRecordIdentityInRepository(Record $record)
    {
        $this->getRepository()->refreshIdentity($record);
    }


    private function clearRelationReferences()
    {
        foreach ($this->relations as $relation) {
            $relation->clearReferences();
        }
    }


    /**
     * Clears repository
     */
    public function clearRepository()
    {
        $this->clearRelationReferences();
        $this->repository->clear();
    }


    /**
     * Gets identifier as array, if it is a composite primary key, otherwise as string
     *
     * @return array|string
     */
    public function getIdentifier()
    {
        return count($this->identifier) == 1 ? $this->identifier[0] : $this->identifier;
    }


    /**
     * Gets identifier as array
     *
     * @return array
     */
    public function getIdentifierAsArray()
    {
        return $this->identifier;
    }


    /**
     * Returns true, if given field name is (part of) the primary key
     *
     * @param  string $name
     * @return bool
     */
    public function isFieldIdentifier($name)
    {
        return in_array($name, $this->identifier);
    }


    /**
     * Gets field definition
     *
     * @param  string $name
     * @return array
     * @throws TableException
     */
    public function getField($name)
    {
        $this->throwExceptionIfFieldNotExists($name);
        return $this->fields[$name];
    }


    /**
     * Returns true, if table defines a field for the given name
     *
     * @param string $name
     * @return bool
     */
    public function hasField($name)
    {
        return isset($this->fields[$name]);
    }


    /**
     * Returns true, if field is not nullable
     *
     * @param  string $name
     * @return bool
     * @throws TableException
     */
    public function isFieldRequired($name)
    {
        return !$this->isFieldNullable($name);
    }


    /**
     * Returns true, if field is nullable
     *
     * @param  string $name
     * @return bool
     * @throws TableException
     */
    public function isFieldNullable($name)
    {
        $this->throwExceptionIfFieldNotExists($name);
        return isset($this->fields[$name]['nullable']) && $this->fields[$name]['nullable'] == true;
    }


    /**
     * Gets table relations
     *
     * @return \Dive\Relation\Relation[]
     */
    public function getRelations()
    {
        return $this->relations;
    }


    /**
     * @return \Dive\Relation\Relation[]
     */
    public function getOwningRelations()
    {
        if (null === $this->owningRelations) {
            $this->owningRelations = array();
            $tableName = $this->getTableName();
            foreach ($this->relations as $name => $relation) {
                if ($tableName === $relation->getOwnerTable()) {
                    $this->owningRelations[$name] = $relation;
                }
            }
        }
        return $this->owningRelations;
    }


    /**
     * @return \Dive\Relation\Relation[]
     */
    public function getReferencedRelations()
    {
        if (null === $this->referencedRelations) {
            $this->referencedRelations = array();
            $tableName = $this->getTableName();
            foreach ($this->relations as $name => $relation) {
                if ($tableName === $relation->getReferencedTable()) {
                    $this->referencedRelations[$name] = $relation;
                }
            }
        }
        return $this->referencedRelations;
    }


    /**
     * Returns true, if table has a relation for the given relation name
     *
     * @param  string $name
     * @return bool
     */
    public function hasRelation($name)
    {
        return isset($this->relations[$name]);
    }


    /**
     * Gets relation for given relation name
     *
     * @param  string $name
     * @return \Dive\Relation\Relation
     * @throws TableException
     */
    public function getRelation($name)
    {
        $this->throwExceptionIfRelationNotExists($name);
        return $this->relations[$name];
    }


    /**
     * Gets reference for given record
     *
     * @param  Record $record
     * @param  string $relationName
     * @return null|Record|\Dive\Collection\RecordCollection
     */
    public function getReferenceFor(Record $record, $relationName)
    {
        $relation = $this->getRelation($relationName);
        if ($relation->isOneToOne() || $relation->isOwningSide($relationName)) {
            $refRecord = $relation->getReferencedRecord($record, $relationName);
            if ($refRecord) {
                return $refRecord;
            }
        }
        return $relation->getReferenceFor($record, $relationName);
    }


    /**
     * Sets reference for given record
     *
     * @param Record                                        $record
     * @param string                                        $relationName
     * @param null|Record|\Dive\Collection\RecordCollection $reference
     */
    public function setReferenceFor(Record $record, $relationName, $reference)
    {
        $relation = $this->getRelation($relationName);
        $relation->setReferenceFor($record, $relationName, $reference);
    }


    /**
     * Creates query with this table in from clause
     *
     * @param  string $alias
     * @return Query
     */
    public function createQuery($alias = 'a')
    {
        $from = $this->getTableName() . ' ' . $alias;
        $query = new Query($this->rm);
        $query->from($from);
        return $query;
    }


//    public function count()
//    {
//        return $this->createQuery()->count();
//    }


    /**
     * Finds record by primary key
     *
     * @param  string|array $id
     * @param  string       $fetchMode
     * @return bool|\Dive\Record|array
     */
    public function findByPk($id, $fetchMode = RecordManager::FETCH_RECORD)
    {
        $query = $this->createQuery();
        if (!is_array($id)) {
            $id = array($id);
        }
        $identifier = $this->getIdentifierAsArray();
        $this->throwExceptionIfIdentifierDoesNotMatchFields($id);

        $query->where(implode(' = ? AND ', $identifier) . ' = ?', $id);
        return $query->execute($fetchMode);
    }


//    public function findAll()
//    {
//        $query = $this->createQuery();
//        return $query->execute();
//    }
//
//
//    public function findByUniqueKey($uniqueKey, $fields)
//    {
//
//    }
//
//
//    public function findByField($field, $value)
//    {
//
//    }
//
//
//    public function findOneByField($field, $value)
//    {
//
//    }


    /**
     * checks if field exists and throws an exception if not so
     * @param string $name
     * @throws TableException if field does not exists
     */
    public function throwExceptionIfFieldNotExists($name)
    {
        if (!$this->hasField($name)) {
            throw new TableException("Field '$name' is not defined on table '$this->tableName'!");
        }
    }


    /**
     * checks if relation exists and throws an exception if not so
     * @param string $name
     * @throws TableException if relation does not exists
     */
    public function throwExceptionIfRelationNotExists($name)
    {
        if (!$this->hasRelation($name)) {
            throw new TableException("Relation '$name' is not defined on table '$this->tableName'!");
        }
    }


    /**
     * checks if given name is defined as field or relation on this table and throws an exception if it does not
     * @param $name
     * @throws TableException
     */
    public function throwExceptionIfFieldOrRelationNotExists($name)
    {
        if (!$this->hasField($name) && !$this->hasRelation($name)) {
            throw new TableException("'$name' is neither a field nor a relation on table '$this->tableName'!");
        }
    }


    public function throwExceptionIfIdentifierDoesNotMatchFields($id)
    {
        if (!is_array($id)) {
            $id = array($id);
        }
        if (count($id) != count($this->identifier)) {
            throw new TableException(
                'Id does not match identifier fields: '
                    . implode(', ', $this->identifier)
                    . ' (you gave me: ' . implode(', ', $id) . ')!'
            );
        }
    }

}
