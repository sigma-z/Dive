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
class MysqlDriverTest extends TestCase
{

    public function testFetchConstraintName()
    {
        $conn = self::createConnectionForScheme('mysql');
        $driver = $conn->getDriver();
        $this->assertSame('author_fk_user_id', $driver->fetchConstraintName($conn, 'author', 'user_id'));
    }

}
