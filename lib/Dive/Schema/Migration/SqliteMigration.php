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
 * Date: 01.12.12
 */
class SqliteMigration extends Migration
{

    /**
     * @return array[]
     */
    public function getSqlStatements()
    {
        if ($this->checkOperationForDropAndCreateFallback()) {
            return $this->getDropAndCreateTableFallbackStatements();
        }
        return parent::getSqlStatements();
    }


    /**
     * @return bool
     */
    private function checkOperationForDropAndCreateFallback()
    {
        if ($this->mode != self::ALTER_TABLE) {
            return false;
        }

        $omittedOperations = array(
            self::DROP_COLUMN, self::CHANGE_COLUMN,
            self::ADD_FOREIGN_KEY, self::CHANGE_FOREIGN_KEY, self::DROP_FOREIGN_KEY
        );
        foreach ($this->operations as $operation) {
            if (in_array($operation['type'], $omittedOperations)) {
                return true;
            }
            else if ($operation['type'] == self::ADD_COLUMN) {
                $definition = $this->columns[$operation['name']];
                if (isset($definition['autoIncrement']) && $definition['autoIncrement'] === true) {
                    return true;
                }
                if (isset($definition['primary']) && $definition['primary'] === true) {
                    return true;
                }
            }
        }
        return false;
    }


    /**
     * @return string[]
     */
    private function getDropAndCreateTableFallbackStatements()
    {
        $newColumns = array();
        $oldColumns = array();
        $addedColumns = array();

        foreach ($this->operations as $operation) {
            if ($operation['type'] == self::CHANGE_COLUMN) {
                $newName = $operation['newName'] ?: $operation['name'];
                if (!in_array($newName, $newColumns)) {
                    $newColumns[] = $newName;
                    $oldColumns[] = $operation['name'];
                }
            }
            else if ($operation['type'] == self::ADD_COLUMN) {
                $addedColumns[] = $operation['name'];
            }
        }

        foreach ($this->columns as $name => $colDefinition) {
            if (!in_array($name, $newColumns) && !in_array($name, $addedColumns)) {
                $newColumns[] = $name;
                $oldColumns[] = $name;
            }
        }

        $statements[] = "ALTER TABLE \"$this->tableName\" RENAME TO \"{$this->tableName}_backup\"";
        foreach ($this->getCreateTableStatements() as $stmt) {
            $statements[] = $stmt;
        }
        $statements[] = "INSERT INTO \"$this->tableName\" (" . $this->quoteIdentifiersSeparated($newColumns) . ")"
                . " SELECT " . $this->quoteIdentifiersSeparated($oldColumns)
                . " FROM \"{$this->tableName}_backup\"";
        $statements[] = "DROP TABLE \"{$this->tableName}_backup\"";

        return $statements;
    }


    /**
     * @return string[]
     */
    protected function getCreateTableStatements()
    {
        $statements = parent::getCreateTableStatements();
        // statements for adding indexes
        foreach ($this->indexes as $name => $definition) {
            $statements[] = $this->getCreateIndexSql($name);
        }
        return $statements;
    }

}
