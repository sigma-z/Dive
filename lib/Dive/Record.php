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
interface Record
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


    /**
     * @return Table
     */
    public function getTable();


    /**
     * @return RecordManager
     */
    public function getRecordManager();


    /**
     * @return Event\EventDispatcherInterface
     */
    public function getEventDispatcher();


    /**
     * checks, if record exists or not
     *
     * @return bool
     */
    public function exists();


    /**
     * @param  string $relationName
     * @return bool
     */
    public function hasTableRelation($relationName);


    /**
     * @param  string $relationName
     * @return Relation
     */
    public function getTableRelation($relationName);


    /**
     * @return Relation[]
     */
    public function getTableRelations();


    /**
     * @return array|string|null
     */
    public function getIdentifier();


    /**
     * @return string|null
     */
    public function getIdentifierAsString();


    /**
     * @return null|string
     */
    public function getStoredIdentifierAsString();


    /**
     * @return array|null
     */
    public function getIdentifierFieldIndexed();


    /**
     * @return string
     */
    public function getOid();


    /**
     * @return string
     */
    public function getInternalId();


    /**
     * @param  array|string $identifier
     * @param  array|string $oldIdentifier
     * @throws Record\RecordException
     */
    public function assignIdentifier($identifier, $oldIdentifier = null);


    /**
     * @param  string $name
     * @throws Table\TableException
     * @return \Dive\Collection\RecordCollection|Record|null|mixed|string
     */
    public function get($name);


    /**
     * @param  string                                                     $name
     * @param  \Dive\Collection\RecordCollection|Record|null|mixed|string $value
     * @throws Table\TableException
     */
    public function set($name, $value);


    /**
     * @param string $name
     * @param mixed  $value
     */
    public function mapValue($name, $value);


    /**+
     * @param  string $name
     * @return bool
     */
    public function hasMappedValue($name);


    /**
     * @param  string $name
     * @return mixed
     * @throws Record\RecordException
     */
    public function getMappedValue($name);


    /**
     * @return bool
     */
    public function isModified();


    /**
     * @param  string $fieldName
     * @return bool
     */
    public function isFieldModified($fieldName);


    /**
     * @return array
     */
    public function getModifiedFields();


    /**
     * Gets modified field value
     *
     * @param  string $fieldName
     * @return bool
     */
    public function getModifiedFieldValue($fieldName);


    /**
     * @param  string $fieldName
     * @return mixed|null|string
     */
    public function getOriginalFieldValue($fieldName);


    /**
     * @return string
     */
    public function __toString();


    public function refresh();


    /**
     * @return ErrorStack
     */
    public function getErrorStack();


    /**
     * @param  bool  $deep
     * @param  bool  $withMappedFields
     * @param  array $visited
     * @return array|bool
     */
    public function toArray($deep = true, $withMappedFields = false, array &$visited = array());


    /**
     * @param array $data
     * @param bool  $deep
     * @param bool  $mapVirtualFields
     */
    public function fromArray(array $data, $deep = true, $mapVirtualFields = false);


    /**
     * @param array $references
     */
    public function loadReferences(array $references);


    public function preUpdate();


    public function postUpdate();


    public function preSave();


    public function postSave();


    public function preInsert();


    public function postInsert();


    public function preDelete();


    public function postDelete();


    /**
     * Sets record data
     * NOTE: setData does not change record modified state
     *
     * @param array $data
     */
    public function setData(array $data);


    /**
     * Gets record data
     *
     * @return array
     */
    public function getData();


    /**
     * @param RecordCollection $resultCollection
     */
    public function setResultCollection(RecordCollection $resultCollection);


    /**
     * @return RecordCollection
     */
    public function getResultCollection();
}
