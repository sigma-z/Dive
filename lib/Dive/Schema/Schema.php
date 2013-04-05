<?php
/*
 * This file is part of the Dive ORM framework.
 * (c) Steffen Zeidler <sigma_z@sigma-scripts.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dive\Schema;

/**
 * @author Steffen Zeidler <sigma_z@sigma-scripts.de>
 * Date: 01.11.12
 */
use Dive\Platform\PlatformInterface;

class Schema
{

    /**
     * @var string
     */
    protected $recordBaseClass = '\Dive\Record';
    /**
     * @var string
     */
    protected $tableBaseClass = '\Dive\Table';

    /**
     * @var array
     */
    protected $tableSchemes = array();
    /**
     * @var array
     */
    protected $viewSchemes = array();
    /**
     * @var array keys: table names, values relations as array
     */
    protected $relations = array();
    /**
     * @var bool
     */
    protected $validationEnabled = false;


    /**
     * constructor
     *
     * @param array $definition
     */
    public function __construct(array $definition)
    {
        if (!empty($definition['tables'])) {
            foreach ($definition['tables'] as $name => $tableDefinition) {
                $this->addTableByDefinition($name, $tableDefinition);
            }
        }
        if (!empty($definition['views'])) {
            foreach ($definition['views'] as $name => $viewDefinition) {
                $this->addViewByDefinition($name, $viewDefinition);
            }
            $this->viewSchemes = $definition['views'];
        }
        if (!empty($definition['relations'])) {
            foreach ($definition['relations'] as $name => $relation) {
                $this->addRelation($name, $relation);
            }
        }
    }


    /**
     * Adds relation
     *
     * @param string $name
     * @param array  $relation
     */
    private function addRelation($name, array $relation)
    {
        if (!$this->validationEnabled || $this->validateRelation($relation)) {
            $this->addOwningTableRelation($name, $relation);
            $this->addReferencedTableRelation($name, $relation);
        }
    }


    /**
     * Sets record base class
     *
     * @param string $recordBaseClass
     */
    public function setRecordBaseClass($recordBaseClass)
    {
        $this->recordBaseClass = $recordBaseClass;
    }


    /**
     * Gets record base class
     *
     * @return string
     */
    public function getRecordBaseClass()
    {
        return $this->recordBaseClass;
    }


    /**
     * Sets table base class
     *
     * @param string $tableBaseClass
     */
    public function setTableBaseClass($tableBaseClass)
    {
        $this->tableBaseClass = $tableBaseClass;
    }


    /**
     * Gets table base class
     *
     * @return string
     */
    public function getTableBaseClass()
    {
        return $this->tableBaseClass;
    }


    /**
     * @param boolean $validationEnabled
     */
    public function setValidationEnabled($validationEnabled = true)
    {
        $this->validationEnabled = $validationEnabled === true;
    }


    /**
     * Adds owning table relation
     *
     * @param   string   $name
     * @param   array    $relation
     * @throws  SchemaException
     */
    private function addOwningTableRelation($name, array $relation)
    {
        $tableName = $relation['owningTable'];
        if (isset($this->relations[$tableName]['owning'][$name])) {
            $relationAlias = $relation['owningAlias'];
            throw new SchemaException(
                "Referencing relation '$relationAlias' already defined for '$tableName'!"
            );
        }
        else {
            $this->relations[$tableName]['owning'][$name] = $relation;
        }
    }


    /**
     * Adds referenced table relation
     *
     * @param   string  $name
     * @param   array   $relation
     * @throws  SchemaException
     */
    private function addReferencedTableRelation($name, array $relation)
    {
        $tableName = $relation['refTable'];
        if (isset($this->relations[$tableName]['referenced'][$name])) {
            $relationAlias = $relation['refAlias'];
            throw new SchemaException(
                "Referenced relation '$relationAlias' already defined for '$tableName'!"
            );
        }
        else {
            $this->relations[$tableName]['referenced'][$name] = $relation;
        }
    }


    /**
     * Returns true, if table is defined
     *
     * @param  string $name
     * @return bool
     */
    public function hasTable($name)
    {
        return isset($this->tableSchemes[$name]);
    }


    /**
     * Adds table
     *
     * @param   string $name
     * @param   array  $fields
     * @param   array  $indexes
     * @return  $this
     * @throws  SchemaException
     */
    public function addTable($name, array $fields, array $indexes = array())
    {
        if (empty($fields)) {
            throw new SchemaException("Missing fields for table '$name'!");
        }

        if ($this->validationEnabled) {
            foreach ($fields as $fieldName => $definition) {
                $this->validateField($fieldName, $definition);
            }
            foreach ($indexes as $indexName => $definition) {
                $this->validateIndex($indexName, $definition);
            }
        }

        $this->tableSchemes[$name] = array();
        if (!empty($indexes)) {
            $this->tableSchemes[$name]['indexes'] = $indexes;
        }
        $this->tableSchemes[$name]['fields'] = $fields;

        return $this;
    }


    /**
     * Adds table by definition
     *
     * @param  string $name
     * @param  array  $definition
     * @return $this
     */
    public function addTableByDefinition($name, array $definition)
    {
        $fields = isset($definition['fields']) ? $definition['fields'] : array();
        $indexes = isset($definition['indexes']) ? $definition['indexes'] : array();
        $this->addTable($name, $fields, $indexes);
        if (isset($definition['recordClass'])) {
            $this->setRecordClass($name, $definition['recordClass']);
        }
        return $this;
    }


    /**
     * Adds table field
     *
     * @param   string  $tableName
     * @param   string  $fieldName
     * @param   array   $definition
     * @return  $this
     * @throws  SchemaException
     */
    public function addTableField($tableName, $fieldName, array $definition)
    {
        if ($this->validateField($fieldName, $definition)) {
            $this->tableSchemes[$tableName]['fields'][$fieldName] = $definition;
        }
        return $this;
    }


    /**
     * Gets table names
     *
     * @return array
     */
    public function getTableNames()
    {
        return array_keys($this->tableSchemes);
    }


    /**
     * Gets record class
     *
     * @param  string $name
     * @return string
     */
    public function getRecordClass($name)
    {
        if (empty($this->tableSchemes[$name]['recordClass'])) {
            return $this->recordBaseClass;
        }
        return $this->tableSchemes[$name]['recordClass'];
    }


    /**
     * Sets record class for table
     *
     * @param  string $name
     * @param  string $recordClass
     * @return $this
     */
    public function setRecordClass($name, $recordClass)
    {
        $this->tableSchemes[$name]['recordClass'] = $recordClass;
        return $this;
    }


    /**
     * Gets table class
     *
     * @param   string  $name
     * @param   bool    $autoLoad
     * @return  string
     */
    public function getTableClass($name, $autoLoad = false)
    {
        $recordClass = $this->getRecordClass($name);
        if ($recordClass == $this->recordBaseClass) {
            $tableClass = $this->tableBaseClass;
        }
        else {
            $tableClass = $recordClass . 'Table';
            if ($autoLoad && !class_exists($tableClass)) {
                $tableClass = $this->tableBaseClass;
            }
        }
        return $tableClass;
    }


    /**
     * Gets table fields
     *
     * @param  string $name
     * @return array
     * @throws SchemaException
     */
    public function getTableFields($name)
    {
        if (empty($this->tableSchemes[$name]['fields'])) {
            throw new SchemaException("Missing fields for table '$name'!");
        }
        return $this->tableSchemes[$name]['fields'];
    }


    /**
     * Gets table indexes
     *
     * @param  string $name
     * @return array
     */
    public function getTableIndexes($name)
    {
        if (!empty($this->tableSchemes[$name]['indexes'])) {
            return $this->tableSchemes[$name]['indexes'];
        }
        return array();
    }


    /**
     * Adds table index
     *
     * @param   string       $tableName
     * @param   string       $indexName
     * @param   array|string $fields
     * @param   string       $type
     * @return  $this
     */
    public function addTableIndex($tableName, $indexName, $fields, $type = PlatformInterface::UNIQUE)
    {
        $definition = array(
            'type' => $type,
            'fields' => is_array($fields) ? $fields : array($fields)
        );
        if ($this->validateIndex($indexName, $definition)) {
            $this->tableSchemes[$tableName]['indexes'][$indexName] = $definition;
        }
        return $this;
    }


    /**
     * Gets table relations
     *
     * @param  string $name
     * @return array
     */
    public function getTableRelations($name)
    {
        if (!isset($this->relations[$name]['owning'])) {
            $this->relations[$name]['owning'] = array();
        }
        if (!isset($this->relations[$name]['referenced'])) {
            $this->relations[$name]['referenced'] = array();
        }
        return $this->relations[$name];
    }


    /**
     * Adds table relation
     *
     * @param  string $tableName
     * @param  string $relationFieldName
     * @param  array  $relation
     * @return $this
     */
    public function addTableRelation($tableName, $relationFieldName, array $relation)
    {
        $relation['owningTable'] = $tableName;
        $relation['owningField'] = $relationFieldName;
        $name = $tableName . '.' . $relationFieldName;
        $this->addRelation($name, $relation);
        return $this;
    }


    /**
     * Returns true, if view is defined
     *
     * @param  string $name
     * @return bool
     */
    public function hasView($name)
    {
        return isset($this->viewSchemes[$name]);
    }


    /**
     * Adds view
     *
     * @param  string $name
     * @param  array  $fields
     * @param  string $sqlStatement
     * @throws SchemaException
     * @return $this
     */
    public function addView($name, array $fields, $sqlStatement)
    {
        if ($this->validationEnabled) {
            foreach ($fields as $fieldName => $definition) {
                $this->validateField($fieldName, $definition);
            }
        }
        if (empty($sqlStatement)) {
            throw new SchemaException("Sql statement for view '$name' is empty!");
        }
        $this->viewSchemes[$name] = array(
            'fields' => $fields,
            'sqlStatement' => $sqlStatement
        );
        return $this;
    }


    /**
     * Adds table by definition
     *
     * @param  string $name
     * @param  array  $definition
     * @return $this
     */
    public function addViewByDefinition($name, array $definition)
    {
        $fields         = isset($definition['fields'])          ? $definition['fields']         : array();
        $sqlStatement   = isset($definition['sqlStatement'])    ? $definition['sqlStatement']   : array();
        $this->addView($name, $fields, $sqlStatement);
        return $this;
    }


    /**
     * Adds view field
     *
     * @param   string  $viewName
     * @param   string  $fieldName
     * @param   array   $definition
     * @return  $this
     * @throws  SchemaException
     */
    public function addViewField($viewName, $fieldName, array $definition)
    {
        if ($this->validateField($fieldName, $definition)) {
            $this->viewSchemes[$viewName]['fields'][$fieldName] = $definition;
        }
        return $this;
    }


    /**
     * Gets view names
     *
     * @return array
     */
    public function getViewNames()
    {
        return array_keys($this->viewSchemes);
    }


    /**
     * Gets table fields
     *
     * @param  string $name
     * @return array
     * @throws SchemaException
     */
    public function getViewFields($name)
    {
        if (empty($this->viewSchemes[$name]['fields'])) {
            throw new SchemaException("Missing fields for view '$name'!");
        }
        return $this->viewSchemes[$name]['fields'];
    }


    /**
     * Gets view statement
     *
     * @param  string $name
     * @return string
     * @throws SchemaException
     */
    public function getViewStatement($name)
    {
        if (empty($this->viewSchemes[$name]['sqlStatement'])) {
            throw new SchemaException("Missing sql statement for view '$name'!");
        }
        return $this->viewSchemes[$name]['sqlStatement'];
    }


    /**
     * Validates table or view field
     *
     * @param   string $fieldName
     * @param   array  $definition
     * @throws  SchemaException
     * @return  bool
     */
    protected function validateField($fieldName, array $definition)
    {
        if (!isset($definition['type'])) {
            throw new SchemaException("Definition of schema field '$fieldName' must be an array and define type!");
        }
        return true;
    }


    /**
     * Validates table index
     *
     * @param  string $indexName
     * @param  array  $definition
     * @return bool
     * @throws SchemaException
     */
    protected function validateIndex($indexName, array $definition)
    {
        if (empty($definition['fields']) || !isset($definition['type'])) {
            throw new SchemaException(
                "Definition of schema index '$indexName' must be an array and define fields and type!"
            );
        }
        return true;
    }


    /**
     * Validates relation
     *
     * @param  array $relation
     * @return bool
     * @throws SchemaException
     */
    protected function validateRelation(array $relation)
    {
        $checkKeys = array('owningAlias', 'owningField', 'owningTable', 'refAlias', 'refField', 'refTable', 'type');
        foreach ($checkKeys as $key) {
            if (empty($relation[$key])) {
                throw new SchemaException("Relation must defined value for key '$key'!");
            }
        }
        return true;
    }


    /**
     * Converts schema to array
     *
     * @return array
     */
    public function toArray()
    {
        $schemaDefinition = array();
        if (!empty($this->tableSchemes)) {
            $schemaDefinition['tables'] = $this->tableSchemes;
        }
        if (!empty($this->viewSchemes)) {
            $schemaDefinition['views'] = $this->viewSchemes;
        }
        if (!empty($this->relations)) {
            $schemaDefinition['relations'] = array();
            foreach ($this->relations as $data) {
                if (!empty($data['owning'])) {
                    foreach ($data['owning'] as $relation) {
                        $name = $relation['owningTable'] . '.' . $relation['owningField'];
                        $schemaDefinition['relations'][$name] = $relation;
                    }
                }
            }
        }
        return $schemaDefinition;
    }


}
