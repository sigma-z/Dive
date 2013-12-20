<?php
/*
 * This file is part of the Dive ORM framework.
 * (c) Steffen Zeidler <sigma_z@sigma-scripts.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dive\Schema\Migration;

use Dive\Relation\Relation;
use Dive\Schema\Import\ImporterInterface;
use Dive\Platform\PlatformInterface;
use Dive\Connection\Connection;
use Dive\Schema\Schema;
use Dive\Table;

/**
 * @author Steffen Zeidler <sigma_z@sigma-scripts.de>
 * Date: 24.11.12
 */
abstract class Migration implements MigrationInterface
{

    const RENAME_TABLE  = 'renameTable';

    const ADD_COLUMN    = 'addColumn';
    const CHANGE_COLUMN = 'changeColumn';
    const DROP_COLUMN   = 'dropColumn';

    const ADD_INDEX     = 'addIndex';
    const CHANGE_INDEX  = 'changeIndex';
    const RENAME_INDEX  = 'renamedIndex';
    const DROP_INDEX    = 'dropIndex';

    const ADD_FOREIGN_KEY       = 'addForeignKey';
    const CHANGE_FOREIGN_KEY    = 'changeForeignKey';
    const DROP_FOREIGN_KEY      = 'dropForeignKey';


    /**
     * @var Connection
     */
    protected $conn;
    /**
     * @var string
     */
    protected $mode;
    /**
     * @var PlatformInterface
     */
    protected $platform;
    /**
     * @var bool
     */
    protected $preventErrors = true;
    /**
     * @var array[]
     */
    protected $operations = array();
    /**
     * @var string
     */
    protected $tableName = '';
    /**
     * @var string
     */
    protected $renameTableTo = '';
    /**
     * @var array[]
     */
    protected $columns = array();
    /**
     * @var array[]
     */
    protected $indexes = array();
    /**
     * @var array[]
     */
    protected $foreignKeys = array();
    /**
     * @var array
     */
    protected $options = array();


    /**
     * constructor
     *
     * @param Connection        $conn
     * @param string            $tableName
     * @param string            $mode
     */
    public function __construct(Connection $conn, $tableName, $mode = null)
    {
        $this->conn = $conn;
        $this->platform = $conn->getPlatform();
        $this->tableName = $tableName;
        $this->mode = $mode ?: self::ALTER_TABLE;
    }


    public function importFromDb(ImporterInterface $importer)
    {
        $tableNames = $importer->getTableNames();
        if (!in_array($this->tableName, $tableNames)) {
            if ($this->mode == self::ALTER_TABLE) {
                $this->mode = self::CREATE_TABLE;
            }
        }
        else if ($this->mode == self::CREATE_TABLE) {
            $this->mode = self::ALTER_TABLE;
        }

        if ($this->mode == self::ALTER_TABLE) {
            $this->columns = $importer->getTableFields($this->tableName);
            $this->indexes = $importer->getTableIndexes($this->tableName);

            $pkFields = array();
            foreach ($this->columns as $fieldName => $column) {
                if (isset($column['primary']) && $column['primary'] === true) {
                    $pkFields[] = $fieldName;
                }
            }
            $this->foreignKeys = $importer->getTableForeignKeys($this->tableName, $pkFields, $this->indexes);
        }
    }


    public function importFromSchema(Schema $schema)
    {
        $this->columns = $schema->getTableFields($this->tableName);
        $this->indexes = $schema->getTableIndexes($this->tableName);
        $foreignKeys = $schema->getTableRelations($this->tableName);
        foreach ($foreignKeys['owning'] as $definition) {
            $owningField = $definition['owningField'];
            $this->foreignKeys[$owningField] = array(
                'refTable' => $definition['refTable'],
                'refField' => $definition['refField'],
                'onDelete' => isset($definition['onDelete']) ? $definition['onDelete'] : null,
                'onUpdate' => isset($definition['onUpdate']) ? $definition['onUpdate'] : null
            );
        }
    }


    /**
     * @return \Dive\Connection\Connection
     */
    public function getConnection()
    {
        return $this->conn;
    }


    /**
     * @return string
     */
    public function getTableName()
    {
        return $this->tableName;
    }


    /**
     * @return string
     */
    public function getMode()
    {
        return $this->mode;
    }


    /**
     * @param  bool $preventErrors
     * @return Migration
     */
    public function setPreventErrors($preventErrors = true)
    {
        $this->preventErrors = $preventErrors;
        return $this;
    }


    /**
     * @return Migration
     */
    public function reset()
    {
        $this->operations = array();
        return $this;
    }


    /**
     * rename table

     *
*@param  string $newName
     * @return Migration
     */
    public function renameTable($newName)
    {
        $this->throwExceptionIfMethodNotSupported(array(self::DROP_TABLE, self::CREATE_TABLE));

        if ($this->tableName !== $newName) {
            $this->renameTableTo = $newName;
        }
        return $this;
    }


    /**
     * sets table option
     *
*@example
     *   collation: utf8_unicode_ci
     *   charset:   utf8
     *   engine:    InnoDB          for mysql

     * @param  string $name
     * @param  string $value
     * @return Migration
     */
    public function setTableOption($name, $value)
    {
        $this->options[$name] = $value;
        return $this;
    }


    /**
     * gets table option
     *
     * @param  string $name
     * @return string
     */
    public function getTableOption($name)
    {
        return isset($this->options[$name]) ? $this->options[$name] : null;
    }


    // <editor-fold desc="COLUMNS">
    public function getColumns()
    {
        return $this->columns;
    }


    /**
     * checks if column is defined
     *
     * @param  string   $name
     * @return bool
     */
    public function hasColumn($name)
    {
        return isset($this->columns[$name]);
    }


    /**
     * adds column

     *
*@param  string   $name
     * @param  array    $definition
     * @param  string   $afterColumn
     * @return Migration
     */
    public function addColumn($name, array $definition, $afterColumn = null)
    {
        $this->throwExceptionIfMethodNotSupported(array(self::DROP_TABLE));

        if ($this->hasColumn($name)) {
            return $this->changeColumn($name, $definition);
        }

        $this->columns[$name] = $definition;

        $this->operations[] = array(
            'type' => self::ADD_COLUMN,
            'name' => $name,
            'afterColumn' => $afterColumn
        );
        return $this;
    }


    /**
     * drops column

     *
*@param  string $name
     * @return Migration
     */
    public function dropColumn($name)
    {
        $this->throwExceptionIfMethodNotSupported(array(self::DROP_TABLE, self::CREATE_TABLE));

        if (!$this->hasColumn($name)) {
            return $this;
        }

        unset($this->columns[$name]);

        $this->dropForeignKey($name);

        foreach ($this->indexes as $indexName => $definition) {
            if (false !== ($pos = array_search($name, $definition['fields']))) {
                array_splice($definition['fields'], $pos, 1);
                if (empty($definition['fields'])) {
                    $this->dropIndex($indexName);
                }
                else {
                    $this->changeIndex($indexName, $definition);
                }
            }
        }

        $this->operations[] = array(
            'type' => self::DROP_COLUMN,
            'name' => $name
        );
        return $this;
    }


    /**
     * change column

     *
*@param  string $name
     * @param  array  $definition
     * @param  string $newName
     * @return Migration
     */
    public function changeColumn($name, array $definition = array(), $newName = null)
    {
        $this->throwExceptionIfMethodNotSupported(array(self::DROP_TABLE, self::CREATE_TABLE));
        if (!$this->hasColumn($name)) {
            return $this->addColumn($newName ?: $name, $definition);
        }

        $definition = !empty($definition) ? $definition : $this->columns[$name];
        $foreignKeyDefinition = null;
        $affectedIndexes = array();
        if ($newName) {
            $this->columns[$newName] = $definition;
            unset($this->columns[$name]);

            if ($this->hasForeignKey($name)) {
                $foreignKeyDefinition = $this->foreignKeys[$name];
                $this->dropForeignKey($name);
            }

            foreach ($this->indexes as $indexName => $indexDefinition) {
                if (false !== ($pos = array_search($name, $indexDefinition['fields']))) {
                    $this->dropIndex($indexName);
                    array_splice($indexDefinition['fields'], $pos, 1, $newName);
                    $affectedIndexes[$indexName] = $indexDefinition;
                }
            }
        }
        else {
            $this->columns[$name] = $definition;
        }

        $this->operations[] = array(
            'type' => self::CHANGE_COLUMN,
            'name' => $name,
            'newName' => $newName != $name ? $newName : null
        );

        foreach ($affectedIndexes as $indexName => $indexDefinition) {
            $this->addIndex($indexName, $indexDefinition['fields'], $indexDefinition['type']);
        }

        if ($foreignKeyDefinition) {
            $this->addForeignKey(
                $newName,
                $foreignKeyDefinition['refTable'],
                $foreignKeyDefinition['refField'],
                $foreignKeyDefinition['onDelete'],
                $foreignKeyDefinition['onUpdate']
            );
        }

        return $this;
    }
    // </editor-fold>


    // <editor-fold desc="INDEXES">
    public function getIndexes()
    {
        return $this->indexes;
    }


    /**
     * checks if index is defined
     *
     * @param  string $name
     * @return bool
     */
    public function hasIndex($name)
    {
        return isset($this->indexes[$name]);
    }


    /**
     * adds index

     *
*@param   string          $name
     * @param   string|array    $fields
     * @param   string          $indexType
     * @return  Migration
     */
    public function addIndex($name, $fields, $indexType = null)
    {
        $this->throwExceptionIfMethodNotSupported(array(self::DROP_TABLE));

        if (!is_array($fields)) {
            $fields = array($fields);
        }
        if ($this->hasIndex($name)) {
            return $this->changeIndex($name, $fields, $indexType);
        }
        $this->indexes[$name] = array('fields' => $fields, 'type' => $indexType);

        $this->operations[] = array(
            'type' => self::ADD_INDEX,
            'name' => $name,
        );
        return $this;
    }


    /**
     * drops index

     *
*@param  string $name
     * @return Migration
     */
    public function dropIndex($name)
    {
        $this->throwExceptionIfMethodNotSupported(array(self::DROP_TABLE, self::CREATE_TABLE));

        if (!$this->hasIndex($name)) {
            return $this;
        }

        unset($this->indexes[$name]);

        $this->operations[] = array(
            'type' => self::DROP_INDEX,
            'name' => $name
        );
        return $this;
    }


    /**
     * changes index

     *
*@param   string          $name
     * @param   string|array    $fields
     * @param   string          $indexType
     * @return  Migration
     */
    public function changeIndex($name, $fields, $indexType = null)
    {
        $this->throwExceptionIfMethodNotSupported(array(self::DROP_TABLE));

        if (!is_array($fields)) {
            $fields = array($fields);
        }

        if (!$this->hasIndex($name)) {
            return $this->addIndex($name, $fields, $indexType);
        }

        $droppingFields = array_diff($this->indexes[$name]['fields'], $fields);
        foreach ($droppingFields as $field) {
            $this->dropForeignKey($field);
        }

        $this->indexes[$name] = array('fields' => $fields, 'type' => $indexType);

        $this->operations[] = array(
            'type' => self::CHANGE_INDEX,
            'name' => $name
        );
        return $this;
    }


    /**
     * renames index

     *
*@param  string $name
     * @param  string $newName
     * @throws MigrationException
     * @return Migration
     */
    public function renameIndex($name, $newName)
    {
        $this->throwExceptionIfMethodNotSupported(array(self::DROP_TABLE, self::CREATE_TABLE));
        if (!$this->hasIndex($name)) {
            throw new MigrationException("Can not rename index! Index $name does not exist!");
        }

        $this->indexes[$newName] = $this->indexes[$name];
        unset($this->indexes[$name]);

        $this->operations[] = array(
            'type' => self::RENAME_INDEX,
            'name' => $name,
            'newName' => $newName,
        );
        return $this;
    }
    // </editor-fold>


    // <editor-fold desc="FOREIGN KEYS">
    public function getForeignKeys()
    {
        return $this->foreignKeys;
    }


    /**
     * checks if foreign key is defined

     *
*@param  string $owningField
     * @return Migration
     */
    public function hasForeignKey($owningField)
    {
        return isset($this->foreignKeys[$owningField]);
    }


    /**
     * adds foreign key

     *
*@param   string  $owningField
     * @param   string  $refTable
     * @param   string  $refField
     * @param   string  $onDelete
     * @param   string  $onUpdate
     * @return  Migration
     */
    public function addForeignKey($owningField, $refTable, $refField, $onDelete = null, $onUpdate = null)
    {
        $this->throwExceptionIfMethodNotSupported(array(self::DROP_TABLE));

        if ($this->hasForeignKey($owningField)) {
            return $this->changeForeignKey($owningField, $onDelete, $onUpdate);
        }

        $this->foreignKeys[$owningField] = array(
            'refTable' => $refTable,
            'refField' => $refField,
            'onDelete' => $onDelete,
            'onUpdate' => $onUpdate
        );

        $this->operations[] = array(
            'type' => self::ADD_FOREIGN_KEY,
            'name' => $owningField,
        );
        return $this;
    }


    /**
     * adds foreign key by relation instance

     *
*@param \Dive\Relation\Relation $relation
     * @return Migration
     * @throws MigrationException
     */
    public function addForeignKeyByRelation(Relation $relation)
    {
        if ($relation->getOwningTable() != $this->tableName) {
            throw new MigrationException("
                Relation does not belong to table $this->tableName, it belongs to " . $relation->getOwningTable() . '!'
            );
        }

        $owningField = $relation->getOwningField();
        $referencedTable = $relation->getReferencedTable();
        $referencedField = $relation->getReferencedField();
        $onDelete = $relation->getOnDelete();
        $onUpdate = $relation->getOnUpdate();
        return $this->addForeignKey($owningField, $referencedTable, $referencedField, $onDelete, $onUpdate);
    }


    /**
     * drops foreign key

     *
*@param   string $owningField
     * @return  Migration
     */
    public function dropForeignKey($owningField)
    {
        $this->throwExceptionIfMethodNotSupported(array(self::DROP_TABLE, self::CREATE_TABLE));

        if (!$this->hasForeignKey($owningField)) {
            return $this;
        }

        unset($this->foreignKeys[$owningField]);

        $this->operations[] = array(
            'type' => self::DROP_FOREIGN_KEY,
            'name' => $owningField
        );
        return $this;
    }


    /**
     * changes foreign key
     *
     * @param   string  $owningField
     * @param   string  $onDelete
     * @param   string  $onUpdate
     * @throws  MigrationException
     * @return  MigrationInterface
     */
    public function changeForeignKey($owningField, $onDelete = null, $onUpdate = null)
    {
        $this->throwExceptionIfMethodNotSupported(array(self::DROP_TABLE));

        if (!$this->hasForeignKey($owningField)) {
            throw new MigrationException("Can not change foreign key! There is no foreign key on field $owningField!");
        }

        $this->foreignKeys[$owningField]['onDelete'] = $onDelete;
        $this->foreignKeys[$owningField]['onUpdate'] = $onUpdate;

        $this->operations[] = array(
            'type' => self::CHANGE_FOREIGN_KEY,
            'name' => $owningField
        );
        return $this;
    }
    // </editor-fold>


    /**
     * @throws MigrationException
     * @return array[]
     */
    public function getSqlStatements()
    {
        switch ($this->mode) {
            case self::CREATE_TABLE:
                return $this->getCreateTableStatements();

            case self::ALTER_TABLE:
                $statements = $this->getAlterTableStatements();
                if ($this->renameTableTo) {
                    $statements[] = $this->platform->getRenameTableSql($this->tableName, $this->renameTableTo);
                }
                return $statements;

            case self::DROP_TABLE:
                return $this->getDropTableStatements();
        }

        throw new MigrationException("Migration mode $this->mode is not supported!");
    }


    public function execute($useTransaction = true)
    {
        $statements = $this->getSqlStatements();
        if ($useTransaction) {
            array_unshift($statements, 'BEGIN');
            $statements[] = 'COMMIT';
        }
        foreach ($statements as $stmt) {
            $this->conn->exec($stmt);
        }
        $this->reset();
    }


    protected function getCreateTableStatements()
    {
        $createTableSql = $this->platform->getCreateTableSql(
            $this->tableName,
            $this->columns,
            $this->indexes,
            $this->foreignKeys,
            $this->options
        );
        return array($createTableSql);
    }


    protected function getDropTableStatements()
    {
        $sql = 'DROP TABLE ' . ($this->preventErrors ? ' IF EXISTS' : '')
            . ' ' . $this->conn->quoteIdentifier($this->tableName);
        return array($sql);
    }


    protected function getAlterTableStatements()
    {
        $statements = array();
        foreach ($this->operations as $operation) {
            $statementsByOperation = $this->getOperationStatements($operation);
            if (!empty($statementsByOperation)) {
                $statements = array_merge($statements, $statementsByOperation);
            }
        }
        return $statements;
    }


    protected function getOperationStatements(array $operation)
    {
        $statements = array();
        switch ($operation['type']) {
            case self::ADD_COLUMN:
                $statements[] = $this->getAddColumnSql($operation['name'], $operation['afterColumn']);
                break;

            case self::DROP_COLUMN:
                $statements[] = $this->platform->getDropColumnSql($this->tableName, $operation['name']);
                break;

            case self::CHANGE_COLUMN:
                $newName = isset($operation['newName']) ? $operation['newName'] : $operation['name'];
                $statements[] = $this->getChangeColumnSql($operation['name'], $newName);
                break;

            case self::ADD_INDEX:
                $statements[] = $this->getCreateIndexSql($operation['name']);
                break;

            case self::DROP_INDEX:
                $statements[] = $this->platform->getDropIndexSql($this->tableName, $operation['name']);
                break;

            case self::CHANGE_INDEX:
            case self::RENAME_INDEX:
                $statements[] = $this->platform->getDropIndexSql($this->tableName, $operation['name']);
                $newName = isset($operation['newName']) ? $operation['newName'] : $operation['name'];
                $statements[] = $this->getCreateIndexSql($newName);
                break;

            case self::ADD_FOREIGN_KEY:
                $statements[] = $this->getAddForeignKeySql($operation['name']);
                break;

            case self::DROP_FOREIGN_KEY:
                $constrainName = $this->tableName . '_fk_' . $operation['name'];
                $statements[] = $this->platform->getDropForeignKeySql($this->tableName, $constrainName);
                break;

            case self::CHANGE_FOREIGN_KEY:
                $constrainName = $this->tableName . '_fk_' . $operation['name'];
                $statements[] = $this->platform->getDropForeignKeySql($this->tableName, $constrainName);
                $statements[] = $this->getAddForeignKeySql($operation['name']);
                break;
        }
        return $statements;
    }


    protected function getAddColumnSql($name, $afterColumn = null)
    {
        if (!$this->hasColumn($name)) {
            throw new MigrationException(
                "Cannot add column '$name'! Have you dropped the column or changed afterwards?"
            );
        }
        $definition = $this->columns[$name];
        return $this->platform->getAddColumnSql($this->tableName, $name, $definition, $afterColumn);
    }


    protected function getChangeColumnSql($name, $newName)
    {
        if (!$this->hasColumn($newName)) {
            throw new MigrationException(
                "Cannot add column '$newName'! Have you dropped the column or changed afterwards?"
            );
        }
        $definition = $this->columns[$newName];
        return $this->platform->getChangeColumnSql($this->tableName, $name, $definition, $newName);
    }


    protected function getCreateIndexSql($name)
    {
        if (!$this->hasIndex($name)) {
            throw new MigrationException("Cannot add index '$name'! Have you dropped the index afterwards?");
        }
        $definition = $this->indexes[$name];
        return $this->platform->getCreateIndexSql($this->tableName, $name, $definition['fields'], $definition['type']);
    }


    protected function getAddForeignKeySql($name)
    {
        if (!$this->hasForeignKey($name)) {
            throw new MigrationException(
                "Cannot add foreign key for '$name'! Have you dropped the foreign key afterwards?"
            );
        }
        $definition = $this->foreignKeys[$name];
        return $this->platform->getAddForeignKeySql($this->tableName, $name, $definition);
    }


    /**
     *
     *
     * @param  array $fields
     * @return string
     */
    protected function quoteIdentifiersSeparated($fields)
    {
        return $this->platform->quoteIdentifiersSeparated($fields);
    }


    /**
     * @param  array $unsupportedModes
     * @throws MigrationException
     */
    protected function throwExceptionIfMethodNotSupported(array $unsupportedModes)
    {
        if (in_array($this->mode, $unsupportedModes)) {
            throw new MigrationException("Method not supported for $this->mode!");
        }
    }

}
