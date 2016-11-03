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
use Dive\Record\RecordPropertyEvent;
use Dive\Relation\Relation;
use Dive\Validation\ErrorStack;

/**
 * Representing a database table row as an object.
 *
 * @package Dive
 * @author Steffen Zeidler <sigma_z@sigma-scripts.de>
 * Date: 30.01.13
 *
 * TODO!!!!!
 * Should we keep the identifier values in a special array??
 * When should a record be marked as modified??
 *   case: new record - no data changed                         -> not modified
 *   case: new record - data changed                            -> if data differs from defaults, than it's modified    // different from implementation
 *   case: record exists, not/partial loaded - no data changed  -> not modified
 *   case: record exists, not/partial loaded - data changed     -> if loaded data, has been changed, than it's modified
 *   case: record exists, fully loaded - no data changed        -> not modified
 *   case: record exists, fully loaded - data changed           -> modified
 */
class Record
{

    const NEW_RECORD_ID_MARK = '_';
    const COMPOSITE_ID_SEPARATOR = '|';
    const FROM_ARRAY_EXISTS_KEY = '_exists_';

    /** events */
    const EVENT_PRE_FIELD_VALUE_CHANGE = 'Dive.Record.preFieldValueChange';
    const EVENT_POST_FIELD_VALUE_CHANGE = 'Dive.Record.postFieldValueChange';
    const EVENT_NON_EXISTING_PROPERTY_SET = 'Dive.Record.nonExistingPropertySet';
    const EVENT_NON_EXISTING_PROPERTY_GET = 'Dive.Record.nonExistingPropertyGet';
    const EVENT_PRE_SAVE = 'Dive.Record.preSave';
    const EVENT_POST_SAVE = 'Dive.Record.postSave';
    const EVENT_PRE_INSERT = 'Dive.Record.preInsert';
    const EVENT_POST_INSERT = 'Dive.Record.postInsert';
    const EVENT_PRE_DELETE = 'Dive.Record.preDelete';
    const EVENT_POST_DELETE = 'Dive.Record.postDelete';
    const EVENT_PRE_UPDATE = 'Dive.Record.preUpdate';
    const EVENT_POST_UPDATE = 'Dive.Record.postUpdate';


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

    /** @var ErrorStack */
    protected $_errorStack;


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
     * @return Event\EventDispatcherInterface
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
        foreach ($fields as $fieldName => $definition) {
            if (isset($data[$fieldName])) {
                $value = $data[$fieldName];
            }
            else if (array_key_exists($fieldName, $data)) {
                $value = null;
            }
            else {
                $value = isset($definition['default']) ? $definition['default'] : null;
            }

            $this->_data[$fieldName] = $value;
            $this->handleOwningFieldRelation($fieldName, null);
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
     * @return array|string|null
     */
    public function getIdentifier()
    {
        $identifier = $this->getIdentifierFieldIndexed();
        if ($identifier === null) {
            return null;
        }
        return $this->_table->hasCompositePrimaryKey() ? $identifier : current($identifier);
    }


    /**
     * @return string|null
     */
    public function getIdentifierAsString()
    {
        $identifier = $this->getIdentifierFieldIndexed();
        if ($identifier === null) {
            return null;
        }
        return implode(self::COMPOSITE_ID_SEPARATOR, $identifier);
    }


    /**
     * @return null|string
     */
    public function getStoredIdentifierAsString()
    {
        if ($this->isModified()) {
            $idFields = $this->_table->getIdentifierFields();
            $identifierValues = array();
            foreach ($idFields as $idField) {
                $identifierValues[$idField] = $this->isFieldModified($idField)
                    ? $this->getModifiedFieldValue($idField)
                    : $this->get($idField);
            }
            return $this->_table->getIdentifierAsString($identifierValues);
        }
        return $this->getIdentifierAsString();
    }


    /**
     * @return array|null
     */
    public function getIdentifierFieldIndexed()
    {
        return $this->_table->getIdentifierFieldValues($this->_data);
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
            $id = $this->getStoredIdentifierAsString();
        }
        if (empty($id)) {
            $id = self::NEW_RECORD_ID_MARK . $this->getOid();
        }
        return $id;
    }


    /**
     * @param  array|string $identifier
     * @param  array|string $oldIdentifier
     * @throws Record\RecordException
     */
    public function assignIdentifier($identifier, $oldIdentifier = null)
    {
        $identifierFields = $this->_table->getIdentifierFields();
        $identifier = array_combine($identifierFields, (array)$identifier);
        if (count($identifier) != count($identifierFields)) {
            throw new RecordException(
                "Identifier '"
                    . implode(self::COMPOSITE_ID_SEPARATOR, $identifier)
                    . "' does not match table identifier!"
            );
        }

        foreach ($identifier as $fieldName => $id) {
            $this->_data[$fieldName] = $id;
        }

        $newIdentifierAsString = implode(self::COMPOSITE_ID_SEPARATOR, $identifier);
        $oldIdentifierAsString = is_string($oldIdentifier) ? $oldIdentifier : implode(self::COMPOSITE_ID_SEPARATOR, $oldIdentifier);
        $relations = $this->_table->getRelations();
        foreach ($relations as $relationName => $relation) {
            $relation->updateRecordIdentifier($this, $relationName, $newIdentifierAsString, $oldIdentifierAsString);
        }

        $this->_modifiedFields = array();
        $this->_exists = true;

        $repository = $this->_table->getRepository();
        $repository->refreshIdentity($this, $oldIdentifierAsString);
    }


    /**
     * @param  string $name
     * @throws Table\TableException
     * @return \Dive\Collection\RecordCollection|Record|null|mixed|string
     */
    public function get($name)
    {
        if ($this->_table->hasField($name)) {
            if (array_key_exists($name, $this->_data)) {
                return $this->_data[$name];
            }
            return $this->_table->getFieldDefaultValue($name);
        }

        if ($this->_table->hasRelation($name)) {
            return $this->_table->getReferenceFor($this, $name);
        }

        $recordPropertyEvent = new RecordPropertyEvent($this, $name);
        $this->getEventDispatcher()->dispatch(self::EVENT_NON_EXISTING_PROPERTY_GET, $recordPropertyEvent);
        if ($recordPropertyEvent->isPropagationStopped()) {
            return $recordPropertyEvent->getValue();
        }

        throw $this->_table->getFieldOrRelationNotExistsException($name);
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
     * @param  string                                                     $name
     * @param  \Dive\Collection\RecordCollection|Record|null|mixed|string $value
     * @throws Table\TableException
     */
    public function set($name, $value)
    {
        if ($this->_table->hasField($name)) {
            $this->setFieldValue($name, $value);
            return;
        }
        if ($this->_table->hasRelation($name)) {
            $this->_table->setReferenceFor($this, $name, $value);
            return;
        }

        $recordPropertyEvent = new RecordPropertyEvent($this, $name, $value);
        $this->getEventDispatcher()->dispatch(self::EVENT_NON_EXISTING_PROPERTY_SET, $recordPropertyEvent);
        if ($recordPropertyEvent->isPropagationStopped()) {
            return;
        }

        throw $this->_table->getFieldOrRelationNotExistsException($name);
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
        $oldValue = $this->get($fieldName);

        $fieldValueChangeEvent = new FieldValueChangeEvent($this, $fieldName, $value, $oldValue);
        $this->getEventDispatcher()->dispatch(self::EVENT_PRE_FIELD_VALUE_CHANGE, $fieldValueChangeEvent);
        if ($fieldValueChangeEvent->isPropagationStopped()) {
            return;
        }

        if ($value != $oldValue) {
            $fieldIsModified = array_key_exists($fieldName, $this->_modifiedFields);
            if ($fieldIsModified && $this->_modifiedFields[$fieldName] == $value) {
                unset($this->_modifiedFields[$fieldName]);
            }
            else if (!$fieldIsModified) {
                $this->_modifiedFields[$fieldName] = $oldValue;
            }
            $this->_data[$fieldName] = $value;
        }
        $this->handleOwningFieldRelation($fieldName, $oldValue);

        $fieldValueChangeEvent = new FieldValueChangeEvent($this, $fieldName, $value, $oldValue);
        $this->getEventDispatcher()->dispatch(self::EVENT_POST_FIELD_VALUE_CHANGE, $fieldValueChangeEvent);
    }


    /**
     * @param string $fieldName
     * @param string $oldValue
     */
    private function handleOwningFieldRelation($fieldName, $oldValue)
    {
        $referencedRelations = $this->_table->getReferencedRelationsIndexedByOwningField();
        if (isset($referencedRelations[$fieldName])) {
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
     * @param  string $fieldName
     * @return mixed|null|string
     */
    public function getOriginalFieldValue($fieldName)
    {
        if ($this->isFieldModified($fieldName)) {
            return $this->getModifiedFieldValue($fieldName);
        }
        return $this->get($fieldName);
    }


    /**
     * @return string
     */
    public function __toString()
    {
        return $this->_table->getTableName() . ' id: ' . ($this->getIdentifierAsString() ?: $this->getInternalId());
    }


    /**
     * @deprecated use RecordManager->saveRecord($record)->commit() instead
     */
    public function save()
    {
        $rm = $this->getRecordManager();
        $rm->scheduleSave($this)->commit();
    }


    /**
     * @deprecated use RecordManager->deleteRecord($record)->commit() instead
     */
    public function delete()
    {
        $rm = $this->getRecordManager();
        $rm->scheduleDelete($this)->commit();
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
     * @return ErrorStack
     */
    public function getErrorStack()
    {
        if ($this->_errorStack === null) {
            $this->_errorStack = new ErrorStack();
        }
        return $this->_errorStack;
    }


    /**
     * @param  bool  $deep
     * @param  bool  $withMappedFields
     * @param  array $visited
     * @return array|bool
     */
    public function toArray($deep = true, $withMappedFields = false, array &$visited = array())
    {
        $oid = $this->getOid();
        if (in_array($oid, $visited, true)) {
            return false;
        }
        $visited[] = $oid;

        $data = $withMappedFields ? array_merge($this->_mappedValues, $this->_data) : $this->_data;
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
        $exists = isset($data[self::FROM_ARRAY_EXISTS_KEY]) && $data[self::FROM_ARRAY_EXISTS_KEY] === true;
        $relationReferences = array();
        foreach ($data as $name => $value) {
            if ($this->_table->hasField($name)) {
                $this->set($name, $value);
            }
            else if ($this->_table->hasRelation($name)) {
                if ($deep) {
                    $relationReferences[$name] = $value;
                }
            }
            else if ($mapVirtualFields) {
                $this->mapValue($name, $value);
            }
        }

        if ($exists) {
            $this->_exists = true;
            $this->refresh();
        }

        if ($relationReferences) {
            $this->setRelatedFromArray($relationReferences, $mapVirtualFields);
        }
    }


    /**
     * @param array $relationReferences
     * @param bool  $mapVirtualFields
     */
    private function setRelatedFromArray(array $relationReferences, $mapVirtualFields)
    {
        foreach ($relationReferences as $relationName => $related) {
            $relation = $this->_table->getRelation($relationName);
            if ($relation->isOneToMany() && $relation->isOwningSide($relationName)) {
                $this->setRelatedCollectionFromArray($related, $mapVirtualFields, $relationName);
            }
            else {
                $this->setRelatedRecordFromArray($related, $mapVirtualFields, $relationName);
            }
        }
    }


    /**
     * @param array  $related
     * @param bool   $mapVirtualFields
     * @param string $relationName
     */
    private function setRelatedCollectionFromArray(array $related, $mapVirtualFields, $relationName)
    {
        $relation = $this->getTableRelation($relationName);
        $rm = $this->getRecordManager();
        $relatedTableName = $relation->getJoinTableName($relationName);
        $collection = new RecordCollection($rm->getTable($relatedTableName), $this, $relation);
        foreach ($related as $data) {
            $relatedExists = isset($data[self::FROM_ARRAY_EXISTS_KEY]) && $data[self::FROM_ARRAY_EXISTS_KEY] === true;
            $relatedRecord = $rm->getOrCreateRecord($relatedTableName, $data, $relatedExists);
            $relatedRecord->fromArray($data, true, $mapVirtualFields);
            $collection[] = $relatedRecord;
        }
        $this->set($relationName, $collection);
    }


    /**
     * @param array  $related
     * @param bool   $mapVirtualFields
     * @param string $relationName
     */
    private function setRelatedRecordFromArray(array $related, $mapVirtualFields, $relationName)
    {
        $relation = $this->getTableRelation($relationName);
        $rm = $this->getRecordManager();
        $relatedTableName = $relation->getJoinTableName($relationName);
        $relatedExists = isset($related[self::FROM_ARRAY_EXISTS_KEY]) && $related[self::FROM_ARRAY_EXISTS_KEY] === true;
        $relatedRecord = $rm->getOrCreateRecord($relatedTableName, $related, $relatedExists);
        $relatedRecord->fromArray($related, true, $mapVirtualFields);
        $this->set($relationName, $relatedRecord);
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
        if (!$relation->hasReferenceLoadedFor($this, $relationName)) {
            return false;
        }

        /** @var Record|Record[]|RecordCollection $related */
        $related = $this->get($relationName);
        if ($relation->isOneToMany() && $relation->isOwningSide($relationName)) {
            $reference = array();
            foreach ($related as $relatedRecord) {
                $reference[] = $relatedRecord->toArray(true, $withMappedFields, $visited);
            }
            return $reference;
        }
        return $related->toArray(true, $withMappedFields, $visited);
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


    public function preUpdate()
    {
    }


    public function postUpdate()
    {
    }


    public function preSave()
    {
    }


    public function postSave()
    {
    }


    public function preInsert()
    {
    }


    public function postInsert()
    {
    }


    public function preDelete()
    {
    }


    public function postDelete()
    {
    }

}
