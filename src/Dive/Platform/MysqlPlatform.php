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
class MysqlPlatform extends Platform
{

    const INDEX_FULLTEXT = 'FULLTEXT';


    /**
     * constructor
     *
     * @param DataTypeMapper $dataTypeMapper
     */
    public function __construct(DataTypeMapper $dataTypeMapper)
    {
        parent::__construct($dataTypeMapper);

        $this->identifierQuote = '`';
        $this->indexTypes[self::INDEX_FULLTEXT] = self::INDEX_FULLTEXT;
        $this->supportedEncodings = [
            self::ENC_UTF8 => 'utf8',
            self::ENC_LATIN1 => 'latin1'
        ];
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
        $sql = parent::getAddColumnSql($tableName, $columnName, $definition, $afterColumn);
        if ($afterColumn) {
            $sql .= ' AFTER ' . $this->quoteIdentifier($afterColumn);
        }
        return $sql;
    }


    /**
     * gets column definition as sql
     *
     * @param   array   $definition
     * @return  string
     * @throws  PlatformException
     */
    public function getColumnDefinitionSql(array $definition)
    {
        if (isset($definition['dbType'])) {
            $dbType = $definition['dbType'];
        }
        else {
            $dbType = $this->getDataType($definition);
            $length = $this->getColumnLength($definition);
            if ($dbType === 'enum') {
                if (empty($definition['values']) || !is_array($definition['values'])) {
                    throw new PlatformException('Missing values for enum for column!');
                }
                $dbType  .= '(';
                /** @var array $values */
                $values = $definition['values'];
                foreach ($values as $value) {
                    $dbType .= $this->quote($value, 'string') . ',';
                }
                $dbType = substr($dbType, 0, -1) . ')';
            }
            // TODO length is not supported for some types
            else if ($dbType !== 'double' && $length) {
                if (isset($definition['scale'])) {
                    $precision = $length - 1;
                    $scale = $definition['scale'];
                    $length = "$precision,$scale";
                }
                $dbType .= '(' . $length . ')';
            }

            if (isset($definition['unsigned']) && $definition['unsigned'] === true) {
                $dbType .= ' UNSIGNED';
                if (isset($definition['zerofill']) && $definition['zerofill'] === true) {
                    $dbType .= ' ZEROFILL';
                }
            }
        }

        $notNull    = !isset($definition['nullable']) || $definition['nullable'] !== true ? ' NOT NULL' : '';
        $charset    = isset($definition['charset'])     ? ' CHARACTER SET ' . $definition['charset']    : '';
        $collation  = isset($definition['collation'])   ? ' COLLATE '  . $definition['collation']       : '';
        $autoIncrement = isset($definition['autoIncrement']) && $definition['autoIncrement'] === true
            ? ' AUTO_INCREMENT'
            : '';
        $default = isset($definition['default'])
            ? ' DEFAULT ' . $this->quote($definition['default'], $definition['type'])
            : '';
        $comment = isset($definition['comment'])
            ? ' COMMENT ' . $this->quote($definition['comment'], 'string')
            : '';

        return $dbType . $charset  . $collation . $default . $autoIncrement . $notNull . $comment;
    }


    /**
     * gets column length
     *
     * @param   array   $definition
     * @return  string
     */
    protected function getColumnLength(array $definition)
    {
        if ($definition['type'] === 'time') {
            return 8;
        }
        return parent::getColumnLength($definition);
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
        $sql = parent::getDropIndexSql($tableName, $indexName);
        $sql .= ' ON ' . $this->quoteIdentifier($tableName);
        return $sql;
    }


    /**
     * gets disable foreign key checks as sql
     *
     * @return string
     */
    public function getDisableForeignKeyChecksSql()
    {
        return 'SET FOREIGN_KEY_CHECKS=0';
    }

    /**
     * gets enable foreign key checks as sql
     *
     * @return string
     */
    public function getEnableForeignKeyChecksSql()
    {
        return 'SET FOREIGN_KEY_CHECKS=1';
    }


    /**
     * Gets set connection encoding sql statement
     *
     * @param  string $encoding
     * @return string
     */
    public function getSetConnectionEncodingSql($encoding)
    {
        $encoding = $this->getEncodingSqlName($encoding);
        return "SET NAMES '$encoding'";
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
            . ' DROP FOREIGN KEY ' . $this->quoteIdentifier($constraintName);
        return $sql;
    }


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
    ) {
        $sql = parent::getCreateTableSql($tableName, $columns, $indexes, $foreignKeys, $tableOptions);
        if (!empty($tableOptions['engine'])) {
            $sql .= ' ENGINE ' . $tableOptions['engine'];
        }
        if (!empty($tableOptions['charset'])) {
            $sql .= ' CHARACTER SET ' . $tableOptions['charset'];
        }
        if (!empty($tableOptions['collation'])) {
            $sql .= ' COLLATE ' . $tableOptions['collation'];
        }
        if (!empty($tableOptions['comment'])) {
            $sql .= ' COMMENT ' . $this->stringQuote . $tableOptions['comment'] . $this->stringQuote;
        }
        return $sql;
    }


    /**
     * gets version sql
     *
     * @return string
     */
    public function getVersionSql()
    {
        return 'SELECT VERSION()';
    }
}
