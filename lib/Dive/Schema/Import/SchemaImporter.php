<?php
/*
 * This file is part of the Dive ORM framework.
 * (c) Steffen Zeidler <sigma_z@sigma-scripts.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dive\Schema\Import;

use Dive\Connection\Connection;
use Dive\Platform\PlatformInterface;
use Dive\Relation\Relation;
use Dive\Schema\DataTypeMapper\DataTypeMapper;
use Dive\Schema\SchemaException;
use Dive\Util\CamelCase;

/**
 * @author Steffen Zeidler <sigma_z@sigma-scripts.de>
 * Date: 04.11.12
 */
abstract class SchemaImporter implements SchemaImporterInterface
{

    /**
     * @var \Dive\Connection\Connection
     */
    protected $conn;
    /**
     * @var string
     */
    protected $recordNamespace;
    /**
     * @var \Dive\Schema\DataTypeMapper\DataTypeMapper
     */
    protected $dataTypeMapper;
    /**
     * @var array
     */
    protected $schemaDefinition = array(
        'tables' => array(),
        'relations' => array()
    );

    /**
     * @var array[]
     * key: table name
     * value array with keys 'indexes', 'fields', 'pkFields'
     */
    private $tableStructureCache = array();


    /**
     * constructor
     *
     * @param Connection        $conn
     * @param DataTypeMapper    $dataTypeMapper
     * @param string            $recordNamespace
     */
    public function __construct(Connection $conn, DataTypeMapper $dataTypeMapper = null, $recordNamespace = '\\')
    {
        $this->conn = $conn;
        $this->recordNamespace = $recordNamespace;
        if ($dataTypeMapper === null) {
            // TODO potential security issue!!
            $class = '\Dive\Schema\DataTypeMapper\\' . ucfirst($conn->getScheme()) . 'DataTypeMapper';
            $dataTypeMapper = new $class();
        }
        $this->dataTypeMapper = $dataTypeMapper;
    }


    /**
     * @return DataTypeMapper
     */
    public function getDataTypeMapper()
    {
        return $this->dataTypeMapper;
    }


    /**
     * Gets connection
     *
     * @return \Dive\Connection\Connection
     */
    public function getConnection()
    {
        return $this->conn;
    }


    /**
     * Gets primary key fields
     *
     * @param string $tableName
     * @return array
     */
    public function getPkFields($tableName)
    {
        $pkFields = array();
        $fields = $this->getTableFields($tableName);
        foreach ($fields as $fieldName => $field) {
            if (isset($field['primary']) && $field['primary'] === true) {
                $pkFields[] = $fieldName;
            }
        }
        return $pkFields;
    }


    /**
     * @param string $fieldName
     * @param array  $pkFields
     * @param array  $indexes
     * @return bool
     */
    protected function isFieldUnique($fieldName, array $pkFields, array $indexes)
    {
        if (isset($pkFields[0]) && !isset($pkFields[1]) && $fieldName == $pkFields[0]) {
            return true;
        }
        foreach ($indexes as $index) {
            if ($index['type'] == PlatformInterface::UNIQUE
                && !isset($index['fields'][1])
                && $fieldName == $index['fields'][0])
            {
                return true;
            }
        }
        return false;
    }


    /**
     * Imports schema definition
     *
     * @param  array $schemaDefinition schema definition, that will be updated through schema import
     * @return array
     */
    public function importDefinition(array $schemaDefinition = null)
    {
        if (empty($schemaDefinition)) {
            $schemaDefinition = array('tables' => array(), 'relations' => array());
        }
        $this->schemaDefinition = $schemaDefinition;

        $this->importTables();
        $this->importViews();

        // sort tables
        ksort($this->schemaDefinition['tables']);
        // sort relations
        ksort($this->schemaDefinition['relations']);

        return $this->schemaDefinition;
    }


    /**
     * Imports tables schema
     */
    protected function importTables()
    {
        $tables = $this->getTableNames();
        foreach ($tables as $tableName) {
            $fields = $this->getTableFields($tableName);

            // update field data, if exists
            foreach ($fields as $fieldName => &$fieldProperties) {
                if (isset($this->schemaDefinition['tables'][$tableName]['fields'][$fieldName])) {
                    $actualFieldProperties = $this->schemaDefinition['tables'][$tableName]['fields'][$fieldName];
                    $fieldProperties = array_merge($actualFieldProperties, $fieldProperties);
                }
            }

            $indexes = $this->getTableIndexes($tableName);
            $relations = $this->getTableForeignKeys($tableName, $indexes);

            // add foreign key name to field property 'foreign'
            foreach ($relations as $name => $relation) {
                $field = $relation['owningField'];
                $fields[$field]['foreign'] = $name;

                if (isset($this->schemaDefinition['relations'][$name])) {
                    $relation = array_merge($this->schemaDefinition['relations'][$name], $relation);
                }
                else {
                    $relation = $this->guessRelationAliases($relation);
                }
                $this->schemaDefinition['relations'][$name] = $relation;
            }

            $tableDefinition = array(
                'fields' => $fields,
                'indexes' => $indexes
            );

            $this->schemaDefinition['tables'][$tableName] = $tableDefinition;
        }
    }


    /**
     * imports views schema
     */
    protected function importViews()
    {
        $views = $this->getViewNames();
        foreach ($views as $viewName) {
            $fields = $this->getViewFields($viewName);
            $this->schemaDefinition['tables'][$viewName]['fields'] = $fields;
        }
    }


    /**
     * tries to guess relation alias names
     *
     * @param   array $relation
     * @return  array
     */
    protected function guessRelationAliases(array $relation)
    {
        $isOneToOne = $relation['type'] == Relation::ONE_TO_ONE;

        if ($relation['owningTable'] == $relation['refTable']) {
            $relation['owningAlias'] = 'Parent';
            $relation['refAlias'] = $isOneToOne ? 'Child' : 'Children';
            return $relation;
        }

//        $relation['owningAlias'] = CamelCase::toCamelCase($relation['refTable']);
//        $relation['refAlias'] = CamelCase::toCamelCase($relation['owningTable']);

        $relation['owningAlias'] = CamelCase::toCamelCase($relation['owningTable']);
        $relation['refAlias'] = CamelCase::toCamelCase($relation['refTable']);

        if (!$isOneToOne) {
            $relation['owningAlias'] .= 'HasMany';
        }
        return $relation;
    }


    /**
     * parses type definition
     *
     * @param  string $type
     * @throws \Dive\Schema\SchemaException
     * @return array
     */
    protected function parseDbType($type)
    {
        $definition = array();
        $dataType = '';
        $length = null;
        $values = null;
        $match = array();

        // parsing type like varchar(32) OR char(2)
        if (preg_match('/^(\w+)\((\d+)\)/', $type, $match)) {
            $dataType = $match[1];
            $length = $match[2];
        }
        // parsing type like float(5,2) OR decimal(5,2)
        else if (preg_match('/^(\w+)\((\d+),(\d+)\)/', $type, $match)) {
            $dataType = $match[1];
            $length = $match[2] + 1;
            $definition['scale'] = $match[3];
        }
        // parsing type like date OR timestamp
        else if (preg_match('/^\w+/', $type, $match)) {
            $dataType = $match[0];
        }

        // parsing attributes like UNSIGNED, ZEROFILL, AND BINARY
        if (isset($match[0])) {
            $attributesString = strtolower(substr($type, strlen($match[0])));
            $attributesString = trim($attributesString);
            if (!empty($attributesString)) {
                $attributes = explode(' ', trim($attributesString));
                foreach ($attributes as $attr) {
                    $definition[$attr] = true;
                }
            }
        }

        $dataType = strtolower($dataType);
        switch ($dataType) {
            case 'time':
                $length = 5;
                break;
            case 'year':
                $length = 4;
                break;
            case 'enum':
                if (preg_match('/^enum\((.+)\)/i', $type, $match)) {
                    $values = self::parseInlineValues($match[1]);
                }
                break;
            case 'set':
                if (preg_match('/^set\((.+)\)/i', $type, $match)) {
                    $values = self::parseInlineValues($match[1]);
                }
                break;
        }

        if (!$this->dataTypeMapper->hasDataType($dataType)) {
            throw new SchemaException("Data type $dataType is not defined! (Found in string $type)");
        }
        $definition['type'] = $this->dataTypeMapper->getOrmType($dataType);

        // get length by longest value entry
        if (!empty($values)) {
            foreach ($values as $value) {
                if ($length < strlen($value)) {
                    $length = strlen($value);
                }
            }
            $definition['values'] = $values;
        }
        if ($length !== null) {
            $definition['length'] = (int)$length;
        }

        return $definition;
    }


    /**
     * parses inline values from type definition
     *
     * @static
     * @param  string $string
     * @return array
     */
    protected static function parseInlineValues($string)
    {
        $values = array();
        $value = '';
        $string = str_replace(array('\\', "''"), array('\\\\', "\\'"), $string);
        $strLen = strlen($string);
        $cursor = 0;
        while ($cursor < $strLen) {
            switch ($string[$cursor]) {
                case '\\':
                    $value .= $string[$cursor + 1];
                    $cursor++;
                    break;
                case ',':
                    $values[] = $value;
                    $value = '';
                    break;
                case "'":
                    if (empty($value)) {
                        $value = $string[$cursor + 1];
                        $cursor++;
                    }
                    break;
                default:
                    $value .= $string[$cursor];
            }
            $cursor++;
        }

        if (!empty($value)) {
            $values[] = $value;
        }

        return $values;
    }


    /**
     * @param  string $tableName
     * @return bool
     */
    protected function hasCachedTableIndexes($tableName)
    {
        return isset($this->tableStructureCache[$tableName]['indexes']);
    }


    /**
     * @param string $tableName
     * @param array  $indexes
     */
    protected function cacheTableIndexes($tableName, array $indexes)
    {
        $this->tableStructureCache[$tableName]['indexes'] = $indexes;
    }


    /**
     * @param  string $tableName
     * @return array[]
     * @throws \Dive\Schema\SchemaException
     */
    protected function getCachedTableIndexes($tableName)
    {
        if ($this->hasCachedTableIndexes($tableName)) {
            return $this->tableStructureCache[$tableName]['indexes'];
        }
        throw new SchemaException("No cached indexes for table '$tableName'!");
    }


    /**
     * @param  string $tableName
     * @return bool
     */
    protected function hasCachedTableFields($tableName)
    {
        return isset($this->tableStructureCache[$tableName]['fields']);
    }


    /**
     * @param string $tableName
     * @param array  $fields
     */
    protected function cacheTableFields($tableName, array $fields)
    {
        $this->tableStructureCache[$tableName]['fields'] = $fields;
    }


    /**
     * @param  string $tableName
     * @return array[]
     * @throws \Dive\Schema\SchemaException
     */
    protected function getCachedTableFields($tableName)
    {
        if ($this->hasCachedTableFields($tableName)) {
            return $this->tableStructureCache[$tableName]['fields'];
        }
        throw new SchemaException("No cached fields for table '$tableName'!");
    }


    /**
     * @param string $tableName
     */
    public function resetTableCache($tableName = null)
    {
        if ($tableName) {
            unset($this->tableStructureCache[$tableName]);
        }
        else {
            $this->tableStructureCache = array();
        }
    }

}
