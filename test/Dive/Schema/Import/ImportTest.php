<?php
/*
 * This file is part of the Dive ORM framework.
 * (c) Steffen Zeidler <sigma_z@sigma-scripts.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dive\Test\Schema\Import;

/**
 * @author Steffen Zeidler <sigma_z@sigma-scripts.de>
 * Date: 14.01.13
 */
use Dive\Platform\PlatformInterface;
use Dive\Relation\Relation;
use Dive\Schema\Import\Importer;
use Dive\Schema\Import\ImporterInterface;
use Dive\Schema\Schema;
use Dive\TestSuite\TestCase;

class ImportTest extends TestCase
{

    /**
     * @param $database
     * @return ImporterInterface
     */
    private function getImporter($database)
    {
        $conn = $this->createDatabaseConnectionOrMarkTestSkipped($database);
        $driver = $conn->getDriver();
        return $driver->getSchemaImporter($conn);
    }



    /**
     * @dataProvider provideGetTableNames
     */
    public function testGetTableNames($database, array $expectedArray)
    {
        $this->getExpectedOrMarkTestIncomplete($expectedArray, $database);
        $importer = $this->getImporter($database);
        $expected = array('article', 'article2tag', 'author', 'comment', 'donation', 'tag', 'user');

        $actual = $importer->getTableNames(true);
        sort($actual);
        $this->assertEquals($expected, $actual);
    }


    /**
     * @return array
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
     */
    public function testGetTableFields($database, array $expectedArray)
    {
        $expected = $this->getExpectedOrMarkTestIncomplete($expectedArray, $database);
        $importer = $this->getImporter($database);

        $actual = $importer->getTableFields('user');
        $this->assertEquals($expected, $actual);
    }


    /**
     * @return array
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
                        'length'    => 64
                    ),
                    'password'  => array(
                        'type'      => 'string',
                        'length'    => 32
                    )
                )
            )
        );

        return self::getDatabaseAwareTestCases($testCases);
    }


    /**
     * @dataProvider provideGetTableIndexes
     */
    public function testGetTableIndexes($database, array $expectedArray)
    {
        $expected = $this->getExpectedOrMarkTestIncomplete($expectedArray, $database);
        $importer = $this->getImporter($database);

        $actual = $importer->getTableIndexes('user');
        $this->assertEquals($expected, $actual);
    }


    /**
     * @return array
     */
    public function provideGetTableIndexes()
    {
        $testCases = array();

        $testCases[] = array(
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

        return self::getDatabaseAwareTestCases($testCases);
    }


    /**
     * @dataProvider provideGetTableForeignKeys
     */
    public function testGetTableForeignKeys($database, array $expectedArray)
    {
        $expected = $this->getExpectedOrMarkTestIncomplete($expectedArray, $database);
        $importer = $this->getImporter($database);

        $actual = $importer->getTableForeignKeys('author');
        $this->assertEquals($expected, $actual);
    }


    /**
     * @return array
     */
    public function provideGetTableForeignKeys()
    {
        $testCases = array();

        $testCases[] = array(
            'expectedArray' => array(
                'sqlite' => array(
                    'author.user_id' => array(
                        'owningTable' => 'author',
                        'owningField' => 'user_id',
                        'refTable' => 'user',
                        'refField' => 'id',
                        'onDelete' => PlatformInterface::CASCADE,
                        'onUpdate' => PlatformInterface::CASCADE,
                        'type' => Relation::ONE_TO_ONE
                    ),
                    'author.editor_id' => array(
                        'owningTable' => 'author',
                        'owningField' => 'editor_id',
                        'refTable' => 'author',
                        'refField' => 'id',
                        'onDelete' => PlatformInterface::SET_NULL,
                        'onUpdate' => PlatformInterface::CASCADE,
                        'type' => Relation::ONE_TO_MANY
                    )
                ),
                'mysql' => array(
                    'author.user_id' => array(
                        'owningTable' => 'author',
                        'owningField' => 'user_id',
                        'refTable' => 'user',
                        'refField' => 'id',
                        'onDelete' => PlatformInterface::CASCADE,
                        'onUpdate' => PlatformInterface::CASCADE,
                        'type' => Relation::ONE_TO_ONE
                    ),
                    'author.editor_id' => array(
                        'owningTable' => 'author',
                        'owningField' => 'editor_id',
                        'refTable' => 'author',
                        'refField' => 'id',
                        'onDelete' => PlatformInterface::SET_NULL,
                        'onUpdate' => PlatformInterface::CASCADE,
                        'type' => Relation::ONE_TO_MANY
                    )
                )
            )
        );

        return self::getDatabaseAwareTestCases($testCases);
    }


    /**
     * @dataProvider provideGetViewNames
     */
    public function testGetViewNames($database, array $expectedArray)
    {
        $expected = $this->getExpectedOrMarkTestIncomplete($expectedArray, $database);
        $importer = $this->getImporter($database);

        $actual = $importer->getViewNames();
        $this->assertEquals($expected, $actual);
    }


    /**
     * @return array
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
     */
    public function testGetViewFields($database, array $expectedArray)
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
     */
    public function testImportDefinitionIsValidSchema($database)
    {
        $importer = $this->getImporter($database);
        $definition = $importer->importDefinition();
        $schema = new Schema($definition);
        $this->assertInstanceOf('\Dive\Schema\Schema', $schema);
    }

}