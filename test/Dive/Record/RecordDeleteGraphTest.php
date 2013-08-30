<?php
/*
 * This file is part of the Dive ORM framework.
 * (c) Steffen Zeidler <sigma_z@sigma-scripts.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dive\Test\Record;

use Dive\TestSuite\TestCase;

/**
 * @author  Steffen Zeidler <sigma_z@sigma-scripts.de>
 * @created 30.08.13
 */
class RecordDeleteGraphTest extends TestCase
{

    public function testOneToOneReferencedSide()
    {
        $this->markTestIncomplete();

        $author = $this->createOneToOneReferencedRecords();
        $changeSet = $author->delete();

        $this->assertFalse($author->exists());
        $affected = $changeSet->getScheduledForDelete();
        echo count($affected);
    }


    public function testOneToOneOwningSide()
    {
        $this->markTestIncomplete();

        $author = $this->createOneToOneReferencedRecords();
        $user = $author->User;
        $changeSet = $user->delete();

        $this->assertFalse($user->exists());
        $affected = $changeSet->getScheduledForDelete();
        echo count($affected);
    }


    private function createOneToOneReferencedRecords()
    {
        $graphData = array(
            'firstname' => 'John',
            'lastname' => 'Doe',
            'email' => 'jdo@example.com',
            'User' => array(
                'username' => 'John',
                'password' => 'secret'
            )
        );

        $rm = self::createDefaultRecordManager();
        $table = $rm->getTable('author');
        $author = $table->createRecord();
        $author->fromArray($graphData);
        $author->save();

        return $author;
    }

}