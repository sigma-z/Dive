<?php
/*
 * This file is part of the Dive ORM framework.
 * (c) Steffen Zeidler <sigma_z@sigma-scripts.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Dive\Table\Behavior;

use Dive\Record;
use Dive\Record\RecordPropertyEvent;
use Dive\Table;

/**
 * Class DelegateBehavior
 * @author  Steffen Zeidler <sigma_z@sigma-scripts.de>
 * @created 10.03.14
 */
class DelegateBehavior extends Behavior
{

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return array(
            Record::EVENT_NON_EXISTING_PROPERTY_GET => 'onGet',
            Record::EVENT_NON_EXISTING_PROPERTY_SET => 'onSet'
        );
    }


    public function onGet(RecordPropertyEvent $event)
    {
        $record = $event->getRecord();
        $table = $record->getTable();
        $relationName = $this->getTableConfigValue($table->getTableName(), 'delegateToRelation');
        $relation = $this->getOneToOneRelation($table, $relationName);
        if ($relation) {
            $delegateRecord = $record->get($relationName);
            if ($delegateRecord !== null) {
                try {
                    $value = $delegateRecord->get($event->getProperty());
                    $event->setValue($value);
                }
                catch (Table\TableException $e) {
                }
            }
            else {
                $event->setValue(false);
            }
            $event->stopPropagation();
        }
    }


    public function onSet(RecordPropertyEvent $event)
    {
        $record = $event->getRecord();
        $table = $record->getTable();
        $relationName = $this->getTableConfigValue($table->getTableName(), 'delegateToRelation');
        $relation = $this->getOneToOneRelation($table, $relationName);
        if ($relation) {
            $delegateRecord = $record->get($relationName);
            if ($delegateRecord === null) {
                $delegateTable = $relation->getJoinTable($table->getRecordManager(), $relationName);
                $delegateRecord = $delegateTable->createRecord();
            }
            try {
                $delegateRecord->set($event->getProperty(), $event->getValue());
                $record->set($relationName, $delegateRecord);
                $event->stopPropagation();
            }
            catch (Table\TableException $e) {
            }
        }
    }


    /**
     * @param  Table  $table
     * @param  string $relationName
     * @return bool|\Dive\Relation\Relation
     */
    private function getOneToOneRelation(Table $table, $relationName)
    {
        if ($relationName && $table->hasRelation($relationName)) {
            $relation = $table->getRelation($relationName);
            if ($relation->isOneToOne()) {
                return $relation;
            }
        }
        return false;
    }

}
