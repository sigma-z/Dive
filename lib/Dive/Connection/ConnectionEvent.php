<?php
/*
 * This file is part of the Dive ORM framework.
 * (c) Steffen Zeidler <sigma_z@sigma-scripts.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dive\Connection;

/**
 * @author Steffen Zeidler <sigma_z@sigma-scripts.de>
 * Date: 30.10.12
 */

class ConnectionEvent extends \Dive\Event\Event
{

    /**
     * @var \Dive\Connection\Connection
     */
    private $connection;


    /**
     * @param \Dive\Connection\Connection $connection
     */
    public function __construct(\Dive\Connection\Connection $connection)
    {
        $this->connection = $connection;
    }


    /**
     * @return \Dive\Connection\Connection
     */
    public function getConnection()
    {
        return $this->connection;
    }

}