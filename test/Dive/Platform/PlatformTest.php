<?php
/*
 * This file is part of the Dive ORM framework.
 * (c) Steffen Zeidler <sigma_z@sigma-scripts.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dive\TestSuite\Platform;

use Dive\Exception;
use Dive\Platform\PlatformException;
use Dive\Platform\PlatformInterface;
use Dive\TestSuite\TestCase;


/**
 * @author Steffen Zeidler <sigma_z@sigma-scripts.de>
 * Date: 20.12.12
 */
class PlatformTest extends TestCase
{

    /**
     * gets platform for given scheme
     *
     * @param  string $scheme
     * @return PlatformInterface|bool
     */
    private function createPlatform($scheme)
    {
        $dataTypeMapper = $this->createInstanceOrMarkTestSkipped('Schema\DataTypeMapper', 'DataTypeMapper', $scheme);
        $platform = $this->createInstanceOrMarkTestSkipped('Platform', 'Platform', $scheme, array($dataTypeMapper));
        return $platform;
    }


    /**
     * @dataProvider provideGetDropTableSql
     */
    public function testGetDropTableSql($database, $tableName, array $expectedArray)
    {
        $scheme = self::getSchemeFromDsn($database['dsn']);
        if (!isset($expectedArray[$scheme])) {
            $this->markTestIncomplete('Test is not implemented, yet!');
        }

        $platform = $this->createPlatform($scheme);
        $actual = $platform->getDropTableSql($tableName);
        $this->assertEquals($expectedArray[$scheme], $actual);
    }


    public function provideGetDropTableSql()
    {
        $testCases = array();

        $testCases[] = array(
            'tableName' => 'author',
            'expectedArray' => array(
                'sqlite' => 'DROP TABLE IF EXISTS "author"',
                'mysql' => 'DROP TABLE IF EXISTS `author`'
            )
        );

        return self::getDatabaseAwareTestCases($testCases);
    }


    /**
     * @dataProvider provideGetCreateTableSql
     */
    public function testGetCreateTableSql($database, $tableName, array $expectedArray)
    {
        $schema = self::getSchema();

        $scheme = self::getSchemeFromDsn($database['dsn']);
        if (!isset($expectedArray[$scheme])) {
            $this->markTestIncomplete('Test is not implemented, yet!');
        }

        $platform = $this->createPlatform($scheme);
        $columns = $schema->getTableFields($tableName);
        $indexes = $schema->getTableIndexes($tableName);
        $relations = $schema->getTableRelations($tableName);
        $foreignKeys = array();
        foreach ($relations['owning'] as $owningRelation) {
            $foreignKeys[$owningRelation['owningField']] = $owningRelation;
        }

        $actual = $platform->getCreateTableSql($tableName, $columns, $indexes, $foreignKeys);
        $this->assertEquals($expectedArray[$scheme], $actual);
    }


    public function provideGetCreateTableSql()
    {
        $testCases = array(
            // test case #0
            array(
                'tableName' => 'user',
                'expectedArray' => array(
                    'sqlite' => "CREATE TABLE IF NOT EXISTS \"user\" (\n"
                                . "\"id\" integer PRIMARY KEY AUTOINCREMENT NOT NULL,\n"
                                . "\"username\" varchar(64) NOT NULL,\n"
                                . "\"password\" varchar(32) NOT NULL\n"
                                . ")",
                    'mysql' => "CREATE TABLE IF NOT EXISTS `user` (\n"
                                . "`id` bigint(10) UNSIGNED AUTO_INCREMENT NOT NULL,\n"
                                . "`username` varchar(64) NOT NULL,\n"
                                . "`password` varchar(32) NOT NULL,\n"
                                . "PRIMARY KEY(`id`),\n"
                                . "UNIQUE INDEX `UNIQUE` (`username`)\n"
                                . ")"
                )
            ),

            // test case #1
            array(
                'tableName' => 'author',
                'expectedArray' => array(
                    'sqlite' => "CREATE TABLE IF NOT EXISTS \"author\" (\n"
                                . "\"id\" integer PRIMARY KEY AUTOINCREMENT NOT NULL,\n"
                                . "\"firstname\" varchar(64),\n"
                                . "\"lastname\" varchar(64) NOT NULL,\n"
                                . "\"email\" varchar(255) NOT NULL,\n"
                                . "\"user_id\" unsigned bigint(10) NOT NULL,\n"
                                . "\"editor_id\" unsigned bigint(10),\n"
                                . "CONSTRAINT \"author_fk_user_id\" FOREIGN KEY (\"user_id\") REFERENCES \"user\" (\"id\") ON DELETE CASCADE ON UPDATE CASCADE,\n"
                                . "CONSTRAINT \"author_fk_editor_id\" FOREIGN KEY (\"editor_id\") REFERENCES \"author\" (\"id\") ON DELETE SET NULL ON UPDATE CASCADE\n"
                                . ")",
                    'mysql' => "CREATE TABLE IF NOT EXISTS `author` (\n"
                                . "`id` bigint(10) UNSIGNED AUTO_INCREMENT NOT NULL,\n"
                                . "`firstname` varchar(64),\n"
                                . "`lastname` varchar(64) NOT NULL,\n"
                                . "`email` varchar(255) NOT NULL,\n"
                                . "`user_id` bigint(10) UNSIGNED NOT NULL,\n"
                                . "`editor_id` bigint(10) UNSIGNED,\n"
                                . "PRIMARY KEY(`id`),\n"
                                . "UNIQUE INDEX `UNIQUE` (`firstname`, `lastname`),\n"
                                . "UNIQUE INDEX `UQ_user_id` (`user_id`),\n"
                                . "CONSTRAINT `author_fk_user_id` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,\n"
                                . "CONSTRAINT `author_fk_editor_id` FOREIGN KEY (`editor_id`) REFERENCES `author` (`id`) ON DELETE SET NULL ON UPDATE CASCADE\n"
                                . ")"
                )
            )
        );

        return self::getDatabaseAwareTestCases($testCases);
    }


    /**
     * @dataProvider provideGetRenameTableSql
     */
    public function testGetRenameTableSql($database, $tableName, $renameTo, array $expectedArray)
    {
        $scheme = self::getSchemeFromDsn($database['dsn']);
        if (!isset($expectedArray[$scheme])) {
            $this->markTestIncomplete('Test is not implemented, yet!');
        }

        $platform = $this->createPlatform($scheme);
        $actual = $platform->getRenameTableSql($tableName, $renameTo);
        $this->assertEquals($expectedArray[$scheme], $actual);
    }


    public function provideGetRenameTableSql()
    {
        $testCases = array();

        $testCases[] = array(
            'tableName' => 'author',
            'renameTo' => 'person',
            'expectedArray' => array(
                'sqlite' => 'ALTER TABLE "author" RENAME TO "person"',
                'mysql' => 'ALTER TABLE `author` RENAME TO `person`'
            )
        );

        return self::getDatabaseAwareTestCases($testCases);
    }


    /**
     * @expectedException \Dive\Platform\PlatformException
     */
    public function testGetMysqlEnumColumnDefinitionThrowsExceptionOnMissingValues()
    {
        $platform = $this->createPlatform('mysql');
        $platform->getColumnDefinitionSql(array('type' => 'enum'));
    }


    /**
     * @dataProvider provideGetColumnDefinitionSql
     */
    public function testGetColumnDefinitionSql($database, array $definition, array $expectedArray)
    {
        $scheme = self::getSchemeFromDsn($database['dsn']);
        if (!isset($expectedArray[$scheme])) {
            $this->markTestIncomplete('Test is not implemented, yet!');
        }

        $platform = $this->createPlatform($scheme);
        $actual = $platform->getColumnDefinitionSql($definition);
        $this->assertEquals($expectedArray[$scheme], $actual);
    }


    /**
     * @return array[]
     */
    public function provideGetColumnDefinitionSql()
    {
        $testCases = array();

        // boolean
        $testCases[] = array(
            'definition' => array(
                'type' => 'boolean'
            ),
            'expectedArray' => array(
                'sqlite' => 'boolean NOT NULL',
                'mysql' => 'boolean NOT NULL'
            )
        );

        // unsigned decimal
        $testCases[] = array(
            'definition' => array(
                'type' => 'decimal',
                'scale' => 3,
                'length' => 9,
                'unsigned' => true
            ),
            'expectedArray' => array(
                'sqlite' => 'unsigned decimal(8,3) NOT NULL',
                'mysql' => 'decimal(8,3) UNSIGNED NOT NULL',
            )
        );

        // signed decimal
        $testCases[] = array(
            'definition' => array(
                'type' => 'decimal',
                'scale' => 3,
                'length' => 7,
                'nullable' => true
            ),
            'expectedArray' => array(
                'sqlite' => 'decimal(6,3)',
                'mysql' => 'decimal(6,3)',
            )
        );

        // double
        $testCases[] = array(
            'definition' => array(
                'type' => 'decimal',
                'length' => 7,
                'nullable' => true
            ),
            'expectedArray' => array(
                'sqlite' => 'decimal(7)',
                'mysql' => 'decimal(7)',
            )
        );

        // unsigned zerofill integer (only supported by mysql)
        $testCases[] = array(
            'definition' => array(
                'type' => 'integer',
                'length' => 6,
                'zerofill' => true,
                'unsigned' => true,
                'nullable' => true
            ),
            'expectedArray' => array(
                'sqlite' => 'unsigned mediumint(6)',
                'mysql' => 'mediumint(6) UNSIGNED ZEROFILL',
            )
        );

        // string
        $testCases[] = array(
            'definition' => array(
                'type' => 'string',
                'length' => 255,
                'nullable' => true
            ),
            'expectedArray' => array(
                'sqlite' => 'varchar(255)',
                'mysql' => 'varchar(255)'
            )
        );

        $testCases[] = array(
            'definition' => array(
                'type' => 'datetime',
            ),
            'expectedArray' => array(
                'sqlite' => 'datetime NOT NULL',
                'mysql' => 'datetime NOT NULL'
            )
        );

        $testCases[] = array(
            'definition' => array(
                'type' => 'date',
            ),
            'expectedArray' => array(
                'sqlite' => 'date NOT NULL',
                'mysql' => 'date NOT NULL'
            )
        );

        $testCases[] = array(
            'definition' => array(
                'type' => 'time',
            ),
            'expectedArray' => array(
                'sqlite' => 'character(8) NOT NULL',
                'mysql' => 'char(8) NOT NULL'
            )
        );

        $testCases[] = array(
            'definition' => array(
                'type' => 'timestamp',
            ),
            'expectedArray' => array(
               'sqlite' => 'integer NOT NULL',
               'mysql' => 'timestamp NOT NULL'
            )
        );

        $testCases[] = array(
            'definition' => array(
                'type' => 'blob',
                'nullable' => true
            ),
            'expectedArray' => array(
               'sqlite' => 'blob',
               'mysql' => 'blob'
            )
        );

        // enum
        $testCases[] = array(
            'definition' => array(
                'type' => 'enum',
                'values' => array('a', 'b', 'c'),
                'nullable' => true
            ),
            'expectedArray' => array(
                'sqlite' => 'varchar',
                'mysql' => "enum('a','b','c')",
            )
        );

        // primary key
        $testCases[] = array(
            'definition' => array(
                'type' => 'integer',
                'primary' => true,
                'autoIncrement' => true,
                'length' => 10
            ),
            'expectedArray' => array(
                'sqlite' => 'integer PRIMARY KEY AUTOINCREMENT NOT NULL',
                'mysql' => 'bigint(10) AUTO_INCREMENT NOT NULL',
            )
        );

        // custom type (unsigned will be ignored)
        $testCases[] = array(
            'definition' => array(
                'dbType' => 'integer',
                'nullable' => false,
                'unsigned' => true
            ),
            'expectedArray' => array(
                'sqlite' => 'integer NOT NULL',
                'mysql' => 'integer NOT NULL',
            )
        );

        return self::getDatabaseAwareTestCases($testCases);
    }


    /**
     * @dataProvider provideAddColumnSql
     */
    public function testAddColumnSql($database, $column, array $definition, $afterColumn, array $expectedArray)
    {
        $scheme = self::getSchemeFromDsn($database['dsn']);
        if (!isset($expectedArray[$scheme])) {
            $this->markTestIncomplete('Test is not implemented, yet!');
        }

        $platform = $this->createPlatform($scheme);
        $actual = $platform->getAddColumnSql('user', $column, $definition, $afterColumn);
        $this->assertEquals($expectedArray[$scheme], $actual);
    }


    public function provideAddColumnSql()
    {
        $testCases = array();

        // adding column after id
        $testCases[] = array(
            'column' => 'manager_id',
            'definition' => array(
                'type' => 'integer'
            ),
            'afterColumn' => 'id',
            array(
                'sqlite' => 'ALTER TABLE "user" ADD COLUMN "manager_id" integer NOT NULL',
                'mysql' => 'ALTER TABLE `user` ADD COLUMN `manager_id` int NOT NULL AFTER `id`'
            )
        );

        // adding column
        $testCases[] = array(
            'column' => 'manager_id',
            'definition' => array(
                'type' => 'integer'
            ),
            'afterColumn' => null,
            array(
                'sqlite' => 'ALTER TABLE "user" ADD COLUMN "manager_id" integer NOT NULL',
                'mysql' => 'ALTER TABLE `user` ADD COLUMN `manager_id` int NOT NULL'
            )
        );

        return self::getDatabaseAwareTestCases($testCases);
    }


    /**
     * @dataProvider provideChangeColumnSql
     */
    public function testChangeColumnSql($database, $column, array $definition, $newColumn, array $expectedArray)
    {
        $scheme = self::getSchemeFromDsn($database['dsn']);
        if (!isset($expectedArray[$scheme])) {
            $this->markTestIncomplete('Test is not implemented, yet!');
        }

        $expected = $expectedArray[$scheme];
        if ($expected instanceof Exception) {
            $this->setExpectedException(get_class($expected));
        }
        $platform = $this->createPlatform($scheme);
        $actual = $platform->getChangeColumnSql('user', $column, $definition, $newColumn);
        $this->assertEquals($expected, $actual);
    }


    public function provideChangeColumnSql()
    {
        $testCases = array();
        $platformException = new PlatformException();

        // adding column after id
        $testCases[] = array(
            'column' => 'manager_id',
            'definition' => array(
                'type' => 'integer'
            ),
            'newColumn' => 'leader_id',
            array(
                'sqlite' => $platformException,
                'mysql' => 'ALTER TABLE `user` CHANGE COLUMN `manager_id` `leader_id` int NOT NULL'
            )
        );

        // adding column
        $testCases[] = array(
            'column' => 'manager_id',
            'definition' => array(
                'type' => 'integer'
            ),
            'newColumn' => null,
            array(
                'sqlite' => $platformException,
                'mysql' => 'ALTER TABLE `user` CHANGE COLUMN `manager_id` `manager_id` int NOT NULL'
            )
        );

        return self::getDatabaseAwareTestCases($testCases);
    }


    /**
     * @dataProvider provideDropColumnSql
     */
    public function testDropColumnSql($database, $column, array $expectedArray)
    {
        $scheme = self::getSchemeFromDsn($database['dsn']);
        if (!isset($expectedArray[$scheme])) {
            $this->markTestIncomplete('Test is not implemented, yet!');
        }

        $expected = $expectedArray[$scheme];
        if ($expected instanceof Exception) {
            $this->setExpectedException(get_class($expected));
        }
        $platform = $this->createPlatform($scheme);
        $actual = $platform->getDropColumnSql('user', $column);
        $this->assertEquals($expected, $actual);
    }


    public function provideDropColumnSql()
    {
        $testCases = array();
        $platformException = new PlatformException();

        // adding column after id
        $testCases[] = array(
            'column' => 'manager_id',
            array(
                'sqlite' => $platformException,
                'mysql' => 'ALTER TABLE `user` DROP COLUMN `manager_id`'
            )
        );

        // adding column
        $testCases[] = array(
            'column' => 'manager_id',
            array(
                'sqlite' => $platformException,
                'mysql' => 'ALTER TABLE `user` DROP COLUMN `manager_id`'
            )
        );

        return self::getDatabaseAwareTestCases($testCases);
    }


    /**
     * @dataProvider provideGetCreateIndexSql
     */
    public function testGetCreateIndexSql($database, $indexName, $fields, $indexType, array $expectedArray)
    {
        $scheme = self::getSchemeFromDsn($database['dsn']);
        if (!isset($expectedArray[$scheme])) {
            $this->markTestIncomplete('Test is not implemented, yet!');
        }

        $expected = $expectedArray[$scheme];
        $platform = $this->createPlatform($scheme);
        $actual = $platform->getCreateIndexSql('user', $indexName, $fields, $indexType);
        $this->assertEquals($expected, $actual);
    }


    public function provideGetCreateIndexSql()
    {
        $testCases = array();

        $testCases[] = array(
            'indexName' => 'UQ_username',
            'fields' => 'username',
            'indexType' => PlatformInterface::UNIQUE,
            array(
                'sqlite' => 'CREATE UNIQUE INDEX IF NOT EXISTS "user_UQ_username" ON "user" ("username")',
                'mysql' => 'CREATE UNIQUE INDEX IF NOT EXISTS `UQ_username` ON `user` (`username`)'
            )
        );
        $testCases[] = array(
            'indexName' => 'IX_username',
            'fields' => 'username',
            'indexType' => PlatformInterface::INDEX,
            array(
                'sqlite' => 'CREATE INDEX IF NOT EXISTS "user_IX_username" ON "user" ("username")',
                'mysql' => 'CREATE INDEX IF NOT EXISTS `IX_username` ON `user` (`username`)'
            )
        );
        $testCases[] = array(
            'indexName' => 'IX_username',
            'fields' => array('username', 'password'),
            'indexType' => PlatformInterface::INDEX,
            array(
                'sqlite' => 'CREATE INDEX IF NOT EXISTS "user_IX_username" ON "user" ("username", "password")',
                'mysql' => 'CREATE INDEX IF NOT EXISTS `IX_username` ON `user` (`username`, `password`)'
            )
        );

        return self::getDatabaseAwareTestCases($testCases);
    }


    /**
     * @dataProvider provideGetDropIndexSql
     */
    public function testGetDropIndexSql($database, $indexName, array $expectedArray)
    {
        $scheme = self::getSchemeFromDsn($database['dsn']);
        if (!isset($expectedArray[$scheme])) {
            $this->markTestIncomplete('Test is not implemented, yet!');
        }

        $expected = $expectedArray[$scheme];
        $platform = $this->createPlatform($scheme);
        $actual = $platform->getDropIndexSql('user', $indexName);
        $this->assertEquals($expected, $actual);
    }


    public function provideGetDropIndexSql()
    {
        $testCases = array();

        $testCases[] = array(
            'indexName' => 'UNIQUE',
            'expected' => array(
                'sqlite' => 'DROP INDEX IF EXISTS "user_UNIQUE"',
                'mysql' => 'DROP INDEX IF EXISTS `UNIQUE` ON `user`'
            )
        );

        return self::getDatabaseAwareTestCases($testCases);
    }


    /**
     * @dataProvider provideGetAddForeignKeySql
     */
    public function testGetAddForeignKeySql(
        $database,
        $tableName,
        $owningField,
        array $definition,
        array $expectedArray
    ) {
        $scheme = self::getSchemeFromDsn($database['dsn']);
        if (!isset($expectedArray[$scheme])) {
            $this->markTestIncomplete('Test is not implemented, yet!');
        }

        $expected = $expectedArray[$scheme];
        if ($expected instanceof Exception) {
            $this->setExpectedException(get_class($expected));
        }
        $platform = $this->createPlatform($scheme);
        $actual = $platform->getAddForeignKeySql($tableName, $owningField, $definition);
        $this->assertEquals($expected, $actual);
    }


    public function provideGetAddForeignKeySql()
    {
        $testCases = array();
        $platformException = new PlatformException();

        $testCases[] = array(
            'tableName' => 'user',
            'owningField' => 'manager_id',
            'definition' => array(
                'refTable' => 'user',
                'refField' => 'id',
                'constraint' => 'manager_id_constraint'
            ),
            'expected' => array(
                'sqlite' => $platformException,
                'mysql' => 'ALTER TABLE `user` ADD CONSTRAINT `manager_id_constraint` FOREIGN KEY (`manager_id`) REFERENCES `user` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT'
            )
        );

        $testCases[] = array(
            'tableName' => 'user',
            'owningField' => 'manager_id',
            'definition' => array(
                'refTable' => 'user',
                'refField' => 'id'
            ),
            'expected' => array(
                'sqlite' => $platformException,
                'mysql' => 'ALTER TABLE `user` ADD CONSTRAINT `user_fk_manager_id` FOREIGN KEY (`manager_id`) REFERENCES `user` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT'
            )
        );

        $testCases[] = array(
            'tableName' => 'user',
            'owningField' => 'manager_id',
            'definition' => array(
                'refTable' => 'user',
                'refField' => 'id',
                'onDelete' => PlatformInterface::SET_NULL,
                'onUpdate' => PlatformInterface::CASCADE
            ),
            'expected' => array(
                'sqlite' => $platformException,
                'mysql' => 'ALTER TABLE `user` ADD CONSTRAINT `user_fk_manager_id` FOREIGN KEY (`manager_id`) REFERENCES `user` (`id`) ON DELETE SET NULL ON UPDATE CASCADE'
            )
        );

        return self::getDatabaseAwareTestCases($testCases);
    }


    /**
     * @dataProvider provideDropForeignKeySql
     */
    public function testDropForeignKeySql($database, $tableName, $constraintName, array $expectedArray)
    {
        $scheme = self::getSchemeFromDsn($database['dsn']);
        if (!isset($expectedArray[$scheme])) {
            $this->markTestIncomplete('Test is not implemented, yet!');
        }

        $expected = $expectedArray[$scheme];
        if ($expected instanceof Exception) {
            $this->setExpectedException(get_class($expected));
        }
        $platform = $this->createPlatform($scheme);
        $actual = $platform->getDropForeignKeySql($tableName, $constraintName);
        $this->assertEquals($expected, $actual);
    }


    public function provideDropForeignKeySql()
    {
        $testCases = array();
        $platformException = new PlatformException();

        $testCases[] = array(
            'tableName' => 'user',
            'constraintName' => 'user_fk_manager_id',
            'expected' => array(
                'sqlite' => $platformException,
                'mysql' => 'ALTER TABLE `user` DROP FOREIGN KEY `user_fk_manager_id`'
            )
        );

        return self::getDatabaseAwareTestCases($testCases);
    }


    /**
     * @dataProvider provideGetForeignKeyDefinitionSql
     */
    public function testGetForeignKeyDefinitionSql($database, $owningField, array $definition, array $expectedArray)
    {
        $scheme = self::getSchemeFromDsn($database['dsn']);
        if (!isset($expectedArray[$scheme])) {
            $this->markTestIncomplete('Test is not implemented, yet!');
        }

        $expected = $expectedArray[$scheme];
        $platform = $this->createPlatform($scheme);
        $actual = $platform->getForeignKeyDefinitionSql($owningField, $definition);
        $this->assertEquals($expected, $actual);
    }


    public function provideGetForeignKeyDefinitionSql()
    {
        $testCases = array();

        $testCases[] = array(
            'owningField' => 'manager_id',
            'definition' => array(
                'constraint' => 'user_fk_manager_id',
                'refTable' => 'user',
                'refField' => 'id'
            ),
            'expected' => array(
                'sqlite' => 'CONSTRAINT "user_fk_manager_id" FOREIGN KEY ("manager_id") REFERENCES "user" ("id") ON DELETE RESTRICT ON UPDATE RESTRICT',
                'mysql' => 'CONSTRAINT `user_fk_manager_id` FOREIGN KEY (`manager_id`) REFERENCES `user` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT'
            )
        );

        return self::getDatabaseAwareTestCases($testCases);
    }


    /**
     * @dataProvider provideGetCreateViewSql
     */
    public function testGetCreateViewSql($database, $viewName, $sqlStatement, array $expectedArray)
    {
        $scheme = self::getSchemeFromDsn($database['dsn']);
        if (!isset($expectedArray[$scheme])) {
            $this->markTestIncomplete('Test is not implemented, yet!');
        }

        $expected = $expectedArray[$scheme] . "\nAS\n$sqlStatement";
        $platform = $this->createPlatform($scheme);
        $actual = $platform->getCreateViewSql($viewName, $sqlStatement);
        $this->assertEquals($expected, $actual);
    }


    public function provideGetCreateViewSql()
    {
        $testCases = array();

        $testCases[] = array(
            'viewName' => 'author_user_view',
            'sqlStatement' => 'SELECT a.id, a.firstname, a.lastname, u.username FROM author a LEFT JOIN user u ON a.user_id = u.id',
            'expected' => array(
                'sqlite' => 'CREATE VIEW "author_user_view"',
                'mysql' => 'CREATE VIEW `author_user_view`'
            )
        );

        return self::getDatabaseAwareTestCases($testCases);
    }


    /**
     * @dataProvider provideGetDropViewSql
     */
    public function testGetDropViewSql($database, $viewName, array $expectedArray)
    {
        $scheme = self::getSchemeFromDsn($database['dsn']);
        if (!isset($expectedArray[$scheme])) {
            $this->markTestIncomplete('Test is not implemented, yet!');
        }

        $expected = $expectedArray[$scheme];
        $platform = $this->createPlatform($scheme);
        $actual = $platform->getDropViewSql($viewName);
        $this->assertEquals($expected, $actual);
    }


    public function provideGetDropViewSql()
    {
        $testCases = array();

        $testCases[] = array(
            'viewName' => 'author_user_view',
            'expected' => array(
                'sqlite' => 'DROP VIEW IF EXISTS "author_user_view"',
                'mysql' => 'DROP VIEW IF EXISTS `author_user_view`'
            )
        );

        return self::getDatabaseAwareTestCases($testCases);
    }

}
