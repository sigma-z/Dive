<?php
/*
 * This file is part of the Dive ORM framework.
 * (c) Steffen Zeidler <sigma_z@sigma-scripts.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dive;

use Dive\Collection\RecordCollection;
use Dive\Record\FieldValueChangeEvent;
use Dive\Record\RecordException;
use Dive\Relation\Relation;

/**
 * Representing a database table row as an object.
 *
 * @package Dive
 * @author Steffen Zeidler <sigma_z@sigma-scripts.de>
 * Date: 30.01.13
 *
 * TODO!!!!!
 * Should we keep the identifier values in a special array??
 * What should happen with the record references, if identifier values had been changed??
 * When should a record be marked as modified??
 *   case: new record - no data changed                         -> not modified
 *   case: new record - data changed                            -> if data differs from defaults, than it's modified    // different from implementation
 *   case: record exists, not/partial loaded - no data changed  -> not modified
 *   case: record exists, not/partial loaded - data changed     -> if loaded data, has been changed, than it's modified
 *   case: record exists, fully loaded - no data changed        -> not modified
 *   case: record exists, fully loaded - data changed           -> modified
 * Should we handle partial loaded records? How to mark fields as not loaded, yet?
 * UnitOfWork for performing saves and deletes?
 *   Idea:
 *     - RecordManager should hold a UnitOfWork, which handles changes provided by the object graph.
 *     - UnitOfWork should calculate by iterating through the object graphs, if records has to been inserted,
 *       updated, and deleted.
 */
class Record
{

    const NEW_RECORD_ID_MARK = '_';
    const COMPOSITE_ID_SEPARATOR = '|';
    const FROM_ARRAY_EXISTS_KEY = '_exists_';

    /** events */
    const EVENT_PRE_FIELD_VALUE_CHANGE = 'Dive.Record.preFieldValueChange';
    const EVENT_POST_FIELD_VALUE_CHANGE = 'Dive.Record.postFieldValueChange';


    /** @var Table */
    protected $_table;

    /** @var array */
    protected $_data = array();

    /** @var array */
    protected $_mappedValues = array();

    /** @var array */
    protected $_modifiedFields = array();

    /** @var RecordCollection */
    protected $_resultCollection;

    /** @var bool */
    protected $_exists = false;


    /**
     * @param Table $table
     * @param bool  $exists
     */
    public function __construct(Table $table, $exists = false)
    {
        $this->_table = $table;
        $this->_exists = $exists;
    }


    /**
     * @return Table
     */
    public function getTable()
    {
        return $this->_table;
    }


    /**
     * @return RecordManager
     */
    public function getRecordManager()
    {
        return $this->_table->getRecordManager();
    }


    /**
     * @return Event\DispatcherInterface
     */
    public function getEventDispatcher()
    {
        return $this->_table->getEventDispatcher();
    }


    /**
     * checks, if record exists or not
     *
     * @return bool
     */
    public function exists()
    {
        return $this->_exists;
    }


    /**
     * @param  string $relationName
     * @return bool
     */
    public function hasTableRelation($relationName)
    {
        return $this->_table->hasRelation($relationName);
    }


    /**
     * @param  string $relationName
     * @return Relation
     */
    public function getTableRelation($relationName)
    {
        return $this->_table->getRelation($relationName);
    }


    /**
     * @return Relation[]
     */
    public function getTableRelations()
    {
        return $this->_table->getRelations();
    }


    /**
     * @param string $relationName
     * @return null|RecordCollection|Record[]|Record
     */
    public function getOriginalReference($relationName)
    {
        $relation = $this->_table->getRelation($relationName);
        return $relation->getOriginalReferencedIds($this, $relationName);
    }


    /**
     * Sets record data
     * NOTE: setData does not change record modified state
     *
     * @param array $data
     */
    public function setData(array $data)
    {
        $fields = $this->_table->getFields();
        foreach ($fields as $field => $def) {
            if (isset($data[$field])) {
                $this->_data[$field] = $data[$field];
            }
            else if (array_key_exists($field, $data)) {
                $this->_data[$field] = null;
            }
            else {
                $this->_data[$field] = isset($def['default']) ? $def['default'] : null;
            }
        }
    }


    /**
     * Gets record data
     *
     * @return array
     */
    public function getData()
    {
        return $this->_data;
    }


    /**
     * @return array|string
     */
    public function getIdentifier()
    {
        $identifier = $this->_table->getIdentifier();
        if (!is_array($identifier)) {
            $identifier = array($identifier);
        }

        $idValues = array();
        foreach ($identifier as $fieldName) {
            $idValue = $this->get($fieldName);
            if (null === $idValue) {
                return null;
            }
            $idValues[$fieldName] = $idValue;
        }
        return count($idValues) == 1 ? $idValues[$identifier[0]] : $idValues;
    }


    /**
     * @return string
     */
    public function getIdentifierAsString()
    {
        $identifier = $this->getIdentifier();
        if (is_array($identifier)) {
            return implode(self::COMPOSITE_ID_SEPARATOR, $identifier);
        }
        return (string)$identifier;
    }


    /**
     * @return string
     */
    public function getOid()
    {
        return spl_object_hash($this);
    }


    /**
     * @return string
     */
    public function getInternalId()
    {
        $id = '';
        if ($this->exists()) {
            $id = $this->getIdentifierAsString();
        }
        if (empty($id)) {
            $id = self::NEW_RECORD_ID_MARK . $this->getOid();
        }
        return $id;
    }


    /**
     * @param  array|string $identifier
     * @throws Record\RecordException
     */
    public function assignIdentifier($identifier)
    {
        $identifierFields = $this->_table->getIdentifierAsArray();
        if (!is_array($identifier)) {
            $identifier = array($identifierFields[0] => $identifier);
        }
        if (count($identifier) != count($identifierFields)) {
            throw new RecordException(
                "Identifier '"
                    . implode(self::COMPOSITE_ID_SEPARATOR, $identifier)
                    . "' does not match table identifier!"
            );
        }

        $oldIdentifier = $this->getInternalId();
        foreach ($identifier as $fieldName => $id) {
            $this->_data[$fieldName] = $id;
        }
        $this->_modifiedFields = array();
        $this->_exists = true;

        $relations = $this->_table->getRelations();
        foreach ($relations as $relationName => $relation) {
            if ($relation->isOwningSide($relationName)) {
                $relation->updateRecordIdentifier($this, $oldIdentifier);
            }
        }
    }


    /**
     * @param  string $name
     * @return \Dive\Collection\RecordCollection|Record|null|mixed|string
     */
    public function get($name)
    {
        $this->_table->throwExceptionIfFieldOrRelationNotExists($name);

        if ($this->_table->hasField($name)) {
            if (array_key_exists($name, $this->_data)) {
                return $this->_data[$name];
            }
            return $this->_table->getFieldDefaultValue($name);
        }

        if ($this->_table->hasRelation($name)) {
            return $this->_table->getReferenceFor($this, $name);
        }

        return null;
    }


    /**
     * @param  string $name
     * @return \Dive\Collection\RecordCollection|Record|null|mixed|string
     */
    public function __get($name)
    {
        return $this->get($name);
    }


    /**
     * @param string                                                     $name
     * @param \Dive\Collection\RecordCollection|Record|null|mixed|string $value
     */
    public function set($name, $value)
    {
        $this->_table->throwExceptionIfFieldOrRelationNotExists($name);

        if ($this->_table->hasField($name)) {
            $this->setFieldValue($name, $value);
        }

        if ($this->_table->hasRelation($name)) {
            $this->_table->setReferenceFor($this, $name, $value);
        }
    }


    /**
     * TODO how to handle boolean fields?
     *
     * @param string                                                      $name
     * @param \Dive\Collection\RecordCollection|Record|null|mixed|string  $value
     */
    public function __set($name, $value)
    {
        $this->set($name, $value);
    }


    /**
     * @param string $fieldName
     * @param mixed  $value
     */
    protected function setFieldValue($fieldName, $value)
    {
        $actualValue = $this->get($fieldName);

        $fieldValueChangeEvent = new FieldValueChangeEvent($this, $fieldName, $value, $actualValue);
        $this->getEventDispatcher()->dispatch(self::EVENT_PRE_FIELD_VALUE_CHANGE, $fieldValueChangeEvent);
        if ($fieldValueChangeEvent->isPropagationStopped()) {
            return;
        }

        if ($value != $actualValue) {
            $fieldIsModified = array_key_exists($fieldName, $this->_modifiedFields);
            if ($fieldIsModified && $this->_modifiedFields[$fieldName] == $value) {
                unset($this->_modifiedFields[$fieldName]);
            }
            else if (!$fieldIsModified) {
                $this->_modifiedFields[$fieldName] = $actualValue;
            }
            $this->_data[$fieldName] = $value;
        }
        $this->handleOwningFieldRelation($fieldName);

        $fieldValueChangeEvent = new FieldValueChangeEvent($this, $fieldName, $value, $actualValue);
        $this->getEventDispatcher()->dispatch(self::EVENT_POST_FIELD_VALUE_CHANGE, $fieldValueChangeEvent);
    }


    /**
     * @param string $fieldName
     */
    private function handleOwningFieldRelation($fieldName)
    {
        $referencedRelations = $this->_table->getReferencedRelationsIndexedByOwningField();
        if (isset($referencedRelations[$fieldName])) {
            $oldValue = $this->getModifiedFieldValue($fieldName);
            $newValue = $this->_data[$fieldName];
            $referencedRelations[$fieldName]->updateOwningReferenceByForeignKey($this, $newValue, $oldValue);
        }
    }


    /**
     * @param string $name
     * @param mixed  $value
     */
    public function mapValue($name, $value)
    {
        $this->_mappedValues[$name] = $value;
    }


    /**+
     * @param  string $name
     * @return bool
     */
    public function hasMappedValue($name)
    {
        return array_key_exists($name, $this->_mappedValues);
    }


    /**
     * @param  string $name
     * @return mixed
     * @throws Record\RecordException
     */
    public function getMappedValue($name)
    {
        if ($this->hasMappedValue($name)) {
            return $this->_mappedValues[$name];
        }
        throw new Record\RecordException("$name is not a mapped field!");
    }


    /**
     * @return bool
     */
    public function isModified()
    {
        return !empty($this->_modifiedFields);
    }


    /**
     * @param  string $fieldName
     * @return bool
     */
    public function isFieldModified($fieldName)
    {
        $this->_table->throwExceptionIfFieldNotExists($fieldName);
        return array_key_exists($fieldName, $this->_modifiedFields);
    }


    /**
     * @return array
     */
    public function getModifiedFields()
    {
        return $this->_modifiedFields;
    }


    /**
     * Gets modified field value
     *
     * @param  string $fieldName
     * @return bool
     */
    public function getModifiedFieldValue($fieldName)
    {
        if ($this->isFieldModified($fieldName)) {
            return $this->_modifiedFields[$fieldName];
        }
        return false;
    }


    /**
     * @deprecated use RecordManager->saveRecord($record)->commit() instead
     */
    public function save()
    {
        $rm = $this->getRecordManager();
        $rm->save($this)->commit();
    }


    /**
     * @deprecated use RecordManager->deleteRecord($record)->commit() instead
     */
    public function delete()
    {
        $rm = $this->getRecordManager();
        $rm->delete($this)->commit();
    }


    /**
     * @param RecordCollection $resultCollection
     */
    public function setResultCollection(RecordCollection $resultCollection)
    {
        $this->_resultCollection = $resultCollection;
    }


    /**
     * @return RecordCollection
     */
    public function getResultCollection()
    {
        return $this->_resultCollection;
    }


    public function refresh()
    {
        if (!$this->_exists) {
            return;
        }

        $identifier = $this->getIdentifier();
        $values = $this->_table->findByPk($identifier, RecordManager::FETCH_SINGLE_ARRAY);

        if ($values !== false) {
            $this->_data = $values;
            $this->_modifiedFields = array();
        }
        else {
            $this->_exists = false;
        }
    }


    /**
     * @param  bool  $deep
     * @param  bool  $withMappedFields
     * @param  array $visited
     * @return array
     */
    public function toArray($deep = true, $withMappedFields = false, array &$visited = array())
    {
        $oid = $this->getOid();
        if (in_array($oid, $visited)) {
            return false;
        }

        $visited[] = $oid;
        $data = array();
        if ($withMappedFields) {
            $data = $this->_mappedValues;
        }
        $data = array_merge($data, $this->_data);

        if ($deep) {
            $references = $this->getReferencesAsArray($withMappedFields, $visited);
            $data += $references;
        }

        if ($this->exists()) {
            $data[self::FROM_ARRAY_EXISTS_KEY] = true;
        }

        return $data;
    }


    /**
     * @param array $data
     * @param bool  $deep
     * @param bool  $mapVirtualFields
     */
    public function fromArray(array $data, $deep = true, $mapVirtualFields = false)
    {
        $exists = false;
        $rm = $this->getRecordManager();
        foreach ($data as $name => $value) {
            if ($this->_table->hasField($name)) {
                $this->set($name, $value);
            }
            else if ($this->_table->hasRelation($name)) {
                if ($deep) {
                    $relation = $this->_table->getRelation($name);
                    $relatedTable = $relation->getJoinTable($rm, $name);
                    if ($relation->isOneToMany() && $relation->isOwningSide($name)) {
                        $collection = new RecordCollection($relatedTable, $this, $relation);
                        foreach ($value as $relatedData) {
                            $relatedRecord = $relatedTable->createRecord();
                            $relatedRecord->fromArray($relatedData, $deep, $mapVirtualFields);
                            $collection[$relatedRecord->getInternalId()] = $relatedRecord;
                        }
                        $this->set($name, $collection);
                    }
                    else {
                        $relatedRecord = $relatedTable->createRecord();
                        $relatedRecord->fromArray($value, $deep, $mapVirtualFields);
                        $this->set($name, $relatedRecord);
                    }
                }
            }
            else if ($name == self::FROM_ARRAY_EXISTS_KEY) {
                $exists = true;
            }
            else if ($mapVirtualFields) {
                $this->mapValue($name, $value);
            }
        }

        if ($exists) {
            $this->_exists = true;
            $this->refresh();
        }
    }


    /**
     * @param  string $withMappedFields
     * @param  array  $visited
     * @return array
     */
    private function getReferencesAsArray($withMappedFields, array &$visited)
    {
        $references = array();
        $tableName = $this->_table->getTableName();
        $relations = $this->_table->getRelations();
        foreach ($relations as $relation) {
            $refTable = $relation->getReferencedTable();
            $owningAlias = $relation->getOwningAlias();
            if ($tableName == $refTable && !isset($references[$owningAlias])) {
                $reference = $this->getReferenceAsArray($relation, $owningAlias, $withMappedFields, $visited);
                if ($reference !== false) {
                    $references[$owningAlias] = $reference;
                }
            }

            $owningTable = $relation->getOwningTable();
            $refAlias = $relation->getReferencedAlias();
            if ($tableName == $owningTable && !isset($references[$refAlias])) {
                $reference = $this->getReferenceAsArray($relation, $refAlias, $withMappedFields, $visited);
                if ($reference !== false && !isset($references[$refAlias])) {
                    $references[$refAlias] = $reference;
                }
            }
        }
        return $references;
    }


    /**
     * @param  Relation $relation
     * @param  string   $relationName
     * @param  bool     $withMappedFields
     * @param  array    $visited
     * @return array|bool
     */
    private function getReferenceAsArray(Relation $relation, $relationName, $withMappedFields, array &$visited)
    {
        if ($relation->hasReferenceLoadedFor($this, $relationName)) {
            /** @var Record|Record[]|RecordCollection $related */
            $related = $this->get($relationName);
            if ($relation->isOneToMany() && $relation->isOwningSide($relationName)) {
                $reference = array();
                foreach ($related as $relatedRecord) {
                    $reference[] = $relatedRecord->toArray(true, $withMappedFields, $visited);
                }
                return $reference;
            }
            else {
                return $related->toArray(true, $withMappedFields, $visited);
            }
        }
        return false;
    }


    /**
     * @param array $references
     */
    public function loadReferences(array $references)
    {
        foreach ($references as $relationName => $relatedReferences) {
            /** @var RecordCollection|Record[]|Record $related */
            $related = $this->_table->getRelation($relationName)->getReferenceFor($this, $relationName);
            if (is_array($relatedReferences)) {
                if ($related instanceof RecordCollection) {
                    foreach ($related as $relatedRecord) {
                        $relatedRecord->loadReferences($relatedReferences);
                    }
                }
                else if ($related instanceof Record) {
                    $related->loadReferences($relatedReferences);
                }
            }
        }
    }

}
