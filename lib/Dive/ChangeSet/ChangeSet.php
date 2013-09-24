<?php
/*
 * This file is part of the Dive ORM framework.
 * (c) Steffen Zeidler <sigma_z@sigma-scripts.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dive\ChangeSet;

use Dive\Collection\RecordCollection;
use Dive\Record;
use Dive\Platform\PlatformInterface;
use Dive\RecordManager;
use Dive\Relation\Relation;

/**
 * @author Steffen Zeidler <sigma_z@sigma-scripts.de>
 * Date: 06.02.13
 */
class ChangeSet implements ChangeSetInterface
{

    const SAVE = 'save';
    const DELETE = 'delete';

    /** @var \Dive\Record[] */
    private $scheduledForInsert = array();

    /** @var \Dive\Record[] */
    private $scheduledForUpdate = array();

    /** @var \Dive\Record[] */
    private $scheduledForDelete = array();

    /** @var string */
    private $constraintHandling = null;


    public function calculateSave(Record $record, $constraintHandling)
    {
        $this->resetScheduled();
        $this->constraintHandling = $constraintHandling;
        $this->processRecordSave($record);
    }


    public function calculateDelete(Record $record, $constraintHandling)
    {
        $this->resetScheduled();
        $this->constraintHandling = $constraintHandling;
        $this->processRecordDelete($record);
    }


    public function resetScheduled()
    {
        $this->scheduledForInsert = array();
        $this->scheduledForUpdate = array();
        $this->scheduledForDelete = array();
    }


    private function processRecordDelete(Record $record, array &$visited = array())
    {
        if (!$record->exists()) {
            return;
        }

        $oid = $record->getOid();
        if (in_array($oid, $visited)) {
            return;
        }
        $visited[] = $oid;

        if ($this->constraintHandling == RecordManager::CONSTRAINT_DIVE) {
            $relations = $record->getTable()->getRelations();
            // iterate owning side relations
            foreach ($relations as $relationName => $relation) {
                if ($relation->isReferencedSide($relationName)) {
                    continue;
                }

                $onDeleteConstraint = $relation->getOnDelete();
                switch ($onDeleteConstraint) {
                    case PlatformInterface::CASCADE:
                        $this->deleteCascade($record, $relation, $visited);
                        break;

                    case PlatformInterface::RESTRICT:
                    case PlatformInterface::NO_ACTION:
                        break;

                    case PlatformInterface::SET_NULL:
                        break;

                    case PlatformInterface::SET_DEFAULT:
                        break;
                }
            }
        }

        $this->scheduleRecord($record, self::DELETE);
    }


    private function deleteCascade(Record $record, Relation $relation, array &$visited)
    {
        if ($relation->isOneToMany()) {
            $related = $record->get($relation->getReferencedAlias());
            if ($related instanceof RecordCollection) {
                $recordIds = $related->getSnapshotIdentifiers();
                $repository = $related->getTable()->getRepository();
                foreach ($recordIds as $recordId) {
                    $relatedRecord = $repository->getByInternalId($recordId);
                    $this->processRecordDelete($relatedRecord, $visited);
                }
            }
        }
        else {
            $relatedTable = $record->getRecordManager()->getTable($relation->getOwningTable());
            //echo $relatedTable . "\n";
            $relatedRecord = $relatedTable->createQuery('a')
                ->where('a.' . $relation->getOwningField() . ' = ?', $record->getIdentifierAsString())
                ->fetchOneAsObject();
            //echo 'deleteCascade: ' . $relatedRecord->getTable()->getTableName() . "\n";
            if ($relatedRecord) {
                $this->processRecordDelete($relatedRecord, $visited);
            }
        }
    }


    /**
     * TODO what is with unloaded references?
     * TODO implement constraint handing
     *
     * @param Record $record
     * @param array  $visited
     */
    private function processRecordSave(Record $record, array &$visited = array())
    {
        $oid = $record->getOid();
        if (in_array($oid, $visited)) {
            return;
        }
        $visited[] = $oid;

        $relations = $record->getTable()->getRelations();
        // iterate owning side relations
        foreach ($relations as $relationName => $relation) {
            if ($relation->isReferencedSide($relationName) && $relation->hasReferenceFor($record, $relationName)) {
                $related = $record->get($relationName);
                $this->processRecordSave($related, $visited);
            }
        }

        $this->scheduleRecord($record, self::SAVE);

        // iterate referenced side relations
        foreach ($relations as $relationName => $relation) {
            if ($relation->isOwningSide($relationName) && $relation->hasReferenceFor($record, $relationName)) {
                $related = $record->get($relationName);
                if ($relation->isOneToMany()) {
                    foreach ($related as $relatedRecord) {
                        $this->processRecordSave($relatedRecord, $visited);
                    }
                }
                else {
                    $this->processRecordSave($related, $visited);
                }
            }
        }
    }


    private function scheduleRecord(Record $record, $operation)
    {
        $recordExists = $record->exists();
        if ($operation == self::SAVE) {
            if ($recordExists) {
                if ($record->isModified()) {
                    $this->scheduledForUpdate[] = $record;
                }
            }
            else {
                $this->scheduledForInsert[] = $record;
            }
        }
        else if ($recordExists) {
            $this->scheduledForDelete[] = $record;
        }
    }


    /**
     * @return \Dive\Record[]
     */
    public function getScheduledForInsert()
    {
        return $this->scheduledForInsert;
    }


    /**
     * @return \Dive\Record[]
     */
    public function getScheduledForUpdate()
    {
        return $this->scheduledForUpdate;
    }


    /**
     * @return \Dive\Record[]
     */
    public function getScheduledForDelete()
    {
        return $this->scheduledForDelete;
    }

}
