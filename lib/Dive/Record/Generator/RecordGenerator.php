<?php
 /*
 * This file is part of the Dive ORM framework.
 * (c) Steffen Zeidler <sigma_z@sigma-scripts.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dive\Record\Generator;

use Dive\RecordManager;
use Dive\Relation\Relation;
use Dive\Table;
use Dive\Util\FieldValuesGenerator;

/**
 * @author  Steffen Zeidler <sigma_z@sigma-scripts.de>
 * @created 12.04.13
 */
class RecordGenerator
{

    /**
     * @var RecordManager
     */
    private $rm = null;

    /**
     * @var FieldValuesGenerator
     */
    private $fieldValueGenerator = null;

    /**
     * @var array
     * keys: table names
     * values: rows
     */
    private $tableRows = array();

    /**
     * @var array[]
     * keys: keys provided by table rows
     * values: record identifier as string
     */
    private $recordAliasIdMap = array();

    /**
     * @var array
     * keys: table name
     * values: map field name
     */
    private $tableMapFields = array();


    /**
     * @param RecordManager        $rm
     * @param FieldValuesGenerator $fieldValuesGenerator
     */
    public function __construct(RecordManager $rm, FieldValuesGenerator $fieldValuesGenerator)
    {
        $this->rm = $rm;
        $this->fieldValueGenerator = $fieldValuesGenerator;
    }


    /**
     * Clears data of tableRows and recordAliasIdMap
     */
    public function clear()
    {
        $this->tableRows = array();
        $this->tableMapFields = array();
        $this->recordAliasIdMap = array();
    }


    /**
     * Sets tables mapping fields
     *
     * @param  array $tablesMapField
     * @return $this
     */
    public function setTablesMapField(array $tablesMapField)
    {
        $this->tableMapFields = array();
        foreach ($tablesMapField as $tableName => $mapField) {
            $this->setTableMapField($tableName, $mapField);
        }
        return $this;
    }


    /**
     * Sets table map field
     *
     * @param  string $tableName
     * @param  string $mapField
     * @return $this
     */
    public function setTableMapField($tableName, $mapField)
    {
        $this->tableMapFields[$tableName] = $mapField;
        return $this;
    }


    /**
     * Sets tables rows
     *
     * @param  array $tablesRows
     * @return $this
     */
    public function setTablesRows(array $tablesRows)
    {
        $this->tableRows = array();
        foreach ($tablesRows as $tableName => $tableRows) {
            $this->setTableRows($tableName, $tableRows);
        }
        return $this;
    }


    /**
     * Sets table rows
     *
     * @param  string $tableName
     * @param  array  $rows
     * @return $this
     */
    public function setTableRows($tableName, array $rows)
    {
        $this->tableRows[$tableName] = $rows;
        return $this;
    }


    public function generate()
    {
        foreach ($this->tableRows as $tableName => $rows) {
            $rowKey = 0;
            $table = $this->rm->getTable($tableName);
            foreach ($rows as $key => $row) {
                // if row is a string, than try to map it
                if (is_string($row)) {
                    $mapField = $this->getTableMapField($tableName);
                    $key = $row;
                    $row = array($mapField => $row);
                }
                else if (!is_string($key)) {
                    $key = null;
                }
                $this->saveRecord($table, $row, $key);
                $rowKey++;
            }
        }
    }


    /**
     * Saves record
     *
     * @param  Table  $table
     * @param  array  $row
     * @param  string $key
     * @return string
     */
    private function saveRecord(Table $table, array $row, $key)
    {
        $tableName = $table->getTableName();
        if (!isset($this->recordAliasIdMap[$tableName])) {
            $this->recordAliasIdMap[$tableName] = array();
        }
        // keep foreign key relations in array to process after record has been saved
        $owningRelations = array();
        foreach ($row as $relationName => $value) {
            if ($table->hasRelation($relationName)) {
                $relation = $table->getRelation($relationName);
                if ($relation->isReferencedSide($relationName)) {
                    $row = $this->saveRecordOnReferencedRelation($row, $relation, $value);
                }
                else {
                    $owningRelations[$relationName] = array(
                        'related' => $value,
                        'relation' => $relation
                    );
                }
                unset($row[$relationName]);
            }
        }

        $row = $this->saveRequiredRelations($table, $row);

        // save record
        $row = $this->fieldValueGenerator->getRandomRecordData($table->getFields(), $row);
        $record = $this->rm->getRecord($tableName, $row);
        $this->rm->save($record);
        $this->rm->commit();

        // keep record identifier in the record map
        $id = $record->getIdentifierAsString();
        if ($key === null) {
            $this->recordAliasIdMap[$tableName]["__autoindexed__" . $id] = $id;
        }
        else {
            $this->recordAliasIdMap[$tableName][$key] = $id;
        }

        // save owning relations
        foreach ($owningRelations as $relationData) {
            /** @var $relation Relation */
            $relation = $relationData['relation'];
            $relatedRows = $relationData['related'];
            $this->saveRecordsOnOwningRelation($relation, $relatedRows, $id);
        }

        return $id;
    }


    /**
     * Saves records related for referenced relation
     *
     * @param Relation      $relation
     * @param array|string  $relatedRows
     * @param string        $id
     */
    private function saveRecordsOnOwningRelation(Relation $relation, $relatedRows, $id)
    {
        $owningTable = $relation->getOwningTable();
        $owningField = $relation->getOwningField();
        if ($relation->isOneToMany()) {
            foreach ($relatedRows as $relatedKey => $relatedRow) {
                $this->saveRecordOnOwningRelation($relatedRow, $owningTable, $owningField, $id, $relatedKey);
            }
        }
        else {
            $this->saveRecordOnOwningRelation($relatedRows, $owningTable, $owningField, $id);
        }
    }


    /**
     * Saves related record for referenced relation
     *
     * @param array|string  $relatedRow
     * @param string        $refTableName
     * @param string        $refField
     * @param string        $id
     * @param string        $relatedKey
     */
    private function saveRecordOnOwningRelation($relatedRow, $refTableName, $refField, $id, $relatedKey = null)
    {
        if (is_string($relatedRow)) {
            $mapField = $this->getTableMapField($refTableName);
            $relatedKey = $relatedRow;
            $relatedRow = array($mapField => $relatedRow);
        }
        $relatedRow[$refField] = $id;
        if (!$relatedKey || !$this->isInRecordMap($refTableName, $relatedKey)) {
            $this->saveRelatedRecord($refTableName, $relatedKey, $relatedRow);
        }
    }


    /**
     * Saves record on owning relation
     *
     * @param  array                    $row
     * @param  \Dive\Relation\Relation  $relation
     * @param  string                   $value
     * @return array
     */
    private function saveRecordOnReferencedRelation(array $row, Relation $relation, $value)
    {
        $refTable = $relation->getReferencedTable();
        $relatedId = $this->getRecordIdFromMap($refTable, $value);
        if ($relatedId === false) {
            $relatedId = $this->saveRelatedRecord($refTable, $value);
        }
        $row[$relation->getOwningField()] = $relatedId;
        return $row;
    }


    /**
     * TODO explain method
     *
     * @param  string $tableName
     * @param  string $key
     * @param  array  $additionalData
     * @throws RecordGeneratorException
     * @return string
     */
    private function saveRelatedRecord($tableName, $key, array $additionalData = array())
    {
        if ($key && isset($this->tableRows[$tableName])) {
            if (isset($this->tableRows[$tableName][$key])) {
                $row = $this->tableRows[$tableName][$key];
            }
            else if (in_array($key, $this->tableRows[$tableName])) {
                $mapField = $this->getTableMapField($tableName);
                $row = array($mapField => $key);
            }
            else {
                throw new RecordGeneratorException(
                    "Related row '$key' could not be found for table '$tableName'!"
                );
            }
            if (!empty($additionalData)) {
                $row = array_merge($row, $additionalData);
            }
        }
        else {
            $row = $additionalData;
        }
        // TODO: glady - change 1
//        if (empty($row)) {
//            throw new RecordGeneratorException("Empty row for table '$tableName'!");
//        }
        $table = $this->rm->getTable($tableName);
        return $this->saveRecord($table, $row, $key);
    }


    /**
     * removes records by their record alias id map
     * @link https://github.com/sigma-z/Dive/issues/2
     */
    public function removeGeneratedRecords()
    {
        foreach ($this->recordAliasIdMap as $tableName => $recordKeys) {
            $table = $this->rm->getTable($tableName);
            foreach ($recordKeys as $id) {
                $record = $table->findByPk($id);
                $this->rm->delete($record);
            }
        }
        $this->rm->commit();

        $this->clear();
    }


    /**
     * @param  string $table
     * @param  string $alias
     * @return bool
     */
    public function isInRecordMap($table, $alias)
    {
        return isset($this->recordAliasIdMap[$table][$alias]);
    }


    /**
     * @param  string $tableName
     * @param  string $alias
     * @return bool|string
     */
    public function getRecordIdFromMap($tableName, $alias)
    {
        if ($this->isInRecordMap($tableName, $alias)) {
            return $this->recordAliasIdMap[$tableName][$alias];
        }
        return false;
    }


    /**
     * @param  string $tableName
     * @return string
     * @throws RecordGeneratorException
     */
    private function getTableMapField($tableName)
    {
        if (isset($this->tableMapFields[$tableName])) {
            return $this->tableMapFields[$tableName];
        }
        throw new RecordGeneratorException("No map field defined for single value mapping on table '$tableName'!");
    }


    /**
     * @param Table $table
     * @param array $row
     * @return array
     */
    private function saveRequiredRelations(Table $table, array $row)
    {
        foreach ($table->getRelations() as $relationName => $relation) {
            $owning = $relation->getOwningField();
            if (!$relation->isReferencedSide($relationName) || isset($row[$relationName]) || isset($row[$owning])) {
                continue;
            }
            if ($table->isFieldRequired($owning)) {
                $row[$owning] = $this->saveRelatedRecord($relation->getReferencedTable(), null, array());
            }
        }
        return $row;
    }

}
