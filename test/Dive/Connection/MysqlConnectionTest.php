<?php
 /*
 * This file is part of the Dive ORM framework.
 * (c) Steffen Zeidler <sigma_z@sigma-scripts.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dive\Test\Connection;

use Dive\Platform\PlatformInterface;
use Dive\TestSuite\TestCase;

/**
 * @author  Steffen Zeidler <sigma_z@sigma-scripts.de>
 * @created 18.03.13
 */
class MysqlConnectionTest extends TestCase
{

    public function testConnect()
    {
        $conn = self::createConnectionForScheme('mysql');
        $conn->setEncoding(PlatformInterface::ENC_LATIN1);
        $conn->connect();

        $result = $conn->query("SHOW VARIABLES LIKE 'character_set_connection'");
        $this->assertEquals('latin1', $result[0]['Value']);
    }

}
