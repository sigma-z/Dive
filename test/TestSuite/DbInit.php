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
use Dive\Connection\Driver\DriverInterface;
use Dive\Platform\PlatformInterface;
use Dive\Schema\Migration\Migration;
use Dive\Schema\Migration\MigrationInterface;
use Dive\Schema\Schema;

/**
 * @author Steffen Zeidler <sigma_z@sigma-scripts.de>
 * Date: 08.12.12
 */
class DbInit
{

    /**
     * @var Connection
     */
    private $conn;
    /**
     * @var Schema
     */
    private $schema;


    /**
     * @param Connection $conn
     * @param Schema     $schema
     */
    public function __construct(Connection $conn, Schema $schema)
    {
        $this->conn = $conn;
        $this->schema = $schema;
    }


    public function init()
    {
        $this->conn->getEventDispatcher()->addListener(Connection::EVENT_POST_CONNECT, array($this, 'initSchema'));
    }


    public function initSchema()
    {
        $driver = $this->conn->getDriver();

        // (re)create tables
        $tableNames = $this->schema->getTableNames();
        $this->conn->disableForeignKeys();
        foreach ($tableNames as $tableName) {
            $this->dropTable($driver, $tableName);
            $this->createTable($driver, $tableName);
        }
        $this->conn->enableForeignKeys();

        // (re)create views
        $viewNames = $this->schema->getViewNames();
        $platform = $driver->getPlatform();
        foreach ($viewNames as $viewName) {
            $this->dropView($platform, $viewName);
            $this->createView($platform, $viewName);
        }
    }


    /**
     * drop table
     *
     * @param DriverInterface $driver
     * @param string          $tableName
     */
    private function dropTable(DriverInterface $driver, $tableName)
    {
        $driver->createSchemaMigration($this->conn, $tableName, MigrationInterface::DROP_TABLE)->execute();
    }


    /**
     * (re)create table
     *
     * @param DriverInterface $driver
     * @param string          $tableName
     */
    private function createTable(DriverInterface $driver, $tableName)
    {
        /** @var $createMigration Migration */
        $createMigration = $driver->createSchemaMigration($this->conn, $tableName, MigrationInterface::CREATE_TABLE);
        $createMigration->importFromSchema($this->schema);
        if ($this->conn->getScheme() === 'mysql') {
            // TODO tableOptions should be defined by Schema
            $createMigration->setTableOption('engine', 'InnoDB');
            $createMigration->setTableOption('collation', 'utf8_unicode_ci');
        }
        $createMigration->execute();
    }


    /**
     * drop view
     *
     * @param PlatformInterface $platform
     * @param string            $viewName
     */
    private function dropView(PlatformInterface $platform, $viewName)
    {
        $sql = $platform->getDropViewSql($viewName);
        $this->conn->exec($sql);
    }


    /**
     * (re)create view
     *
     * @param PlatformInterface $platform
     * @param string            $viewName
     */
    private function createView(PlatformInterface $platform, $viewName)
    {
        $sqlStatement = $this->schema->getViewStatement($viewName);
        $sql = $platform->getCreateViewSql($viewName, $sqlStatement);
        $this->conn->exec($sql);
    }

}
