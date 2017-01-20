<?php
/*
 * This file is part of the Dive ORM framework.
 * (c) Steffen Zeidler <sigma_z@sigma-scripts.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dive\TestSuite;

use Dive\RecordManager;
use Dive\TestSuite\Record\Record;

/**
 * @author Steffen Zeidler <sigma_z@sigma-scripts.de>
 * @date   20.01.2017
 */
trait RecordBehaviorTrait
{

    /** @var RecordManager */
    private $rm;


    /**
     * @return RecordManager
     */
    private function givenIHaveARecordManager()
    {
        if (!$this->rm) {
            $this->rm = TestCase::createDefaultRecordManager();
        }
        return $this->rm;
    }


    /**
     * @param string $tableName
     * @param array  $recordData
     * @return Record
     */
    private function givenIHaveStoredARecord($tableName, array $recordData = [])
    {
        $rm = $this->givenIHaveARecordManager();
        $record = $this->givenIHaveCreatedARecord($tableName, $recordData);
        $rm->scheduleSave($record)->commit();
        return $record;
    }


    /**
     * @param string $tableName
     * @param array  $recordData
     * @return Record
     */
    private function givenIHaveCreatedARecord($tableName, array $recordData = [])
    {
        $table = $this->givenIHaveATable($tableName);
        return TestCase::getRecordWithRandomData($table, $recordData);
    }


    /**
     * @param string $fieldValue
     * @param array  $recordData
     * @return Record
     */
    private function givenIHaveStoredAUniqueConstraintTestRecord($fieldValue, array $recordData = [])
    {
        $recordData = array_merge([
            'single_unique' => $fieldValue,
            'single_unique_null_constrained' => $fieldValue,
            'composite_unique1' => $fieldValue,
            'composite_unique2' => $fieldValue,
            'composite_unique_null_constrained1' => $fieldValue,
            'composite_unique_null_constrained2' => $fieldValue
        ], $recordData);
        return $this->givenIHaveStoredARecord('unique_constraint_test', $recordData);
    }


    /**
     * @param string $fieldValue
     * @param array  $recordData
     * @return Record
     */
    private function givenIHaveCreatedAUniqueConstraintTestRecord($fieldValue, array $recordData = [])
    {
        $recordData = array_merge([
            'single_unique' => $fieldValue,
            'single_unique_null_constrained' => $fieldValue,
            'composite_unique1' => $fieldValue,
            'composite_unique2' => $fieldValue,
            'composite_unique_null_constrained1' => $fieldValue,
            'composite_unique_null_constrained2' => $fieldValue
        ], $recordData);
        return $this->givenIHaveCreatedARecord('unique_constraint_test', $recordData);
    }


    /**
     * @param string $tableName
     * @return \Dive\Table
     */
    private function givenIHaveATable($tableName)
    {
        $rm = $this->givenIHaveARecordManager();
        return $rm->getTable($tableName);
    }

}