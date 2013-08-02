<?php
/*
 * This file is part of the Dive ORM framework.
 * (c) Steffen Zeidler <sigma_z@sigma-scripts.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
/**
 * @author Steffen Zeidler <sigma_z@sigma-scripts.de>
 * Date: 06.02.13
 */

namespace Dive\UnitOfWork;

use Dive\Record;

class ChangeSet implements ChangeSetInterface
{

    const SAVE = 'save';
    const DELETE = 'delete';

    private $mode = null;
    /**
     * @var \Dive\Record[]
     */
    private $scheduledForInsert = array();
    /**
     * @var \Dive\Record[]
     */
    private $scheduledForUpdate = array();
    /**
     * @var \Dive\Record[]
     */
    private $scheduledForDelete = array();


    public function calculateSave(Record $record)
    {
        $this->mode = self::SAVE;
        $this->resetScheduled();
        $this->processRecord($record);
    }


    public function calculateDelete(Record $record)
    {
        $this->mode = self::DELETE;
        $this->resetScheduled();
        $this->processRecord($record);
    }


    public function isSave()
    {
        return $this->mode == self::SAVE;
    }


    public function isDelete()
    {
        return $this->mode == self::DELETE;
    }


    public function resetScheduled()
    {
        $this->scheduledForInsert = array();
        $this->scheduledForUpdate = array();
        $this->scheduledForDelete = array();
    }


    private function processRecord(Record $record, array &$visited = array())
    {
        $oid = $record->getOid();
        if (in_array($oid, $visited)) {
            return;
        }
        $visited[] = $oid;

        $relations = $record->getTable()->getRelations();
        foreach ($relations as $relationName => $relation) {
            if ($relation->isOwningSide($relationName) && $relation->hasReferenceFor($record, $relationName)) {
                $related = $record->get($relationName);
                $this->processRecord($related, $visited);
            }
        }

        $this->scheduleRecord($record);

        foreach ($relations as $relationName => $relation) {
            if (!$relation->isOwningSide($relationName) && $relation->hasReferenceFor($record, $relationName)) {
                $related = $record->get($relationName);
                if ($relation->isOneToMany()) {
                    foreach ($related as $relatedRecord) {
                        $this->processRecord($relatedRecord, $visited);
                    }
                }
                else {
                    $this->processRecord($related, $visited);
                }
            }
        }
    }


    private function scheduleRecord(Record $record)
    {
        $recordExists = $record->exists();
        if ($this->isSave()) {
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
