<?php
/*
 * This file is part of the Dive ORM framework.
 * (c) Steffen Zeidler <sigma_z@sigma-scripts.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dive\Test\Schema;

/**
 * @author Steffen Zeidler <sigma_z@sigma-scripts.de>
 * Date: 01.11.12
 */

use Dive\Platform\PlatformInterface;
use Dive\Relation\Relation;
use Dive\Schema\Schema;
use Dive\Schema\SchemaException;
use Dive\TestSuite\TestCase;

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


    /**
     * @expectedException \Dive\Schema\SchemaException
     */
    public function testAddTableThrowsMissingFieldException()
    {
        $this->schema->addTable('stats', array());
    }


    /**
     * @dataProvider provideGetTableClassWithoutAutoload
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
        $this->assertEquals('\Dive\Table', $tableClass);
    }


    public function testGetTableNames()
    {
        $expected = array('article', 'article2tag', 'author', 'comment', 'tag', 'user');
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


    /**
     * @expectedException \Dive\Schema\SchemaException
     */
    public function testGetTableFieldsFromUnknownTableThrowsException()
    {
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
     */
    public function testGetTableRelations($table, $expected)
    {
        $tableRelations = $this->schema->getTableRelations($table);
        $actual = array(
            'owning' => array_keys($tableRelations['owning']),
            'referenced' => array_keys($tableRelations['referenced'])
        );
        sort($actual['owning']);
        sort($actual['referenced']);
        $this->assertEquals($expected, $actual);
    }


    public function provideGetTableRelations()
    {
        $testCases = array();

        $testCases[] = array(
            'table' => 'author',
            'expected' => array(
                'owning' => array('author.editor_id', 'author.user_id'),
                'referenced' => array('article.author_id', 'author.editor_id')
            )
        );
        $testCases[] = array(
            'table' => 'user',
            'expected' => array(
                'owning' => array(),
                'referenced' => array('author.user_id', 'comment.user_id')
            )
        );
        $testCases[] = array(
            'table' => 'article',
            'expected' => array(
                'owning' => array('article.author_id'),
                'referenced' => array('article2tag.article_id', 'comment.article_id')
            )
        );
        $testCases[] = array(
            'table' => 'comment',
            'expected' => array(
                'owning' => array('comment.article_id', 'comment.user_id'),
                'referenced' => array()
            )
        );
        $testCases[] = array(
            'table' => 'tag',
            'expected' => array(
                'owning' => array(),
                'referenced' => array('article2tag.tag_id')
            )
        );
        $testCases[] = array(
            'table' => 'article2tag',
            'expected' => array(
                'owning' => array('article2tag.article_id', 'article2tag.tag_id'),
                'referenced' => array()
            )
        );

        return $testCases;
    }


    /**
     * @expectedException \Dive\Schema\SchemaException
     * @dataProvider provideAddRelationThrowsInvalidRelationException
     */
    public function testAddRelationThrowsInvalidRelationException($missingKey)
    {
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


    /**
     * @expectedException \Dive\Schema\SchemaException
     */
    public function testAddRelationThrowsRelationAlreadyExistsException()
    {
        $this->schema->addTableRelation('author', 'user_id', $this->schemaDefinition['relations']['author.user_id']);
    }


    /**
     * @expectedException \Dive\Schema\SchemaException
     */
    public function testAddTableFieldThrowsInvalidFieldException()
    {
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


    /**
     * @expectedException \Dive\Schema\SchemaException
     */
    public function testAddTableIndexThrowsInvalidIndexException()
    {
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
