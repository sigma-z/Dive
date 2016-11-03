<?php
/*
 * This file is part of the Dive ORM framework.
 * (c) Steffen Zeidler <sigma_z@sigma-scripts.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Dive\Connection\Driver;

use Dive\Connection\Connection;
use Dive\Platform\MysqlPlatform;
use Dive\Schema\DataTypeMapper\MysqlDataTypeMapper;
use Dive\Schema\Import\MysqlSchemaImporter;
use Dive\Schema\Migration\MysqlMigration;

/**
 * @author Steffen Zeidler <sigma_z@sigma-scripts.de>
 * Date: 21.12.12
 */
class MysqlDriver implements DriverInterface
{

    /**
     * @var \Dive\Schema\DataTypeMapper\MysqlDataTypeMapper
     */
    private $dataTypeMapper = null;


    /**
     * gets schema importer
     *
     * @param   Connection $conn
     * @return  \Dive\Schema\Import\MysqlSchemaImporter
     */
    public function getSchemaImporter(Connection $conn)
    {
        return new MysqlSchemaImporter($conn, $this->getDataTypeMapper());
    }


    /**
     * @param   \Dive\Connection\Connection $conn
     * @param   string $tableName
     * @param   string $mode
     * @return  \Dive\Schema\Migration\MysqlMigration
     */
    public function createSchemaMigration(Connection $conn, $tableName, $mode = null)
    {
        return new MysqlMigration($conn, $tableName, $mode);
    }


    /**
     * gets database name
     *
     * @param   \Dive\Connection\Connection $conn
     * @throws  DriverException
     * @return  string
     */
    public function getDatabaseName(Connection $conn)
    {
        $result = $conn->query('SELECT DATABASE()', array(), \PDO::FETCH_COLUMN);
        return $result[0];
    }


    /**
     * gets data type mapper
     *
     * @return \Dive\Schema\DataTypeMapper\DataTypeMapper
     */
    public function getDataTypeMapper()
    {
        if ($this->dataTypeMapper === null) {
            $this->dataTypeMapper = new MysqlDataTypeMapper();
        }
        return $this->dataTypeMapper;
    }


    /**
     * gets platform
     *
     * @return \Dive\Platform\MysqlPlatform
     */
    public function getPlatform()
    {
        $dataTypeMapper = $this->getDataTypeMapper();
        return new MysqlPlatform($dataTypeMapper);
    }

}
