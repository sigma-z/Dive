<?php
 /*
 * This file is part of the Dive ORM framework.
 * (c) Steffen Zeidler <sigma_z@sigma-scripts.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dive\Test\Table;


use Dive\Collection\Collection;
use Dive\Record;
use Dive\RecordManager;
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
        $this->assertEquals($id, $record->get('id'));
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


    /**
     * @param string $database
     * @dataProvider provideDatabaseAwareTestCases
     */
    public function testFindByUniqueIndexReturnsRecord($database)
    {
        $rm = self::createRecordManager($database);
        $table = $rm->getTable('user');
        $data = array(
            'username' => 'John Doe',
            'password' => 'my secret'
        );
        $id = self::insertDataset($table, $data);
        $this->assertTrue($id !== false);

        $record = $table->findByUniqueIndex('UNIQUE', array('username' => 'John Doe'));

        $this->assertInstanceOf('\Dive\Record', $record);
        $this->assertEquals('user', $record->getTable()->getTableName());
        $this->assertEquals($id, $record->id);
        $this->assertSame($table->findByPk($id), $record);
    }

    /**
     * @param string $database
     * @dataProvider provideDatabaseAwareTestCases
     */
    public function testFindByUniqueIndexReturnsFalse($database)
    {
        $rm = self::createRecordManager($database);
        $table = $rm->getTable('user');
        $data = array(
            'username' => 'John Doe',
            'password' => 'my secret'
        );
        $id = self::insertDataset($table, $data);
        $this->assertTrue($id !== false);

        $record = $table->findByUniqueIndex('UNIQUE', array('username' => 'Johanna Stuart'));

        $this->assertFalse($record);
    }


    /**
     * @param string $database
     * @dataProvider provideDatabaseAwareTestCases
     */
    public function testGetUniqueIndexes($database)
    {
        $rm = self::createRecordManager($database);
        $table = $rm->getTable('user');

        $actual = $table->getUniqueIndexes();

        $this->assertCount(1, $actual);
        $this->assertArrayHasKey('UNIQUE', $actual);
    }


    /**
     * @param string $database
     * @dataProvider provideDatabaseAwareTestCases
     */
    public function testGetUniqueIndexesOnTableWithoutUniques($database)
    {
        $rm = self::createRecordManager($database);
        $table = $rm->getTable('donation');
        $actual = $table->getUniqueIndexes();
        $this->assertEmpty($actual);
    }


    /**
     * @param string $database
     * @dataProvider provideDatabaseAwareTestCases
     */
    public function testGetUniqueIndexesOnTableWithTwoUniques($database)
    {
        $rm = self::createRecordManager($database);
        $table = $rm->getTable('author');
        $actual = $table->getUniqueIndexes();
        $this->assertCount(2, $actual);
        $this->assertArrayHasKey('UNIQUE', $actual);
        $this->assertArrayHasKey('UQ_user_id', $actual);
    }


    /**
     * @param $database
     * @param $tableName
     * @param $tableRows
     * @param $findFieldValues
     * @param $expectedRecords
     * @dataProvider provideFindByFields
     */
    public function testFindByFields(
        $database, $tableName, $tableRows, $findFieldValues, $expectedRecords
    )
    {
        $rm = self::createRecordManager($database);
        $recordGenerator = self::createRecordGenerator($rm);
        $recordGenerator
            ->setTableRows($tableName, $tableRows)
            ->generate();

        $table = $rm->getTable('author');
        $records = $table->findByFieldValues($findFieldValues);

        $this->assertInstanceOf('\Dive\Collection\Collection', $records);
        $this->assertEquals(count($expectedRecords), $records->count());

        $expectedCollection = new Collection();
        foreach ($expectedRecords as $alias) {
            $id = $recordGenerator->getRecordIdFromMap($tableName, $alias);
            $expectedCollection->add($table->findByPk($id));
        }
        foreach ($expectedCollection as $index => $expected) {
            $this->assertSame($expected, $records[$index]);
        }
    }


    public function provideFindByFields()
    {
        $johnDoe = array(
            'firstname' => 'John',
            'lastname' => 'Doe',
            'email' => 'john.doe@example.com'
        );
        $johnDoeJr = array(
            'firstname' => 'John Jr.',
            'lastname' => 'Doe',
            'email' => 'john.doe.junior@example.com'
        );

        $nullableUniqueRows = $this->getNullableUniqueRowsForAuthor();

        $databases = self::getDatabases();
        $testCases = array();
        foreach ($databases as $database) {
            $testCases[] = array(
                'database'  => $database,
                'table'     => 'author',
                'rows'      => array(
                    'JohnD' => $johnDoe,
                ),
                'findFields' => $johnDoe,
                'expectedRecords' => array('JohnD'),
            );
            $testCases[] = array(
                'database'  => $database,
                'table'     => 'author',
                'rows'      => array(
                    'JohnDJr' => $johnDoeJr,
                ),
                'findFields' => $johnDoe,
                'expectedRecords' => array(),
            );
            $testCases[] = array(
                'database'  => $database,
                'table'     => 'author',
                'rows'      => array(
                    'JohnD' => $johnDoe,
                    'JohnDJr' => $johnDoeJr
                ),
                'findFields' => $johnDoe,
                'expectedRecords' => array('JohnD'),
            );
            $testCases[] = array(
                'database'  => $database,
                'table'     => 'author',
                'rows'      => array(
                    'JohnD' => $johnDoe,
                    'JohnDJr' => $johnDoeJr
                ),
                'findFields' => array('lastname' => 'Doe'),
                'expectedRecords' => array('JohnD', 'JohnDJr'),
            );

            $testCases[] = array(
                'database'  => $database,
                'table'     => 'author',
                'rows'      => $nullableUniqueRows,
                'findFields' => array('lastname' => 'Anonymous'),
                'expectedRecords' => array('null_1', 'null_2', 'null_3', 'not_null')
            );
            $testCases[] = array(
                'database'  => $database,
                'table'     => 'author',
                'rows'      => $nullableUniqueRows,
                'findFields' => array('firstname' => null, 'lastname' => 'Anonymous'),
                'expectedRecords' => array('null_1', 'null_2', 'null_3'),
            );
        }
        return $testCases;
    }

    /**
     * @return array
     */
    private function getNullableUniqueRowsForAuthor()
    {
        $nullAnonymous1 = array(
            'firstname' => null,
            'lastname' => 'Anonymous',
            'email' => 'first.anonymous@example.com'
        );
        $nullAnonymous2 = array(
            'firstname' => null,
            'lastname' => 'Anonymous',
            'email' => 'second.anonymous@example.com'
        );
        $nullAnonymous3 = array(
            'firstname' => null,
            'lastname' => 'Anonymous',
            'email' => 'third.anonymous@example.com'
        );
        $notAnonymous = array(
            'firstname' => 'Not',
            'lastname' => 'Anonymous',
            'email' => 'not.anonymous@example.com'
        );
        $nullableUniqueRows = array(
            'null_1' => $nullAnonymous1,
            'null_2' => $nullAnonymous2,
            'null_3' => $nullAnonymous3,
            'not_null' => $notAnonymous
        );
        return $nullableUniqueRows;
    }


    /**
     * @param string $database
     * @dataProvider provideDatabaseAwareTestCases
     * @expectedException \Dive\Table\TableException
     */
    public function testNullableUniqueThrowsExceptionOnNotUniqueResult($database)
    {
        $rm = self::createRecordManager($database);
        $recordGenerator = self::createRecordGenerator($rm);
        $recordGenerator
            ->setTableRows('author', $this->getNullableUniqueRowsForAuthor())
            ->generate();

        $table = $rm->getTable('author');
        $table->findByUniqueIndex('UNIQUE', array('firstname' => null, 'lastname' => 'Anonymous'));
    }


    /**
     * @TODO: is this case, what we want? e.g. mySql allows this case, but should Dive work the same way?
     * @param string $database
     * @dataProvider provideDatabaseAwareTestCases
     */
    public function testNullableUniqueWorksLikeFindByFieldValuesWhenFetchModeIsCollection($database)
    {
        $rm = self::createRecordManager($database);
        $recordGenerator = self::createRecordGenerator($rm);
        $recordGenerator
            ->setTableRows('author', $this->getNullableUniqueRowsForAuthor())
            ->generate();

        $table = $rm->getTable('author');
        $records = $table->findByUniqueIndex(
            'UNIQUE',
            array('firstname' => null, 'lastname' => 'Anonymous'),
            RecordManager::FETCH_RECORD_COLLECTION
        );

        $expectedRecords = array('null_1', 'null_2', 'null_3');
        $this->assertInstanceOf('\Dive\Collection\Collection', $records);
        $this->assertEquals(count($expectedRecords), $records->count());

        $expectedCollection = new Collection();
        foreach ($expectedRecords as $alias) {
            $id = $recordGenerator->getRecordIdFromMap('author', $alias);
            $expectedCollection->add($table->findByPk($id));
        }
        foreach ($expectedCollection as $index => $expected) {
            $this->assertSame($expected, $records[$index]);
        }
    }


    /**
     * @param string $database
     * @dataProvider provideDatabaseAwareTestCases
     */
    public function testNullableUniqueWorksForNotNullValues($database)
    {
        $rm = self::createRecordManager($database);
        $recordGenerator = self::createRecordGenerator($rm);
        $recordGenerator
            ->setTableRows('author', $this->getNullableUniqueRowsForAuthor())
            ->generate();
        $id = $recordGenerator->getRecordIdFromMap('author', 'not_null');

        $table = $rm->getTable('author');
        $record = $table->findByUniqueIndex(
            'UNIQUE',
            array('firstname' => 'Not', 'lastname' => 'Anonymous')
        );

        $this->assertInstanceOf('\Dive\Record', $record);
        $this->assertSame($table->findByPk($id), $record);
    }


    /**
     * @param string $database
     * @dataProvider provideDatabaseAwareTestCases
     * @expectedException \Dive\Table\TableException
     */
    public function testFindByUniqueThrowsExceptionWhenIndexDoesNotExist($database)
    {
        $rm = self::createRecordManager($database);
        $recordGenerator = self::createRecordGenerator($rm);
        $recordGenerator
            ->setTableRows('author', $this->getNullableUniqueRowsForAuthor())
            ->generate();

        $table = $rm->getTable('author');
        $table->findByUniqueIndex(
            'NOT_A_VALID_INDEX_NAME',
            array('firstname' => null, 'lastname' => 'Anonymous'),
            RecordManager::FETCH_RECORD_COLLECTION
        );
    }


    /**
     * @param string $database
     * @dataProvider provideDatabaseAwareTestCases
     * @expectedException \Dive\Table\TableException
     */
    public function testFindByUniqueThrowsExceptionWhenIndexIsNotUnique($database)
    {
        $schemaDefinition = self::getSchemaDefinition();
        // define 'UNIQUE' is not a unique index
        $schemaDefinition['tables']['author']['indexes']['UNIQUE']['type'] = 'index';

        $rm = self::createRecordManager($database, $schemaDefinition);
        $recordGenerator = self::createRecordGenerator($rm);
        $recordGenerator
            ->setTableRows('author', $this->getNullableUniqueRowsForAuthor())
            ->generate();

        $table = $rm->getTable('author');
        $table->findByUniqueIndex(
            'UNIQUE',
            array('firstname' => 'Not', 'lastname' => 'Anonymous')
        );
    }
}
