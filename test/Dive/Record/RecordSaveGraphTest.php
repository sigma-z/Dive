<?php
/*
 * This file is part of the Dive ORM framework.
 * (c) Steffen Zeidler <sigma_z@sigma-scripts.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dive\Test\Record;

use Dive\Record;
use Dive\Table;
use Dive\TestSuite\TestCase;

/**
 * @author  Steffen Zeidler <sigma_z@sigma-scripts.de>
 * @created 02.08.13
 */
class RecordSaveGraphTest extends TestCase
{

    /**
     * @dataProvider provideSaveGraph
     */
    public function testSaveGraph($tableName, array $graphData, $expectedOperation)
    {
        $this->markTestSkipped();

        $rm = self::createDefaultRecordManager();
        $table = $rm->getTable($tableName);

        $record = $table->createRecord();
        $record->fromArray($graphData);
        $changeSet = $record->save();

        $this->assertTrue($record->exists());

        $method = 'getScheduledFor' . ucfirst($expectedOperation);
        $affected = call_user_func(array($changeSet, $method));

        $this->assertTrue(in_array($record, $affected, true));
        $this->assertSavedGraph($graphData, $record);

        $this->assertNumOfRecords($affected);
    }


    private function assertSavedGraph(array $graphData, Record $record)
    {
        $table = $record->getTable();
        foreach ($table->getRelations() as $relAlias => $relation) {
            if (!empty($graphData[$relAlias])) {
                $this->assertTrue($relation->hasReferenceFor($record, $relAlias));

                /** @var \Dive\Record[] $related */
                $related = $record->get($relAlias);
                $refField = $relation->getReferencedField();
                $owningField = $relation->getOwnerField();

                if ($relation->isOwningSide($relAlias) || !$relation->isOneToMany()) {
                    $related = array($related);
                }

                foreach ($related as $relatedRecord) {
                    if ($relation->isOwningSide($relAlias)) {
                        $backRelAlias = $relation->getReferencedAlias();
                        $recordId = $relatedRecord->get($refField);
                        $foreignKeyFieldValue = $record->get($owningField);
                    }
                    else {
                        $backRelAlias = $relation->getOwnerAlias();
                        $recordId = $record->get($refField);
                        $foreignKeyFieldValue = $relatedRecord->get($owningField);
                    }
                    $message = "Expected that back reference from table '"
                        . $relatedRecord->getTable()
                        . "' to '$backRelAlias' is set!";

                    $this->assertTrue($relation->hasReferenceFor($relatedRecord, $backRelAlias), $message);
                    $this->assertEquals($recordId, $foreignKeyFieldValue);

                    $this->assertSavedGraph($graphData[$relAlias], $relatedRecord);
                }
            }
        }
    }


    /**
     *
     * @param \Dive\Record[] $affected
     */
    private function assertNumOfRecords($affected)
    {
        /** @var Table[] $tables */
        $tables = array();
        $actualNumOfRecords = array();
        foreach ($affected as $record) {
            $table = $record->getTable();
            $tableName = $table->getTableName();
            if (!isset($tables[$tableName])) {
                $tables[$tableName] = $table;
                $actualNumOfRecords[$tableName] = 0;
            }
            $actualNumOfRecords[$tableName]++;
        }

        foreach ($actualNumOfRecords as $tableName => $numOfRecords) {
            $expected = $tables[$tableName]->getRepository()->count();
            $actual = $numOfRecords;
            $message = "Number of records does not match number of records in repository";
            $this->assertEquals($expected, $actual, $message);

            $expected = $tables[$tableName]->createQuery()->select('COUNT(*)')->fetchSingleScalar();
            $message = "Number of records does not match number of records in database table";
            $this->assertEquals($expected, $actual, $message);
        }
    }


    public function provideSaveGraph()
    {
        $testCases = array();

        $testCases[] = array(
            'table' => 'author',
            'graph' => array(
                'firstname' => 'John',
                'lastname' => 'Doe',
                'email' => 'jdo@example.com',
                'User' => array(
                    'username' => 'John',
                    'password' => 'secret'
                )
            ),
            'expectedOperation' => 'insert'
        );

        $testCases[] = array(
            'table' => 'user',
            'graph' => array(
                'username' => 'John',
                'password' => 'secret',
                'Author' => array(
                    'firstname' => 'John',
                    'lastname' => 'Doe',
                    'email' => 'jdo@example.com',
                )
            ),
            'expectedOperation' => 'insert'
        );

        $testCases[] = array(
            'table' => 'author',
            'graph' => array(
                'firstname' => 'John',
                'lastname' => 'Doe',
                'email' => 'jdo@example.com',
                'User' => array(
                    'username' => 'John',
                    'password' => 'secret'
                ),
                'Editor' => array(
                    'firstname' => 'Lisa',
                    'lastname' => 'Wood',
                    'email' => 'lwo@example.com',
                    'User' => array(
                        'username' => 'Lisa',
                        'password' => 'secret'
                    )
                )
            ),
            'expectedOperation' => 'insert'
        );

        $testCases[] = array(
            'table' => 'author',
            'graph' => array(
                'firstname' => 'John',
                'lastname' => 'Doe',
                'email' => 'jdo@example.com',
                'User' => array(
                    'username' => 'John',
                    'password' => 'secret'
                ),
                'Author' => array(
                    array(
                        'firstname' => 'Lisa',
                        'lastname' => 'Wood',
                        'email' => 'lwo@example.com',
                        'User' => array(
                            'username' => 'Lisa',
                            'password' => 'secret'
                        )
                    ),
                    array(
                        'firstname' => 'Sue',
                        'lastname' => 'Miller',
                        'email' => 'smi@example.com',
                        'User' => array(
                            'username' => 'Sue',
                            'password' => 'secret'
                        )
                    )
                )
            ),
            'expectedOperation' => 'insert'
        );

        return $testCases;
    }

}