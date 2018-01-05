<?php
/*
 * This file is part of the Dive ORM framework.
 * (c) Steffen Zeidler <sigma_z@sigma-scripts.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dive\Test\Connection\Driver;

use Dive\TestSuite\TestCase;

/**
 * @author Steffen Zeidler <sigma_z@sigma-scripts.de>
 * @date   05.01.2018
 */
class SqliteDriverTest extends TestCase
{

    /**
     * @expectedException \Dive\Connection\Driver\DriverException
     */
    public function testFetchConstraintName()
    {
        $conn = self::createConnectionForScheme('sqlite');
        $driver = $conn->getDriver();
        $driver->fetchConstraintName($conn, 'author', 'user_id');
    }

}
