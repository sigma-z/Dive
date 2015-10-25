<?php
/*
 * This file is part of the Dive ORM framework.
 * (c) Steffen Zeidler <sigma_z@sigma-scripts.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dive\Collection;

use Dive\Model;
use Dive\Relation\Relation;
use Dive\Record;
use Dive\Table;

/**
 * @author Steffen Zeidler <sigma_z@sigma-scripts.de>
 * Date: 11.02.13
 *
 * @property Record[] $items
 */
class RecordCollection extends Collection
{

    /** @var array */
    private $identifiers = null;

    /** @var Table */
    protected $table = null;

    /**
     * referenced record, referenced by all the records in this collection
     * @var Record
     */
    private $refRecord = null;

    /** @var Relation */
    private $relation = null;


    /**
     * @param Table     $table
     * @param Record    $record
     * @param Relation  $relation
     */
    public function __construct(Table $table, Record $record = null, Relation $relation = null)
    {
        $this->table = $table;
        $this->refRecord = $record;
        $this->relation = $relation;
    }


    /**
     * @return Table
     */
    public function getTable()
    {
        return $this->table;
    }


    /**
     * @param  Record $item
     * @return bool
     */
    public function has($item)
    {
        if ($item instanceof Model) {
            $item = $item->getRecord();
        }
        return in_array($item, $this->items, true);
    }


    /**
     * @param  Record $item
     * @param  string $id
     * @throws CollectionException
     * @return $this
     */
    public function add($item, $id = null)
    {
        if ($item instanceof Model) {
            $item = $item->getRecord();
        }
        $this->throwExceptionIfRecordDoesNotMatchTable($item);

        // TODO throw exception record already added??
        // TODO how to better track, that record is already in the collection?? This way is very expensive, especially for many records!
        if ($this->has($item)) {
            return $this;
        }

        $this->items[] = $item;
        if ($this->identifiers !== null) {
            $this->identifiers[] = $item->getInternalId();
        }

        if ($this->refRecord && $this->relation) {
            $this->relation->updateRecordReference($item, $this->refRecord);
        }
        return $this;
    }


    /**
     * offsetSet() for handling null-value for $offset
     *
     * @overridden
     *
     * @param  string $offset
     * @param  Record $value
     */
    public function offsetSet($offset, $value)
    {
        $this->add($value, $offset);
    }


    /**
     * @param mixed $offset
     */
    public function offsetUnset($offset)
    {
        parent::offsetUnset($offset);
        unset($this->identifiers[$offset]);
    }


    /**
     * TODO unit test
     *
     * @param  string $id
     * @throws CollectionException
     * @return Record
     */
    public function getById($id)
    {
        foreach ($this->items as $record) {
            if ($record->getInternalId() == $id) {
                return $record;
            }
        }
        throw new CollectionException("Could not find record in collection with ID '$id'!");
    }


    /**
     * unlink the record
     *
     * @param  Record $record
     * @return bool
     */
    public function unlinkRecord(Record $record)
    {
        if ($record instanceof Model) {
            $record = $record->getRecord();
        }

        $key = $this->search($record);
        if ($key === false) {
            return false;
        }
        $this->offsetUnset($key);
        if ($this->refRecord && $this->relation) {
            $this->relation->updateRecordReference($record, null);
        }
        return true;
    }


    /**
     * @param  Record $record
     * @return int|bool FALSE if not found
     */
    public function search(Record $record)
    {
        return array_search($record, $this->items, true);
    }


    /**
     * deletes record from collection
     *
     * @param  Record $record
     * @throws CollectionException
     * @return bool
     */
    public function deleteRecord(Record $record)
    {
        if ($record instanceof Model) {
            $record = $record->getRecord();
        }

        if (!$this->has($record)) {
            $id = $record->getInternalId();
            throw new CollectionException("$id is not in collection!");
        }
        $this->table->getRecordManager()->scheduleDelete($record);
        return $this->unlinkRecord($record);
    }


//    /**
//     * @param string $newIdentifier
//     * @param string $oldIdentifier
//     */
//    public function updateIdentifier($newIdentifier, $oldIdentifier)
//    {
//        $this->throwExceptionIfIdDoesNotExists($oldIdentifier);
//
//        $record = $this->get($oldIdentifier);
//        $this->offsetUnset($oldIdentifier);
//        $this->items[$newIdentifier] = $record;
//    }


    /**
     * gets record identifiers
     *
     * @return string[]
     */
    public function getIdentifiers()
    {
        if ($this->identifiers === null) {
            $this->identifiers = array();
            foreach ($this->items as $owningRecord) {
                $this->identifiers[] = $owningRecord->getInternalId();
            }
        }
        return $this->identifiers;
    }


    /**
     * @param  bool $deep
     * @param  bool $withMappedFields
     * @return array
     */
    public function toArray($deep = false, $withMappedFields = false)
    {
        $data = array();
        foreach ($this->items as $record) {
            $data[] = $record->toArray($deep, $withMappedFields);
        }
        return $data;
    }


    /**
     * @param  mixed $record
     * @throws CollectionException
     */
    protected function throwExceptionIfRecordDoesNotMatchTable($record)
    {
        if (!($record instanceof Record)) {
            throw new CollectionException(
                'Argument #1 must be an instance of \Dive\Record!'
                    . 'You gave me: ' . (is_object($record) ? get_class($record) : gettype($record))
            );
        }
        if ($record->getTable() != $this->table) {
            throw new CollectionException(
                'Add record does not match table instance!'
                    . ' Expected: ' . $this->table->getTableName()
                    . ' You gave me: ' . $record->getTable()->getTableName()
            );
        }
    }

//    /**
//     * @param  string $id
//     * @throws CollectionException
//     */
//    protected function throwExceptionIfIdDoesNotExists($id)
//    {
//        if (!$this->has($id)) {
//            var_dump($this->keys());
//            throw new CollectionException("Id '$id' does not exists in collection!");
//        }
//    }

}
