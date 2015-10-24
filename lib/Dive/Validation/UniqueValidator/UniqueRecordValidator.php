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
use Dive\Query\Query;
use Dive\Record;
use Dive\Validation\RecordValidator;

/**
 * Class UniqueRecordValidator
 * @author  Steffen Zeidler <sigma_z@sigma-scripts.de>
 * @created 30.05.2014
 */
class UniqueRecordValidator extends RecordValidator
{

    /**
     * @param  Record $record
     * @throws \InvalidArgumentException
     * @return bool
     */
    public function validate($record)
    {
        if ($this->isCheckDisabled(self::CODE_RECORD_UNIQUE)) {
            return true;
        }

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

        $query = $this->getUniqueIndexesQuery($record, $uniqueIndexesToCheck);
        $result = $query->fetchOneAsArray();
        if ($result) {
            foreach ($result as $uniqueName => $hit) {
                if ($hit) {
                    $uniqueIndex = $uniqueIndexesToCheck[$uniqueName];
                    foreach ($uniqueIndex['fields'] as $field) {
                        $errorStack = $record->getErrorStack();
                        $errorStack->add($field, 'unique');
                    }
                }
            }
        }
        return empty($result);
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
        $recordExists = $record->exists();
        $identifier = array();

        if ($recordExists) {
            $condition = '';
            foreach ($record->getIdentifierFieldIndexed() as $idField => $idValue) {
                $condition .= $conn->quoteIdentifier($idField) . ' != ? AND ';
                $identifier[] = $idValue;
            }
            // strip last AND from string
            $condition = substr($condition, 0, -4);
            $conditions['primary'] = $condition;
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
            $conditions[$uniqueName] = $condition;
            $queryParams = array_merge($queryParams, $conditionParams);
        }

        $whereCondition = ($recordExists ? array_shift($conditions) . ' AND (' : '')
            . implode(' OR ', $conditions)
            . ($recordExists ? ')' : '');

        $query = $table->createQuery();
        $query->where($whereCondition);

        foreach ($conditions as $uniqueName => $condition) {
            $query->addSelect("($condition) AS " . $conn->quoteIdentifier($uniqueName));
        }

        $whereParams = $recordExists
            ? array_merge($identifier, $queryParams)
            : $queryParams;
        $query->setParams(Query::PART_SELECT, $queryParams);
        $query->setParams(Query::PART_WHERE, $whereParams);
        $query->limit(1);

        return $query;
    }

}
