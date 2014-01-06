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
        $rm->save($record);

        $expectedRecordsForSave = $recordsToInsert;
        if ($record->exists()) {
            $expectedRecordsForSave[] = $record;
        }
        $this->assertScheduledRecordsForCommit($rm, $expectedRecordsForSave, array());

        $this->markTestIncomplete('TODO: Setting foreign key fields and updating identifiers in reference map');

        $rm->commit();

        // TODO assert that records has been saved and all references to them are updated
        //$this->assertRecordsSaved($expectedRecordsForSave);

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
                    'lastname' => 'Hanson'
                )
            )
        );

        $testCases[] = array(
            'tableName' => 'author',
            'saveGraph' => array(
                'firstname' => 'Carl',
                'lastname' => 'Hanson',
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
}
