<?php
/*
 * This file is part of the Dive ORM framework.
 * (c) Steffen Zeidler <sigma_z@sigma-scripts.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dive\Test\Schema;

use Dive\Platform\PlatformInterface;
use Dive\Relation\Relation;
use Dive\Schema\Schema;
use Dive\Schema\SchemaException;
use Dive\Table;
use Dive\TestSuite\TestCase;

/**
 * @author Steffen Zeidler <sigma_z@sigma-scripts.de>
 * Date: 01.11.12
 */
class SchemaTest extends TestCase
{

    /**
     * @var Schema
     */
    private $schema;
    /**
     * @var array
     */
    private $schemaDefinition = array();


    protected function setUp()
    {
        parent::setUp();

        $this->schemaDefinition = include FIXTURE_DIR . '/schema.php';
        $this->schema = new Schema($this->schemaDefinition);
        $this->schema->setValidationEnabled(true);
    }


    public function testHasTable()
    {
        $this->assertFalse($this->schema->hasTable('unknown'));
    }

    public function testAddTableThrowsMissingFieldException()
    {
        $this->expectException(SchemaException::class);
        $this->schema->addTable('stats', array());
    }


    /**
     * @dataProvider provideGetTableClassWithoutAutoload
     * @param string $tableBaseClass
     * @param string $recordBaseClass
     * @param string $recordClass
     * @param string $expectedRecordClass
     * @param string $expectedTableClass
     */
    public function testGetTableClassWithoutAutoload(
        $tableBaseClass, $recordBaseClass, $recordClass, $expectedRecordClass, $expectedTableClass
    )
    {
        $this->schema->setRecordClass('user', null);

        if ($tableBaseClass) {
            $this->schema->setTableBaseClass($tableBaseClass);
        }
        if ($recordBaseClass) {
            $this->schema->setRecordBaseClass($recordBaseClass);
        }
        if ($recordClass) {
            $this->schema->setRecordClass('user', $recordClass);
        }
        $recordClass = $this->schema->getRecordClass('user');
        $this->assertEquals($expectedRecordClass, $recordClass);
        $tableClass = $this->schema->getTableClass('user', false);
        $this->assertEquals($expectedTableClass, $tableClass);
    }


    /**
     * @return array[]
     */
    public function provideGetTableClassWithoutAutoload()
    {
        return array(
            array(
                null,
                null,
                null,
                '\Dive\Record',
                '\Dive\Table'
            ),
            array(
                null,
                '\Dive\Test\BaseRecord',
                null,
                '\Dive\Test\BaseRecord',
                '\Dive\Table'
            ),
            array(
                '\Dive\Test\BaseTable',
                null,
                null,
                '\Dive\Record',
                '\Dive\Test\BaseTable'
            ),
            array(
                null,
                '\Dive\Test\BaseRecord',
                '\Dive\Test\Record',
                '\Dive\Test\Record',
                '\Dive\Test\RecordTable'
            ),
            array(
                '\MyTableClass',
                '\MyRecordClass',
                null,
                '\MyRecordClass',
                '\MyTableClass'
            ),
            array(
                '\MyTableClass',
                '\MyBaseRecordClass',
                '\MyRecordClass',
                '\MyRecordClass',
                '\MyRecordClassTable'
            ),
        );
    }


    public function testGetTableClassWithAutoload()
    {
        $tableClass = $this->schema->getTableClass('user', true);
        $this->assertEquals(Table::class, ltrim($tableClass, '\\'));
    }


    public function testGetTableNames()
    {
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
        $actual = $this->schema->getTableNames();
        sort($actual);
        $this->assertEquals($expected, $actual);
    }


    public function testHasView()
    {
        $this->assertTrue($this->schema->hasView('author_user_view'));
    }


    public function testGetViewNames()
    {
        $expected = array('author_user_view');
        $actual = $this->schema->getViewNames();
        $this->assertEquals($expected, $actual);
    }


    public function testAddViewField()
    {
        $this->schema->addViewField('author_user_view', 'description', array('type' => 'string', 'length' => '2000'));
        $fields = $this->schema->getViewFields('author_user_view');
        $this->assertArrayHasKey('description', $fields);
    }


    public function testGetViewStatement()
    {
        $sqlStatement = $this->schema->getViewStatement('author_user_view');
        $this->assertTrue(false !== stripos($sqlStatement, 'SELECT'));
    }


    public function testGetTableFields()
    {
        $fields = $this->schema->getTableFields('article2tag');
        $expected = array('article_id', 'tag_id');
        $this->assertEquals($expected, array_keys($fields));
    }

    public function testGetTableFieldsFromUnknownTableThrowsException()
    {
        $this->expectException(SchemaException::class);
        $this->schema->getTableFields('unknown');
    }


    public function testGetTableIndexes()
    {
        $uniqueConstraints = $this->schema->getTableIndexes('tag');
        $expected = array(
            'UNIQUE' => array(
                'type' => PlatformInterface::UNIQUE,
                'fields' => array('name')
            )
        );
        $this->assertEquals($expected, $uniqueConstraints);
    }


    /**
     * @dataProvider provideGetTableRelations
     * @param $tableName
     * @param $expected
     */
    public function testGetTableRelations($tableName, $expected)
    {
        $tableRelations = $this->schema->getTableRelations($tableName);
        $actual = array(
            'owning' => array_keys($tableRelations['owning']),
            'referenced' => array_keys($tableRelations['referenced'])
        );
        sort($actual['owning']);
        sort($actual['referenced']);
        $this->assertEquals($expected, $actual);
    }


    /**
     * @return array[]
     */
    public function provideGetTableRelations()
    {
        $testCases = array();

        $testCases[] = array(
            'tableName' => 'author',
            'expected' => array(
                'owning' => array('author.editor_id', 'author.user_id'),
                'referenced' => array('article.author_id', 'author.editor_id')
            )
        );
        $testCases[] = array(
            'tableName' => 'user',
            'expected' => array(
                'owning' => array(),
                'referenced' => array('author.user_id', 'author_user_view.user_id', 'comment.user_id')
            )
        );
        $testCases[] = array(
            'tableName' => 'article',
            'expected' => array(
                'owning' => array('article.author_id'),
                'referenced' => array('article2tag.article_id', 'comment.article_id')
            )
        );
        $testCases[] = array(
            'tableName' => 'comment',
            'expected' => array(
                'owning' => array('comment.article_id', 'comment.comment_id', 'comment.user_id'),
                'referenced' => array('comment.comment_id')
            )
        );
        $testCases[] = array(
            'tableName' => 'tag',
            'expected' => array(
                'owning' => array(),
                'referenced' => array('article2tag.tag_id')
            )
        );
        $testCases[] = array(
            'tableName' => 'article2tag',
            'expected' => array(
                'owning' => array('article2tag.article_id', 'article2tag.tag_id'),
                'referenced' => array()
            )
        );

        return $testCases;
    }


    /**
     * @dataProvider provideAddRelationThrowsInvalidRelationException
     * @param string $missingKey
     * @throws SchemaException
     */
    public function testAddRelationThrowsInvalidRelationException($missingKey)
    {
        $this->expectException(SchemaException::class);
        $relation = array(
            'owningTable' => 'user',
            'owningField' => 'username',
            'owningAlias' => 'somewhere',
            'refTable' => 'author',
            'refAlias' => 'somewhat',
            'refField' => 'id',
            'type' => Relation::ONE_TO_ONE
        );
        unset($relation[$missingKey]);
        $this->schema->addTableRelation('user', 'username', $relation);
    }


    /**
     * @return array[]
     */
    public function provideAddRelationThrowsInvalidRelationException()
    {
        // NOTE owningField and owningTable are set by addTableRelation(), therefore the exception won't be thrown
        $fieldsToCheck = array('owningAlias', /*'owningField', 'owningTable',*/ 'refAlias', 'refField', 'refTable', 'type');
        $testCases = array();
        foreach ($fieldsToCheck as $field) {
            $testCases[] = array($field);
        }
        return $testCases;
    }


    public function testAddRelation()
    {
        $this->schema->addTable('somewhere', array('id' => array('type' => 'integer')));
        $this->schema->addTable('somewhat', array('id' => array('type' => 'integer')));
        $relation = array(
            'owningTable' => 'somewhere',
            'owningField' => 'id',
            'owningAlias' => 'Somewhat',
            'refTable' => 'somewhat',
            'refAlias' => 'Somewhere',
            'refField' => 'id',
            'type' => Relation::ONE_TO_ONE
        );
        $this->schema->addTableRelation('somewhere', 'id', $relation);

        $tableRelations = $this->schema->getTableRelations('somewhere');
        $expected = array(
            'owning' => array('somewhere.id'),
            'referenced' => array()
        );
        $actual = array(
            'owning' => array_keys($tableRelations['owning']),
            'referenced' => array_keys($tableRelations['referenced'])
        );
        $this->assertEquals($expected, $actual);

        $tableRelations = $this->schema->getTableRelations('somewhat');
        $expected = array(
            'owning' => array(),
            'referenced' => array('somewhere.id')
        );
        $actual = array(
            'owning' => array_keys($tableRelations['owning']),
            'referenced' => array_keys($tableRelations['referenced'])
        );
        $this->assertEquals($expected, $actual);
    }

    public function testAddRelationThrowsRelationAlreadyExistsException()
    {
        $this->expectException(SchemaException::class);
        $this->schema->addTableRelation('author', 'user_id', $this->schemaDefinition['relations']['author.user_id']);
    }

    public function testAddTableFieldThrowsInvalidFieldException()
    {
        $this->expectException(SchemaException::class);
        $this->schema->addTableField('user', 'saved_on', array());
    }


    public function testAddTableField()
    {
        $this->schema->addTableField('user', 'saved_on', array('type' => 'datetime'));
        $fields = $this->schema->getTableFields('user');
        $this->assertArrayHasKey('saved_on', $fields);
    }


    public function testAddTableIndex()
    {
        $this->schema->addTableIndex('user', 'UQ_username_password', array('username', 'password'));
        $indexes = $this->schema->getTableIndexes('user');
        $this->assertArrayHasKey('UQ_username_password', $indexes);
    }

    public function testAddTableIndexThrowsInvalidIndexException()
    {
        $this->expectException(SchemaException::class);
        $this->schema->addTableIndex('user', 'UQ_username_password', array());
    }


    public function testToArray()
    {
        $tableNames = array_keys($this->schemaDefinition['tables']);
        $viewNames = array_keys($this->schemaDefinition['views']);
        $relationNames = array_keys($this->schemaDefinition['relations']);

        $actual = $this->schema->toArray();
        $this->assertEquals($tableNames, array_keys($actual['tables']));
        $this->assertEquals($viewNames, array_keys($actual['views']));
        $this->assertEquals($relationNames, array_keys($actual['relations']));
    }

}
