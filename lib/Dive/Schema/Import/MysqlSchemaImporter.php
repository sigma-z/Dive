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

/**
 * @author Steffen Zeidler <sigma_z@sigma-scripts.de>
 * Date: 04.11.12
 */
class MysqlSchemaImporter extends SchemaImporter
{

    /**
     * gets table names
     *
     * @return array
     */
    public function getTableNames()
    {
        return $this->getTableNamesByTableType('BASE TABLE');
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
     * @param  string $name
     * @return array
     */
    private function getFields($name)
    {
        $fields = array();
        $dbFields = $this->conn->query('SHOW FIELDS FROM ' . $this->conn->quoteIdentifier($name));
        foreach ($dbFields as $fieldData) {
            $fieldDefinition = $this->parseDbType($fieldData['Type']);

            if ($fieldData['Key'] == 'PRI')                 $fieldDefinition['primary'] = true;
            if ($fieldData['Null'] == 'YES')                $fieldDefinition['nullable'] = true;
            if ($fieldData['Default'] !== null)             $fieldDefinition['default'] = $fieldData['Default'];
            if ($fieldData['Extra'] == 'auto_increment')    $fieldDefinition['autoIncrement'] = true;

            $fields[$fieldData['Field']] = $fieldDefinition;
        }
        return $fields;
    }


    /**
     * gets table indexes
     *
     * @param  string $tableName
     * @return array
     */
    public function getTableIndexes($tableName)
    {
        if (!$this->hasCachedTableIndexes($tableName)) {
            $query = 'SHOW INDEXES FROM ' . $this->conn->quoteIdentifier($tableName)
                . ' WHERE Key_name <> ?';
            $rows = $this->conn->query($query, array('PRIMARY'));
            $indexes = array();
            foreach ($rows as $row) {
                $name = $row['Key_name'];
                if (!isset($indexes[$name])) {
                    $type = $row['Non_unique'] === '1' ? PlatformInterface::INDEX : PlatformInterface::UNIQUE;
                    $indexes[$name] = array('type' => $type, 'fields' => array());
                }
                $indexes[$name]['fields'][] = $row['Column_name'];
            }
            $this->cacheTableIndexes($tableName, $indexes);
        }
        return $this->getCachedTableIndexes($tableName);
    }


    /**
     * gets table foreign keys
     *
     * @param  string $tableName
     * @return  array
     */
    public function getTableForeignKeys($tableName)
    {
        $createTableStmt = $this->conn->query('SHOW CREATE TABLE ' . $this->conn->quoteIdentifier($tableName));
        $pattern = '/CONSTRAINT\s*`(\w+)`\s*FOREIGN KEY\s*\(`(\w+)`\)\s*REFERENCES\s*`(\w+)`\s*\(`(\w+)`\)\s*(.*)/';
        if (!preg_match_all($pattern, $createTableStmt[0]['Create Table'], $matches, PREG_SET_ORDER)) {
            return array();
        }

        $indexes = $this->getTableIndexes($tableName);
        $pkFields = $this->getPkFields($tableName);
        $foreignKeys = array();
        foreach ($matches as $match) {
            $localField = $match[2];
            $name = $tableName  . '.' . $localField;
            $relationType = $this->isFieldUnique($localField, $pkFields, $indexes)
                ? Relation::ONE_TO_ONE
                : Relation::ONE_TO_MANY;

            $foreignKey = array(
                'owningTable' => $tableName,
                'owningField' => $localField,
                'refTable' => $match[3],
                'refField' => $match[4],
                'onDelete' => PlatformInterface::RESTRICT,
                'onUpdate' => PlatformInterface::RESTRICT,
                'type' => $relationType
            );

            $behavior = $match[5];
            $pattern = '/ON\s+(UPDATE|DELETE)\s+(CASCADE|SET NULL|NO ACTION|RESTRICT)/';
            preg_match_all($pattern, $behavior, $behaviorMatches, PREG_SET_ORDER);

            foreach ($behaviorMatches as $behaviorMatch) {
                if ($behaviorMatch[1] == 'DELETE') {
                    $foreignKey['onDelete'] = $behaviorMatch[2];
                }
                else {
                    $foreignKey['onUpdate'] = $behaviorMatch[2];
                }
            }

            $foreignKeys[$name] = $foreignKey;
        }

        return $foreignKeys;
    }


    /**
     * gets view names
     *
     * @return array
     */
    public function getViewNames()
    {
        return $this->getTableNamesByTableType('VIEW');
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
     * gets base table and view names
     *
     * @param  string $tableType
     * @return array
     */
    private function getTableNamesByTableType($tableType)
    {
        $sql = 'SHOW FULL TABLES';
        if ($tableType) {
            $sql .= ' WHERE table_type = \'' . strtoupper($tableType) . '\'';
        }
        return $this->conn->query($sql, array(), \PDO::FETCH_COLUMN);
    }

}
