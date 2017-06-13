<?php
/*
 * This file is part of the Dive ORM framework.
 * (c) Steffen Zeidler <sigma_z@sigma-scripts.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dive\Test\Schema\Import;

use Dive\Schema\Import\MysqlSchemaImporter;
use Dive\TestSuite\TestCase;

/**
 * @author Steffen Zeidler <sigma_z@sigma-scripts.de>
 * @date   13.06.2017
 */
class MysqlSchemaImporterTest extends TestCase
{

    /**
     * @expectedException \Dive\Schema\Import\SchemaImporterException
     */
    public function testMultipleForeignKeyConstraintDefinitionsWillThrowException()
    {
        $database = $this->getDatabaseForSchemeOrSkipTest('mysql');
        $importer = $this->getImporter($database);
        $importer->getTableForeignKeys('author');
    }


    /**
     * @param  array $database
     * @return MysqlSchemaImporter
     */
    private function getImporter(array $database)
    {
        $conn = $this->createDatabaseConnectionOrMarkTestSkipped($database);
        $driver = $conn->getDriver();
        $dataTypeMapper = $driver->getDataTypeMapper();
        $importer = $this->getMockBuilder(MysqlSchemaImporter::class)
            ->setMethods(['getCreateTableStatement'])
            ->setConstructorArgs([$conn, $dataTypeMapper])
            ->getMock();
        $createTableStatement = [
            'Table' => 'author',
            'Create Table' => 'CREATE TABLE `author` (
                 CONSTRAINT `author_fk_editor_id` FOREIGN KEY (`editor_id`) REFERENCES `author` (`id`),
                 CONSTRAINT `author_fk_editor_id_1` FOREIGN KEY (`editor_id`) REFERENCES `author` (`id`),
                 CONSTRAINT `author_fk_user_id` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`)
            )'
        ];
        $importer->expects($this->any())
            ->method('getCreateTableStatement')
            ->will($this->returnValue($createTableStatement));
        return $importer;
    }

}