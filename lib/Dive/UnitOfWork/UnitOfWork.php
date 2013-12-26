<?php
/*
 * This file is part of the Dive ORM framework.
 * (c) Steffen Zeidler <sigma_z@sigma-scripts.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dive\UnitOfWork;

use Dive\Exception;
use Dive\Platform\PlatformInterface;
use Dive\Record;
use Dive\RecordManager;
use Dive\Relation\Relation;
use Dive\Table;

/**
 * @author Steffen Zeidler <sigma_z@sigma-scripts.de>
 * Date: 06.02.13
 */
class UnitOfWork
{

//    const EVENT_PRE_SCHEDULE_DELETE = 'Dive.UnitOfWork.preScheduleDelete';
//    const EVENT_POST_SCHEDULE_DELETE = 'Dive.UnitOfWork.postScheduleDelete';
//    const EVENT_PRE_SCHEDULE_SAVE   = 'Dive.UnitOfWork.preScheduleSave';
//    const EVENT_POST_SCHEDULE_SAVE  = 'Dive.UnitOfWork.postScheduleSave';

    const OPERATION_SAVE = 'save';
    const OPERATION_DELETE = 'delete';

    /** @var RecordManager */
    private $rm = null;

    /** @var array */
    private $scheduledForCommit = array();

    /** @var Record[] */
    private $recordIdentityMap = array();


    /**
     * @param RecordManager $rm
     */
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
        $identifierFields = $table->getIdentifierFields();
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
        $operation = self::OPERATION_SAVE;
        $this->throwAlreadyScheduledAsDeleteException($record, $operation);

        if (!$record->exists() || $record->isModified()) {
            $this->scheduleRecordForCommit($record, $operation);
        }
        $this->handleUpdateConstraints($record);
    }


    /**
     * @param Record $record
     */
    public function scheduleDelete(Record $record)
    {
        if (!$record->exists()) {
            return;
        }

        $operation = self::OPERATION_DELETE;
        $this->handleDeleteConstraints($record);
        $this->scheduleRecordForCommit($record, $operation);
    }


    /**
     * @param  Record $record
     * @param  string $operation
     * @throws UnitOfWorkException
     */
    private function scheduleRecordForCommit(Record $record, $operation)
    {
        $this->throwAlreadyScheduledAsDeleteException($record, $operation);

        if ($this->isRecordScheduledForCommit($record, $operation)) {
            return;
        }

        $oid = $record->getOid();
        $this->scheduledForCommit[$oid] = $operation;
        $this->recordIdentityMap[$oid] = $record;
    }


    /**
     * @param  Record $record
     * @param  string $operation
     * @throws UnitOfWorkException
     */
    private function throwAlreadyScheduledAsDeleteException(Record $record, $operation)
    {
        if ($operation == self::OPERATION_SAVE && $this->isRecordScheduledForDelete($record)) {
            throw new UnitOfWorkException(
                "Scheduling record for " . strtoupper($operation) . " failed!\n"
                . "Reason: Record already has been scheduled for " . strtoupper(self::OPERATION_DELETE)
            );
        }
    }


    /**
     * @param  Record      $record
     * @param  string|null $operation name of specific operation or NULL for any operation
     * @return bool
     */
    public function isRecordScheduledForCommit(Record $record, $operation = null)
    {
        $oid = $record->getOid();
        if (!isset($this->scheduledForCommit[$oid])) {
            return false;
        }
        return $operation === null || $this->scheduledForCommit[$oid] == $operation;
    }


    /**
     * @param  Record $record
     * @return bool
     */
    private function isRecordScheduledForDelete(Record $record)
    {
        return $this->isRecordScheduledForCommit($record, self::OPERATION_DELETE);
    }


    /**
     * @param  Record $record
     * @return bool
     */
    private function isRecordScheduledForSave(Record $record)
    {
        return $this->isRecordScheduledForCommit($record, self::OPERATION_SAVE);
    }


    /**
     * @param Record $record
     */
    private function handleUpdateConstraints(Record $record)
    {
        if (!$record->exists()) {
            return;
        }

        $owningRelations = $record->getTable()->getOwningRelations();
        foreach ($owningRelations as $relationName => $owningRelation) {
            $owningRecords = $owningRelation->getOriginalReferenceFor($record, $relationName);
            foreach ($owningRecords as $owningRecord) {
                $this->applyUpdateConstraint($owningRelation, $owningRecord, $record);
            }
        }
    }


    /**
     * @param Relation $owningRelation
     * @param Record   $owningRecord
     * @param Record   $referencedRecord
     * @throws UnitOfWorkException
     */
    private function applyUpdateConstraint(Relation $owningRelation, Record $owningRecord, Record $referencedRecord)
    {
        $refFieldName = $owningRelation->getReferencedField();
        if (!$referencedRecord->isFieldModified($refFieldName)) {
            return;
        }

        $owningFieldName = $owningRelation->getOwningField();
        if ($owningRecord->isFieldModified($owningFieldName) && !$this->isRecordScheduledForCommit($owningRecord)) {
            $this->scheduleSave($owningRecord);
            return;
        }

        $constraintName = $owningRelation->getOnUpdate();
        switch ($constraintName) {
            case PlatformInterface::CASCADE:
                $value = $referencedRecord->get($refFieldName);
                $owningRecord->set($owningFieldName, $value);
                if (!$this->isRecordScheduledForSave($owningRecord)) {
                    $this->scheduleSave($owningRecord);
                }
                break;

            case PlatformInterface::SET_NULL:
                $owningRecord->set($owningFieldName, null);
                if (!$this->isRecordScheduledForSave($owningRecord)) {
                    $this->scheduleSave($owningRecord);
                }
                break;

            case PlatformInterface::RESTRICT:
            case PlatformInterface::NO_ACTION:
                // TODO exception not specific enough?
                throw new UnitOfWorkException("Update record is restricted by onUpdate!");
        }
    }


    /**
     * @param Record $record
     * @throws Exception
     */
    private function handleDeleteConstraints(Record $record)
    {
        if (!$record->exists()) {
            return;
        }

        $owningRelations = $record->getTable()->getOwningRelations();
        foreach ($owningRelations as $relationName => $owningRelation) {
            $owningRecords = $owningRelation->getOriginalReferenceFor($record, $relationName);
            foreach ($owningRecords as $owningRecord) {
                $this->applyDeleteConstraint($owningRelation, $owningRecord);
            }
        }
    }


    /**
     * @param  Relation $owningRelation
     * @param  Record   $owningRecord
     * @throws UnitOfWorkException
     */
    private function applyDeleteConstraint(Relation $owningRelation, Record $owningRecord)
    {
        $owningFieldName = $owningRelation->getOwningField();
        if ($owningRecord->isFieldModified($owningFieldName)) {
            return;
        }

        $constraintName = $owningRelation->getOnDelete();
        switch ($constraintName) {
            case PlatformInterface::CASCADE:
                $this->scheduleDelete($owningRecord);
                break;

            case PlatformInterface::SET_NULL:
                if (!$this->isRecordScheduledForDelete($owningRecord)) {
                    $owningRecord->set($owningFieldName, null);
                    $this->scheduleSave($owningRecord);
                }
                break;

            case PlatformInterface::RESTRICT:
            case PlatformInterface::NO_ACTION:
                if (!$this->isRecordScheduledForDelete($owningRecord)) {
                    // TODO exception not specific enough?
                    throw new UnitOfWorkException("Delete record is restricted by onDelete!");
                }
                break;
        }
    }


    public function commitChanges()
    {
        foreach ($this->recordIdentityMap as $record) {
            $recordExists = $record->exists();
            if ($this->isRecordScheduledForSave($record)) {
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


    /**
     * @param Record $record
     */
    private function doInsert(Record $record)
    {
        $identifier = $record->getIdentifierFieldIndexed();
        $table = $record->getTable();
        $data = array();
        foreach ($table->getFields() as $fieldName => $fieldDef) {
            $data[$fieldName] = $record->get($fieldName);
        }
        $conn = $table->getConnection();
        $conn->insert($table, $data);

        if (!$table->hasCompositePrimaryKey()) {
            $id = $conn->getLastInsertId($table->getTableName());
            $identifierFields = $table->getIdentifierFields();
            $identifier = array($identifierFields[0] => $id);
        }

        // assign record identifier
        $record->assignIdentifier($identifier);
    }


    /**
     * @param Record $record
     */
    private function doDelete(Record $record)
    {
        $table = $record->getTable();
        $identifier = $record->getIdentifierFieldIndexed();
        $conn = $table->getConnection();
        $conn->delete($table, $identifier);

        $table->getRepository()->remove($record);
        $relations = $table->getRelations();
        foreach ($relations as $relationName => $relation) {
            $relation->clearReferenceFor($record, $relationName);
        }
    }


    /**
     * TODO think about supporting updates on identifiers
     * @param Record $record
     */
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
