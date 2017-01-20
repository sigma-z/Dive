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
use Dive\TestSuite\RecordBehaviorTrait;
use Dive\TestSuite\TestCase;

/**
 * Class FindOrCreateRecordTableTest
 * @author  Steffen Zeidler <sigma_z@sigma-scripts.de>
 * @created 11.07.2014
 */
class FindOrCreateRecordTableTest extends TestCase
{

    use RecordBehaviorTrait;

    /** @var RecordManager */
    private $rm;

    /** @var Table */
    private $table;

    /** @var Record */
    private $storedRecord;

    /** @var Record */
    private $resultRecord;


    protected function setUp()
    {
        parent::setUp();
        $this->rm = $this->givenIHaveARecordManager();
    }


    public function testStoredRecordCanBeFoundByPrimaryKey()
    {
        $this->table = $this->givenIHaveATable('user');
        $this->storedRecord = $this->givenIHaveStoredARecord('user', ['username' => 'Hugo']);

        $this->whenIFindOrCreateByPrimaryKey();

        $this->thenItShouldHaveFoundOneMatchingRecord();
    }


    public function testNoneExistingPrimaryKey()
    {
        $this->table = $this->givenIHaveATable('user');

        $this->whenIFindOrCreateTheRecordWithData(['username' => 'Hugo', 'id' => 123]);

        $this->thenItShouldNotHaveFoundAnyMatchingRecord();
        $this->thenItShouldHaveCreatedARecordWithData(['username' => 'Hugo', 'id' => 123]);
    }


    public function testExistingRecordCanBeFoundByUniqueIndex()
    {
        $this->table = $this->givenIHaveATable('user');
        $this->storedRecord = $this->givenIHaveStoredARecord('user', ['username' => 'Hugo']);

        $this->whenIFindOrCreateTheRecordWithData(['username' => 'Hugo']);

        $this->thenItShouldHaveFoundOneMatchingRecord();
    }


    public function testNoneMatchingUniqueIndex()
    {
        $this->table = $this->givenIHaveATable('user');

        $this->whenIFindOrCreateTheRecordWithData(['username' => 'Hugo']);

        $this->thenItShouldNotHaveFoundAnyMatchingRecord();
        $this->thenItShouldHaveCreatedARecordWithData(['username' => 'Hugo']);
    }


    public function testFindRecordByRepository()
    {
        $this->table = $this->givenIHaveATable('user');
        $this->storedRecord = $this->givenIHaveStoredARecord('user', ['username' => 'Hugo']);

        $this->whenIFindOrCreateTheRecordFromRepository();

        $this->thenItShouldHaveFoundOneMatchingRecord();
    }


    /**
     * @expectedException \Dive\Table\TableException
     */
    public function testFindOrCreateRecordByUniqueKeyFindsMoreThanOneRecordsThrowsException()
    {
        $this->table = $this->givenIHaveATable('unique_constraint_test');
        $this->givenIHaveStoredAUniqueConstraintTestRecord('test');
        $this->givenIHaveStoredAUniqueConstraintTestRecord('123');

        $this->whenIFindOrCreateTheRecordWithData([
            'single_unique_null_constrained' => 'test', 'single_unique' => '123'
        ]);
    }


    public function testUniqueIndexWithIncompleteFieldValues()
    {
        $this->table = $this->givenIHaveATable('unique_constraint_test');
        $this->givenIHaveStoredAUniqueConstraintTestRecord('test');

        $this->whenIFindOrCreateTheRecordWithData(['composite_unique1' => 'test', 'composite_unique_null_constrained1' => 'test']);

        $this->thenItShouldNotHaveFoundAnyMatchingRecord();
        $this->thenItShouldHaveCreatedARecordWithData(['composite_unique1' => 'test', 'composite_unique_null_constrained1' => 'test']);
    }


    public function testTableHasNoUniqueIndexes()
    {
        $this->table = $this->givenIHaveATable('tree_node');

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
        $this->table = $this->givenIHaveATable('unique_constraint_test');
        $this->givenIHaveStoredAUniqueConstraintTestRecord('abc', $fieldValuesForFind);

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
                    'single_unique_null_constrained' => null,
                ],
                'found' => true
            ],
            [
                'fieldValuesForFind' => [
                    'single_unique_null_constrained' => 'test',
                ],
                'found' => true
            ],
            [
                'fieldValuesForFind' => [
                    'single_unique' => null,
                ],
                'found' => false
            ],
            [
                'fieldValuesForFind' => [
                    'single_unique' => 'test',
                ],
                'found' => true
            ],
            [
                'fieldValuesForFind' => [
                    'composite_unique_null_constrained1' => null,
                    'composite_unique_null_constrained2' => 'test'
                ],
                'found' => true
            ],
            [
                'fieldValuesForFind' => [
                    'composite_unique_null_constrained1' => null,
                    'composite_unique_null_constrained2' => null
                ],
                'found' => true
            ],
            [
                'fieldValuesForFind' => [
                    'composite_unique_null_constrained1' => 'test',
                    'composite_unique_null_constrained2' => 'test'
                ],
                'found' => true
            ],
            [
                'fieldValuesForFind' => [
                    'composite_unique1' => 'test',
                    'composite_unique2' => 'test'
                ],
                'found' => true
            ],
            [
                'fieldValuesForFind' => [
                    'composite_unique1' => 'test',
                    'composite_unique2' => null
                ],
                'found' => false
            ],
            [
                'fieldValuesForFind' => [
                    'composite_unique1' => null,
                    'composite_unique2' => null
                ],
                'found' => false
            ],
        ];
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

}
