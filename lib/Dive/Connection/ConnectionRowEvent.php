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
 * @created 18.03.13
 */


namespace Dive\Connection;

use Dive\Event\Event;
use Dive\Table;


class ConnectionRowEvent extends Event
{
    /**
     * @var Connection
     */
    private $connection = null;
    /**
     * @var Table
     */
    private $table = null;
    /**
     * @var array
     */
    private $identifier = array();
    /**
     * @var array
     */
    private $fields = array();


    /**
     * constructor
     *
     * @param Connection  $connection
     * @param \Dive\Table $table
     * @param array       $fields
     * @param array       $identifier
     */
    public function __construct(
        Connection $connection, Table $table, array $fields = array(), array $identifier = array()
    )
    {
        $this->connection = $connection;
        $this->table = $table;
        $this->fields = $fields;
        $this->identifier = $identifier;
    }


    /**
     * Gets connection
     *
     * @return Connection
     */
    public function getConnection()
    {
        return $this->connection;
    }


    /**
     * Gets table
     *
     * @return Table
     */
    public function getTable()
    {
        return $this->table;
    }


    /**
     * Gets fields
     *
     * @return array
     */
    public function getFields()
    {
        return $this->fields;
    }


    /**
     * Gets identifier
     *
     * @return array
     */
    public function getIdentifier()
    {
        return $this->identifier;
    }

}