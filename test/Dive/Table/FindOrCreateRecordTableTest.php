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


    public function testStoredRecordCanBeFoundByPrimaryKey()
    {
        $this->givenIHaveARecordManager();
        $this->givenIUseTable('user');
        $this->givenIHaveARecordStoredWithData(['username' => 'Hugo']);

        $this->whenIFindOrCreateByPrimaryKey();

        $this->thenItShouldHaveFoundOneMatchingRecord();
    }


    public function testNoneExistingPrimaryKey()
    {
        $this->givenIHaveARecordManager();
        $this->givenIUseTable('user');

        $this->whenIFindOrCreateTheRecordWithData(['username' => 'Hugo', 'id' => 123]);

        $this->thenItShouldNotHaveFoundAnyMatchingRecord();
        $this->thenItShouldHaveCreatedARecordWithData(['username' => 'Hugo', 'id' => 123]);
    }


    public function testExistingRecordCanBeFoundByUniqueIndex()
    {
        $this->givenIHaveARecordManager();
        $this->givenIUseTable('user');
        $this->givenIHaveARecordStoredWithData(['username' => 'Hugo']);

        $this->whenIFindOrCreateTheRecordWithData(['username' => 'Hugo']);

        $this->thenItShouldHaveFoundOneMatchingRecord();
    }


    public function testNoneMatchingUniqueIndex()
    {
        $this->givenIHaveARecordManager();
        $this->givenIUseTable('user');

        $this->whenIFindOrCreateTheRecordWithData(['username' => 'Hugo']);

        $this->thenItShouldNotHaveFoundAnyMatchingRecord();
        $this->thenItShouldHaveCreatedARecordWithData(['username' => 'Hugo']);
    }


    public function testFindRecordByRepository()
    {
        $this->givenIHaveARecordManager();
        $this->givenIUseTable('user');
        $this->givenIHaveARecordStoredWithData(['username' => 'Hugo']);

        $this->whenIFindOrCreateTheRecordFromRepository();

        $this->thenItShouldHaveFoundOneMatchingRecord();
    }


    /**
     * @expectedException \Dive\Table\TableException
     */
    public function testFindOrCreateRecordByUniqueKeyFindsMoreThanOneRecordsThrowsException()
    {
        $this->givenIHaveARecordManager();
        $this->givenIUseTable('unique_constraint_test');

        $recordOneData = $this->givenIHaveStoredAUniqueConstraintTestRecord('test');
        $recordTwoData = $this->givenIHaveStoredAUniqueConstraintTestRecord('123');

        // record data will join the data of unique data from two records, so it can not be found be its unique keys
        $recordData = [];
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


    public function testUniqueIndexWithIncompleteFieldValues()
    {
        $this->givenIHaveARecordManager();
        $this->givenIUseTable('unique_constraint_test');
        $this->givenIHaveStoredAUniqueConstraintTestRecord('test');

        $this->whenIFindOrCreateTheRecordWithData(['composite_unique1' => 'test', 'composite_unique_null_constrained1' => 'test']);

        $this->thenItShouldNotHaveFoundAnyMatchingRecord();
        $this->thenItShouldHaveCreatedARecordWithData(['composite_unique1' => 'test', 'composite_unique_null_constrained1' => 'test']);
    }


    public function testTableHasNoUniqueIndexes()
    {
        $this->givenIHaveARecordManager();
        $this->givenIUseTable('tree_node');

        $this->whenIFindOrCreateTheRecordWithData(['name' => 'My Node']);

        $this->thenItShouldNotHaveFoundAnyMatchingRecord();
        $this->thenItShouldHaveCreatedARecordWithData(['name' => 'My Node']);
    }


    /**
     * @dataProvider provideNullConstraintedUniqueIndex
     * @param array $fieldValuesForFind
     * @param bool  $found
     */
    public function testNullConstraintedUniqueIndex(array $fieldValuesForFind, $found)
    {
        $this->givenIHaveARecordManager();
        $this->givenIUseTable('unique_constraint_test');
        $this->givenIHaveStoredAUniqueConstraintTestRecordWithNullValues('test');

        $this->whenIFindOrCreateTheRecordWithData($fieldValuesForFind);

        $found
            ? $this->thenItShouldHaveFoundOneMatchingRecord()
            : $this->thenItShouldNotHaveFoundAnyMatchingRecord();
    }


    /**
     * @return array[]
     */
    public function provideNullConstraintedUniqueIndex()
    {
        return [
            [
                'fieldValuesForFind' => [
                    'composite_unique_null_constrained1' => null,
                    'composite_unique_null_constrained2' => null
                ],
                'found' => true
            ],
            [
                'fieldValuesForFind' => [
                    'single_unique_null_constrained' => null,
                ],
                'found' => true
            ],
            [
                'fieldValuesForFind' => [
                    'composite_unique_null_constrained1' => null,
                    'composite_unique_null_constrained2' => 'test'
                ],
                'found' => false
            ],
            [
                'fieldValuesForFind' => [
                    'single_unique_null_constrained' => 'test',
                ],
                'found' => false
            ],
        ];
    }


    private function givenIHaveARecordManager()
    {
        $this->rm = self::createDefaultRecordManager();
    }


    /**
     * @param string $tableName
     */
    private function givenIUseTable($tableName)
    {
        $this->table = $this->rm->getTable($tableName);
    }


    /**
     * @param array $recordData
     */
    private function givenIHaveARecordStoredWithData(array $recordData)
    {
        $this->storedRecord = self::getRecordWithRandomData($this->table, $recordData);
        $this->rm->scheduleSave($this->storedRecord)->commit();
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


    private function thenItShouldHaveFoundOneMatchingRecord()
    {
        $this->assertNotNull($this->resultRecord);
        $this->assertTrue($this->resultRecord->exists());
    }


    private function thenItShouldNotHaveFoundAnyMatchingRecord()
    {
        $this->assertNotNull($this->resultRecord);
        $this->assertFalse($this->resultRecord->exists());
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


    private function whenIFindOrCreateTheRecordFromRepository()
    {
        // use fresh instances
        $rm = self::createDefaultRecordManager();
        $userTable = $rm->getTable('user');
        $this->resultRecord = $userTable->findOrCreateRecord(['id' => $this->storedRecord->get('id')]);
    }


    /**
     * @param string $fieldValue
     * @return array
     */
    private function givenIHaveStoredAUniqueConstraintTestRecord($fieldValue)
    {
        $recordData = [
            'single_unique' => $fieldValue,
            'single_unique_null_constrained' => $fieldValue,
            'composite_unique1' => $fieldValue,
            'composite_unique2' => $fieldValue,
            'composite_unique_null_constrained1' => $fieldValue,
            'composite_unique_null_constrained2' => $fieldValue
        ];
        $this->givenIHaveARecordStoredWithData($recordData);
        return $recordData;
    }


    /**
     * @param string $fieldValue
     * @return array
     */
    private function givenIHaveStoredAUniqueConstraintTestRecordWithNullValues($fieldValue)
    {
        $recordData = [
            'single_unique' => $fieldValue,
            'single_unique_null_constrained' => null,
            'composite_unique1' => $fieldValue,
            'composite_unique2' => $fieldValue,
            'composite_unique_null_constrained1' => null,
            'composite_unique_null_constrained2' => null
        ];
        $this->givenIHaveARecordStoredWithData($recordData);
        return $recordData;
    }

}
