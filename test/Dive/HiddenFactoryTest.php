<?php
/*
 * This file is part of the Dive ORM framework.
 * (c) Steffen Zeidler <sigma_z@sigma-scripts.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dive\Test;

use Dive\Record;
use Dive\Table;
use Dive\TestSuite\TestCase;

/**
 * @author Steven Nikolic <steven@nindoo.de>
 * Date: 24.10.13
 */
class HiddenFactoryTest extends TestCase
{
    public function testTableConstructorIsProtected()
    {
        $reflectedTable = new \ReflectionClass('\Dive\Table');
        /** @var $methods \ReflectionMethod[] */
        $methods = array();
        $methods[] = $reflectedTable->getConstructor();
        $methods[] = $reflectedTable->getMethod('createRecordManagerTable');
        $methods[] = $reflectedTable->getMethod('createTableRecord');
        foreach ($methods as $method) {
            $this->assertNotNull($method);
            $this->assertTrue($method->isProtected());
            $this->assertTrue($method->isFinal());
        }
    }


    /**
     * @dataProvider provideInsertUpdateDeleteDatabaseAware
     * @param array $database
     * @param string $tableName
     */
    public function testCreateTable($database, $tableName)
    {
        $recordManager = $this->createRecordManager($database);
        $table = $recordManager->getTable($tableName);
        $this->assertInstanceOf('\Dive\Table', $table);
    }


    public function testRecordConstructorIsProtected()
    {
        $reflectedRecord = new \ReflectionClass('\Dive\Record');
        $recordConstructor = $reflectedRecord->getConstructor();
        $this->assertTrue($recordConstructor->isProtected());
        $this->assertTrue($recordConstructor->isFinal());
    }


    /**
     * @dataProvider provideInsertUpdateDeleteDatabaseAware
     * @param array $database
     * @param string $tableName
     */
    public function testCreateRecordWithRecordManager($database, $tableName)
    {
        $recordManager = $this->createRecordManager($database);
        $record = $recordManager->getRecord($tableName, array());
        $this->assertInstanceOf('\Dive\Record', $record);
    }


    /**
     * @dataProvider provideInsertUpdateDeleteDatabaseAware
     * @param array $database
     * @param string $tableName
     */
    public function testCreateRecordWithTable($database, $tableName)
    {
        $recordManager = $this->createRecordManager($database);
        $table = $recordManager->getTable($tableName);
        $record = $table->createRecord();
        $this->assertInstanceOf('\Dive\Record', $record);
    }


    /**
     * @return array
     */
    public function provideInsertUpdateDeleteDatabaseAware()
    {
        $databases = $this->provideDatabaseAwareTestCases();
        $tableNameTestCases = $this->provideTableNameTestCases();
        return self::combineTestCases($databases, $tableNameTestCases);
    }
}