<?php
/*
 * This file is part of the Dive ORM framework.
 * (c) Steffen Zeidler <sigma_z@sigma-scripts.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Dive\Test\Connection;

use Dive\TestSuite\TestCase;

/**
 * Class ConnectionFailedTransactionTest
 * @author  Steffen Zeidler <sigma_z@sigma-scripts.de>
 * @created 11.04.14
 */
class ConnectionFailedTransactionTest extends TestCase
{

    public function testRollBackOnFailure()
    {
        $rm = self::createDefaultRecordManager();
        $user = $rm->getTable('user')->createRecord();
        $rm->save($user);

        try {
            $rm->commit();
            $this->fail('expected exception to be thrown');
        }
        catch (\Exception $e) {
        }

        $user->set('username', 'user');
        $user->set('password', 'password');

        $rm->save($user);
        $rm->commit();
    }

}