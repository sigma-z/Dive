<?php
/*
 * This file is part of the Dive ORM framework.
 * (c) Steffen Zeidler <sigma_z@sigma-scripts.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dive\Test\Record;

use Dive\Collection\RecordCollection;
use Dive\Record;
use Dive\Record\Generator\RecordGenerator;
use Dive\RecordManager;
use Dive\Relation\ReferenceMap;
use Dive\TestSuite\TableRowsProvider;
use Dive\TestSuite\TestCase;

/**
 * @author  Steffen Zeidler <sigma_z@sigma-scripts.de>
 * @created 30.08.13
 */
class RecordDeleteTest extends TestCase
{

    /** @var RecordGenerator */
    private $recordGenerator;


    protected function setUp()
    {
        parent::setUp();

        $tableRows = TableRowsProvider::provideTableRows();
        $rm = self::createDefaultRecordManager();
        $this->recordGenerator = self::saveTableRows($rm, $tableRows);
    }


    /**
     * @dataProvider provideDelete
     *
     * @param array $loadRecordKeys
     * @param array $deleteRecordKeys
     * @param array $expectedDeleteRecordKeys
     * @param array $expectedSaveRecordKeys
     */
    public function testDelete(
        array $loadRecordKeys,
        array $deleteRecordKeys,
        array $expectedDeleteRecordKeys,
        array $expectedSaveRecordKeys
    ) {
        $rm = self::createDefaultRecordManager();

        foreach ($loadRecordKeys as $loadRecordKey => $tableName) {
            $table = $rm->getTable($tableName);
            $record = $this->getGeneratedRecord($this->recordGenerator, $table, $loadRecordKey);

            // load related references to test that the identifier is removed from references and related record collections
            $relations = $table->getRelations();
            $references = array_combine(array_keys($relations), array_fill(0, count($relations), true));
            $record->loadReferences($references);
        }

        foreach ($deleteRecordKeys as $deleteRecordKey => $tableName) {
            $table = $rm->getTable($tableName);
            $record = $this->getGeneratedRecord($this->recordGenerator, $table, $deleteRecordKey);

            $rm->scheduleDelete($record);
        }

        $expectedSaveRecords = $this->getRecordsForRecordKeys($rm, $expectedSaveRecordKeys);
        $expectedDeleteRecords = $this->getRecordsForRecordKeys($rm, $expectedDeleteRecordKeys);
        $deleteIdMap = array();
        foreach ($expectedDeleteRecords as $deleteRecordKey => $deleteRecord) {
            $deleteIdMap[$deleteRecordKey] = $deleteRecord->getInternalId();
        }
        $this->assertScheduledRecordsForCommit($rm, $expectedSaveRecords, $expectedDeleteRecords);

        $rm->commit();

        // assert that records has been deleted and all references to them are unset
        $this->assertRecordsDeleted($expectedDeleteRecords);

        // assert that no record is scheduled for commit anymore
        $this->assertScheduledOperationsForCommit($rm, 0, 0);
    }


    /**
     * @return array[]
     */
    public function provideDelete()
    {
        $testCases = array();

        $testCases[] = array(
            'loadRecordKeys' => array(
                'DiveORM released' => 'article',
                'JohnD' => 'user'
            ),
            'deleteRecordKeys' => array(
                'DiveORM released' => 'article',
                'helloWorld' => 'article',
                'JohnD' => 'user'
            ),
            'expectedDeleteRecordKeys' => array(
                'DiveORM released#1' => 'comment',
                'DiveORM released#News' => 'article2tag',
                'DiveORM released#Release Notes' => 'article2tag',
                'DiveORM released' => 'article',
                'helloWorld' => 'article',
                'John Doe' => 'author',
                'JohnD' => 'user'
            ),
            'expectedSaveRecordKeys' => array(
                'Bart Simon' => 'author'
            )
        );

        $testCases[] = array(
            'loadRecordKeys' => array(
                'DiveORM released#1' => 'comment',
                'DiveORM released' => 'article'
            ),
            'deleteRecordKeys' => array(
                'DiveORM released#1' => 'comment',
                'DiveORM released' => 'article'
            ),
            'expectedDeleteRecordKeys' => array(
                'DiveORM released#1' => 'comment',
                'DiveORM released#News' => 'article2tag',
                'DiveORM released#Release Notes' => 'article2tag',
                'DiveORM released' => 'article'
            ),
            'expectedSaveRecordKeys' => array()
        );

        // tests, that owning records are removed from related collections
        $testCases[] = array(
            'loadRecordKeys'            => array('AdamE' => 'user'),
            'deleteRecordKeys'          => array('tableSupport#1' => 'comment'),
            'expectedDeleteRecordKeys'  => array('tableSupport#1' => 'comment'),
            'expectedSaveRecordKeys'    => array()
        );

        return $testCases;
    }


    /**
     * @param  RecordManager $rm
     * @param  array         $recordKeys keys: record keys; values: table names
     * @return Record[]      recordKey as keys
     */
    private function getRecordsForRecordKeys(RecordManager $rm, array $recordKeys)
    {
        $records = array();
        foreach ($recordKeys as $recordKey => $tableName) {
            $table = $rm->getTable($tableName);
            $record = $this->getGeneratedRecord($this->recordGenerator, $table, $recordKey);
            $records[$recordKey] = $record;
        }
        return $records;
    }


    /**
     * @param Record[] $expectedDeletedRecords
     */
    private function assertRecordsDeleted(array $expectedDeletedRecords)
    {
        /** @noinspection PhpUnusedLocalVariableInspection */
        foreach ($expectedDeletedRecords as $recordKey => $record) {
            //echo (string)$record . " $recordKey\n";
            $table = $record->getTable();
            $identifier = $record->getIdentifier();
            $result = $table->findByPk($identifier);
            $message = "Record with id: " . (is_array($identifier) ? implode(', ', $identifier) : $identifier)
                . " was not deleted from table '" . $table->getTableName() . "'!";
            $this->assertFalse($result, $message);

            $identifierAsString = $record->getIdentifierAsString();
            $this->assertFalse($table->isInRepository($identifierAsString));

            $this->assertRecordNotReferenced($record);
        }
    }


    /**
     * @param Record $record
     */
    private function assertRecordNotReferenced(Record $record)
    {
        $internalId = $record->getInternalId();
        $table = $record->getTable();
        $relations = $table->getRelations();
        $message = "Record with id '$internalId' in table '" . $table->getTableName() . "'";
        foreach ($relations as $relationName => $relation) {
            $this->assertRecordNotReferencedByRelation($record, $relationName, $message);
        }
    }


    /**
     * @param Record $record
     * @param string $relationName
     * @param string $message
     */
    private function assertRecordNotReferencedByRelation(Record $record, $relationName, $message = '')
    {
        $internalId = $record->getInternalId();
        $relation = $record->getTableRelation($relationName);
        $isReferencedSide = $relation->isReferencedSide($relationName);

        /** @var ReferenceMap $referenceMap */
        $referenceMap = self::readAttribute($relation, 'map');

        /** @var RecordCollection[] $relatedCollections */
        $relatedCollections = self::readAttribute($referenceMap, 'relatedCollections');

        $references = $referenceMap->getMapping();
        $referencedMessage = $message . " expected not be referenced (relation '$relationName')";
        $relatedCollectionMessage = $message
            . " expected not to be in a related collection (relation '$relationName')";

        $oid = $record->getOid();
        if ($isReferencedSide) {
            $isOneToMany = $relation->isOneToMany();
            foreach ($references as $owningIds) {
                if ($isOneToMany) {
                    $this->assertNotContains($internalId, $owningIds, $referencedMessage);
                }
                else {
                    $this->assertNotEquals($internalId, $owningIds, $referencedMessage);
                }
            }

            if ($isOneToMany) {
                foreach ($relatedCollections as $relatedCollection) {
                    $this->assertFalse($relatedCollection->has($record), $relatedCollectionMessage);
                }
            }

            $this->assertFalse($referenceMap->hasFieldMapping($oid));
        }
        else {
            $this->assertArrayNotHasKey($internalId, $references, $referencedMessage);
            $this->assertArrayNotHasKey($oid, $relatedCollections, $relatedCollectionMessage);
        }
    }

}
