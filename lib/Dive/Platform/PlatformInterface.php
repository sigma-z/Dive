<?php
/*
 * This file is part of the Dive ORM framework.
 * (c) Steffen Zeidler <sigma_z@sigma-scripts.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dive\Platform;

/**
 * @author Steffen Zeidler <sigma_z@sigma-scripts.de>
 * Date: 20.12.12
 */
interface PlatformInterface
{

    /**
     * constants for indexes
     */
    const INDEX         = 'index';
    const UNIQUE        = 'unique';

    /**
     * constants foreign key constraints
     */
    const CASCADE       = 'CASCADE';
    const SET_NULL      = 'SET NULL';
    const NO_ACTION     = 'NO ACTION';
    const RESTRICT      = 'RESTRICT';


    const ENC_UTF8      = 'UTF8';
    const ENC_LATIN1    = 'LATIN1';


    /**
     * Gets string quote character
     *
     * @return string
     */
    public function getStringQuote();

    /**
     * Gets identifier quote character
     *
     * @return string
     */
    public function getIdentifierQuote();

    /**
     * quotes value
     *
     * @param   string|\Dive\Expression $value
     * @param   string                  $type
     * @return  string
     */
    public function quote($value, $type = null);

    /**
     * quotes identifier
     *
     * @param  string $string
     * @return string
     */
    public function quoteIdentifier($string);

    /**
     * quotes identifiers separated
     *
     * @param   array   $fields
     * @param   string  $separator
     * @return  string
     */
    public function quoteIdentifiersSeparated(array $fields, $separator = ',');


    /**
     * Gets set connection encoding sql statement
     *
     * @param  string $encoding
     * @return string
     */
    public function getSetConnectionEncodingSql($encoding);


    // <editor-fold desc="TABLE OPERATION">
    /**
     * get create table as sql
     *
     * @param   string  $tableName
     * @param   array   $columns
     * @param   array   $indexes
     * @param   array   $foreignKeys
     * @param   array   $tableOptions
     * @return  string
     */
    public function getCreateTableSql(
        $tableName,
        array $columns,
        array $indexes = array(),
        array $foreignKeys = array(),
        array $tableOptions = array()
    );

    /**
     * gets drop table as sql
     *
     * @param  string $tableName
     * @return string
     */
    public function getDropTableSql($tableName);

    /**
     * gets rename table as sql
     *
     * @param  string $tableName
     * @param  string $renameTo
     * @return string
     */
    public function getRenameTableSql($tableName, $renameTo);
    // </editor-fold>


    // <editor-fold desc="VIEW OPERATION">
    /**
     * Gets create view as sql
     *
     * @param  string $viewName
     * @param  string $sqlStatement
     * @return string
     */
    public function getCreateViewSql($viewName, $sqlStatement);

    /**
     * Gets drop view as sql
     *
     * @param  string $viewName
     * @return string
     */
    public function getDropViewSql($viewName);
    // </editor-fold>


    // <editor-fold desc="COLUMNS">
    /**
     * gets add column as sql
     *
     * @param   string  $tableName
     * @param   string  $columnName
     * @param   array   $definition
     * @param   string  $afterColumn
     * @return  string
     */
    public function getAddColumnSql($tableName, $columnName, array $definition, $afterColumn = null);

    /**
     * gets drop column as sql
     *
     * @param  string $tableName
     * @param  string $columnName
     * @return string
     */
    public function getDropColumnSql($tableName, $columnName);

    /**
     * gets change column as sql
     *
     * @param  string  $tableName
     * @param  string  $columnName
     * @param  array   $definition
     * @param  string  $newColumnName
     * @return string
     */
    public function getChangeColumnSql($tableName, $columnName, array $definition, $newColumnName = null);

    /**
     * gets column definition as sql
     *
     * @param   array   $definition
     * @return  string
     */
    public function getColumnDefinitionSql(array $definition);
    // </editor-fold>


    // <editor-fold desc="INDEXES">
    /**
     * gets add index as sql
     *
     * @param  string       $tableName
     * @param  string       $indexName
     * @param  array|string $fieldNames
     * @param  string       $indexType
     * @return string
     */
    public function getCreateIndexSql($tableName, $indexName, $fieldNames, $indexType = null);

    /**
     * gets drop index as sql
     *
     * @param  string $tableName
     * @param  string $indexName
     * @return string
     */
    public function getDropIndexSql($tableName, $indexName);

    /**
     * Returns TRUE, if NULL-values do NOT disable the unique constraint (ie. Oracle and MSSQL)
     * Returns FALSE for MySQL, SQLITE, PostgreSQL, DB2
     *
     * @return bool
     */
    public function isUniqueConstraintNullConstrained();
    // </editor-fold>


    // <editor-fold desc="FOREIGN KEYS">
    /**
     * gets disable foreign key checks as sql
     *
     * @return string
     */
    public function getDisableForeignKeyChecksSql();

    /**
     * gets enable foreign key checks as sql
     *
     * @return string
     */
    public function getEnableForeignKeyChecksSql();

    /**
     * gets add foreign key as sql
     *
     * @param  string $tableName
     * @param  string $owningField
     * @param  array  $definition
     * @return string
     */
    public function getAddForeignKeySql($tableName, $owningField, array $definition);

    /**
     * gets drop foreign key as sql
     *
     * @param  string $tableName
     * @param  string $constraintName
     * @return string
     */
    public function getDropForeignKeySql($tableName, $constraintName);

    /**
     * gets foreign key definition sql
     *
     * @param  string   $owningField
     * @param  array    $definition
     * @return string
     */
    public function getForeignKeyDefinitionSql($owningField, array $definition);

    /**
     * gets version sql
     *
     * @return string
     */
    public function getVersionSql();
    // </editor-fold>

}
