<?php
/*
 * This file is part of the Dive ORM framework.
 * (c) Steffen Zeidler <sigma_z@sigma-scripts.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Dive\Test\Table\Behaviour;

use Dive\TestSuite\TestCase;

/**
 * Class DelegateBehaviourTest
 * @author  Steffen Zeidler <sigma_z@sigma-scripts.de>
 * @created 10.03.14
 */
class DelegateBehaviourTest extends TestCase
{

    public function testSetDelegatedField()
    {
        $rm = self::createDefaultRecordManager();
        $author = $rm->getTable('author')->createRecord();
        $author->username = 'user-name';

        $this->assertEquals('user-name', $author->User->username);
    }


    public function testGetDelegatedField()
    {
        $rm = self::createDefaultRecordManager();
        $author = $rm->getTable('author')->createRecord();
        $user = $rm->getTable('user')->createRecord();
        $user->username = 'user-name';
        $author->User = $user;

        $this->assertEquals('user-name', $author->username);
    }

}