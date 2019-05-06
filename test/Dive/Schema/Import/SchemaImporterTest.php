<?php
/*
 * This file is part of the Dive ORM framework.
 * (c) Steffen Zeidler <sigma_z@sigma-scripts.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dive\Test\Schema\Import;

use Dive\Connection\Connection;
use Dive\Platform\PlatformInterface;
use Dive\Relation\Relation;
use Dive\Schema\Import\SchemaImporter;
use Dive\Schema\Import\SchemaImporterInterface;
use Dive\Schema\Schema;
use Dive\TestSuite\TestCase;


/**
 * @author Steffen Zeidler <sigma_z@sigma-scripts.de>
 * Date: 14.01.13
 */
class SchemaImporterTest extends TestCase
{

    /** @var SchemaImporterGuessRelationNamesMock|SchemaImporter */
    private $schemaImporter;

    /** @var array */
    private $relationDefinitionWithGuessedNames;

    /** @var array */
    private $importedDefinition = array();


    /**
     * @param  array $database
     * @return SchemaImporterInterface
     */
    private function getImporter(array $database)
    {
        $conn = $this->createDatabaseConnectionOrMarkTestSkipped($database);
        $driver = $conn->getDriver();
        return $driver->getSchemaImporter($conn);
    }


    /**
     * @dataProvider provideGetTableNames
     * @param array $database
     * @param array $expectedArray
     */
    public function testGetTableNames(array $database, array $expectedArray)
    {
        $this->getExpectedOrMarkTestIncomplete($expectedArray, $database);
        $importer = $this->getImporter($database);
        $expected = array(
            'article',
            'article2tag',
            'author',
            'comment',
            'data_types',
            'donation',
            'no_autoincrement_test',
            'tag',
            'tree_node',
            'unique_constraint_test',
            'user'
        );

        $actual = $importer->getTableNames();
        sort($actual);
        $this->assertEquals($expected, $actual);
    }


    /**
     * @return array[]
     */
    public function provideGetTableNames()
    {
        $testCases = array();

        $testCases[] = array(
            'expectedArray' => array(
                'sqlite' => true,
                'mysql' => true
            )
        );

        return self::getDatabaseAwareTestCases($testCases);
    }


    /**
     * @dataProvider provideGetTableFields
     * @param array $database
     * @param array $expectedArray
     */
    public function testGetTableFields(array $database, array $expectedArray)
    {
        $expected = $this->getExpectedOrMarkTestIncomplete($expectedArray, $database);
        $importer = $this->getImporter($database);

        $actual = $importer->getTableFields('user');
        $this->assertEquals($expected, $actual);
    }


    /**
     * @return array[]
     */
    public function provideGetTableFields()
    {
        $testCases = array();

        $testCases[] = array(
            'expectedArray' => array(
                'sqlite' => array(
                    'id'    => array(
                        'primary'   => true,
                        'type'      => 'integer',
                        'autoIncrement' => true
                    ),
                    'username'  => array(
                        'type'      => 'string',
                        'length'    => 64
                    ),
                    'password'  => array(
                        'type'      => 'string',
                        'length'    => 32
                    )
                ),
                'mysql' => array(
                    'id'    => array(
                        'primary'   => true,
                        'type'      => 'integer',
                        'length'    => 10,
                        'unsigned'  => true,
                        'autoIncrement' => true
                    ),
                    'username'  => array(
                        'type'      => 'string',
                        'length'    => 64,
                        'collation' => 'utf8_unicode_ci'
                    ),
                    'password'  => array(
                        'type'      => 'string',
                        'length'    => 32,
                        'collation' => 'utf8_unicode_ci'
                    )
                )
            )
        );

        return self::getDatabaseAwareTestCases($testCases);
    }


    /**
     * @dataProvider provideGetTableIndexes
     * @param array  $database
     * @param string $tableName
     * @param array  $expectedArray
     */
    public function testGetTableIndexes(array $database, $tableName, array $expectedArray)
    {
        $expected = $this->getExpectedOrMarkTestIncomplete($expectedArray, $database);
        $importer = $this->getImporter($database);

        $actual = $importer->getTableIndexes($tableName);
        $this->assertEquals($expected, $actual);
    }


    /**
     * @return array[]
     */
    public function provideGetTableIndexes()
    {
        $testCases = array();

        $testCases[] = array(
            'tableName' => 'user',
            'expectedArray' => array(
                'sqlite' => array(
                    'user_UNIQUE' => array(
                        'fields' => array('username'),
                        'type' => 'unique'
                    )
                ),
                'mysql' => array(
                    'UNIQUE' => array(
                        'fields' => array('username'),
                        'type' => 'unique'
                    )
                )
            )
        );

        $testCases[] = array(
            'tableName' => 'author',
            'expectedArray' => array(
                'sqlite' => array(
                    'author_UNIQUE' => array(
                        'fields' => array('firstname', 'lastname'),
                        'type' => 'unique',
                    ),
                    'author_UQ_user_id' => array(
                        'fields' => array('user_id'),
                        'type' => 'unique'
                    )
                ),
                'mysql' => array(
                    'UNIQUE' => array(
                        'fields' => array('firstname', 'lastname'),
                        'type' => 'unique',
                    ),
                    'UQ_user_id' => array(
                        'fields' => array('user_id'),
                        'type' => 'unique'
                    ),
                    'author_fk_editor_id' => array(
                        'fields' => array('editor_id'),
                        'type' => 'index'
                    )
                )
            )
        );

        return self::getDatabaseAwareTestCases($testCases);
    }


    /**
     * @dataProvider provideGetTableForeignKeys
     * @param array $database
     * @param array $expectedArray
     */
    public function testGetTableForeignKeys(array $database, array $expectedArray)
    {
        $expected = $this->getExpectedOrMarkTestIncomplete($expectedArray, $database);
        $importer = $this->getImporter($database);

        $actual = $importer->getTableForeignKeys('author');
        $this->assertEquals($expected, $actual);
    }


    /**
     * @return array[]
     */
    public function provideGetTableForeignKeys()
    {
        $expectedForeignKeys = [
            'author.user_id' => [
                'owningTable' => 'author',
                'owningField' => 'user_id',
                'refTable' => 'user',
                'refField' => 'id',
                'onDelete' => PlatformInterface::CASCADE,
                'onUpdate' => PlatformInterface::CASCADE,
                'type' => Relation::ONE_TO_ONE
            ],
            'author.editor_id' => [
                'owningTable' => 'author',
                'owningField' => 'editor_id',
                'refTable' => 'author',
                'refField' => 'id',
                'onDelete' => PlatformInterface::SET_NULL,
                'onUpdate' => PlatformInterface::CASCADE,
                'type' => Relation::ONE_TO_MANY
            ]
        ];
        $testCases = [
            'expectedArray' => [
                'sqlite' => $expectedForeignKeys,
                'mysql' => $expectedForeignKeys
            ]
        ];

        return self::getDatabaseAwareTestCases($testCases);
    }


    /**
     * @dataProvider provideGetViewNames
     * @param array $database
     * @param array $expectedArray
     */
    public function testGetViewNames(array $database, array $expectedArray)
    {
        $expected = $this->getExpectedOrMarkTestIncomplete($expectedArray, $database);
        $importer = $this->getImporter($database);

        $actual = $importer->getViewNames();
        $this->assertEquals($expected, $actual);
    }


    /**
     * @return array[]
     */
    public function provideGetViewNames()
    {
        $testCases = array();
        $testCases[] = array(
            'expectedArray' => array(
                'sqlite' => array('author_user_view'),
                'mysql' => array('author_user_view')
            )
        );
        return self::getDatabaseAwareTestCases($testCases);
    }


    /**
     * @dataProvider provideGetViewFields
     * @param array $database
     * @param array $expectedArray
     */
    public function testGetViewFields(array $database, array $expectedArray)
    {
        $expected = $this->getExpectedOrMarkTestIncomplete($expectedArray, $database);
        $importer = $this->getImporter($database);

        $actualViewFields = $importer->getViewFields('author_user_view');
        $actual = array();
        foreach ($actualViewFields as $fieldName => $fieldDefinition) {
            $def = array();
            $def['type'] = isset($fieldDefinition['type']) ? $fieldDefinition['type'] : null;
            if (isset($expected[$fieldName]['length'])) {
                $def['length'] = isset($fieldDefinition['length']) ? $fieldDefinition['length'] : null;
            }
            $actual[$fieldName] = $def;
        }
        $this->assertEquals($expected, $actual);
    }


    /**
     * @return array
     */
    public function provideGetViewFields()
    {
        $testCases = array();
        $testCases[] = array(
            'expectedArray' => array(
                'sqlite' => array(
                    'id' => array(
                        'type' => 'integer'
                    ),
                    'firstname' => array(
                        'type' => 'string',
                        'length' => 64
                    ),
                    'lastname' => array(
                        'type' => 'string',
                        'length' => 64
                    ),
                    'email' => array(
                        'type' => 'string',
                        'length' => 255
                    ),
                    'user_id' => array(
                        'type' => 'integer',
                    ),
                    'username' => array(
                        'type' => 'string',
                        'length' => 64
                    ),
                    'password' => array(
                        'type' => 'string',
                        'length' => 32
                    )
                ),
                'mysql' => array(
                    'id' => array(
                        'type' => 'integer',
                        'length' => 10
                    ),
                    'firstname' => array(
                        'type' => 'string',
                        'length' => 64
                    ),
                    'lastname' => array(
                        'type' => 'string',
                        'length' => 64
                    ),
                    'email' => array(
                        'type' => 'string',
                        'length' => 255
                    ),
                    'user_id' => array(
                        'type' => 'integer',
                    ),
                    'username' => array(
                        'type' => 'string',
                        'length' => 64
                    ),
                    'password' => array(
                        'type' => 'string',
                        'length' => 32
                    )
                )
            )
        );
        return self::getDatabaseAwareTestCases($testCases);
    }


    /**
     * @dataProvider \Dive\TestSuite\TestCase::provideDatabaseAwareTestCases
     * @param array $database
     */
    public function testImportDefinitionIsValidSchema(array $database)
    {
        $importer = $this->getImporter($database);
        $definition = $importer->importDefinition();
        $this->assertArrayHasKey('tables', $definition);
        $this->assertNotEmpty($definition['tables']);
        $this->assertArrayHasKey('views', $definition);
        $this->assertNotEmpty($definition['views']);
        $this->assertArrayHasKey('relations', $definition);
        $this->assertNotEmpty($definition['relations']);
        $schema = new Schema($definition);
        $this->assertInstanceOf('\Dive\Schema\Schema', $schema);
    }


    /**
     * @dataProvider \Dive\TestSuite\TestCase::provideDatabaseAwareTestCases
     * @param array $database
     */
    public function testImportViews(array $database)
    {
        $conn = $this->createDatabaseConnectionOrMarkTestSkipped($database);
        $this->givenIHaveASchemaImporterForConnection($conn);
        $this->whenIImportViews();
        $this->thenItShouldHaveImportedViewsWithSqlStatement();
    }


    /**
     * @dataProvider provideDatabaseAwareTestCases
     * @param  array $database
     */
    public function testGuessRelationNameForParentAndChildrenRelation($database)
    {
        $children = $this->getRelationDefinitionWithParentChildrenRelation();
        $conn = $this->createDatabaseConnectionOrMarkTestSkipped($database);

        $this->givenIHaveAMockOfAbstractSchemaImporterClass($conn);
        $this->whenIGuessRelationNamesFor($children);
        $this->thenOwningAliasShouldBe('Children');
        $this->thenReferencedAliasShouldBe('Parent');
    }

    /**
     * @dataProvider provideDatabaseAwareTestCases
     * @param  array $database
     */
    public function testGuessRelationNameForParentAndChildRelation($database)
    {
        $child = $this->getRelationDefinitionWithParentChildrenRelation();
        $child['type'] = Relation::ONE_TO_ONE;
        $conn = $this->createDatabaseConnectionOrMarkTestSkipped($database);

        $this->givenIHaveAMockOfAbstractSchemaImporterClass($conn);
        $this->whenIGuessRelationNamesFor($child);
        $this->thenOwningAliasShouldBe('Child');
        $this->thenReferencedAliasShouldBe('Parent');
    }


    /**
     * A relation should guess Parent and Children / Child when it is a relation on the same table
     * @return array
     */
    private function getRelationDefinitionWithParentChildrenRelation()
    {
        return array(
            'owningTable' => 'author',
            'owningField' => 'editor_id',
            'refTable' => 'author',
            'refField' => 'id',
            'onDelete' => PlatformInterface::SET_NULL,
            'onUpdate' => PlatformInterface::CASCADE,
            'type' => Relation::ONE_TO_MANY
        );
    }


    /**
     * @param Connection $conn
     */
    private function givenIHaveAMockOfAbstractSchemaImporterClass($conn)
    {
        $this->schemaImporter = $this->getMockForAbstractClass(
            '\Dive\Test\Schema\Import\SchemaImporterGuessRelationNamesMock',
            array($conn)
        );
    }


    /**
     * @param array $relation
     */
    private function whenIGuessRelationNamesFor(array $relation)
    {
        $this->relationDefinitionWithGuessedNames = $this->schemaImporter->guessRelationAliases($relation);
    }


    /**
     * @param string $expected
     */
    private function thenOwningAliasShouldBe($expected)
    {
        $this->assertEquals($expected, $this->relationDefinitionWithGuessedNames['owningAlias']);
    }


    /**
     * @param string $expected
     */
    private function thenReferencedAliasShouldBe($expected)
    {
        $this->assertEquals($expected, $this->relationDefinitionWithGuessedNames['refAlias']);
    }


    private function whenIImportViews()
    {
        $this->importedDefinition = $this->schemaImporter->importDefinition();
    }


    private function thenItShouldHaveImportedViewsWithSqlStatement()
    {
        $this->assertArrayHasKey('views', $this->importedDefinition);
        $this->assertNotEmpty($this->importedDefinition['views']);
        $viewDefinition = current($this->importedDefinition['views']);
        $this->assertArrayHasKey('sqlStatement', $viewDefinition);
        $this->assertNotEmpty($viewDefinition['sqlStatement']);
    }


    /**
     * @param Connection $conn
     */
    private function givenIHaveASchemaImporterForConnection(Connection $conn)
    {
        $this->schemaImporter = $conn->getDriver()->getSchemaImporter($conn);
    }
}


/**
 * mock class for schema importer
 */
abstract class SchemaImporterGuessRelationNamesMock extends SchemaImporter
{

    /**
     * tries to guess relation alias names
     *
     * @param array $relation
     * @return array
     */
    public function guessRelationAliases(array $relation)
    {
        return parent::guessRelationAliases($relation);
    }
}
