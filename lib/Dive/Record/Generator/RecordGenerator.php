<?php
 /*
 * This file is part of the Dive ORM framework.
 * (c) Steffen Zeidler <sigma_z@sigma-scripts.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
/**
 * @author  Steffen Zeidler <sigma_z@sigma-scripts.de>
 * @created 12.04.13
 */

namespace Dive\Record\Generator;

use Dive\RecordManager;
use Dive\Relation\Relation;
use Dive\Table;
use Dive\Util\FieldValuesGenerator;

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
     */
    private $tableRows = array();
    /**
     * @var string[]
     * keys: keys provided by table rows
     * values: record identifier as string
     */
    private $recordMap = array();
    /**
     * @var array
     * keys: table name
     * values: map field name
     */
    private $tableMapFields = array();


    /**
     * @param RecordManager                 $rm
     * @param FieldValuesGenerator    $fieldValuesGenerator
     */
    public function __construct(RecordManager $rm, FieldValuesGenerator $fieldValuesGenerator)
    {
        $this->rm = $rm;
        $this->fieldValueGenerator = $fieldValuesGenerator;
    }


    /**
     * Clears data of tableRows and recordMap
     */
    public function clear()
    {
        $this->tableRows = array();
        $this->recordMap = array();
    }


    /**
     * Sets table data
     *
     * @param string $tableName
     * @param array  $rows
     * @param string $mapField
     */
    public function setTableRows($tableName, array $rows, $mapField = null)
    {
        $this->tableRows[$tableName] = $rows;
        if ($mapField) {
            $this->tableMapFields[$tableName] = $mapField;
        }
    }


    public function generate()
    {
        foreach ($this->tableRows as $tableName => $rows) {
            $rowKey = 0;
            foreach ($rows as $key => $row) {
                // if row is a string, than try to map it
                if (is_string($row)) {
                    $mapField = $this->getTableMapField($tableName);
                    $key = $row;
                    $row = array($mapField => $row);
                }
                else if ($rowKey === $key) {
                    $key = null;
                }
                $table = $this->rm->getTable($tableName);
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
        // keep foreign key relations in array to process after record has been saved
        $referencedRelations = array();
        foreach ($row as $relationName => $value) {
            if ($table->hasRelation($relationName)) {
                $relation = $table->getRelation($relationName);
                if ($relation->isOwningSide($relationName)) {
                    $row = $this->saveRecordOnOwningRelation($row, $relation, $value);
                }
                else {
                    $referencedRelations[$relationName] = array(
                        'related' => $value,
                        'relation' => $relation
                    );
                }
                unset($row[$relationName]);
            }
        }

        $row = $this->fieldValueGenerator->getRandomRecordData($table->getFields(), $row);
        $record = $this->rm->getRecord($table, $row);
        $record->save();

        $id = $record->getIdentifierAsString();
        if ($key) {
            $this->recordMap[$table->getTableName()][$key] = $id;
        }

        foreach ($referencedRelations as $relationData) {
            /** @var $relation Relation */
            $relation = $relationData['relation'];
            $relatedRows = $relationData['related'];
            $this->saveRecordsOnReferencedRelation($relation, $relatedRows, $id);
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
    private function saveRecordsOnReferencedRelation(Relation $relation, $relatedRows, $id)
    {
        $ownerTable = $relation->getOwnerTable();
        $ownerField = $relation->getOwnerField();
        if ($relation->isOneToMany()) {
            foreach ($relatedRows as $relatedKey => $relatedRow) {
                $this->saveRecordOnReferencedRelation($relatedRow, $ownerTable, $ownerField, $id, $relatedKey);
            }
        }
        else {
            $this->saveRecordOnReferencedRelation($relatedRows, $ownerTable, $ownerField, $id);
        }
    }


    /**
     * Saves related record for referenced relation
     *
     * @param array|string  $relatedRow
     * @param string        $refTable
     * @param string        $refField
     * @param string        $id
     * @param string        $relatedKey
     */
    private function saveRecordOnReferencedRelation($relatedRow, $refTable, $refField, $id, $relatedKey = null)
    {
        if (is_string($relatedRow)) {
            $mapField = $this->getTableMapField($refTable);
            $relatedKey = $relatedRow;
            $relatedRow = array($mapField => $relatedRow);
        }
        $relatedRow[$refField] = $id;
        if (!$relatedKey || !$this->isInRecordMap($refTable, $relatedKey)) {
            $this->saveRelatedRecord($refTable, $relatedKey, $relatedRow);
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
    private function saveRecordOnOwningRelation(array $row, Relation $relation, $value)
    {
        $refTable = $relation->getReferencedTable();
        $owningField = $relation->getOwnerField();
        $relatedId = $this->getRecordFromMap($refTable, $value);
        if ($relatedId === false) {
            $relatedId = $this->saveRelatedRecord($refTable, $value);
        }
        $row[$owningField] = $relatedId;
        return $row;
    }


    /**
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
        if (empty($row)) {
            throw new RecordGeneratorException("Empty row for table '$tableName'!");
        }
        $table = $this->rm->getTable($tableName);
        return $this->saveRecord($table, $row, $key);
    }


    private function isInRecordMap($table, $key)
    {
        return isset($this->recordMap[$table][$key]);
    }


    /**
     * @param  string $table
     * @param  string $key
     * @return bool|string
     */
    private function getRecordFromMap($table, $key)
    {
        if ($this->isInRecordMap($table, $key)) {
            return $this->recordMap[$table][$key];
        }
        return false;
    }


    private function getTableMapField($tableName)
    {
        if (isset($this->tableMapFields[$tableName])) {
            return $this->tableMapFields[$tableName];
        }
        throw new RecordGeneratorException("No map field defined for single value mapping on table '$tableName'!");
    }

}