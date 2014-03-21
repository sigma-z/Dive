<?php
/*
 * This file is part of the Dive ORM framework.
 * (c) Steffen Zeidler <sigma_z@sigma-scripts.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dive\Platform;

use Dive\Schema\DataTypeMapper\DataTypeMapper;

/**
 * @author Steffen Zeidler <sigma_z@sigma-scripts.de>
 * Date: 20.12.12
 */
abstract class Platform implements PlatformInterface
{

    /**
     * @var string
     */
    protected $identifierQuote = '"';
    /**
     * @var string
     */
    protected $stringQuote = "'";
    /**
     * @var DataTypeMapper
     */
    protected $dataTypeMapper = null;
    /**
     * @var bool
     */
    protected $preventErrors = true;
    /**
     * index types may changed or extended in inherited classes
     * @var array
     */
    protected $indexTypes = array(
        self::UNIQUE  => 'UNIQUE INDEX',
        self::INDEX   => 'INDEX'
    );
    /**
     * foreign key constraint types may changed or extended in inherited classes
     * @var array
     */
    protected $foreignKeyConstraintTypes = array(
        self::CASCADE       => self::CASCADE,
        self::SET_NULL      => self::SET_NULL,
        self::RESTRICT      => self::RESTRICT,
        self::NO_ACTION     => self::NO_ACTION
    );
    /**
     * array of supported encodings
     * @var array
     */
    protected $supportedEncodings = array();


    /**
     * @param DataTypeMapper $dataTypeMapper
     */
    public function __construct(DataTypeMapper $dataTypeMapper)
    {
        $this->dataTypeMapper = $dataTypeMapper;
    }


    /**
     * @param  bool $preventErrors
     * @return Platform
     */
    public function setPreventErrors($preventErrors = true)
    {
        $this->preventErrors = $preventErrors;
        return $this;
    }


    /**
     * Gets identifier quote character
     *
     * @return string
     */
    public function getIdentifierQuote()
    {
        return $this->identifierQuote;
    }


    /**
     * Gets string quote character
     *
     * @return string
     */
    public function getStringQuote()
    {
        return $this->stringQuote;
    }


    /**
     * @param string|\Dive\Expression $value
     * @param string $type
     * @return string
     */
    public function quote($value, $type = null)
    {
        if ($value instanceof \Dive\Expression) {
            return $value->getSql();
        }
        if ($type === null) {
            $type = gettype($value);
        }

        switch ($type) {
            case 'double':
            case 'float':
            case 'int':
            case 'integer':
                return $value;
            case 'bool':
            case 'boolean':
                return (bool)($value) ? 'TRUE' : 'FALSE';
            case 'array':
            case 'object':
                $value = serialize($value);
                break;
        }
        $stringQuote = $this->stringQuote;
        return $stringQuote . str_replace($stringQuote, $stringQuote . $stringQuote, $value) . $stringQuote;
    }


    /**
     * quotes identifier
     *
     * @param  string $string
     * @return string
     */
    public function quoteIdentifier($string)
    {
        $quote = $this->identifierQuote;
        if (strpos($string, '.')) {
            $e = explode('.', $string);
            return $quote . $e[0] . $quote . '.' . $quote . $e[1] . $quote;
        }
        return $quote . $string . $quote;
    }


    /**
     * gets identifiers quoted and separated
     *
     * @param   array     $fields
     * @param   string    $separator
     * @return  string
     */
    public function quoteIdentifiersSeparated(array $fields, $separator = ', ')
    {
        $sql = '';
        foreach ($fields as $field) {
            $sql .= $this->quoteIdentifier($field) . $separator;
        }
        return substr($sql, 0, strlen($separator) * -1);
    }


    /**
     * get create table as sql
     *
     * @param   string  $tableName
     * @param   array   $columns
     * @param   array   $indexes
     * @param   array   $foreignKeys
     * @param   array   $tableOptions
     * @throws  PlatformException
     * @return  string
     */
    public function getCreateTableSql(
        $tableName,
        array $columns,
        array $indexes = array(),
        array $foreignKeys = array(),
        array $tableOptions = array()
    ) {
        $sql = 'CREATE TABLE ';
        if ($this->preventErrors) {
            $sql .= 'IF NOT EXISTS ';
        }
        $sql .= $this->quoteIdentifier($tableName) . " (\n";

        $primaryKey = array();

        // columns
        foreach ($columns as $name => $definition) {
            $sql .= $this->quoteIdentifier($name) . ' ';
            $sql .= $this->getColumnDefinitionSql($definition) . ",\n";
            if (isset($definition['primary']) && $definition['primary']) {
                $primaryKey[] = $this->quoteIdentifier($name);
            }
        }

        // non auto increment primary key
        if (!empty($primaryKey)) {
            $sql .= 'PRIMARY KEY(' . implode(', ', $primaryKey). "),\n";
        }

        // indexes
        foreach ($indexes as $name => $definition) {
            $indexType = isset($definition['type']) ? $definition['type'] : null;
            $sql .= $this->getIndexDefinitionSql($name, $definition['fields'], $indexType) . ",\n";
        }

        // foreign keys
        foreach ($foreignKeys as $owningField => $definition) {
            if (empty($definition['constraint'])) {
                $definition['constraint'] = $tableName . '_fk_' . $owningField;
            }
            $sql .= $this->getForeignKeyDefinitionSql($owningField, $definition) . ",\n";
        }

        // removing last comma
        $sql = substr($sql, 0, -2) . "\n)";
        //$sql .= $this->getTableOptionsSql();

        return $sql;
    }


    /**
     * gets drop table as sql
     *
     * @param  string $tableName
     * @return string
     */
    public function getDropTableSql($tableName)
    {
        $sql = 'DROP TABLE ';
        if ($this->preventErrors) {
            $sql .= 'IF EXISTS ';
        }
        $sql .= $this->quoteIdentifier($tableName);
        return $sql;
    }


    /**
     * gets rename table as sql
     *
     * @param  string $tableName
     * @param  string $renameTo
     * @return string
     */
    public function getRenameTableSql($tableName, $renameTo)
    {
        $sql = 'ALTER TABLE ' . $this->quoteIdentifier($tableName)
            . ' RENAME TO ' . $this->quoteIdentifier($renameTo);
        return $sql;
    }


    /**
     * Gets create view as sql
     *
     * @param  string $viewName
     * @param  string $sqlStatement
     * @return string
     */
    public function getCreateViewSql($viewName, $sqlStatement)
    {
        $sql = 'CREATE VIEW ' . $this->quoteIdentifier($viewName) . "\n";
        $sql .= "AS\n";
        $sql .= $sqlStatement;
        return $sql;
    }


    /**
     * Gets drop view as sql
     *
     * @param  string $viewName
     * @return string
     */
    public function getDropViewSql($viewName)
    {
        $sql = 'DROP VIEW ';
        if ($this->preventErrors) {
            $sql .= 'IF EXISTS ';
        }
        $sql .= $this->quoteIdentifier($viewName);
        return $sql;
    }


    /**
     * gets add column as sql
     *
     * @param   string  $tableName
     * @param   string  $columnName
     * @param   array   $definition
     * @param   string  $afterColumn
     * @return  string
     */
    public function getAddColumnSql($tableName, $columnName, array $definition, $afterColumn = null)
    {
        $sql = 'ALTER TABLE ' . $this->quoteIdentifier($tableName)
            . ' ADD COLUMN ' . $this->quoteIdentifier($columnName) . ' ' . $this->getColumnDefinitionSql($definition);
        return $sql;
    }


    /**
     * gets drop column as sql
     *
     * @param  string $tableName
     * @param  string $columnName
     * @return string
     */
    public function getDropColumnSql($tableName, $columnName)
    {
        $sql = 'ALTER TABLE ' . $this->quoteIdentifier($tableName)
            . ' DROP COLUMN ' . $this->quoteIdentifier($columnName);
        return $sql;
    }


    /**
     * gets change column as sql
     *
     * @param   string  $tableName
     * @param   string  $columnName
     * @param   array   $definition
     * @param   string  $newColumnName
     * @return  string
     */
    public function getChangeColumnSql($tableName, $columnName, array $definition, $newColumnName = null)
    {
        $newColumnName = $newColumnName ?: $columnName;
        $sql = 'ALTER TABLE ' . $this->quoteIdentifier($tableName)
            . ' CHANGE COLUMN ' . $this->quoteIdentifier($columnName) . ' ' . $this->quoteIdentifier($newColumnName)
            . ' ' . $this->getColumnDefinitionSql($definition);
        return $sql;
    }


    /**
     * gets data type
     *
     * @param  array $definition
     * @return string
     * @throws \LogicException
     */
    protected function getDataType(array $definition)
    {
        if (!isset($definition['type'])) {
            throw new \LogicException('Missing type in definition');
        }

        $length = isset($definition['length']) ? $definition['length'] : null;
        return $this->dataTypeMapper->getMappedDataType($definition['type'], $length);
    }


    /**
     * gets column length
     *
     * @param   array   $definition
     * @return  string
     */
    protected function getColumnLength(array $definition)
    {
        if (isset($definition['precision'])) {
            return $definition['precision'] + 1;
        }
        else if (isset($definition['length'])) {
            return $definition['length'];
        }
        return '';
    }


    /**
     * gets add index as sql
     *
     * @param  string       $tableName
     * @param  string       $indexName
     * @param  array|string $fieldNames
     * @param  string       $indexType
     * @throws PlatformException
     * @return string
     */
    public function getCreateIndexSql($tableName, $indexName, $fieldNames, $indexType = null)
    {
        if (!is_array($fieldNames)) {
            $fieldNames = array($fieldNames);
        }
        if (empty($fieldNames)) {
            throw new PlatformException("CREATE INDEX '$indexName' needs to have at least one field assigned to!");
        }

        $indexTypeSql = $this->getIndexTypeAsSql($indexType);
        $sql = 'CREATE ' . $indexTypeSql;
        if ($this->preventErrors) {
            $sql .= ' IF NOT EXISTS';
        }
        $sql .= ' ' . $this->quoteIdentifier($indexName);
        $sql .= ' ON ' . $this->quoteIdentifier($tableName);
        $sql .= ' (' . $this->quoteIdentifiersSeparated($fieldNames) . ')';
        return $sql;
    }


    /**
     * gets drop index as sql
     *
     * @param  string $tableName
     * @param  string $indexName
     * @return string
     */
    public function getDropIndexSql($tableName, $indexName)
    {
        $sql = 'DROP INDEX ' . ($this->preventErrors ? 'IF EXISTS ' : '') . $this->quoteIdentifier($indexName);
        return $sql;
    }


    /**
     * gets index definition as sql
     *
     * @param  string       $indexName
     * @param  array|string $fieldNames
     * @param  string       $indexType
     * @throws PlatformException
     * @return string
     */
    protected function getIndexDefinitionSql($indexName, $fieldNames, $indexType = null)
    {
        if (!is_array($fieldNames)) {
            $fieldNames = array($fieldNames);
        }
        $indexTypeSql = $this->getIndexTypeAsSql($indexType);
        $sql = $indexTypeSql . ' ' . $this->quoteIdentifier($indexName)
            . ' (' . $this->quoteIdentifiersSeparated($fieldNames) . ')';
        return $sql;

    }


    /**
     * gets index type as sql
     *
     * @param  string $indexType
     * @return string
     * @throws PlatformException
     */
    protected function getIndexTypeAsSql($indexType)
    {
        if ($indexType === null) {
            $indexType = self::UNIQUE;
        }
        if (!isset($this->indexTypes[$indexType])) {
            throw new PlatformException("Index type '$indexType' is not supported!");
        }
        return $this->indexTypes[$indexType];
    }


    /**
     * gets add foreign key as sql
     *
     * @param  string $tableName
     * @param  string $owningField
     * @param  array  $definition
     * @return string
     */
    public function getAddForeignKeySql($tableName, $owningField, array $definition)
    {
        if (!isset($definition['constraint'])) {
            $definition['constraint'] = $tableName . '_fk_' . $owningField;
        }
        $foreignKeyDefinitionSql = $this->getForeignKeyDefinitionSql($owningField, $definition);
        $sql = 'ALTER TABLE ' . $this->quoteIdentifier($tableName)
            . ' ADD ' . $foreignKeyDefinitionSql;
        return $sql;
    }


    /**
     * gets drop foreign key as sql
     *
     * @param  string $tableName
     * @param  string $constraintName
     * @return string
     */
    public function getDropForeignKeySql($tableName, $constraintName)
    {
        $sql = 'ALTER TABLE ' . $this->quoteIdentifier($tableName)
            . ' DROP CONSTRAINT ' . $this->quoteIdentifier($constraintName);
        return $sql;
    }


    /**
     * gets foreign key definition sql
     *
     * @param  string   $owningField
     * @param  array    $definition
     * @return string
     * @throws PlatformException
     */
    public function getForeignKeyDefinitionSql($owningField, array $definition)
    {
        $sql = 'CONSTRAINT ' . $this->quoteIdentifier($definition['constraint'])
            . ' FOREIGN KEY (' . $this->quoteIdentifier($owningField) . ')';
        $sql .= ' REFERENCES ' . $this->quoteIdentifier($definition['refTable']);
        $sql .= ' (' . $this->quoteIdentifier($definition['refField']) . ')';
        $onDelete = isset($definition['onDelete']) ? $definition['onDelete'] : null;
        $onUpdate = isset($definition['onUpdate']) ? $definition['onUpdate'] : null;
        $onDeleteSql = $this->getForeignKeyConstraintTypeAsSql($onDelete);
        $onUpdateSql = $this->getForeignKeyConstraintTypeAsSql($onUpdate);
        $sql .= ' ON DELETE ' . $onDeleteSql;
        $sql .= ' ON UPDATE ' . $onUpdateSql;
        return $sql;
    }


    /**
     * gets foreign key constraint type as sql
     *
     * @param  string $fkConstraintType
     * @return string
     * @throws PlatformException
     */
    protected function getForeignKeyConstraintTypeAsSql($fkConstraintType)
    {
        if (empty($fkConstraintType)) {
            $fkConstraintType = self::RESTRICT;
        }
        if (!isset($this->foreignKeyConstraintTypes[$fkConstraintType])) {
            throw new PlatformException("Foreign key constraint type '$fkConstraintType' is not supported!");
        }
        return $this->foreignKeyConstraintTypes[$fkConstraintType];
    }


    /**
     * Gets encoding sql name
     *
     * @param  string $encoding
     * @return mixed
     */
    public function getEncodingSqlName($encoding)
    {
        if (!isset($this->supportedEncodings[$encoding])) {
            $this->throwUnsupportedEncodingException($encoding);
        }
        return $this->supportedEncodings[$encoding];
    }


    /**
     * Throws unsupported encoding exception
     *
     * @param  string $encoding
     * @throws PlatformException
     */
    protected function throwUnsupportedEncodingException($encoding)
    {
        throw new PlatformException("Encoding '$encoding' is not supported for this platform!");
    }

}
