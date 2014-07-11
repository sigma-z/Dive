<?php
/*
 * This file is part of the Dive ORM framework.
 * (c) Steffen Zeidler <sigma_z@sigma-scripts.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Dive\Test\Table;

use Dive\Record;
use Dive\RecordManager;
use Dive\Table;
use Dive\TestSuite\TestCase;

/**
 * Class FindOrCreateRecordTableTest
 * @author  Steffen Zeidler <sigma_z@sigma-scripts.de>
 * @created 11.07.2014
 */
class FindOrCreateRecordTableTest extends TestCase
{

    /** @var RecordManager */
    private $rm;

    /** @var Table */
    private $table;

    /** @var Record */
    private $storedRecord;

    /** @var Record */
    private $resultRecord;


    public function testFindOrCreateRecordByPrimaryKeyWithRecordFound()
    {
        $this->givenIHaveARecordManager();
        $this->givenIHaveAUserTable();
        $this->givenIHaveARecordStoredWithData(array('username' => 'Hugo'));
        $this->whenIFindOrCreateByPrimaryKey();
        $this->thenItShouldHaveFoundRecord();
    }


    public function testFindOrCreateRecordByPrimaryKeyWithRecordNotFound()
    {
        $this->givenIHaveARecordManager();
        $this->givenIHaveAUserTable();
        $this->whenIFindOrCreateTheRecordWithData(array('username' => 'Hugo', 'id' => 123));
        $this->thenItShouldHaveCreatedARecordWithData(array('username' => 'Hugo', 'id' => 123));
    }


    public function testFindOrCreateRecordByUniqueKeyWithRecordFound()
    {
        $this->givenIHaveARecordManager();
        $this->givenIHaveAUserTable();
        $this->givenIHaveARecordStoredWithData(array('username' => 'Hugo'));
        $this->whenIFindOrCreateTheRecordWithData(array('username' => 'Hugo'));
        $this->thenItShouldHaveFoundRecord();
    }


    public function testFindOrCreateRecordByUniqueKeyWithRecordNotFound()
    {
        $this->givenIHaveARecordManager();
        $this->givenIHaveAUserTable();
        $this->whenIFindOrCreateTheRecordWithData(array('username' => 'Hugo'));
        $this->thenItShouldHaveCreatedARecordWithData(array('username' => 'Hugo'));
    }


    /**
     * @expectedException \Dive\Hydrator\HydratorException
     */
    public function testFindOrCreateRecordByUniqueKeyFindsMoreRecordsThrowsException()
    {
        $this->givenIHaveARecordManager();
        $this->givenIHaveAUniqueConstraintTestTable();

        $recordOneData = array(
            'single_unique' => 'test',
            'single_unique_null_constrained' => 'test',
            'composite_unique1' => 'test',
            'composite_unique2' => 'test',
            'composite_unique_null_constrained1' => 'test',
            'composite_unique_null_constrained2' => 'test'
        );
        $this->givenIHaveARecordStoredWithData($recordOneData);

        $recordTwoData = array(
            'single_unique' => '123',
            'single_unique_null_constrained' => '123',
            'composite_unique1' => '123',
            'composite_unique2' => '123',
            'composite_unique_null_constrained1' => '123',
            'composite_unique_null_constrained2' => '123'
        );
        $this->givenIHaveARecordStoredWithData($recordTwoData);

        // record data will join the data of unique data from two records, so it can not be found be its unique keys
        $recordData = array();
        foreach ($this->table->getFieldNames() as $fieldName) {
            if (strpos($fieldName, 'single_unique_') === 0) {
                $recordData[$fieldName] = $recordOneData[$fieldName];
            }
            else if (isset($recordTwoData[$fieldName])) {
                $recordData[$fieldName] = $recordTwoData[$fieldName];
            }
        }

        $this->whenIFindOrCreateTheRecordWithData($recordData);
    }


    private function givenIHaveARecordManager()
    {
        $this->rm = self::createDefaultRecordManager();
    }


    private function givenIHaveAUserTable()
    {
        $this->table = $this->rm->getTable('user');
    }


    private function givenIHaveAUniqueConstraintTestTable()
    {
        $this->table = $this->rm->getTable('unique_constraint_test');
    }


    /**
     * @param array $recordData
     */
    private function givenIHaveARecordStoredWithData(array $recordData)
    {
        $this->storedRecord = self::getRecordWithRandomData($this->table, $recordData);
        $this->rm->save($this->storedRecord)->commit();
    }


    /**
     * @param array $recordData
     */
    private function whenIFindOrCreateTheRecordWithData(array $recordData)
    {
        $this->resultRecord = $this->table->findOrCreateRecord($recordData);
    }


    private function whenIFindOrCreateByPrimaryKey()
    {
        $this->resultRecord = $this->table->findOrCreateRecord($this->storedRecord->getIdentifierFieldIndexed());
    }


    private function thenItShouldHaveFoundRecord()
    {
        $this->assertNotNull($this->resultRecord);
        $this->assertTrue($this->resultRecord->exists());
    }


    /**
     * @param array $recordData
     */
    private function thenItShouldHaveCreatedARecordWithData(array $recordData)
    {
        $this->assertNotNull($this->resultRecord);
        $this->assertFalse($this->resultRecord->exists());
        foreach ($recordData as $fieldName => $fieldValue) {
            $this->assertEquals($fieldValue, $this->resultRecord->get($fieldName));
        }
    }

}