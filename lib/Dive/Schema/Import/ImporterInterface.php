<?php
/*
 * This file is part of the Dive ORM framework.
 * (c) Steffen Zeidler <sigma_z@sigma-scripts.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dive\Schema\Import;

/**
 * @author Steffen Zeidler <sigma_z@sigma-scripts.de>
 * Date: 04.11.12
 */
interface ImporterInterface
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
     * @param  string       $tableName
     * @param  array|string $pkFields
     * @param  array        $indexes
     * @return array[]
     */
    public function getTableForeignKeys($tableName, $pkFields = null, array $indexes = null);


    /**
     * gets view names
     *
     * @return string[]
     */
    public function getViewNames();


    /**
     * gets field names
     *
     * @param  string $viewName
     * @return array[]
     */
    public function getViewFields($viewName);


    /**
     * imports definition
     *
     * @return array
     */
    public function importDefinition();

}
