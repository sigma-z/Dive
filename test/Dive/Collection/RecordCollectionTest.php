<?php
/*
 * This file is part of the Dive ORM framework.
 * (c) Steffen Zeidler <sigma_z@sigma-scripts.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dive\Test\Collection;

use Dive\Collection\RecordCollection;
use Dive\TestSuite\TestCase;

/**
 * @author Steffen Zeidler <sigma_z@sigma-scripts.de>
 * Date: 11.02.13
 */
class RecordCollectionTest extends TestCase
{

    /**
     * @var \Dive\RecordManager
     */
    private $rm;
    /**
     * @var RecordCollection
     */
    private $userColl;


    protected function setUp()
    {
        parent::setUp();

        $this->rm = self::createDefaultRecordManager();
        $table = $this->rm->getTable('user');
        $coll = new RecordCollection($table);
        $this->userColl = $coll;
    }


    public function testAddNewRecord()
    {
        $user = $this->addRecordToCollection();
        $this->assertTrue($this->userColl->has($user));
    }


    public function testAddExistingRecord()
    {
        $user = $this->addRecordToCollection(array('id' => 7, 'username' => 'Bart'), true);
        $this->assertTrue($this->userColl->has($user));
    }


    /**
     * @expectedException \Dive\Collection\CollectionException
     */
    public function testAddWrongTypeThrowsException()
    {
        /** @noinspection PhpParamsInspection */
        $this->userColl->add(array());
    }


    /**
     * @expectedException \Dive\Collection\CollectionException
     */
    public function testAddWrongRecordTypeWillThrowException()
    {
        $table = $this->rm->getTable('author');
        $author = $table->createRecord();
        $this->userColl->add($author);
    }


    public function testRemoveRecord()
    {
        $user = $this->addRecordToCollection();
        $this->assertTrue($this->userColl->has($user));
        $this->assertTrue($this->userColl->deleteRecord($user));
        $this->assertFalse($this->userColl->has($user));
    }


    /**
     * @expectedException \Dive\Collection\CollectionException
     */
    public function testRemoveNotExistingRecordWillThrowException()
    {
        $table = $this->userColl->getTable();
        $user = $table->createRecord(array('username' => 'notexistinguser', 'password' => 'secretnothing'), false);
        $this->assertFalse($this->userColl->has($user));
        $this->assertFalse($this->userColl->deleteRecord($user));
    }


    public function testUnlink()
    {
        $user = $this->addRecordToCollection();
        $this->assertTrue($this->userColl->unlinkRecord($user));
        $this->assertFalse($this->userColl->has($user));
    }


    public function testGetIdentifiers()
    {
        $userOne = $this->addRecordToCollection();
        $userTwo = $this->addRecordToCollection();
        $expected = array($userOne->getInternalId(), $userTwo->getInternalId());
        $actual = $this->userColl->getIdentifiers();
        $this->assertEquals($expected, $actual);
    }


    public function testAddRecordViaMagicMethod()
    {
        $table = $this->userColl->getTable();
        $user = $table->createRecord();
        $this->userColl[] = $user;
        /** @noinspection PhpUndefinedMethodInspection */
        $this->assertTrue($this->userColl->has($user));
    }


    public function testToArray()
    {
        $userOne = $this->addRecordToCollection();
        $userTwo = $this->addRecordToCollection(array('id' => 7, 'username' => 'Bart'), true);
        $expected = array($userOne->toArray(), $userTwo->toArray());
        $this->assertEquals($expected, $this->userColl->toArray(true));
    }


    /**
     * @param array $data
     * @param bool $exists
     * @return \Dive\Record
     */
    private function addRecordToCollection(array $data = array(), $exists = false)
    {
        $table = $this->userColl->getTable();
        $user = $table->createRecord($data, $exists);
        $this->userColl->add($user);
        return $user;
    }

}
