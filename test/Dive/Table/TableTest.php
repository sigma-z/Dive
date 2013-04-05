<?php
 /*
 * This file is part of the Dive ORM framework.
 * (c) Steffen Zeidler <sigma_z@sigma-scripts.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
/**
 * @author  Steffen Zeidler <sigma_z@sigma-scripts.de>
 * @created 01.03.13
 */

namespace Dive\Test\Table;


use Dive\Table;
use Dive\TestSuite\TestCase;


class TableTest extends TestCase
{

    /**
     * @dataProvider provideDatabaseAwareTestCases
     */
    public function testFindByFk($database)
    {
        // prepare
        $rm = self::createRecordManager($database);
        $table = $rm->getTable('user');
        $data = array(
            'username' => 'John Doe',
            'password' => 'my secret'
        );
        $id = self::insertDataset($table, $data);
        $this->assertTrue($id !== false);

        // execute unit
        $record = $table->findByPk($id);

        // assert
        $this->assertInstanceOf('\Dive\Record', $record);
        $this->assertEquals('user', $record->getTable()->getTableName());
        $this->assertEquals($id, $record->id);
    }


    /**
     * @dataProvider provideDatabaseAwareTestCases
     */
    public function testFindByFkOnNonExistingRecord($database)
    {
        $rm = self::createRecordManager($database);
        $record = $rm->getTable('user')->findByPk(10);
        $this->assertFalse($record);
    }


    /**
     * @dataProvider provideGetRelations
     */
    public function testGetRelations($tableName, $expectedOwning, $expectedReferenced)
    {
        $rm = self::createDefaultRecordManager();
        $table = $rm->getTable($tableName);
        $relations = $table->getRelations();
        $actualOwning = array();
        $actualReferenced = array();
        foreach ($relations as $name => $relation) {
            if ($relation->isOwningSide($name)) {
                $actualOwning[] = $name;
            }
            else {
                $actualReferenced[] = $name;
            }
        }

        $this->assertEquals($expectedOwning, $actualOwning);
        $this->assertEquals($expectedReferenced, $actualReferenced);
    }


    public function provideGetRelations()
    {
        $testCases = array();

        $testCases[] = array(
            'tableName' => 'article2tag',
            'owning' => array('Article', 'Tag'),
            'referenced' => array()
        );

        return $testCases;
    }

}