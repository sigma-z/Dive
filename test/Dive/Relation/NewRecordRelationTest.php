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
 * @created 22.04.13
 */

namespace Dive\Test\Relation;

use Dive\Record\Generator\RecordGenerator;
use Dive\Record;
use Dive\RecordManager;
use Dive\TestSuite\TestCase;
use Dive\Util\FieldValuesGenerator;

class NewRecordRelationTest extends TestCase
{

    public function testOneToOneOwningReferenceOnExistingRecord()
    {
        $this->markTestIncomplete();


        $user = $this->createUser();
        $user->save();
        $author = $this->createAuthor($user);
        $user->Author = $author;

        $this->assertEquals($author, $user->Author);
    }


    public function testOneToOneOwningReferenceOnNewRecord()
    {
        $this->markTestIncomplete();


        $user = $this->createUser();
        $author = $this->createAuthor($user);
        $user->Author = $author;

        $this->assertEquals($author, $user->Author);
    }


    private function createUser($username = 'UserOne')
    {
        $rm = self::createDefaultRecordManager();
        $table = $rm->getTable('user');
        $user = $table->createRecord(array('username' => $username, 'password' => 'my-secret'));
        return $user;
    }


    private function createAuthor(Record $user)
    {
        $rm = self::createDefaultRecordManager();
        $table = $rm->getTable('author');
        $author = $table->createRecord(array('firstname' => $user->username, 'lastname' => $user->username));
        return $author;
    }

}