<?php
/*
 * This file is part of the Dive ORM framework.
 * (c) Steffen Zeidler <sigma_z@sigma-scripts.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dive\TestSuite;

use Dive\Connection\Connection;
use Dive\Schema\Migration\AbstractMigration;
use Dive\Schema\Migration\MigrationInterface;
use Dive\Schema\Schema;

/**
 * @author Steffen Zeidler <sigma_z@sigma-scripts.de>
 * Date: 08.12.12
 */
class DbInit
{

    /**
     * @var \Dive\Connection\Connection
     */
    private $conn;
    /**
     * @var \Dive\Schema\Schema
     */
    private $schema;


    public function __construct(Connection $conn, Schema $schema)
    {
        $this->conn = $conn;
        $this->schema = $schema;
    }


    public function init()
    {
        $tableNames = $this->schema->getTableNames();
        $driver = $this->conn->getDriver();
        $this->conn->disableForeignKeys();
        foreach ($tableNames as $tableName) {
            // drop table
            $dropMigration = $driver->createSchemaMigration($this->conn, $tableName, MigrationInterface::DROP_TABLE);
            $dropMigration->execute();
            // (re)create table
            /** @var $createMigration AbstractMigration */
            $createMigration = $driver->createSchemaMigration($this->conn, $tableName, MigrationInterface::CREATE_TABLE);
            $createMigration->importFromSchema($this->schema);
            if ($this->conn->getScheme() == 'mysql') {
                $createMigration->setTableOption('engine', 'InnoDB');
            }
            $createMigration->execute();
        }
        $this->conn->enableForeignKeys();

        $viewNames = $this->schema->getViewNames();
        $platform = $driver->getPlatform();
        foreach ($viewNames as $viewName) {
            // drop view
            $sql = $platform->getDropViewSql($viewName);
            $this->conn->exec($sql);

            // (re)create view
            $sqlStatement = $this->schema->getViewStatement($viewName);
            $sql = $platform->getCreateViewSql($viewName, $sqlStatement);
            $this->conn->exec($sql);
        }
    }

}
