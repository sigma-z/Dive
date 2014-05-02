<?php
/*
 * This file is part of the Dive ORM framework.
 * (c) Steffen Zeidler <sigma_z@sigma-scripts.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Dive\Test\Record;

use Dive\Exception;
use Dive\RecordManager;
use Dive\TestSuite\TestCase;

/**
 * Class RecordSaveUniqueConstraintTest
 * @author  Steffen Zeidler <sigma_z@sigma-scripts.de>
 * @created 02.05.2014
 */
class RecordSaveUniqueConstraintTest extends TestCase
{

    /** @var RecordManager */
    private $rm;

    /** @var Exception */
    private $raisedException;


    /**
     * @dataProvider provideSaveRecordWithUniqueConstraint

     * @param array $database
     * @param array $recordValuesRecordGiven
     * @param array $recordValuesRecordWhen
     * @param bool  $expectedExceptionThrown
     */
    public function testSaveRecordUniqueConstraint(
        array $database, array $recordValuesRecordGiven, array $recordValuesRecordWhen, $expectedExceptionThrown
    )
    {
        $this->markTestIncomplete('Implement unique constraint validation!');

        $this->rm = self::createRecordManager($database);

        $this->givenIHaveSavedRecordWithData($recordValuesRecordGiven);
        $this->whenITryToSaveRecordWithData($recordValuesRecordWhen);
        if ($expectedExceptionThrown) {
            $this->thenItShouldThrowAUniqueConstraintException();
        }
        else {
            $this->thenThereShouldBeTwoRecordsSaved();
        }
    }


    /**
     * @return array[]
     */
    public function provideSaveRecordWithUniqueConstraint()
    {
        return array_merge(
            $this->provideSaveRecordWithSingleFieldUniqueConstraint(),
            $this->provideSaveRecordWithCompositeUniqueConstraint()
        );
    }


    /**
     * @return array[]
     */
    public function provideSaveRecordWithSingleFieldUniqueConstraint()
    {
        $testCases = array();

        $testCases['singleField-nullConstrained-throwsException'] = array(
            'recordValuesRecordGiven' => array('single_unique' => 'unique'),
            'recordValuesRecordWhen' => array('single_unique' => 'unique'),
            'expectedExceptionThrown' => true
        );
        $testCases['singleField-nullConstrained-noException'] = array(
            'recordValuesRecordGiven' => array('single_unique' => null),
            'recordValuesRecordWhen' => array('single_unique' => 'unique'),
            'expectedExceptionThrown' => false
        );
        $testCases['singleField-nullConstrainedWithNulls-noException'] = array(
            'recordValuesRecordGiven' => array('single_unique' => null),
            'recordValuesRecordWhen' => array('single_unique' => null),
            'expectedExceptionThrown' => false
        );
        $testCases['singleField-notNullConstrained-throwsException'] = array(
            'recordValuesRecordGiven' => array('single_unique_null_constrained' => null),
            'recordValuesRecordWhen' => array('single_unique_null_constrained' => null),
            'expectedExceptionThrown' => true
        );

        return $this->getDatabaseAwareTestCases($testCases);
    }


    /**
     * @return array[]
     */
    public function provideSaveRecordWithCompositeUniqueConstraint()
    {
        $testCases = array();

        $testCases['composite-nullConstrained-throwsException'] = array(
            'recordValuesRecordGiven' => array('composite_unique1' => 'unique', 'composite_unique2' => 'unique'),
            'recordValuesRecordWhen' => array('composite_unique1' => 'unique', 'composite_unique2' => 'unique'),
            'expectedExceptionThrown' => true
        );
        $testCases['composite-nullConstrained-noException'] = array(
            'recordValuesRecordGiven' => array('composite_unique1' => null, 'composite_unique2' => 'unique'),
            'recordValuesRecordWhen' => array('composite_unique1' => 'unique', 'composite_unique2' => 'unique'),
            'expectedExceptionThrown' => false
        );
        $testCases['composite-nullConstrainedWithNulls-noException'] = array(
            'recordValuesRecordGiven' => array('composite_unique1' => null, 'composite_unique2' => 'unique'),
            'recordValuesRecordWhen' => array('composite_unique1' => null, 'composite_unique2' => 'unique'),
            'expectedExceptionThrown' => false
        );
        $testCases['composite-notNullConstrained-throwsException'] = array(
            'recordValuesRecordGiven' => array(
                'composite_unique_null_constrained1' => null,
                'composite_unique_null_constrained2' => 'unique'
            ),
            'recordValuesRecordWhen' => array(
                'composite_unique_null_constrained1' => null,
                'composite_unique_null_constrained2' => 'unique'
            ),
            'expectedExceptionThrown' => true
        );

        return $this->getDatabaseAwareTestCases($testCases);
    }


    /**
     * @param array $recordData
     */
    private function givenIHaveSavedRecordWithData(array $recordData)
    {
        $table = $this->rm->getTable('unique_constraint_test');
        $record = $table->createRecord($recordData);
        $this->rm->save($record);
        $this->rm->commit();
    }


    /**
     * @param array $recordData
     */
    private function whenITryToSaveRecordWithData(array $recordData)
    {
        try {
            $table = $this->rm->getTable('unique_constraint_test');
            $record = $table->createRecord($recordData);
            $this->rm->save($record);
            $this->rm->commit();
       }
       catch (\Dive\Exception $e) {
           $this->raisedException = $e;
       }
    }


    private function thenItShouldThrowAUniqueConstraintException()
    {
        $this->assertNotNull($this->raisedException);
        $this->assertInstanceOf('\\Dive\\Exception', $this->raisedException);
    }


    private function thenThereShouldBeTwoRecordsSaved()
    {
        $this->assertEquals(2, $this->rm->getTable('unique_constraint_test')->count());
    }

}