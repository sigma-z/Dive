<?php
/*
 * This file is part of the Dive ORM framework.
 * (c) Steffen Zeidler <sigma_z@sigma-scripts.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
/**
 * @author Steffen Zeidler <sigma_z@sigma-scripts.de>
 * Date: 11.02.13
 */

namespace Dive\Test\Collection;


class RecordCollectionTest extends \Dive\TestSuite\TestCase
{

    /**
     * @var \Dive\RecordManager
     */
    private $rm;
    /**
     * @var \Dive\Collection\RecordCollection
     */
    private $userColl;


    protected function setUp()
    {
        parent::setUp();

        $this->rm = self::createDefaultRecordManager();
        $table = $this->rm->getTable('user');
        $coll = new \Dive\Collection\RecordCollection($table);
        $this->userColl = $coll;
    }


    public function testAddNewRecord()
    {
        $user = $this->addRecordToCollection();
        $this->assertTrue($this->userColl->has($user->getInternalIdentifier()));
    }


    public function testAddExistingRecord()
    {
        $user = $this->addRecordToCollection(array('id' => 7, 'username' => 'Bart'), true);
        $expected = $this->userColl->has($user->getInternalIdentifier());
        $this->assertTrue($expected);
    }


    /**
     * @expectedException \Dive\Collection\CollectionException
     */
    public function testAddWrongTypeWillThrowException()
    {
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
        $id = $user->getInternalIdentifier();
        $this->assertTrue($this->userColl->removeRecord($user));
        $this->assertFalse($this->userColl->has($id));
    }


    public function testGetIdentifiers()
    {
        $user = $this->addRecordToCollection();
        $id = $user->getInternalIdentifier();
        $expected = array($user->getInternalIdentifier());
        $actual = $this->userColl->getIdentifiers();
        $this->assertEquals($expected, $actual);
    }


    public function testAddRecord()
    {
        $table = $this->userColl->getTable();
        $user = $table->createRecord();
        $this->userColl->add($user);
        $this->assertTrue($this->userColl->has($user->getInternalIdentifier()));
    }


    public function testToArray()
    {
        $this->markTestIncomplete('Dive\Record::toArray() has to be implemented first!');
        $userOne = $this->addRecordToCollection();
        $userTwo = $this->addRecordToCollection(array('id' => 7, 'username' => 'Bart'), true);
        $expected[$userOne->getInternalIdentifier()] = $userOne->toArray();
        $expected[$userTwo->getInternalIdentifier()] = $userTwo->toArray();
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
