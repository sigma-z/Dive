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

    /**
     * @var RecordManager
     */
    private $rm = null;


    protected function setUp()
    {
        parent::setUp();
        $this->rm = self::createDefaultRecordManager();
    }


    protected function tearDown()
    {
        parent::tearDown();
        $this->rm->clearTables();
    }


    public function testOneToOneOwningReferenceOnExistingRecord()
    {
        $user = $this->createUser();
        $user->save();
        $author = $this->createAuthor($user);
        $user->Author = $author;

        $this->assertEquals($author, $user->Author);
        $this->assertEquals($user, $user->Author->User);
    }


    public function testOneToOneOwningReferenceOnNewRecord()
    {
        $user = $this->createUser();
        $author = $this->createAuthor($user);
        $user->Author = $author;

        $this->assertEquals($author, $user->Author);
        $this->assertEquals($user, $user->Author->User);
    }


    private function createUser($username = 'UserOne')
    {
        $table = $this->rm->getTable('user');
        $user = $table->createRecord(array('username' => $username, 'password' => 'my-secret'));
        return $user;
    }


    private function createAuthor(Record $user)
    {
        $table = $this->rm->getTable('author');
        $author = $table->createRecord(array('firstname' => $user->username, 'lastname' => $user->username));
        return $author;
    }

}