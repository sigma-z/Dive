<?php
/*
 * This file is part of the Dive ORM framework.
 * (c) Steffen Zeidler <sigma_z@sigma-scripts.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dive\Schema\Import;

use Dive\Schema\SchemaException;

/**
 * @author Steffen Zeidler <sigma_z@sigma-scripts.de>
 * Date: 04.11.12
 */
interface SchemaImporterInterface
{

    /**
     * gets table names
     *
     * @return string[]
     */
    public function getTableNames();


    /**
     * Gets primary key fields
     *
     * @param string $tableName
     * @return array
     */
    public function getPkFields($tableName);


    /**
     * gets table fields
     *
     * @param  string $tableName
     * @return array[]
     */
    public function getTableFields($tableName);


    /**
     * gets table indexes
     *
     * @param  string $tableName
     * @return array[]
     */
    public function getTableIndexes($tableName);


    /**
     * gets table foreign keys
     *
     * @param  string $tableName
     * @return array[]
     */
    public function getTableForeignKeys($tableName);


    /**
     * gets view names
     *
     * @return string[]
     */
    public function getViewNames();


    /**
     * gets view fields
     *
     * @param  string $viewName
     * @return array[]
     */
    public function getViewFields($viewName);


    /**
     * gets view statement
     * @param string $viewName
     * @return string
     * @throws SchemaException
     */
    public function getViewStatement($viewName);


    /**
     * imports definition
     *
     * @return array
     */
    public function importDefinition();

}
