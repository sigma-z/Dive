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

namespace Dive\Test\Connection;

use Dive\Platform\PlatformInterface;
use Dive\TestSuite\TestCase;

class MysqlConnectionTest extends TestCase
{

    public function testConnect()
    {
        $database = self::getDatabaseForScheme('mysql');
        if ($database === false) {
            $this->markTestSkipped("Skipped, no database connection defined for scheme 'mysql'");
        }
        $conn = $this->createDatabaseConnection($database);
        $conn->setEncoding(PlatformInterface::ENC_LATIN1);
        $conn->connect();

        $result = $conn->query("SHOW VARIABLES LIKE 'character_set_connection'");
        $this->assertEquals('latin1', $result[0]['Value']);
    }
}