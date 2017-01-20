<?php
/*
 * This file is part of the Dive ORM framework.
 * (c) Steffen Zeidler <sigma_z@sigma-scripts.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dive\Test\Record;

use Dive\Query\Query;
use Dive\TestSuite\Model\User;
use Dive\TestSuite\RecordBehaviorTrait;
use Dive\TestSuite\TestCase;

/**
 * @author Steffen Zeidler <sigma_z@sigma-scripts.de>
 * @date   20.01.2017
 */
class RecordModifiedTest extends TestCase
{

    use RecordBehaviorTrait;


    public function testRecordIsNotModifiedAfterInsert()
    {
        $rm = $this->givenIHaveARecordManager();
        /** @var User $user */
        $user = $this->givenIHaveCreatedARecord('user');
        $user->username = 'Joe';
        $this->assertTrue($user->isModified());

        $rm->scheduleSave($user)->commit();
        $this->assertFalse($user->isModified());

        $user->set('username', 'changedValue');
        $this->assertTrue($user->isModified());

        $rm->scheduleSave($user)->commit();
        $this->assertFalse($user->isModified());
    }


    public function testRecordIsNotModifiedAfterUpdate()
    {
        $rm = $this->givenIHaveARecordManager();
        $user = $this->givenIHaveStoredARecord('user', ['username' => 'Joe']);

        $user->set('username', 'changedValue');
        $this->assertTrue($user->isModified());

        $rm->scheduleSave($user)->commit();
        $this->assertFalse($user->isModified());
    }


    public function testRecordIsModifiedAfterQuery()
    {
        $this->givenIHaveARecordManager();
        $user = $this->givenIHaveStoredARecord('user', ['username' => 'Joe']);

        $user->set('username', 'changedValue');
        $this->assertTrue($user->isModified());

        /** @var Query $query */
        $query = $user->getTable()->createQuery('u');
        $user = $query->fetchOneAsObject();
        $this->assertTrue($user->isModified());
    }


    public function testRecordIsNotModifiedAfterRefresh()
    {
        $this->givenIHaveARecordManager();
        $user = $this->givenIHaveStoredARecord('user', ['username' => 'Joe']);

        $user->set('username', 'changedValue');
        $this->assertTrue($user->isModified());

        $user->refresh();
        $this->assertFalse($user->isModified());
    }

}