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
 * Date: 30.10.12
 */

namespace Dive\Connection;

use Dive\Event\Event;


class ConnectionEvent extends Event
{

    /**
     * @var Connection
     */
    private $connection = null;
    /**
     * @var string
     */
    private $sql = '';
    /**
     * @var array
     */
    private $params = array();


    /**
     * constructor
     *
     * @param Connection $connection
     * @param string     $sql
     * @param array      $params
     */
    public function __construct(Connection $connection, $sql = '', array $params = array())
    {
        $this->connection = $connection;
        $this->sql = $sql;
        $this->params = $params;
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
     * Gets sql statement
     *
     * @return string
     */
    public function getStatement()
    {
        return $this->sql;
    }


    /**
     * Gets sql statement params
     *
     * @return array
     */
    public function getParams()
    {
        return $this->params;
    }

}