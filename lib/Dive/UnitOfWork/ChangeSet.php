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
        $this->processGraph($record);
    }


    public function calculateDelete(Record $record)
    {
        $this->mode = self::DELETE;
        $this->resetScheduled();
        $this->processGraph($record);
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


    private function processGraph(Record $record)
    {
        $recordExists = $record->exists();
        if ($recordExists && $this->isDelete()) {
            $this->scheduledForDelete[] = $record;
            return;
        }
        if ($recordExists) {
            if ($record->isModified()) {
                $this->scheduledForUpdate[] = $record;
            }
        }
        else {
            $this->scheduledForInsert[] = $record;
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
