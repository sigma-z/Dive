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
use Dive\UnitOfWork\UnitOfWorkException;
use Dive\Util\FieldValuesGenerator;

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
     * @dataProvider provideDatabaseAwareTestCases
     */
    public function testSingleFieldUniqueConstraintNullConstrainedViolationThrowsException(array $database)
    {
        $this->givenIHaveConnectedTheDatabase($database);
        $this->givenIHaveStoredRecordWithData(array('single_unique' => 'unique'));
        $this->whenITryToSaveRecordWithData(array('single_unique' => 'unique'));
        $this->thenItShouldThrowAUniqueConstraintException();
    }


    /**
     * @dataProvider provideDatabaseAwareTestCases
     */
    public function testSingleFieldUniqueConstraintNotNullConstrainedViolationThrowsException(array $database)
    {
        $this->givenIHaveConnectedTheDatabase($database);
        $this->givenIHaveStoredRecordWithData(array('single_unique_null_constrained' => 'unique'));
        $this->whenITryToSaveRecordWithData(array('single_unique_null_constrained' => 'unique'));
        $this->thenItShouldThrowAUniqueConstraintException();
    }


    /**
     * @dataProvider provideDatabaseAwareTestCases
     */
    public function testSingleFieldUniqueConstraintNullConstrainedIsValid(array $database)
    {
        $this->givenIHaveConnectedTheDatabase($database);
        $this->givenIHaveStoredRecordWithData(array('single_unique' => null));
        $this->whenITryToSaveRecordWithData(array('single_unique' => 'unique'));
        $this->thenThereShouldBeTwoRecordsSaved();
    }


    /**
     * @dataProvider provideDatabaseAwareTestCases
     */
    public function testSingleFieldUniqueConstraintNullConstrainedIsValidWithNullValues(array $database)
    {
        $this->givenIHaveConnectedTheDatabase($database);
        $this->givenIHaveStoredRecordWithData(array('single_unique' => null));
        $this->whenITryToSaveRecordWithData(array('single_unique' => null));
        $this->thenThereShouldBeTwoRecordsSaved();
    }


    /**
     * @dataProvider provideDatabaseAwareTestCases
     */
    public function testCompositeUniqueConstraintNullConstrainedViolationThrowsException(array $database)
    {
        $this->givenIHaveConnectedTheDatabase($database);
        $this->givenIHaveStoredRecordWithData(array('composite_unique1' => 'unique', 'composite_unique2' => 'unique'));
        $this->whenITryToSaveRecordWithData(array('composite_unique1' => 'unique', 'composite_unique2' => 'unique'));
        $this->thenItShouldThrowAUniqueConstraintException();
    }


    /**
     * @dataProvider provideDatabaseAwareTestCases
     */
    public function testCompositeUniqueConstraintNotNullConstrainedViolationThrowsException(array $database)
    {
        $this->givenIHaveConnectedTheDatabase($database);
        $this->givenIHaveStoredRecordWithData(array(
            'composite_unique_null_constrained1' => null,
            'composite_unique_null_constrained2' => 'unique'
        ));
        $this->whenITryToSaveRecordWithData(array(
            'composite_unique_null_constrained1' => null,
            'composite_unique_null_constrained2' => 'unique'
        ));
        $this->thenItShouldThrowAUniqueConstraintException();
    }


    /**
     * @dataProvider provideDatabaseAwareTestCases
     */
    public function testCompositeUniqueConstraintNullConstrainedIsValid(array $database)
    {
        $this->givenIHaveConnectedTheDatabase($database);
        $this->givenIHaveStoredRecordWithData(array('composite_unique1' => null, 'composite_unique2' => 'unique'));
        $this->whenITryToSaveRecordWithData(array('composite_unique1' => 'unique', 'composite_unique2' => 'unique'));
        $this->thenThereShouldBeTwoRecordsSaved();
    }


    /**
     * @dataProvider provideDatabaseAwareTestCases
     */
    public function testCompositeUniqueConstraintNullConstrainedIsValidWithNullValues(array $database)
    {
        $this->givenIHaveConnectedTheDatabase($database);
        $this->givenIHaveStoredRecordWithData(array('composite_unique1' => null, 'composite_unique2' => 'unique'));
        $this->whenITryToSaveRecordWithData(array('composite_unique1' => null, 'composite_unique2' => 'unique'));
        $this->thenThereShouldBeTwoRecordsSaved();
    }


    /**
     * @param array $database
     */
    private function givenIHaveConnectedTheDatabase(array $database)
    {
        $this->rm = self::createRecordManager($database);
    }


    /**
     * @param array $recordData
     */
    private function givenIHaveStoredRecordWithData(array $recordData)
    {
        $record = $this->createRecordWithRandomData($recordData);
        $this->rm->save($record);
        $this->rm->commit();
    }


    /**
     * @param array $recordData
     */
    private function whenITryToSaveRecordWithData(array $recordData)
    {
        $this->raisedException = null;
        try {
            $record = $this->createRecordWithRandomData($recordData);
            $this->rm->save($record);
            $this->rm->commit();
        }
        // TODO use a more specific exception
        catch (UnitOfWorkException $e) {
           $this->raisedException = $e;
        }
    }


    private function thenItShouldThrowAUniqueConstraintException()
    {
        $this->assertNotNull($this->raisedException, 'Expected exception to be thrown');
        $this->assertInstanceOf('\\Dive\\UnitOfWork\UnitOfWorkException', $this->raisedException);
    }


    private function thenThereShouldBeTwoRecordsSaved()
    {
        $this->assertEquals(null, $this->raisedException, 'Expected exception NOT to be thrown');
        $this->assertEquals(2, $this->rm->getTable('unique_constraint_test')->count());
    }


    /**
     * @param array $recordData
     * @return \Dive\Record
     */
    private function createRecordWithRandomData(array $recordData)
    {
        $table = $this->rm->getTable('unique_constraint_test');
        $fieldValueGenerator = new FieldValuesGenerator();
        $recordData = $fieldValueGenerator->getRandomRecordData(
            $table->getFields(), $recordData, FieldValuesGenerator::MAXIMAL_WITHOUT_AUTOINCREMENT
        );
        return $table->createRecord($recordData);
    }

}