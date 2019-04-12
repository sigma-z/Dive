<?php
/*
 * This file is part of the Dive ORM framework.
 * (c) Steffen Zeidler <sigma_z@sigma-scripts.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dive\UnitOfWork;

use Dive\Collection\RecordCollection;
use Dive\Exception;
use Dive\Platform\PlatformInterface;
use Dive\Record;
use Dive\RecordManager;
use Dive\Relation\Relation;
use Dive\Table;
use Dive\Validation\RecordInvalidException;

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
    private $recordManager;

    /**
     * @var string[]
     * keys: record oid's
     */
    private $scheduledForCommit = [];

    /**
     * @var Record[]
     * keys: record oid's
     */
    private $restrictNotDeletedOnCommit = [];

    /**
     * @var Record[]
     * keys: record oid's
     */
    private $recordIdentityMap = [];

    /**
     * @var array
     */
    private $visitedSaveRecords = [];


    /**
     * @param RecordManager $rm
     */
    public function __construct(RecordManager $rm)
    {
        $this->recordManager = $rm;
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
    public function getOrCreateRecord(Table $table, array $data, $exists = false)
    {
        $id = $table->getIdentifierAsString($data);
        if ($id !== false && $table->isInRepository($id)) {
            $record = $table->getFromRepository($id);
        }
        else {
            $record = $table->createRecord($data, $exists);
        }
        return $record;
    }


    /**
     * @param Record $record
     * @param bool   $resetVisited
     */
    public function scheduleSave(Record $record, $resetVisited = false)
    {
        if ($record->getTable()->isView()) {
            return;
        }

        $oid = $record->getOid();

        if ($resetVisited) {
            $this->visitedSaveRecords = [];
        }
        else if (in_array($oid, $this->visitedSaveRecords, true)) {
            return;
        }
        $this->visitedSaveRecords[] = $oid;

        $operation = self::OPERATION_SAVE;
        $this->throwAlreadyScheduledAsDeleteException($record, $operation);
        $this->handleReferencedInserts($record);

        if (!$record->exists() || $record->isModified()) {
            $this->scheduleRecordForCommit($record, $operation);
        }

        $this->handleOwningInserts($record);
        $this->handleUpdateConstraints($record);
    }


    /**
     * @param Record $record
     */
    public function scheduleDelete(Record $record)
    {
        if ($record->getTable()->isView()) {
            return;
        }

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
        if ($operation === self::OPERATION_SAVE && $this->isRecordScheduledForDelete($record)) {
            throw new UnitOfWorkException(
                'Scheduling record for ' . strtoupper($operation) . " failed!\n"
                . 'Reason: Record already has been scheduled for ' . strtoupper(self::OPERATION_DELETE)
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
        return $operation === null || $this->scheduledForCommit[$oid] === $operation;
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
            $owningTableName = $owningRelation->getOwningTable();
            if (!$this->recordManager->getTable($owningTableName)->isView()) {
                $owningRecords = $owningRelation->getOriginalReferenceFor($record, $relationName);
                foreach ($owningRecords as $owningRecord) {
                    $this->applyUpdateConstraint($owningRelation, $owningRecord, $record);
                }
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
                throw new UnitOfWorkException('Update record is restricted by onUpdate!');
        }
    }


    /**
     * @param Record $record
     */
    private function handleReferencedInserts(Record $record)
    {
        $table = $record->getTable();
        if (!$table->hasAutoIncrementTrigger()) {
            return;
        }

        $relations = $table->getReferencedRelationsIndexedByOwningField();
        foreach ($relations as $relation) {
            $relationName = $relation->getReferencedAlias();
            if ($relation->hasReferenceLoadedFor($record, $relationName)) {
                $referencedRecord = $relation->getReferenceFor($record, $relationName);
                if (!$referencedRecord->exists() || $this->hasRecordIdentifierChanged($record)) {
                    $this->scheduleSave($referencedRecord);
                }
            }
        }
    }


    /**
     * @param Record $record
     * @return bool
     */
    private function hasRecordIdentifierChanged(Record $record)
    {
        $identifierFields = $record->getTable()->getIdentifierFields();
        $modifiedFields = $record->getModifiedFields();
        return (bool)array_intersect($identifierFields, array_keys($modifiedFields));
    }


    /**
     * @param Record $record
     */
    private function handleOwningInserts(Record $record)
    {
        $owningRelations = $record->getTable()->getOwningRelations();
        foreach ($owningRelations as $relationName => $relation) {
            if ($relation->hasReferenceLoadedFor($record, $relationName)) {
                $related = $relation->getReferenceFor($record, $relationName);
                if ($related instanceof RecordCollection) {
                    foreach ($related as $relatedRecord) {
                        $this->scheduleSave($relatedRecord);
                    }
                }
                else if ($related instanceof Record) {
                    $this->scheduleSave($related);
                }
            }
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
            $owningTableName = $owningRelation->getOwningTable();
            if (!$this->recordManager->getTable($owningTableName)->isView()) {
                $owningRecords = $owningRelation->getOriginalReferenceFor($record, $relationName);
                foreach ($owningRecords as $owningRecord) {
                    $this->applyDeleteConstraint($owningRelation, $owningRecord);
                }
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
                // if not deleted yet, it has to be deleted before commit for comply with the constraint
                if (!$this->isRecordScheduledForDelete($owningRecord)) {
                    $this->markRecordForRestrictOnCommitWhenNotDeleted($owningRecord);
                }
            break;
        }
    }


    public function commitChanges()
    {
        $this->checkRestrictOnCommit();
        $this->validateScheduledSaveRecords();

        $conn = $this->recordManager->getConnection();
        $conn->beginTransaction();

        try {
            $this->processChanges();
            $conn->commit();
        }
        catch (\PDOException $e) {
            $conn->rollBack();
            throw $e;
        }

        $this->resetScheduled();
    }


    private function processChanges()
    {
        foreach ($this->recordIdentityMap as $oid => $record) {
            $recordExists = $record->exists();
            if ($this->isRecordScheduledForSave($record)) {
                $record->preSave();
                if ($recordExists) {
                    $this->doUpdate($record);
                }
                else {
                    $this->doInsert($record);
                }
                $record->postSave();
            }
            else {
                if ($recordExists) {
                    $this->doDelete($record);
                }
                else {
                    // remove record from schedule, so record references do not have to be updated
                    unset($this->recordIdentityMap[$oid]);
                    unset($this->scheduledForCommit[$oid]);
                }
            }
        }
    }


    public function resetScheduled()
    {
        $this->scheduledForCommit = [];
        $this->recordIdentityMap = [];
        $this->restrictNotDeletedOnCommit = [];
    }


    /**
     * @param Record $record
     */
    private function doInsert(Record $record)
    {
        $this->invokeRecordEvent(Record::EVENT_PRE_INSERT, $record);
        $this->invokeRecordEvent(Record::EVENT_PRE_SAVE, $record);

        $table = $record->getTable();
        $data = array();
        foreach ($table->getFields() as $fieldName => $fieldDef) {
            $data[$fieldName] = $record->get($fieldName);
        }
        $conn = $table->getConnection();
        $conn->insert($table, $data);
        $oldIdentifier = $record->getInternalId();

        $identifier = $this->getIdentifier($record);
        if (!$table->hasCompositePrimaryKey()) {
            $this->setForeignKeyFieldOfRelatedRecords($record, $identifier);
        }

        // assign record identifier
        $record->assignIdentifier($identifier, $oldIdentifier);

        $this->invokeRecordEvent(Record::EVENT_POST_SAVE, $record);
        $this->invokeRecordEvent(Record::EVENT_POST_INSERT, $record);
    }


    /**
     * @param Record $record
     *
     * @return null|string
     */
    private function getIdentifier(Record $record)
    {
        if($record->getTable()->hasAutoIncrementTrigger()){
            return $record->getTable()->getConnection()->getLastInsertId($record->getTable()->getTableName());
        }
        return $record->getIdentifier();
    }


    /**
     * @param Record $record
     */
    private function doDelete(Record $record)
    {
        $this->invokeRecordEvent(Record::EVENT_PRE_DELETE, $record);

        $table = $record->getTable();
        $identifier = $record->getIdentifierFieldIndexed();
        $conn = $table->getConnection();
        $conn->delete($table, $identifier);

        // remove record from it's references
        $this->removeRecordReferences($record);

        $this->invokeRecordEvent(Record::EVENT_POST_DELETE, $record);
    }


    /**
     * @param Record $record
     */
    private function removeRecordReferences(Record $record)
    {
        $table = $record->getTable();
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
        $this->invokeRecordEvent(Record::EVENT_PRE_UPDATE, $record);
        $this->invokeRecordEvent(Record::EVENT_PRE_SAVE, $record);

        $table = $record->getTable();
        $newIdentifierValues = [];
        $identifierValues = [];
        $modifiedFields = [];
        foreach ($table->getFields() as $fieldName => $fieldDef) {
            if (isset($fieldDef['primary']) && $fieldDef['primary'] === true) {
                $fieldValue = $record->get($fieldName);
                $identifierValues[] = $record->isFieldModified($fieldName)
                    ? $record->getModifiedFieldValue($fieldName)
                    : $fieldValue;
                $newIdentifierValues[] = $fieldValue;
            }
            if ($record->isFieldModified($fieldName)) {
                $modifiedFields[$fieldName] = $record->get($fieldName);
            }
        }

        $conn = $table->getConnection();
        $conn->update($table, $modifiedFields, $identifierValues);

        // assign record identifier
        if ($newIdentifierValues !== $identifierValues) {
            $record->assignIdentifier($newIdentifierValues, $identifierValues);
        }
        else {
            $record->clearModified();
        }

        $this->invokeRecordEvent(Record::EVENT_POST_SAVE, $record);
        $this->invokeRecordEvent(Record::EVENT_POST_UPDATE, $record);
    }


    /**
     * @param Record $record
     * @param string $id
     */
    private function setForeignKeyFieldOfRelatedRecords(Record $record, $id)
    {
        $table = $record->getTable();
        $owningRelations = $table->getOwningRelations();
        foreach ($owningRelations as $relationName => $relation) {
            $owningField = $relation->getOwningField();
            /** @var Record|Record[] $related */
            $related = $relation->getReferenceFor($record, $relationName);
            if ($related instanceof RecordCollection) {
                foreach ($related as $relatedRecord) {
                    $relatedRecord->set($owningField, $id);
                }
            }
            else if ($related instanceof Record) {
                $related->set($owningField, $id);
            }
        }
    }


    /**
     * @param string $eventName
     * @param Record $record
     */
    private function invokeRecordEvent($eventName, Record $record)
    {
        $length = 12; // length of Dive.Record.
        $hookMethod = substr($eventName, $length);
        if (method_exists($record, $hookMethod)) {
            $record->{$hookMethod}();
        }

        $eventDispatcher = $this->recordManager->getEventDispatcher();
        if ($eventDispatcher->hasListeners($eventName)) {
            $recordEvent = new Record\RecordEvent($record);
            $eventDispatcher->dispatch($eventName, $recordEvent);
        }
    }


    /**
     * @param Record $record
     */
    private function markRecordForRestrictOnCommitWhenNotDeleted(Record $record)
    {
        $oid = $record->getOid();
        // record will have to be deleted before record with restricted relation
        $this->recordIdentityMap[$oid] = $record;
        $this->restrictNotDeletedOnCommit[$oid] = $record;
    }


    /**
     * @throws UnitOfWorkException
     */
    private function checkRestrictOnCommit()
    {
        foreach ($this->restrictNotDeletedOnCommit as $restrictedRecord) {
            if (!$this->isRecordScheduledForDelete($restrictedRecord)) {
                // TODO exception not specific enough?
                throw new UnitOfWorkException('Delete record is restricted by onDelete!');
            }
        }
    }


    private function validateScheduledSaveRecords()
    {
        foreach ($this->recordIdentityMap as $record) {
            if ($this->isRecordScheduledForSave($record) && !$this->validateRecord($record)) {
                throw RecordInvalidException::createByRecord($record);
            }
        }
    }


    /**
     * @param Record $record
     * @return bool
     */
    private function validateRecord(Record $record)
    {
        $validator = $this->recordManager->getRecordValidationContainer();
        return $validator->validate($record);

    }

}
