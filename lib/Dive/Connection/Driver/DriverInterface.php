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

/**
 * @author Steffen Zeidler <sigma_z@sigma-scripts.de>
 * Date: 21.12.12
 */
interface DriverInterface
{

    /**
     * establishes database connection
     *
     * @param   string $dsn
     * @param   string $user
     * @param   string $password
     * @return  \PDO
     */
    //public function connect($dsn, $user = '', $password = '');

    /**
     * gets schema importer
     *
     * @param   Connection $conn
     * @return  \Dive\Schema\Import\ImporterInterface
     */
    public function getSchemaImporter(Connection $conn);

    /**
     * @param   \Dive\Connection\Connection $conn
     * @param   string $tableName
     * @param   string $mode
     * @return  \Dive\Schema\Migration\MigrationInterface
     */
    public function createSchemaMigration(Connection $conn, $tableName, $mode = null);

    /**
     * gets database name
     *
     * @param   \Dive\Connection\Connection $conn
     * @return  string
     */
    public function getDatabaseName(Connection $conn);

    /**
     * @return \Dive\Schema\DataTypeMapper\DataTypeMapper
     */
    public function getDataTypeMapper();

    /**
     * @return \Dive\Platform\PlatformInterface
     */
    public function getPlatform();

}
