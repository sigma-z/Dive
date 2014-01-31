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
use Dive\Table;
use Dive\TestSuite\TestCase;

/**
 * @author  Steffen Zeidler <sigma_z@sigma-scripts.de>
 * @created 01.03.13
 */
class TableTest extends TestCase
{

    /**
     * @param string $database
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
     * @param string $database
     * @dataProvider provideDatabaseAwareTestCases
     */
    public function testFindByFkOnNonExistingRecord($database)
    {
        $rm = self::createRecordManager($database);
        $record = $rm->getTable('user')->findByPk(10);
        $this->assertFalse($record);
    }


    /**
     * @param string $database
     * @dataProvider provideDatabaseAwareTestCases
     * @expectedException \Dive\Table\TableException
     */
    public function testFindByFkOnNonMatchingIdentifier($database)
    {
        $rm = self::createRecordManager($database);
        $rm->getTable('user')->findByPk(array(10,10,10));
    }


    /**
     * @param string $tableName
     * @param array  $expectedOwning
     * @param array  $expectedReferenced
     * @dataProvider provideGetRelations
     */
    public function testGetRelations($tableName, array $expectedOwning, array $expectedReferenced)
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

        $this->assertEquals($expectedOwning, $actualOwning, 'Owning relations do not match!');
        $this->assertEquals($expectedReferenced, $actualReferenced, 'Referenced relations do not match!');
    }


    /**
     * @return array
     */
    public function provideGetRelations()
    {
        $testCases = array();

        $testCases[] = array(
            'tableName' => 'article2tag',
            'owning' => array(),
            'referenced' => array('Article', 'Tag')
        );

        $testCases[] = array(
            'tableName' => 'author',
            'owning' => array('Article', 'Author'),
            'referenced' => array('User', 'Editor')
        );

        $testCases[] = array(
            'tableName' => 'user',
            'owning' => array('Author', 'Comment'),
            'referenced' => array()
        );

        return $testCases;
    }


    /**
     * @param string $database
     * @dataProvider provideDatabaseAwareTestCases
     */
    public function testToString($database)
    {
        $rm = self::createRecordManager($database);
        $table = $rm->getTable('user');
        $tableToString = (string)$table;
        $this->assertInternalType('string', $tableToString);
    }


    public function testCreateRecord()
    {
        $rm = self::createDefaultRecordManager();
        $table = $rm->getTable('user');
        $this->assertInstanceOf('\Dive\TestSuite\Model\User', $table->createRecord());
    }


    /**
     * @param string $database
     * @param string $field
     * @param bool $throwsException
     * @dataProvider provideGetField
     */
    public function testGetField($database, $field, $throwsException)
    {
        if ($throwsException) {
            $this->setExpectedException('Dive\Table\TableException');
        }
        $rm = self::createRecordManager($database);
        $table = $rm->getTable('user');
        $table->getField($field);
    }


    /**
     * @return array
     */
    public function provideGetField()
    {
        $testCases = $this->provideDatabaseAwareTestCases();
        $fields = array(
            'id' => false,
            'username' => false,
            'password' => false,
            'notexistingfield' => true
        );
        foreach ($testCases as $key => $testCase) {
            foreach ($fields as $field => $throwsException) {
                $testCases[$key] = array_merge(
                    $testCase,
                    array('field' => $field, 'throwsException' => $throwsException)
                );
            }
        }
        return $testCases;
    }


    /**
     * @dataProvider provideGetIdentifierQueryExpression
     *
     * @param string $tableName
     * @param string $queryAlias
     * @param string $expected
     */
    public function testGetIdentifierQueryExpression($tableName, $queryAlias, $expected)
    {
        $rm = self::createDefaultRecordManager();
        $table = $rm->getTable($tableName);
        $actual = $table->getIdentifierQueryExpression($queryAlias);

        $idQuote = $rm->getConnection()->getIdentifierQuote();
        $actual = str_replace($idQuote, '', $actual);

        $this->assertEquals($expected, $actual);
    }


    /**
     * @return array[]
     */
    public function provideGetIdentifierQueryExpression()
    {
        $testCases = array();

        $testCases[] = array(
            'tableName' => 'article2tag',
            'queryAlias' => 'a',
            'expected' => "a.article_id || '" . Record::COMPOSITE_ID_SEPARATOR . "' || a.tag_id"
        );

        $testCases[] = array(
            'tableName' => 'article2tag',
            'queryAlias' => '',
            'expected' => "article_id || '" . Record::COMPOSITE_ID_SEPARATOR . "' || tag_id"
        );

        $testCases[] = array(
            'tableName' => 'user',
            'queryAlias' => 'a',
            'expected' => 'a.id'
        );

        $testCases[] = array(
            'tableName' => 'user',
            'queryAlias' => '',
            'expected' => 'id'
        );

        return $testCases;
    }

}
