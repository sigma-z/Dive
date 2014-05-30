<?php
/*
 * This file is part of the Dive ORM framework.
 * (c) Steffen Zeidler <sigma_z@sigma-scripts.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Dive\Validation\UniqueValidator;

use Dive\Exception;
use Dive\Record;
use Dive\Validation\ValidatorInterface;

/**
 * Class UniqueRecordValidator
 * @author  Steffen Zeidler <sigma_z@sigma-scripts.de>
 * @created 30.05.2014
 */
class UniqueRecordValidator implements ValidatorInterface
{

    /**
     * @param  Record $record
     * @throws \InvalidArgumentException
     * @return bool
     */
    public function validate($record)
    {
        if (!($record instanceof Record)) {
            throw new \InvalidArgumentException("Expects record instance as #1 argument");
        }

        if ($record->exists() && !$record->isModified()) {
            return true;
        }
        $uniqueIndexesToCheck = $this->getUniqueConstraintsToCheck($record);
        return $this->isRecordIsUnique($record, $uniqueIndexesToCheck);
    }


    /**
     * @param  Record $record
     * @return array[]
     */
    private function getUniqueConstraintsToCheck(Record $record)
    {
        $table = $record->getTable();
        $uniqueIndexes = $table->getUniqueIndexes();
        $uniqueIndexesToCheck = array();
        foreach ($uniqueIndexes as $uniqueName => $uniqueIndexDefinition) {
            if ($this->isCheckRequired($record, $uniqueName)) {
                $uniqueIndexesToCheck[$uniqueName] = $uniqueIndexDefinition;
            }
        }
        return $uniqueIndexesToCheck;
    }


    /**
     * @param  Record $record
     * @param  string $uniqueName
     * @return bool
     * @throws \Dive\Table\TableException
     */
    private function isCheckRequired(Record $record, $uniqueName)
    {
        $table = $record->getTable();
        $uniqueIndex = $table->getIndex($uniqueName);
        $isNullConstrained = $table->isUniqueIndexNullConstrained($uniqueName);
        $uniqueFields = $uniqueIndex['fields'];
        $checkIsRequired = false;
        foreach ($uniqueFields as $fieldName) {
            if ($record->exists() && !$record->isFieldModified($fieldName)) {
                continue;
            }
            if ($isNullConstrained) {
                return true;
            }
            if ($record->get($fieldName) === null) {
                return false;
            }
            $checkIsRequired = true;
        }
        return $checkIsRequired;
    }


    /**
     * @param Record $record
     * @param array  $uniqueIndexesToCheck
     * @return bool
     */
    private function isRecordIsUnique(Record $record, array $uniqueIndexesToCheck)
    {
        if (!$uniqueIndexesToCheck) {
            return true;
        }

        $query = $this->getUniqueIndexesQuery($record, $uniqueIndexesToCheck);
        return !$query->hasResult();
    }


    /**
     * @param Record $record
     * @param array  $uniqueIndexesToCheck
     * @return \Dive\Query\Query
     * @throws \Dive\Exception
     */
    private function getUniqueIndexesQuery(Record $record, array $uniqueIndexesToCheck)
    {
        $table = $record->getTable();
        $query = $table->createQuery();

        foreach ($uniqueIndexesToCheck as $uniqueName => $uniqueIndexToCheck) {
            $isNullConstrained = $table->isUniqueIndexNullConstrained($uniqueName);
            $params = array();
            $condition = '';
            $fieldNames = $uniqueIndexToCheck['fields'];
            foreach ($fieldNames as $fieldName) {
                $fieldValue = $record->get($fieldName);
                if ($fieldValue !== null) {
                    $condition .= $fieldName . ' = ? AND ';
                    $params[] = $fieldValue;
                }
                else if ($isNullConstrained) {
                    $condition .= $fieldName . ' IS NULL AND ';
                }
                else {
                    throw new Exception("Cannot process unique index for creating query to check whether the record is unique, or not!");
                }
            }
            $condition = substr($condition, 0, -4);
            $query->orWhere($condition, $params);
        }

        return $query;
    }

}