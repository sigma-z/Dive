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
    protected $recordManager;
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
//    /**
//     * @var \Dive\Relation\Relation[]
//     */
//    protected $relations = array();
    /**
     * @var array
     */
    protected $indexes = array();
//    /**
//     * @var \Dive\Table\Repository
//     * keys: oid
//     */
//    private $repository;


    /**
     * @param RecordManager $recordManager
     * @param string    $tableName
     * @param string    $recordClass
     * @param array     $fields
     * @param array     $relations
     * @param array     $indexes
     */
    public function __construct(
            RecordManager $recordManager,
            $tableName,
            $recordClass,
            array $fields,
            array $relations = array(),
            array $indexes = array()
        )
    {
        $this->recordManager = $recordManager;
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
    }


    public function getRecordManager()
    {
        return $this->recordManager;
    }


    /**
     * @return string
     */
    public function getTableName()
    {
        return $this->tableName;
    }


    /**
     * creates record
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


    public function getRecordClass()
    {
        return $this->recordClass;
    }


    /**
     * @return Connection
     */
    public function getConnection()
    {
        return $this->recordManager->getConnection();
    }


    /**
     * @return array
     */
    public function getFields()
    {
        return $this->fields;
    }


    public function getFieldNames()
    {
        return array_keys($this->fields);
    }


    /**
     * gets default value for field
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


    public function getIndex($name)
    {
        return isset($this->indexes[$name]) ? $this->indexes[$name] : null;
    }


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


//    public function setRepository(\Dive\Table\Repository $repository)
//    {
//        $this->repository = $repository;
//    }
//
//
//    public function getRepository()
//    {
//        if (null === $this->repository) {
//            $this->repository = new \Dive\Table\Repository();
//        }
//        return $this->repository;
//    }


    /**
     * @return array|string
     */
    public function getIdentifier()
    {
        return count($this->identifier) == 1 ? $this->identifier[0] : $this->identifier;
    }


    /**
     * @return array
     */
    public function getIdentifierAsArray()
    {
        return $this->identifier;
    }


    /**
     * @param  string $name
     * @return bool
     */
    public function isFieldIdentifier($name)
    {
        return in_array($name, $this->identifier);
    }


    /**
     * @param string $name
     * @return array
     * @throws TableException
     */
    public function getField($name)
    {
        $this->throwExceptionIfFieldNotExists($name);
        return $this->fields[$name];
    }


    /**
     * @param string $name
     * @return bool
     */
    public function hasField($name)
    {
        return isset($this->fields[$name]);
    }


    /**
     * @param string $name
     * @return bool
     * @throws TableException
     */
    public function isFieldRequired($name)
    {
        return !$this->isFieldNullable($name);
    }


    /**
     * @param string $name
     * @return bool
     * @throws TableException
     */
    public function isFieldNullable($name)
    {
        $this->throwExceptionIfFieldNotExists($name);
        return isset($this->fields[$name]['nullable']) && $this->fields[$name]['nullable'] == true;
    }


    /**
     * @return \Dive\Relation\Relation[]
     */
    public function getRelations()
    {
        return $this->relations;
    }


    /**
     * @param string $name
     * @return bool
     */
    public function hasRelation($name)
    {
        return isset($this->relations[$name]);
    }


    /**
     * @param string $name
     * @return \Dive\Relation\Relation
     * @throws TableException
     */
    public function getRelation($name)
    {
        $this->throwExceptionIfRelationNotExists($name);
        return $this->relations[$name];
    }


    /**
     * @param  string $alias
     * @return Query
     */
    public function createQuery($alias = 'a')
    {
        $from = $this->getTableName() . ' ' . $alias;
        $query = new Query($this->recordManager);
        $query->from($from);
        return $query;
    }


//    public function count()
//    {
//        return $this->createQuery()->count();
//    }
//
//
//    private function clearRelationReferences()
//    {
//        foreach ($this->relations as $relation) {
//            $relation->clearReferences();
//        }
//    }
//
//
//    public function clearRepository()
//    {
//        $this->clearRelationReferences();
//        $this->getRepository()->clear();
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
