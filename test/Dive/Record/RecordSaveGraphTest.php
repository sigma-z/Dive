<?php
/*
 * This file is part of the Dive ORM framework.
 * (c) Steffen Zeidler <sigma_z@sigma-scripts.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dive\Test\Record;

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
        $rm = self::createDefaultRecordManager();
        $table = $rm->getTable($tableName);

        $record = $table->createRecord();
        $record->fromArray($graphData);
        $changeSet = $record->save();

        $this->assertTrue($record->exists());

        $method = 'getScheduledFor' . ucfirst($expectedOperation);
        $affected = call_user_func(array($changeSet, $method));
        $this->assertTrue(in_array($record, $affected, true));

        // TODO generalize assertion for data provider
        $this->assertEquals($record->User->getIdentifierAsString(), $record->user_id);
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

        return $testCases;
    }

}