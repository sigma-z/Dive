<?php
 /*
 * This file is part of the Dive ORM framework.
 * (c) Steffen Zeidler <sigma_z@sigma-scripts.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dive\TestSuite;

use Dive\Connection\Connection;
use Dive\Table;

/**
 * @author  Steffen Zeidler <sigma_z@sigma-scripts.de>
 * @created 01.03.13
 */
class DatasetRegistry
{

    /** @var Connection[] */
    private $connections = array();

    /** @var \Dive\Table[] */
    private $tables = array();

    /** @var array */
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
        $conn = $table->getConnection();
        $connIndex = array_search($conn, $this->connections);
        if ($connIndex === false) {
            $this->connections[] = $conn;
            $connIndex = count($this->connections) - 1;
        }
        $tableName = $table->getTableName();
        $this->tables[$connIndex][$tableName] = $table;
        $key = $this->getIdAsString($id);
        $this->registry[$connIndex][$tableName][$key] = $id;
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
            $connIndex = $this->getConnectionIndex($table->getConnection());
            unset($this->registry[$connIndex][$tableName][$key]);
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
        $connIndex = $this->getConnectionIndex($table->getConnection());
        if ($connIndex === false) {
            return false;
        }
        $tableName = $table->getTableName();
        $key = $this->getIdAsString($id);
        return isset($this->registry[$connIndex][$tableName][$key]);
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
     * Gets registered connections
     *
     * @return \Dive\Connection\Connection[]
     */
    public function getConnections()
    {
        return $this->connections;
    }


    /**
     * Gets tables with registered dataset ids
     *
     * @param  \Dive\Connection\Connection $connection
     * @return \Dive\Table[]
     */
    public function getTables(Connection $connection)
    {
        $connIndex = $this->getConnectionIndex($connection);
        if ($connIndex === false) {
            return array();
        }
        return isset($this->tables[$connIndex]) ? $this->tables[$connIndex] : array();
    }


    /**
     * Gets registered ids for given table
     *
     * @param  \Dive\Table $table
     * @return array
     */
    public function getByTable(Table $table)
    {
        $connIndex = $this->getConnectionIndex($table->getConnection());
        if ($connIndex === false) {
            return array();
        }
        $tableName = $table->getTableName();
        if (isset($this->registry[$connIndex][$tableName])) {
            return array_values($this->registry[$connIndex][$tableName]);
        }
        return array();
    }


    /**
     * Gets index for given connection
     *
     * @param  Connection $conn
     * @return int|bool False if not found
     */
    private function getConnectionIndex(Connection $conn)
    {
        return array_search($conn, $this->connections);
    }


    /**
     * Clear dataset ids by given table
     *
     * @param \Dive\Table $table
     */
    public function clearByTable(Table $table)
    {
        $connIndex = $this->getConnectionIndex($table->getConnection());
        if ($connIndex === false) {
            return;
        }
        $tableName = $table->getTableName();
        unset($this->tables[$connIndex][$tableName]);
        unset($this->registry[$connIndex][$tableName]);
    }


    /**
     * clear registry
     */
    public function clear()
    {
        $this->connections = array();
        $this->tables = array();
        $this->registry = array();
    }

}