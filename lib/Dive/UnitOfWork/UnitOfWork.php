<?php
/*
 * This file is part of the Dive ORM framework.
 * (c) Steffen Zeidler <sigma_z@sigma-scripts.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dive\UnitOfWork;

use Dive\Record;
use Dive\RecordManager;
use Dive\Table;

/**
 * @author Steffen Zeidler <sigma_z@sigma-scripts.de>
 * Date: 06.02.13
 */
class UnitOfWork
{

    const SAVE = 'save';
    const DELETE = 'delete';

    /** @var RecordManager */
    private $rm = null;

    /** @var array */
    private $scheduledForCommit = array();

    /** @var \Dive\Record[] */
    private $recordIdentityMap = array();


    public function __construct(RecordManager $rm)
    {
        $this->rm = $rm;
    }


    /**
     * Gets record (retrieved from repository if exists, or create new record!)
     *
     * @param  Table $table
     * @param  array $data
     * @param  bool  $exists
     * @throws \UnexpectedValueException
     * @return Record
     */
    public function getRecord(Table $table, array $data, $exists = false)
    {
        $id = $this->getIdentifierFromData($table, $data);
        if ($id !== false && $table->isInRepository($id)) {
            $record = $table->getFromRepository($id);
        }
        else {
            $record = $table->createRecord($data, $exists);
        }
        return $record;
    }


    /**
     * Gets identifier as string, but returns false, if identifier could not be determined
     *
     * @param  Table $table
     * @param  array $data
     * @return bool|string
     */
    private function getIdentifierFromData(Table $table, array $data)
    {
        $identifierFields = $table->getIdentifierAsArray();
        $identifier = array();
        foreach ($identifierFields as $fieldName) {
            if (!isset($data[$fieldName])) {
                return false;
            }
            $identifier[] = $data[$fieldName];
        }
        return implode(Record::COMPOSITE_ID_SEPARATOR, $identifier);
    }


    /**
     * @param Record $record
     */
    public function scheduleSave(Record $record)
    {
        $operation = self::SAVE;
        $this->scheduleRecordForCommit($record, $operation);
        $this->handleConstraints($record, $operation);
    }


    /**
     * @param Record $record
     */
    public function scheduleDelete(Record $record)
    {
        $operation = self::DELETE;
        $this->scheduleRecordForCommit($record, $operation);
        $this->handleConstraints($record, $operation);
    }


    private function scheduleRecordForCommit(Record $record, $operation)
    {
        $oid = $record->getOid();
        if (isset($this->scheduledForCommit[$oid])) {
            if ($this->scheduledForCommit[$oid] == $operation) {
                return;
            }
            else {
                throw new UnitOfWorkException(
                    "Scheduling record for " . strtoupper($operation) . " failed!\n"
                    . "Reason: Record already has been scheduled for " . strtoupper($this->scheduledForCommit[$oid])
                );
            }
        }

        $this->scheduledForCommit[$oid] = $operation;
        $this->recordIdentityMap[$oid] = $record;
    }


    private function handleConstraints(Record $record, $operation)
    {
        if (!$record->exists()) {
            return;
        }

//        $owningRelations = $record->getTable()->getOwningRelations();
//        foreach ($owningRelations as $relation) {
//            if ($operation == self::SAVE && $record->isFieldModified($relation->getReferencedField())) {
//
//            }
//
//            $owningTable = $this->rm->getTable($relation->getOwningTable());
//            $query = $owningTable->createQuery()
//                ->where($relation->getOwningField() . ' = ?', $record->getIdentifierAsString());
//            $records = $query->execute();
//            foreach ($records as $record) {
//                echo $operation == self::DELETE ? $relation->getOnDelete() : $relation->getOnUpdate() . "\n";
//            }
//        }
    }


    public function commitChanges()
    {
        foreach ($this->recordIdentityMap as $oid => $record) {
            $isSave = $this->scheduledForCommit[$oid] === self::SAVE;
            $recordExists = $record->exists();
            if ($isSave) {
                if ($recordExists) {
                    $this->doUpdate($record);
                }
                else {
                    $this->doInsert($record);
                }
            }
            else if ($recordExists) {
                $this->doDelete($record);
            }
        }

        $this->resetScheduled();
    }


    public function resetScheduled()
    {
        $this->scheduledForCommit = array();
        $this->recordIdentityMap = array();
    }


    private function doInsert(Record $record)
    {
        $table = $record->getTable();
        $pkFields = array();
        $data = array();
        foreach ($table->getFields() as $fieldName => $fieldDef) {
            if (isset($fieldDef['primary']) && $fieldDef['primary'] === true) {
                $pkFields[] = $fieldName;
            }
            $data[$fieldName] = $record->get($fieldName);
        }
        $conn = $table->getConnection();
        $conn->insert($table, $data);

        // only one primary key field
        if (!isset($pkFields[1])) {
            $id = $conn->getLastInsertId($record->getTable()->getTableName());
            $record->assignIdentifier($id);
            $table->refreshRecordIdentityInRepository($record);
        }
    }


    private function doDelete(Record $record)
    {
        $table = $record->getTable();
        $identifier = array();
        foreach ($table->getFields() as $fieldName => $fieldDef) {
            if (isset($fieldDef['primary']) && $fieldDef['primary'] === true) {
                $identifier[$fieldName] = $record->get($fieldName);
            }
        }
        $conn = $table->getConnection();
        $conn->delete($table, $identifier);
    }


    private function doUpdate(Record $record)
    {
        $table = $record->getTable();
        $identifier = array();
        $modifiedFields = array();
        foreach ($table->getFields() as $fieldName => $fieldDef) {
            if (isset($fieldDef['primary']) && $fieldDef['primary'] === true) {
                $identifier[$fieldName] = $record->get($fieldName);
            }
            if ($record->isFieldModified($fieldName)) {
                $modifiedFields[$fieldName] = $record->get($fieldName);
            }
        }

        $conn = $table->getConnection();
        $conn->update($table, $modifiedFields, $identifier);
    }

}
