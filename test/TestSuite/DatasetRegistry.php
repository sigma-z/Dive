<?php
 /*
 * This file is part of the Dive ORM framework.
 * (c) Steffen Zeidler <sigma_z@sigma-scripts.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
/**
 * @author  Steffen Zeidler <sigma_z@sigma-scripts.de>
 * @created 01.03.13
 */

namespace Dive\TestSuite;

use Dive\Table;

class DatasetRegistry
{

    /**
     * @var \Dive\Table[]
     */
    private $tables = array();
    /**
     * @var array
     */
    private $registry = array();


    /**
     * Adds dataset id
     *
     * @param  \Dive\Table  $table
     * @param  string|array $id
     * @return $this
     */
    public function add(Table $table, $id)
    {
        $tableName = $table->getTableName();
        $this->tables[$tableName] = $table;
        $key = $this->getIdAsString($id);
        $this->registry[$tableName][$key] = $id;
        return $this;
    }


    /**
     * Removes dataset id, if registered
     *
     * @param  \Dive\Table  $table
     * @param  string|array $id
     * @return $this
     */
    public function remove(Table $table, $id)
    {
        $tableName = $table->getTableName();
        $key = $this->getIdAsString($id);
        if ($this->isRegistered($table, $key)) {
            unset($this->registry[$tableName][$key]);
        }
        return $this;
    }


    /**
     * Returns true, if dataset id is registered
     *
     * @param  \Dive\Table $table
     * @param  string|array $id
     * @return bool
     */
    public function isRegistered(Table $table, $id)
    {
        $tableName = $table->getTableName();
        $key = $this->getIdAsString($id);
        return isset($this->registry[$tableName][$key]);
    }


    /**
     * Gets dataset id as string
     *
     * @param  string|array $id
     * @return string
     */
    private function getIdAsString($id)
    {
        if (is_string($id)) {
            return $id;
        }
        return implode('-', $id);
    }


    /**
     * Gets tables with registered dataset ids
     *
     * @return \Dive\Table[]
     */
    public function getTables()
    {
        return $this->tables;
    }


    /**
     * Gets registered ids for given table
     *
     * @param  \Dive\Table $table
     * @return array
     */
    public function getByTable(Table $table)
    {
        $tableName = $table->getTableName();
        if (isset($this->registry[$tableName])) {
            return array_values($this->registry[$tableName]);
        }
        return array();
    }


    /**
     * Clear dataset ids by given table
     *
     * @param \Dive\Table $table
     */
    public function clearByTable(Table $table)
    {
        $tableName = $table->getTableName();
        unset($this->tables[$tableName]);
        unset($this->registry[$tableName]);
    }


    /**
     * clear registry
     */
    public function clear()
    {
        $this->tables = array();
        $this->registry = array();
    }

}