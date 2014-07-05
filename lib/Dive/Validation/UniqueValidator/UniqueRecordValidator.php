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
     * @param  Record $record
     * @param  array  $uniqueIndexesToCheck
     * @return bool
     */
    private function isRecordIsUnique(Record $record, array $uniqueIndexesToCheck)
    {
        if (!$uniqueIndexesToCheck) {
            return true;
        }

        // TODO hwo to know which unique index breaks the constraint for adding unique errors to the error stack
        $query = $this->getUniqueIndexesQuery($record, $uniqueIndexesToCheck);
        $isInvalid = $query->hasResult();
        if ($isInvalid) {
            $errorStack = $record->getErrorStack();
            $errorStack->add('unique index', 'unique');
        }
        return !$isInvalid;
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
        $conn = $table->getConnection();
        $conditions = array();
        $queryParams = array();

        if ($record->exists()) {
            $condition = '';
            foreach ($record->getIdentifierFieldIndexed() as $idField => $idValue) {
                $condition .= $conn->quoteIdentifier($idField) . ' != ? AND ';
                $queryParams[] = $idValue;
            }
            // strip last AND from string
            $condition = substr($condition, 0, -4);
            $conditions[] = $condition;
        }

        foreach ($uniqueIndexesToCheck as $uniqueName => $uniqueIndexToCheck) {
            $isNullConstrained = $table->isUniqueIndexNullConstrained($uniqueName);
            $conditionParams = array();
            $condition = '';
            $fieldNames = $uniqueIndexToCheck['fields'];
            foreach ($fieldNames as $fieldName) {
                $fieldNameQuoted = $conn->quoteIdentifier($fieldName);
                $fieldValue = $record->get($fieldName);
                if ($fieldValue !== null) {
                    $condition .= $fieldNameQuoted . ' = ? AND ';
                    $conditionParams[] = $fieldValue;
                }
                else if ($isNullConstrained) {
                    $condition .= $fieldNameQuoted . ' IS NULL AND ';
                }
                else {
                    throw new Exception("Cannot process unique index for creating query to check whether the record is unique, or not!");
                }
            }
            // strip last AND from string
            $condition = substr($condition, 0, -4);
            $conditions[] = $condition;
            $queryParams = array_merge($queryParams, $conditionParams);
        }

        $condition = ($record->exists() ? array_shift($conditions) . ' AND (' : '')
            . implode(' OR ', $conditions)
            . ($record->exists() ? ')' : '');

        $query = $table->createQuery();
        $query->where($condition, $queryParams);
        return $query;
    }

}
