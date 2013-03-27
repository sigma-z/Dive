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


use Dive\Record\RecordException;

class Record
{

    const NEW_RECORD_ID_MARK = "\1";


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
//    /**
//     * @var \Dive\Collection\RecordCollection
//     */
//    protected $_resultCollection;
    /**
     * @var bool
     */
    protected $_exists = false;
    /**
     * @var array
     */
    private $_internalReferenceMap = array();


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
            $identifier = implode('-', $identifier);
        }
        return $identifier;
    }


    public function getOid()
    {
        return spl_object_hash($this);
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
                "Identifier '" . implode('-', $identifier) .  "' does not match table identifier!"
            );
        }

        foreach ($identifier as $fieldName => $id) {
            $this->_data[$fieldName] = $id;
        }
        $this->_modifiedFields = array();
        $this->_exists = true;
    }


    public function get($name)
    {
        return $this->__get($name);
    }


    /**
     * @param string $name
     * @param mixed $value
     */
    public function set($name, $value)
    {
        $this->__set($name, $value);
    }


    /**
     * @param  string $name
     * @return mixed
     */
    public function __get($name)
    {
        $this->_table->throwExceptionIfFieldOrRelationNotExists($name);

        if ($this->_table->hasField($name)) {
            if (array_key_exists($name, $this->_data)) {
                return $this->_data[$name];
            }
            return $this->_table->getFieldDefaultValue($name);
        }

//        if ($this->_table->hasRelation($name)) {
//            return $this->_table->getReferenceFor($this, $name);
//        }

        return null;
    }


    /**
     * TODO how to handle boolean fields?
     *
     * @param string $name
     * @param mixed  $value
     */
    public function __set($name, $value)
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
        }

//        if ($this->_table->hasRelation($name)) {
//            $this->_table->setReferenceFor($this, $name, $value);
//        }
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


    public function toArray($deep = true)
    {
        return $this->_data;
    }

}