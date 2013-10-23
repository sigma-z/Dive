<?php
 /*
 * This file is part of the Dive ORM framework.
 * (c) Steffen Zeidler <sigma_z@sigma-scripts.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


namespace Dive\Test\Table;

use Dive\Table;
use Dive\TestSuite\TestCase;


/**
 * @author  Steffen Zeidler <sigma_z@sigma-scripts.de>
 * @created 27.03.13
 */
class RepositoryTest extends TestCase
{

    /**
     * @var Table
     */
    private $table = null;
    /**
     * @var Table\Repository
     */
    private $repository = null;


    public function setUp()
    {
        $tableName = 'user';
        parent::setUp();

        // table instance is always the same
        $this->table = self::createDefaultRecordManager()->getTable($tableName);
        // repository instance is always the same, too, and therefore is must be cleared in setUp()
        $this->repository = $this->table->getRepository();
        $this->repository->clear();

        $this->assertEquals($tableName, $this->repository->getTable()->getTableName());
    }


    public function testAdd()
    {
        $record = $this->table->createRecord();
        $actual = $this->repository->has($record->getOid());

        $this->assertTrue($actual);
    }


    /**
     * @dataProvider provideExistingFlag
     */
    public function testRemove($exist)
    {
        $record = $this->table->createRecord(array('id' => 7, 'username' => 'Bart'), $exist);
        $isInRepository = $this->repository->has($record->getOid());
        $this->assertTrue($isInRepository);

        $this->repository->remove($record);
        $actual = $this->repository->has($record->getOid());
        $this->assertFalse($actual);
    }


    /**
     * @dataProvider provideExistingFlag
     */
    public function testGetByOid($exist)
    {
        $record = $this->table->createRecord(array('id' => 7, 'username' => 'Bart'), $exist);
        $oid = $record->getOid();
        $isInRepository = $this->repository->has($oid);
        $this->assertTrue($isInRepository);
        $this->assertEquals($record, $this->repository->getByOid($oid));
    }


    /**
     * @dataProvider provideExistingFlag
     */
    public function testGetByInternalId($exist)
    {
        $record = $this->table->createRecord(array('id' => 7, 'username' => 'Bart'), $exist);
        $id = $record->getInternalId();
        $isInRepository = $this->repository->hasByInternalId($id);
        $this->assertTrue($isInRepository);
        $this->assertEquals($record, $this->repository->getByInternalId($id));
    }


    /**
     * @return array
     */
    public function provideExistingFlag()
    {
        return array(
            array(false),   // new record
            array(true)     // existing record
        );
    }


    public function testGetByInternalIdNotFound()
    {
        $this->assertFalse($this->repository->getByInternalId(7));
    }


    public function testGetById()
    {
        $record = $this->table->createRecord(array('id' => 7, 'username' => 'Bart'), true);
        $recordFromRepository = $this->repository->getByInternalId(7);

        $this->assertEquals($record, $recordFromRepository);
    }


    /**
     * @expectedException \Dive\Table\RepositoryException
     */
    public function testGetByOidNotInRepositoryThrowsException()
    {
        $this->repository->getByOid(7);
    }


    public function testCount()
    {
        $this->table->createRecord(array('id' => 7, 'username' => 'Bart'), true);

        $this->assertEquals(1, $this->repository->count());
    }


    public function testClear()
    {
        $this->table->createRecord(array('id' => 7, 'username' => 'Bart'), true);
        $this->repository->clear();

        $this->assertEquals(0, $this->repository->count());
    }

}