<?php
/*
 * This file is part of the Dive ORM framework.
 * (c) Steffen Zeidler <sigma_z@sigma-scripts.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dive\Collection;

use Dive\Relation\Relation;
use Dive\Record;
use Dive\Table;

/**
 * @author Steffen Zeidler <sigma_z@sigma-scripts.de>
 * Date: 11.02.13
 *
 * @property \Dive\Record[] $items
 */
class RecordCollection extends Collection
{

    /**
     * @var Table
     */
    protected $table = null;
    /**
     * @var array
     */
    protected $toBeDeleted = array();
    /**
     * @var array
     */
    protected $toBeInserted = array();
    /**
     * @var \Dive\Record
     */
    private $refRecord = null;
    /**
     * @var \Dive\Relation\Relation
     */
    private $relation = null;


    /**
     * @param \Dive\Table             $table
     * @param \Dive\Record            $record
     * @param \Dive\Relation\Relation $relation
     */
    public function __construct(Table $table, Record $record = null, Relation $relation = null)
    {
        $this->table = $table;
        $this->refRecord = $record;
        $this->relation = $relation;
    }


    /**
     * @return \Dive\Table
     */
    public function getTable()
    {
        return $this->table;
    }


    /**
     * @param  \Dive\Record $item
     * @param  string $id
     * @throws CollectionException
     * @return $this
     */
    public function add($item, $id = null, $updateRecordReference = true)
    {
        $this->throwExceptionIfRecordDoesNotMatchTable($item);

        if (null === $id) {
            $id = $item->getInternalIdentifier();
        }
        // TODO throw exception record already added??
        $has = $this->has($id);
        parent::offsetSet($id, $item);

        if (!$has && $updateRecordReference && $this->refRecord && $this->relation) {
            $this->relation->updateRecordReference($item, $this->refRecord);
        }
        return $this;
    }


    /**
     * offsetSet() for handling null-value for $offset
     *
     * @overridden
     *
     * @param  string       $offset
     * @param  \Dive\Record $value
     */
    public function offsetSet($offset, $value)
    {
        $this->add($value, $offset);
    }


    /**
     * removes item for the given $id
     *
     * @param  string $id
     * @return bool
     */
    public function remove($id)
    {
        $removed = false;
        if ($this->has($id)) {
            $this->toBeDeleted[] = $id;
            $recordToBeRemoved = $this->get($id);
            $removed = parent::remove($id);
            if ($this->refRecord && $this->relation) {
                $this->relation->updateRecordReference($recordToBeRemoved, null);
            }
        }
        return $removed;
    }


    /**
     * removes record from collection
     *
     * @param \Dive\Record $record
     * @return bool
     */
    public function removeRecord(Record $record)
    {
        return $this->remove($record->getInternalIdentifier());
    }


    /**
     * gets record identifiers
     *
     * @return string[]
     */
    public function getIdentifiers()
    {
        return $this->keys();
    }


    public function toArray($deep = false)
    {
        $data = array();
        foreach ($this->items as $id => $record) {
            $data[$id] = $record->toArray($deep);
        }
        return $data;
    }


    /**
     * @param  mixed $record
     * @throws CollectionException
     */
    private function throwExceptionIfRecordDoesNotMatchTable($record)
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

}
