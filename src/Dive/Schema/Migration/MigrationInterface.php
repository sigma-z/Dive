<?php
/*
 * This file is part of the Dive ORM framework.
 * (c) Steffen Zeidler <sigma_z@sigma-scripts.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dive\Schema\Migration;

/**
 * @author Steffen Zeidler <sigma_z@sigma-scripts.de>
 * Date: 24.11.12
 */
use Dive\Schema\Schema;

interface MigrationInterface
{

    const CREATE_TABLE  = 'CREATE TABLE';
    const DROP_TABLE    = 'DROP TABLE';
    const ALTER_TABLE   = 'ALTER TABLE';


    /**
     * renames table
     *
     * @param  string $newName
     * @return MigrationInterface
     */
    public function renameTable($newName);

    // <editor-fold desc="COLUMNS">
    /**
     * checks if column is defined
     *
     * @param   string  $name
     * @return  bool
     */
    public function hasColumn($name);

    /**
     * adds column
     *
     * @param   string  $name
     * @param   array   $definition
     * @param   string  $afterColumn
     * @return  MigrationInterface
     */
    public function addColumn($name, array $definition, $afterColumn = null);

    /**
     * drops column
     *
     * @param  string $name
     * @return MigrationInterface
     */
    public function dropColumn($name);

    /**
     * change column
     *
     * @param  string $name
     * @param  array  $definition
     * @param  string $newName
     * @return MigrationInterface
     */
    public function changeColumn($name, array $definition = array(), $newName = null);
    // </editor-fold>


    // <editor-fold desc="INDEXES">
    /**
     * checks if index is defined
     *
     * @param  string $name
     * @return bool
     */
    public function hasIndex($name);

    /**
     * adds index
     *
     * @param   string          $name
     * @param   string|array    $fields
     * @param   string          $indexType
     * @return  MigrationInterface
     */
    public function addIndex($name, $fields, $indexType = null);

    /**
     * drops index
     *
     * @param  string $name
     * @return MigrationInterface
     */
    public function dropIndex($name);

    /**
     * changes index
     *
     * @param   string          $name
     * @param   string|array    $fields
     * @param   string          $indexType
     * @return  MigrationInterface
     */
    public function changeIndex($name, $fields, $indexType = null);

    /**
     * renames index
     *
     * @param  string $name
     * @param  string $newName
     * @return MigrationInterface
     */
    public function renameIndex($name, $newName);
    // </editor-fold>


    // <editor-fold desc="FOREIGN KEYS">
    /**
     * checks if foreign key is defined
     *
     * @param  string $owningField
     * @return MigrationInterface
     */
    public function hasForeignKey($owningField);

    /**
     * adds foreign key
     *
     * @param   string  $owningField
     * @param   string  $refTable
     * @param   string  $refField
     * @param   string  $onDelete
     * @param   string  $onUpdate
     * @return  MigrationInterface
     */
    public function addForeignKey($owningField, $refTable, $refField, $onDelete = null, $onUpdate = null);

    /**
     * drops foreign key
     *
     * @param   string $owningField
     * @return  MigrationInterface
     */
    public function dropForeignKey($owningField);

    /**
     * changes foreign key
     *
     * @param   string  $owningField
     * @param   string  $onDelete
     * @param   string  $onUpdate
     * @return  MigrationInterface
     */
    public function changeForeignKey($owningField, $onDelete = null, $onUpdate = null);
    // </editor-fold>

    /**
     * imports definitions from schema
     *
     * @param \Dive\Schema\Schema $schema
     */
    public function importFromSchema(Schema $schema);


    /**
     * @return array[]
     */
    public function getSqlStatements();


    /**
     * @param  bool $useTransaction
     */
    public function execute($useTransaction = true);

}
