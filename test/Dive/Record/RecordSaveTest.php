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
use Dive\Record\Generator\RecordGenerator;
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
        $record = $table->createRecord();
        $record->fromArray($saveGraph);

        $rm->save($record);

        $expectedRecordsForSave = array($record);
        $this->assertScheduledRecordsForCommit($rm, $expectedRecordsForSave, array());

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

        return $testCases;
    }

}
