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
 * Date: 11.02.13
 *
 * @property \Dive\Record[] $items
 */

namespace Dive\Collection;

use Dive\Relation\Relation;
use Dive\Record;
use Dive\Table;


class RecordCollection extends Collection
{

    /**
     * @var Table
     */
    protected $table;
    /**
     * @var array
     */
    protected $toBeDeleted = array();
    /**
     * @var array
     */
    protected $toBeInserted = array();
    /**
     * @var \Dive\Relation\Relation
     */
    private $relation;


    /**
     * @param \Dive\Table $table
     */
    public function __construct(Table $table)
    {
        $this->table = $table;
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
    public function add($item, $id = null)
    {
        $this->throwExceptionIfRecordDoesNotMatchTable($item);

        if (null === $id) {
            $id = $item->getInternalIdentifier();
        }
        // TODO throw exception record already added??
        if ($this->offsetExists($id)) {
            // throw new CollectionException('..');
        }
        $this->offsetSet($id, $item);
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
        if ($offset === null) {
            $this->add($value);
        }
        else {
            parent::offsetSet($offset, $value);
        }
    }


    /**
     * removes item for the given $id
     *
     * @param  string $id
     * @return bool
     */
    public function remove($id)
    {
        if ($this->has($id)) {
            $this->toBeDeleted[] = $id;
        }
        return parent::remove($id);
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


    public function setRelation(Relation $relation)
    {
        $this->relation = $relation;
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
