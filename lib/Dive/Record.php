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
 * RecordManager/UnitOfWork/ChangeSet for performing saves and deletes?
 *   Idea:
 *     - RecordManager should hold a UnitOfWork, which handles changes provided by the object graph.
 *     - ChangeSet should calculate by iterating through the object graph, if records has to been inserted,
 *       updated, and deleted.
 */

namespace Dive;


use Dive\Collection\RecordCollection;
use Dive\Record\RecordException;
use Dive\Relation\Relation;

class Record
{

    const NEW_RECORD_ID_MARK = "_";
    const COMPOSITE_ID_SEPARATOR = '|';


    /**
     * @var array
     */
    private $_identifiers = array();
    /**
     * @var Table
     */
    protected $_table;
    /**
     * @var array
     */
    protected $_data = array();
    /**
     * @var array
     */
    protected $_mappedValues = array();
    /**
     * @var array
     */
    protected $_modifiedFields = array();
    /**
     * @var RecordCollection
     */
    protected $_resultCollection;
    /**
     * @var bool
     */
    protected $_exists = false;


    /**
     * @param Table $table
     * @param array $data
     * @param bool  $exists
     */
    public function __construct(Table $table, array $data = array(), $exists = false)
    {
        $this->_table = $table;
        $this->_exists = $exists;
        $this->setData($data);
        //$this->fromArray($data);
        // TODO It violates law of demeter and does work in the constructor! Does anyone have an idea, how to do better?
        $table->getRepository()->add($this);
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
     * checks, if record exists or not
     *
     * @return bool
     */
    public function exists()
    {
        return $this->_exists;
    }


    /**
     * Sets record data
     * NOTE: setData does not change record modified state
     *
     * @param array $data
     */
    public function setData(array $data)
    {
        foreach ($this->_table->getFields() as $field => $def) {
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
            $identifier = implode(self::COMPOSITE_ID_SEPARATOR, $identifier);
        }
        return $identifier;
    }


    public function getOid()
    {
        return spl_object_hash($this);
    }


    public function getIntId()
    {
        return $this->getInternalIdentifier();
    }


    public function getInternalIdentifier()
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
                    .  "' does not match table identifier!"
            );
        }

        foreach ($identifier as $fieldName => $id) {
            $this->_data[$fieldName] = $id;
        }
        $this->_modifiedFields = array();
        $this->_exists = true;
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
            $actualValue = $this->get($name);
            if ($value != $actualValue) {
                $fieldIsModified = array_key_exists($name, $this->_modifiedFields);
                if ($fieldIsModified && $this->_modifiedFields[$name] == $value) {
                    unset($this->_modifiedFields[$name]);
                }
                else if (!$fieldIsModified) {
                    $this->_modifiedFields[$name] = $actualValue;
                }
                $this->_data[$name] = $value;
            }

            $this->handleOwningFieldRelation($name);
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


    private function handleOwningFieldRelation($fieldName)
    {
        $owningRelations = $this->_table->getOwningRelationsIndexedByOwnerField();
        if (isset($owningRelations[$fieldName])) {
            $oldValue = $this->getModifiedFieldValue($fieldName);
            $newValue = $this->_data[$fieldName];
            $owningRelations[$fieldName]->updateOwningReferenceByForeignKey($this, $newValue, $oldValue);
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
     * Persists the record
     */
    public function save()
    {
        $rm = $this->_table->getRecordManager();
        $rm->saveRecord($this);
    }


    /**
     * Removes the record
     */
    public function delete()
    {
        $rm = $this->_table->getRecordManager();
        $rm->deleteRecord($this);
        $this->_exists = false;
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


    public function toArray($deep = true, $withMappedFields = false, array &$visited = array())
    {
        $data = array();
        if ($withMappedFields) {
            $data = $this->_mappedValues;
        }
        $data = array_merge($data, $this->_data);

        if ($deep) {
            $references = $this->getReferencesAsArray($withMappedFields, $visited);
            $data += $references;
        }

        return $data;
    }


    public function fromArray(array $data, $deep = true, $mapVirtualFields = false)
    {
        $rm = $this->getRecordManager();
        foreach ($data as $name => $value) {
            if ($this->_table->hasField($name)) {
                $this->set($name, $value);
            }
            else if ($this->_table->hasRelation($name)) {
                if ($deep) {
                    $relation = $this->_table->getRelation($name);
                    $relatedTable = $relation->getJoinTable($rm, $name);
                    if ($relation->isOneToMany() && !$relation->isOwningSide($name)) {
                        $collection = new RecordCollection($relatedTable, $this, $relation);
                        foreach ($value as $relatedData) {
                            $relatedRecord = $relatedTable->createRecord();
                            $relatedRecord->fromArray($relatedData, $deep, $mapVirtualFields);
                            $collection[] = $relatedRecord;
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
            else if ($mapVirtualFields) {
                $this->mapValue($name, $value);
            }
        }
    }


    private function getReferencesAsArray($withMappedFields, array &$visited)
    {
        $references = array();
        $tableName = $this->_table->getTableName();
        $relations = $this->_table->getRelations();
        foreach ($relations as $relation) {
            $ownerTable = $relation->getOwnerTable();
            if ($tableName == $ownerTable) {
                $ownerAlias = $relation->getOwnerAlias();
                $reference = $this->getReferenceAsArray($relation, $ownerAlias, $withMappedFields, $visited);
                if ($reference !== false) {
                    $references[$ownerAlias] = $reference;
                }
            }

            $refTable = $relation->getReferencedTable();
            if ($tableName == $refTable) {
                $refAlias = $relation->getReferencedAlias();
                $reference = $this->getReferenceAsArray($relation, $refAlias, $withMappedFields, $visited);
                if ($reference !== false) {
                    $references[$refAlias] = $reference;
                }
            }
        }
        return $references;
    }


    private function getReferenceAsArray(Relation $relation, $relationAlias, $withMappedFields, array &$visited)
    {
        if ($relation->hasReferenceFor($this, $relationAlias)) {
            /** @var Record|Record[]|RecordCollection $related */
            $related = $this->get($relationAlias);
            if ($relation->isOneToMany() && !$relation->isOwningSide($relationAlias)) {
                $reference = array();
                foreach ($related as $relatedRecord) {
                    if (!$this->visited($relatedRecord, $visited)) {
                        $visited[] = $relatedRecord->getOid();
                        $reference[] = $relatedRecord->toArray(true, $withMappedFields, $visited);
                    }
                }
                return $reference;
            }
            else {
                $visited[] = $related->getOid();
                return $related->toArray(true, $withMappedFields, $visited);
            }
        }
        return false;
    }


    /**
     *
     *
     * @param  Record $relatedRecord
     * @param  array  $visited
     * @return bool
     */
    private function visited(Record $relatedRecord = null, array $visited = array())
    {
        if (!$relatedRecord) {
            return true;
        }
        return in_array($relatedRecord->getOid(), $visited);
    }

}
