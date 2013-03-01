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
        return false;
    }


    /**
     * TODO implement delete
     */
    public function deleteGraph(Record $record, ChangeSet $changeSet)
    {
        return false;
    }

}