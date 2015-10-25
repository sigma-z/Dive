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
use Dive\Relation\Relation;
use Dive\Validation\ErrorStack;

/**
 * @author Steffen Zeidler <sigma_z@sigma-scripts.de>
 * @date   24.10.2015
 */
class Model implements Record
{

    /** @var ConcreteRecord */
    protected $record;


    /**
     * Model constructor.
     *
     * @param ConcreteRecord $record
     */
    public function __construct(ConcreteRecord $record)
    {
        $this->record = $record;
    }


    /**
     * @return ConcreteRecord
     */
    public function getRecord()
    {
        return $this->record;
    }


    ///**
    // * @param string $name
    // * @param array  $arguments
    // * @return mixed
    // */
    //public function __call($name, $arguments)
    //{
    //    return call_user_func_array(array($this->record, $name), $arguments);
    //}
    //
    //


    /**
     * @return Table
     */
    public function getTable()
    {
        return $this->record->getTable();
    }


    /**
     * @return RecordManager
     */
    public function getRecordManager()
    {
        return $this->record->getRecordManager();
    }


    /**
     * @return Event\EventDispatcherInterface
     */
    public function getEventDispatcher()
    {
        return $this->record->getEventDispatcher();
    }


    /**
     * checks, if record exists or not
     *
     * @return bool
     */
    public function exists()
    {
        return $this->record->exists();
    }


    /**
     * @param  string $relationName
     * @return bool
     */
    public function hasTableRelation($relationName)
    {
        return $this->record->hasTableRelation($relationName);
    }


    /**
     * @param  string $relationName
     * @return Relation
     */
    public function getTableRelation($relationName)
    {
        return $this->record->getTableRelation($relationName);
    }


    /**
     * @return Relation[]
     */
    public function getTableRelations()
    {
        return $this->record->getTableRelations();
    }


    /**
     * @return array|string|null
     */
    public function getIdentifier()
    {
        return $this->record->getIdentifier();
    }


    /**
     * @return string|null
     */
    public function getIdentifierAsString()
    {
        return $this->record->getIdentifierAsString();
    }


    /**
     * @return null|string
     */
    public function getStoredIdentifierAsString()
    {
        return $this->record->getStoredIdentifierAsString();
    }


    /**
     * @return array|null
     */
    public function getIdentifierFieldIndexed()
    {
        return $this->record->getIdentifierFieldIndexed();
    }


    /**
     * @return string
     */
    public function getOid()
    {
        return $this->record->getOid();
    }


    /**
     * @return string
     */
    public function getInternalId()
    {
        return $this->record->getInternalId();
    }


    /**
     * @param  array|string $identifier
     * @param  array|string $oldIdentifier
     * @throws Record\RecordException
     */
    public function assignIdentifier($identifier, $oldIdentifier = null)
    {
        $this->record->assignIdentifier($identifier, $oldIdentifier);
    }


    /**
     * @param  string $name
     * @throws Table\TableException
     * @return \Dive\Collection\RecordCollection|Record|null|mixed|string
     */
    public function __get($name)
    {
        return $this->record->get($name);
    }

    /**
     * @param  string $name
     * @throws Table\TableException
     * @return \Dive\Collection\RecordCollection|Record|null|mixed|string
     */
    public function get($name)
    {
        return $this->record->get($name);
    }


    /**
     * @param  string                                                     $name
     * @param  \Dive\Collection\RecordCollection|Record|null|mixed|string $value
     * @throws Table\TableException
     */
    public function __set($name, $value)
    {
        $this->record->set($name, $value);
    }


    /**
     * @param  string                                                     $name
     * @param  \Dive\Collection\RecordCollection|Record|null|mixed|string $value
     * @throws Table\TableException
     */
    public function set($name, $value)
    {
        $this->record->set($name, $value);
    }


    /**
     * @param string $name
     * @param mixed  $value
     */
    public function mapValue($name, $value)
    {
        $this->record->mapValue($name, $value);
    }


    /**+
     * @param  string $name
     * @return bool
     */
    public function hasMappedValue($name)
    {
        return $this->record->hasMappedValue($name);
    }


    /**
     * @param  string $name
     * @return mixed
     * @throws Record\RecordException
     */
    public function getMappedValue($name)
    {
        return $this->record->getMappedValue($name);
    }


    /**
     * @return bool
     */
    public function isModified()
    {
        return $this->record->isModified();
    }


    /**
     * @param  string $fieldName
     * @return bool
     */
    public function isFieldModified($fieldName)
    {
        return $this->record->isFieldModified($fieldName);
    }


    /**
     * @return array
     */
    public function getModifiedFields()
    {
        return $this->record->getModifiedFields();
    }


    /**
     * Gets modified field value
     *
     * @param  string $fieldName
     * @return bool
     */
    public function getModifiedFieldValue($fieldName)
    {
        return $this->record->getModifiedFieldValue($fieldName);
    }


    /**
     * @param  string $fieldName
     * @return mixed|null|string
     */
    public function getOriginalFieldValue($fieldName)
    {
        return $this->record->getOriginalFieldValue($fieldName);
    }


    /**
     * @return string
     */
    public function __toString()
    {
        return $this->record->__toString();
    }


    public function refresh()
    {
        $this->record->refresh();
    }


    /**
     * @return ErrorStack
     */
    public function getErrorStack()
    {
        return $this->record->getErrorStack();
    }


    /**
     * @param  bool  $deep
     * @param  bool  $withMappedFields
     * @param  array $visited
     * @return array|bool
     */
    public function toArray($deep = true, $withMappedFields = false, array &$visited = array())
    {
        return $this->record->toArray($deep, $withMappedFields, $visited);
    }


    /**
     * @param array $data
     * @param bool  $deep
     * @param bool  $mapVirtualFields
     */
    public function fromArray(array $data, $deep = true, $mapVirtualFields = false)
    {
        $this->record->fromArray($data, $deep, $mapVirtualFields);
    }


    /**
     * @param array $references
     */
    public function loadReferences(array $references)
    {
        $this->record->loadReferences($references);
    }


    public function preUpdate()
    {
        $this->record->preUpdate();
    }


    public function postUpdate()
    {
        $this->record->postUpdate();
    }


    public function preSave()
    {
        $this->record->preSave();
    }


    public function postSave()
    {
        $this->record->postSave();
    }


    public function preInsert()
    {
        $this->record->preInsert();
    }


    public function postInsert()
    {
        $this->record->postInsert();
    }


    public function preDelete()
    {
        $this->record->preDelete();
    }


    public function postDelete()
    {
        $this->record->postDelete();
    }


    /**
     * Sets record data
     * NOTE: setData does not change record modified state
     *
     * @param array $data
     */
    public function setData(array $data)
    {
        $this->record->setData($data);
    }


    /**
     * Gets record data
     *
     * @return array
     */
    public function getData()
    {
        return $this->record->getData();
    }


    /**
     * @param RecordCollection $resultCollection
     */
    public function setResultCollection(RecordCollection $resultCollection)
    {
        $this->record->setResultCollection($resultCollection);
    }


    /**
     * @return RecordCollection
     */
    public function getResultCollection()
    {
        return $this->record->getResultCollection();
    }

}
