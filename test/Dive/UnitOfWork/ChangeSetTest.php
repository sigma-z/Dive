<?php
/*
 * This file is part of the Dive ORM framework.
 * (c) Steffen Zeidler <sigma_z@sigma-scripts.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dive\Test\UnitOfWork;

use Dive\Record;
use Dive\RecordManager;
use Dive\TestSuite\TestCase;
use Dive\UnitOfWork\ChangeSet;

/**
 * @author  Steffen Zeidler <sigma_z@sigma-scripts.de>
 * @created 02.08.13
 */
class ChangeSetTest extends TestCase
{

    /** @var RecordManager */
    private static $rm = null;


    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();

        self::$rm = self::createDefaultRecordManager();
    }


//    public function testCalcSaveGraphWithExistingRecordsOneToOneOwningSide()
//    {
//        $table = self::$rm->getTable('author');
//
//        $graphData = array(
//            'firstname' => 'John',
//            'lastname' => 'Doe',
//            'email' => 'jdo@example.com',
//            'User' => array(
//                'username' => 'John',
//                'password' => 'secret'
//            )
//        );
//        $record = $table->createRecord();
//        $record->fromArray($graphData);
//
//        $this->assertTrue($record->exists());
//    }


    /**
     * @dataProvider provideCalcSaveGraph
     *
     * @param string $tableName
     * @param array  $graphData
     * @param array  $expectedScheduled
     */
    public function testCalcSaveGraph($tableName, array $graphData, array $expectedScheduled)
    {
        $table = self::$rm->getTable($tableName);

        $record = $table->createRecord();
        $record->fromArray($graphData);

        $changeSet = new ChangeSet();
        $changeSet->calculateSave($record);

        $this->assertChangeSet($expectedScheduled, $changeSet);
    }


    public function provideCalcSaveGraph()
    {
        $testCases = array();

        $testCases[] = array(
            'table' => 'author',
            'graphData' => array(
                'firstname' => 'John',
                'lastname' => 'Doe',
                'email' => 'jdo@example.com',
                'User' => array(
                    'username' => 'John',
                    'password' => 'secret'
                )
            ),
            'expectedScheduled' => array(
                'inserts' => array(
                    array(
                        'table' => 'user',
                        'fields' => array('username' => 'John')
                    ),
                    array(
                        'table' => 'author',
                        'fields' => array('email' => 'jdo@example.com')
                    )
                ),
                'updates' => array(),
                'deletes' => array()
            )
        );

        $testCases[] = array(
            'table' => 'user',
            'graphData' => array(
                'username' => 'John',
                'password' => 'secret',
                'Author' => array(
                    'firstname' => 'John',
                    'lastname' => 'Doe',
                    'email' => 'jdo@example.com'
                )
            ),
            'expectedScheduled' => array(
                'inserts' => array(
                    array(
                        'table' => 'user',
                        'fields' => array('username' => 'John')
                    ),
                    array(
                        'table' => 'author',
                        'fields' => array('email' => 'jdo@example.com')
                    )
                ),
                'updates' => array(),
                'deletes' => array()
            )
        );

        $testCases[] = array(
            'table' => 'user',
            'graphData' => array(
                'username' => 'John',
                'password' => 'secret',
                'Author' => array(
                    'firstname' => 'John',
                    'lastname' => 'Doe',
                    'email' => 'jdo@example.com',
                    'Editor' => array(
                        'firstname' => 'Edith',
                        'lastname' => 'Marple',
                        'email' => 'ema@example.com',
                    )
                )
            ),
            'expectedScheduled' => array(
                'inserts' => array(
                    array(
                        'table' => 'user',
                        'fields' => array('username' => 'John')
                    ),
                    array(
                        'table' => 'author',
                        'fields' => array('email' => 'ema@example.com')
                    ),
                    array(
                        'table' => 'author',
                        'fields' => array('email' => 'jdo@example.com')
                    )
                ),
                'updates' => array(),
                'deletes' => array()
            )
        );

        $testCases[] = array(
            'table' => 'user',
            'graphData' => array(
                'username' => 'John',
                'password' => 'secret',
                'Author' => array(
                    'firstname' => 'John',
                    'lastname' => 'Doe',
                    'email' => 'jdo@example.com',
                    'Editor' => array(
                        'firstname' => 'Edith',
                        'lastname' => 'Marple',
                        'email' => 'ema@example.com',
                        'Author' => array(
                            array(
                                'firstname' => 'Jamie T.',
                                'lastname' => 'Kirk',
                                'email' => 'jki@example.com'
                            ),
                            array(
                                'firstname' => 'Bart',
                                'lastname' => 'Simpson',
                                'email' => 'bsi@example.com',
                                'User' => array(
                                    'username' => 'bartman',
                                    'password' => 'ay-caramba'
                                )
                            )
                        )
                    )
                )
            ),
            'expectedScheduled' => array(
                'inserts' => array(
                    array(
                        'table' => 'user',
                        'fields' => array('username' => 'John')
                    ),
                    array(
                        'table' => 'author',
                        'fields' => array('email' => 'ema@example.com')
                    ),
                    array(
                        'table' => 'author',
                        'fields' => array('email' => 'jki@example.com')
                    ),
                    array(
                        'table' => 'user',
                        'fields' => array('username' => 'bartman')
                    ),
                    array(
                        'table' => 'author',
                        'fields' => array('email' => 'bsi@example.com')
                    ),
                    array(
                        'table' => 'author',
                        'fields' => array('email' => 'jdo@example.com')
                    )
                ),
                'updates' => array(),
                'deletes' => array()
            )
        );

        return $testCases;
    }


    /**
     * @param array                      $expectedData
     * @param \Dive\UnitOfWork\ChangeSet $changeSet
     */
    private function assertChangeSet(array $expectedData, ChangeSet $changeSet)
    {
        $this->assertScheduledRecords($expectedData['deletes'], $changeSet->getScheduledForDelete());
        $this->assertScheduledRecords($expectedData['updates'], $changeSet->getScheduledForUpdate());
        $this->assertScheduledRecords($expectedData['inserts'], $changeSet->getScheduledForInsert());
    }


    /**
     * @param array             $expectedSchedules
     * @param \Dive\Record[]    $scheduledRecords
     */
    private function assertScheduledRecords(array $expectedSchedules, array $scheduledRecords)
    {
        $this->assertEquals(count($expectedSchedules), count($scheduledRecords));
        foreach ($scheduledRecords as $index => $record) {
            $this->assertEquals($expectedSchedules[$index]['table'], $record->getTable()->getTableName());
            $actualFields = array();
            foreach ($expectedSchedules[$index]['fields'] as $fieldName => $value) {
                $actualFields[$fieldName] = $record->get($fieldName);
            }
            $this->assertEquals($expectedSchedules[$index]['fields'], $actualFields);
        }
    }

}