<?php
/*
 * This file is part of the Dive ORM framework.
 * (c) Steffen Zeidler <sigma_z@sigma-scripts.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dive\Collection;

/**
 * @author Steffen Zeidler <sigma_z@sigma-scripts.de>
 * Date: 11.02.13
 */
interface CollectionInterface extends \Countable, \ArrayAccess, \IteratorAggregate
{

    /**
     * @param  \Dive\Record|array $item
     * @param  string $id
     */
    public function add($item, $id = null);


    /**
     * @abstract
     * @param  string $id
     * @return \Dive\Record
     */
    public function get($id);


    /**
     * @abstract
     * @param  string $id
     * @return bool
     */
    public function remove($id);


    /**
     * @abstract
     * @param  string $id
     * @return bool
     */
    public function has($id);


    /**
     * Gets keys of collection items
     *
     * @return array
     */
    public function keys();


    /**
     * Checks if the collection is empty
     *
     * @return bool
     */
    public function isEmpty();


    /**
     * @return mixed
     */
    public function first();


    /**
     * @return mixed
     */
    public function last();


    /**
     * @return mixed
     */
    public function next();


    /**
     * @return string|int
     */
    public function key();


    /**
     * @param array|\Dive\Record[] $items keys are the primary keys of the items, values can be array or objects
     */
    public function setItems(array $items);


    /**
     * @return array|\Dive\Record[]
     */
    public function getItems();

}
