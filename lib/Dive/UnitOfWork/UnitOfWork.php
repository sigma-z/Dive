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
use Dive\RecordManager;
use Dive\Table;
use Dive\Exception as DiveException;


class UnitOfWork
{

    /**
     * @var RecordManager
     */
    private $rm = null;


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
        $identifierFields = $table->getIdentifierAsArray();
        $identifier = array();
        foreach ($identifierFields as $fieldName) {
            if (!array_key_exists($fieldName, $data)) {
                var_dump($data);
                throw new \UnexpectedValueException("Identifier field '$fieldName'' is not set!");
            }
            $identifier[] = $data[$fieldName];
        }
        $id = implode('-', $identifier);

        // TODO implement repository handling!!
//        if ($table->isInRepository($id)) {
//            $record = $table->getFromRepository($id);
//        }
//        else {
            $record = $table->createRecord($data, $exists);
//        }
        return $record;
    }


    /**
     * TODO implement save
     */
    public function saveGraph(Record $record, ChangeSet $changeSet)
    {
        $changeSet->calculateSave($record);
        $this->executeChangeSet($changeSet);
    }


    /**
     * TODO implement delete
     */
    public function deleteGraph(Record $record, ChangeSet $changeSet)
    {
        $changeSet->calculateDelete($record);
        $this->executeChangeSet($changeSet);
    }


    private function executeChangeSet(ChangeSet $changeSet)
    {
        $conn = $this->rm->getConnection();
        try {
            $conn->beginTransaction();
            foreach ($changeSet->getScheduledForInsert() as $recordInsert) {
                $this->doInsert($recordInsert);
            }
            foreach ($changeSet->getScheduledForUpdate() as $recordUpdate) {
                $this->doUpdate($recordUpdate);
            }
            foreach ($changeSet->getScheduledForDelete() as $recordDelete) {
                $this->doDelete($recordDelete);
            }
            $conn->commit();
        }
        catch (DiveException $e) {
            $conn->rollBack();
            throw $e;
        }
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
            $id = $conn->getLastInsertId();
            $record->assignIdentifier($id);
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