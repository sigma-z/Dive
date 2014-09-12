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
use Dive\Relation\ReferenceMap;
use Dive\Relation\Relation;
use Dive\Table;
use Dive\TestSuite\TableRowsProvider;
use Dive\TestSuite\TestCase;

/**
 * @author  Steffen Zeidler <sigma_z@sigma-scripts.de>
 * @created 30.12.13
 */
class RecordSaveTest extends TestCase
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
     * @dataProvider provideSave
     * @param string $tableName
     * @param array  $saveGraph
     */
    public function testSave($tableName, array $saveGraph)
    {
        $rm = self::createDefaultRecordManager();

        $table = $rm->getTable($tableName);
        $this->translateRecordKeysToIdentifiersInSaveGraph($table, $saveGraph);

        $record = $table->createRecord();
        $record->fromArray($saveGraph);

        $recordsToInsert = self::getRecordsToInsertFromGraph($record);
        $recordReferenceMaps = self::getRecordsToInsertReferenceMaps($recordsToInsert);

        $rm->scheduleSave($record);
        $rm->commit();

        $this->assertRecordsInserted($recordsToInsert, $recordReferenceMaps);

        // assert that no record is scheduled for commit anymore
        $this->assertScheduledOperationsForCommit($rm, 0, 0);
    }


    /**
     * @return array[]
     */
    public function provideSave()
    {
        $testCases = array();

        $testCases[] = array(
            'tableName' => 'user',
            'saveGraph' => array(
                'username' => 'CarlH',
                'password' => 'my-secret'
            )
        );

        $testCases[] = array(
            'tableName' => 'user',
            'saveGraph' => array(
                'username' => 'CarlH',
                'password' => 'my-secret',
                'Author' => array(
                    'firstname' => 'Carl',
                    'lastname' => 'Hanson',
                    'email' => 'c.hanson@example.com'
                )
            )
        );

        $testCases[] = array(
            'tableName' => 'author',
            'saveGraph' => array(
                'firstname' => 'Carl',
                'lastname' => 'Hanson',
                'email' => 'c.hanson@example.com',
                'User' => array(
                    'username' => 'CarlH',
                    'password' => 'my-secret'
                )
            )
        );

        $testCases[] = array(
            'tableName' => 'user',
            'saveGraph' => array(
                'username' => 'CarlH',
                'password' => 'my-secret',
                'Author' => array(
                    'recordKey' => 'Jamie T. Kirk'
                )
            )
        );

        $testCases[] = array(
            'tableName' => 'article',
            'saveGraph' => array(
                'recordKey' => 'DiveORM released',
                'Author' => array(
                    'recordKey' => 'Jamie T. Kirk'
                ),
                'Comment' => array(
                    array('recordKey' => 'DiveORM released#1'),
                    array(
                        'title' => 'Still waiting to see more of it...',
                        'text' => 'I really can NOT wait any longer!',
                        'User' => array(
                            'recordKey' => 'BartS'
                        ),
                        'datetime' => '2014-01-17 14:31:00'
                    )
                )
            )
        );

        $testCases[] = array(
            'tableName' => 'article',
            'saveGraph' => array(
                'title' => 'test article',
                'teaser' => 'test article',
                'text' => 'test article',
                'changed_on' => '2014-01-17 22:04:00',
                'Author' => array(
                    'recordKey' => 'Jamie T. Kirk'
                ),
                'Comment' => array(
                    array('recordKey' => 'DiveORM released#1'),
                    array(
                        'title' => 'Still waiting to see more of it...',
                        'text' => 'I really can NOT wait any longer!',
                        'User' => array(
                            'recordKey' => 'BartS'
                        ),
                        'datetime' => '2014-01-17 14:31:00'
                    )
                )
            )
        );

        return $testCases;
    }


    /**
     * @param  Record $record
     * @param  array  $visited
     * @return Record[]
     */
    private static function getRecordsToInsertFromGraph(Record $record, array $visited = array())
    {
        $oid = $record->getOid();
        if (in_array($oid, $visited)) {
            return array();
        }
        $visited[] = $oid;

        $recordsToInsert = array();
        if (!$record->exists()) {
            $recordsToInsert[] = $record;
        }

        $table = $record->getTable();
        $relations = $table->getRelations();
        foreach ($relations as $relationName => $relation) {
            if ($relation->hasReferenceLoadedFor($record, $relationName)) {
                $related = $relation->getReferenceFor($record, $relationName);
                if ($related) {
                    if ($related instanceof RecordCollection) {
                        foreach ($related as $relatedRecord) {
                            $relatedRecordsToInsert = self::getRecordsToInsertFromGraph($relatedRecord, $visited);
                            $recordsToInsert = array_merge($recordsToInsert, $relatedRecordsToInsert);
                        }
                    }
                    else {
                        $relatedRecordsToInsert = self::getRecordsToInsertFromGraph($related, $visited);
                        $recordsToInsert = array_merge($recordsToInsert, $relatedRecordsToInsert);
                    }
                }
            }
        }
        return $recordsToInsert;
    }


    /**
     * @param  Record[] $recordsToInsert
     * @return array[]
     */
    private static function getRecordsToInsertReferenceMaps(array $recordsToInsert)
    {
        $recordReferencesMap = array();
        foreach ($recordsToInsert as $record) {
            $recordReferencesMap[$record->getOid()] = self::getRecordReferences($record);
        }
        return $recordReferencesMap;
    }


    /**
     * @param Record $record
     * @return array
     */
    private static function getRecordReferences(Record $record)
    {
        $map = array();
        $table = $record->getTable();
        $relations = $table->getRelations();
        foreach ($relations as $relationName => $relation) {
            if ($relation->hasReferenceLoadedFor($record, $relationName)) {
                /** @var RecordCollection|Record[]|Record $related */
                $related = $relation->getReferenceFor($record, $relationName);
                if ($related) {
                    if ($related instanceof RecordCollection) {
                        $map[$relationName] = array();
                        foreach ($related as $relatedRecord) {
                            $map[$relationName][] = $relatedRecord->getOid();
                        }
                    }
                    else if ($related instanceof Record) {
                        $map[$relationName] = $related->getOid();
                    }
                }
            }
        }
        return $map;
    }


    /**
     * @param \Dive\Table $table
     * @param array       $saveGraph
     */
    private function translateRecordKeysToIdentifiersInSaveGraph(Table $table, array &$saveGraph)
    {
        if (isset($saveGraph['recordKey'])) {
            $record = $this->getGeneratedRecord($this->recordGenerator, $table, $saveGraph['recordKey']);
            $saveGraph = array_merge($saveGraph, $record->getIdentifierFieldIndexed());
            $saveGraph[Record::FROM_ARRAY_EXISTS_KEY] = true;
            unset($saveGraph['recordKey']);
        }

        $rm = $table->getRecordManager();
        $relations = $table->getRelations();
        foreach ($relations as $relationName => $relation) {
            if (isset($saveGraph[$relationName])) {
                $relatedTable = $relation->getJoinTable($rm, $relationName);
                if ($relation->isOwningSide($relationName) && $relation->isOneToMany()) {
                    foreach ($saveGraph[$relationName] as &$related) {
                        $this->translateRecordKeysToIdentifiersInSaveGraph($relatedTable, $related);
                    }
                }
                else {
                    $this->translateRecordKeysToIdentifiersInSaveGraph($relatedTable, $saveGraph[$relationName]);
                }
            }
        }
    }


    /**
     * @param Record[] $recordsInserted
     * @param array[]  $recordReferenceMaps
     */
    private function assertRecordsInserted(array $recordsInserted, array $recordReferenceMaps)
    {
        foreach ($recordsInserted as $record) {
            $this->assertIdentifierUpdatedInRepository($record);
            $oid = $record->getOid();
            $this->assertRecordReferenceMap($record, $recordReferenceMaps[$oid]);
        }
    }


    /**
     * @param Record $record
     */
    private function assertIdentifierUpdatedInRepository(Record $record)
    {
        $repository = $record->getTable()->getRepository();
        $internalId = $record->getInternalId();
        $this->assertStringStartsNotWith('_', $internalId[0]);
        $this->assertTrue($repository->hasByInternalId($internalId));
    }


    /**
     * @param Record $record
     * @param array  $referenceMap
     */
    private function assertRecordReferenceMap(Record $record, array $referenceMap)
    {
        if (empty($referenceMap)) {
            return;
        }

        foreach ($referenceMap as $relationName => $refObjectIds) {
            $relation = $record->getTableRelation($relationName);
            if ($relation->isOwningSide($relationName)) {
                $this->assertOwningRelatedReferences($record, $relation, $refObjectIds);
            }
            else {
                $this->assertReferencedRelatedReferences($record, $relation, $refObjectIds);
            }
        }
    }


    /**
     * @param Record        $referencedRecord
     * @param Relation      $relation
     * @param string|array  $refObjectIds
     */
    private function assertOwningRelatedReferences(Record $referencedRecord, Relation $relation, $refObjectIds)
    {
        $oldIdentifier = Record::NEW_RECORD_ID_MARK . $referencedRecord->getOid();
        $newIdentifier = $referencedRecord->getInternalId();
        $rm = $referencedRecord->getRecordManager();

        /** @var ReferenceMap $referenceMap */
        $referenceMap = self::readAttribute($relation, 'map');
        $referenceMapping = $referenceMap->getMapping();
        // assert that references exists
        $this->assertArrayNotHasKey($oldIdentifier, $referenceMapping);
        $this->assertArrayHasKey($newIdentifier, $referenceMapping);

        // assert that correct references exists
        $refTable = $relation->getJoinTable($rm, $relation->getOwningAlias());
        $refTableRepository = $refTable->getRepository();
        if ($relation->isOneToMany()) {
            $expectedOwningIds = array();
            foreach ($refObjectIds as $refObjectId) {
                $expectedOwningIds[] = $refTableRepository->getByOid($refObjectId)->getInternalId();
            }
        }
        else {
            $expectedOwningIds = $refTableRepository->getByOid($refObjectIds)->getInternalId();
        }
        $this->assertEquals($expectedOwningIds, $referenceMapping[$newIdentifier]);

        // assert record collection
        if ($relation->isOneToMany()) {
            $recordCollection = $referenceMap->getRelatedCollection($referencedRecord->getOid());
            $this->assertNotNull($recordCollection);
            $this->assertCount(count($refObjectIds), $recordCollection);
        }
    }


    /**
     * @param Record    $owningRecord
     * @param Relation  $relation
     * @param string    $refObjectId
     */
    private function assertReferencedRelatedReferences(Record $owningRecord, Relation $relation, $refObjectId)
    {
        $oldIdentifier = Record::NEW_RECORD_ID_MARK . $owningRecord->getOid();
        $newIdentifier = $owningRecord->getInternalId();
        $rm = $owningRecord->getRecordManager();

        /** @var ReferenceMap $referenceMap */
        $referenceMap = self::readAttribute($relation, 'map');
        $referenceMapping = $referenceMap->getMapping();

        $refTable = $relation->getJoinTable($rm, $relation->getReferencedAlias());
        $refTableRepository = $refTable->getRepository();
        $referencedRecord = $refTableRepository->getByOid($refObjectId);
        $refId = $referencedRecord->getInternalId();

        // assert that owning record id is mapped for referenced record
        $this->assertArrayHasKey($refId, $referenceMapping);
        if ($relation->isOneToMany()) {
            $this->assertNotContains($oldIdentifier, $referenceMapping[$refId]);
            $this->assertContains($newIdentifier, $referenceMapping[$refId]);
        }
        else {
            $this->assertEquals($newIdentifier, $referenceMapping[$refId]);
        }

        // assert FALSE, because object field mapping is only for records, that are not stored in database, yet
        $this->assertFalse($referenceMap->hasFieldMapping($owningRecord->getOid()));

        // assert record collection
        if ($relation->isOneToMany()) {
            $recordCollection = $referenceMap->getRelatedCollection($referencedRecord->getOid());
            $this->assertNotNull($recordCollection);
            $this->assertTrue($recordCollection->has($owningRecord));
        }
    }
}
