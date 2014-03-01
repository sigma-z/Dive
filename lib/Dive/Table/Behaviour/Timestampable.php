<?php
 /*
 * This file is part of the Dive ORM framework.
 * (c) Steffen Zeidler <sigma_z@sigma-scripts.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dive\Table\Behaviour;

use Dive\Record;
use Dive\Record\RecordEvent;

/**
 * TODO unit test this class!!!
 * Class Timestampable
 *
 * @author  Steffen Zeidler <sigma_z@sigma-scripts.de>
 */
class Timestampable implements BehaviourInterface
{

    /** @var array */
    protected $tableEventFields = array();


    /**
     * @param string        $eventName
     * @param string        $tableName
     * @param array|string  $fields
     */
    public function setTableEventFields($eventName, $tableName, $fields)
    {
        $this->tableEventFields[$tableName][$eventName] = (array)$fields;
    }


    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return array(
            Record::EVENT_PRE_SAVE => 'onSave',
            Record::EVENT_PRE_UPDATE => 'onUpdate',
            Record::EVENT_PRE_INSERT => 'onInsert'
        );
    }


    /**
     * @param RecordEvent $event
     */
    public function onSave(RecordEvent $event)
    {
        $record = $event->getRecord();
        $fields = $this->getTableEventFields($record, Record::EVENT_PRE_SAVE);
        $this->setFieldTimestamps($record, $fields);
    }


    /**
     * @param RecordEvent $event
     */
    public function onInsert(RecordEvent $event)
    {
        $record = $event->getRecord();
        $fields = $this->getTableEventFields($record, Record::EVENT_PRE_INSERT);
        $this->setFieldTimestamps($record, $fields);
    }


    /**
     * @param RecordEvent $event
     */
    public function onUpdate(RecordEvent $event)
    {
        $record = $event->getRecord();
        $fields = $this->getTableEventFields($record, Record::EVENT_PRE_UPDATE);
        $this->setFieldTimestamps($record, $fields);
    }


    /**
     * @return string
     */
    public function getTimestamp()
    {
        $datetime = new \DateTime();
        return $datetime->format('Y-m-d H:i:s');
    }


    /**
     * @param  Record $record
     * @param  string $eventName
     * @return array
     */
    private function getTableEventFields(Record $record, $eventName)
    {
        $tableName = $record->getTable()->getTableName();
        if (!empty($this->tableEventFields[$tableName][$eventName])) {
            return $this->tableEventFields[$tableName][$eventName];
        }
        return array();
    }


    /**
     * @param Record $record
     * @param array  $fields
     */
    private function setFieldTimestamps(Record $record, array $fields)
    {
        $timestamp = $this->getTimestamp();
        foreach ($fields as $field) {
            $record->set($field, $timestamp);
        }
    }
}
