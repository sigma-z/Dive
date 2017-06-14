<?php
/*
 * This file is part of the Dive ORM framework.
 * (c) Steffen Zeidler <sigma_z@sigma-scripts.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dive\Schema\Import;

use Dive\Platform\PlatformInterface;
use Dive\Relation\Relation;
use Dive\Schema\SchemaException;

/**
 * @author Steffen Zeidler <sigma_z@sigma-scripts.de>
 * Date: 08.11.12
 */
class SqliteSchemaImporter extends SchemaImporter
{

    /**
     * gets table names
     *
     * @return string[]
     */
    public function getTableNames()
    {
        return $this->getTableNamesByTableType('table');
    }


    /**
     * gets table fields
     *
     * @param  string $tableName
     * @return array
     */
    public function getTableFields($tableName)
    {
        if (!$this->hasCachedTableFields($tableName)) {
            $fields = $this->getFields($tableName);
            $this->cacheTableFields($tableName, $fields);
        }
        return $this->getCachedTableFields($tableName);
    }


    /**
     * gets table/view fields
     *
     * @param  string $tableName
     * @return array
     */
    private function getFields($tableName)
    {
        $fields = array();
        $dbFields = $this->conn->query('PRAGMA table_info (' . $this->conn->quoteIdentifier($tableName) . ')');
        foreach ($dbFields as $fieldData) {
            $dbType = $fieldData['type'];
            $unsigned = (0 === stripos($dbType, 'unsigned '));
            if ($unsigned) {
                $dbType = substr($dbType, 9);
            }
            $fieldDefinition = $this->parseDbType($dbType);

            if ($fieldData['pk'] === '1')           $fieldDefinition['primary'] = true;
            if ($fieldData['notnull'] !== '1')      $fieldDefinition['nullable'] = true;
            if ($fieldData['dflt_value'] !== null)  $fieldDefinition['default'] = $fieldData['dflt_value'];
            if ($fieldData['pk'] === '1' && strcasecmp($dbType, 'integer') === 0) {
                $fieldDefinition['autoIncrement'] = true;
            }
            if ($unsigned) {
                $fieldDefinition['unsigned'] = true;
            }

            $fields[$fieldData['name']] = $fieldDefinition;
        }
        return $fields;
    }


    /**
     * gets table unique keys
     *
     * @param  string $tableName
     * @return array[]
     */
    public function getTableIndexes($tableName)
    {
        if (!$this->hasCachedTableIndexes($tableName)) {
            $query = 'PRAGMA index_list(' . $this->conn->quoteIdentifier($tableName) . ')';
            $rows = $this->conn->query($query);
            $indexes = array();
            foreach ($rows as $row) {
                $name = $row['name'];
                $query = 'PRAGMA index_info(' . $this->conn->quoteIdentifier($name) . ')';
                $type = $row['unique'] === '1' ? PlatformInterface::UNIQUE : PlatformInterface::INDEX;
                $indexes[$name] = array('type' => $type, 'fields' => array());
                $indexRows = $this->conn->query($query);
                $tableFields = $this->getFields($tableName);

                foreach ($indexRows as $indexRow) {
                    $fieldName = $indexRow['name'];
                    if (isset($tableFields[$fieldName]['nullable']) && $tableFields[$fieldName]['nullable'] === true
                        && $this->conn->getPlatform()->isUniqueConstraintNullConstrained()) {
                        $indexes[$name]['nullConstrained'] = true;
                    }
                    $indexes[$name]['fields'][] = $fieldName;
                }
            }
            $this->cacheTableIndexes($tableName, $indexes);
        }
        return $this->getCachedTableIndexes($tableName);
    }


    /**
     * gets table foreign keys
     *
     * @param  string $tableName
     * @return array[]
     */
    public function getTableForeignKeys($tableName)
    {
        $indexes = $this->getTableIndexes($tableName);
        $pkFields = $this->getPkFields($tableName);

        $query = 'PRAGMA foreign_key_list(' . $this->conn->quoteIdentifier($tableName) . ')';
        $rows = $this->conn->query($query);
        $foreignKeys = array();
        foreach ($rows as $row) {
            $localField = $row['from'];
            $name = $tableName  . '.' . $localField;
            $relationType = $this->isFieldUnique($localField, $pkFields, $indexes)
                ? Relation::ONE_TO_ONE
                : Relation::ONE_TO_MANY;

            $foreignKey = array(
                'owningTable' => $tableName,
                'owningField' => $localField,
                'refTable' => $row['table'],
                'refField' => $row['to'],
                'onDelete' => $row['on_delete'],
                'onUpdate' => $row['on_update'],
                'type' => $relationType
            );

            $foreignKeys[$name] = $foreignKey;
        }
        return $foreignKeys;
    }


    /**
     * gets view names
     *
     * @return string[]
     */
    public function getViewNames()
    {
        return $this->getTableNamesByTableType('view');
    }


    /**
     * gets view fields
     *
     * @param  string $viewName
     * @return array
     */
    public function getViewFields($viewName)
    {
        return $this->getFields($viewName);
    }


    /**
     * gets view statement
     *
     * @param string $viewName
     * @return string
     * @throws SchemaException
     */
    public function getViewStatement($viewName)
    {
        $createViewStatement = $this->conn->queryOne(
            "SELECT sql FROM sqlite_master WHERE type = 'view' AND name = ?", array($viewName)
        );
        if (empty($createViewStatement)) {
            throw new SchemaException("Could not fetch table structure from database for '$viewName'.");
        }

        $quotedName = $this->conn->quoteIdentifier($viewName);
        $pattern = '/CREATE\s+.*?VIEW\s+' . preg_quote($quotedName, '/') . '\s+AS\s+(.+)$/';
        if (!preg_match($pattern, $createViewStatement['sql'], $matches)) {
            return '';
        }
        return $matches[1];
    }


    /**
     * gets base table and view names
     *
     * @param  string $tableType
     * @return array
     */
    private function getTableNamesByTableType($tableType)
    {
        $sql = 'SELECT tbl_name FROM ' . $this->conn->quoteIdentifier('sqlite_master');
        // ignore internal tables of sqlite
        $sql .=' WHERE tbl_name NOT LIKE ' . $this->conn->quote('sqlite_%');

        if ($tableType) {
            $sql .= ' AND type = \'' . strtolower($tableType) . '\'';
        }
        return $this->conn->query($sql, array(), \PDO::FETCH_COLUMN);
    }

}
