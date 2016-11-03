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
use Dive\Platform\SqlitePlatform;
use Dive\Schema\DataTypeMapper\SqliteDataTypeMapper;
use Dive\Schema\Import\SqliteSchemaImporter;
use Dive\Schema\Migration\SqliteMigration;

/**
 * @author Steffen Zeidler <sigma_z@sigma-scripts.de>
 * Date: 21.12.12
 */
class SqliteDriver implements DriverInterface
{

    /**
     * @var \Dive\Schema\DataTypeMapper\SqliteDataTypeMapper
     */
    private $dataTypeMapper = null;


    /**
     * gets schema importer
     *
     * @param   Connection $conn
     * @return  \Dive\Schema\Import\SqliteSchemaImporter
     */
    public function getSchemaImporter(Connection $conn)
    {
        return new SqliteSchemaImporter($conn, $this->getDataTypeMapper());
    }


    /**
     * @param   \Dive\Connection\Connection $conn
     * @param   string $tableName
     * @param   string $mode
     * @return  \Dive\Schema\Migration\SqliteMigration
     */
    public function createSchemaMigration(Connection $conn, $tableName, $mode = null)
    {
        return new SqliteMigration($conn, $tableName, $mode);
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
        $parsed = parse_url($conn->getDsn());
        if (isset($parsed['path'])) {
            return basename($parsed['path']);
        }
        throw new DriverException('Could not get database name');
    }


    /**
     * gets data type mapper
     *
     * @return \Dive\Schema\DataTypeMapper\DataTypeMapper
     */
    public function getDataTypeMapper()
    {
        if ($this->dataTypeMapper === null) {
            $this->dataTypeMapper = new SqliteDataTypeMapper();
        }
        return $this->dataTypeMapper;
    }


    /**
     * gets platform
     *
     * @return \Dive\Platform\SqlitePlatform
     */
    public function getPlatform()
    {
        $dataTypeMapper = $this->getDataTypeMapper();
        return new SqlitePlatform($dataTypeMapper);
    }


}
