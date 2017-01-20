<?php
/*
 * This file is part of the Dive ORM framework.
 * (c) Steffen Zeidler <sigma_z@sigma-scripts.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dive\Test\Validation\UniqueValidator;

use Dive\Record;
use Dive\TestSuite\RecordBehaviorTrait;
use Dive\TestSuite\TestCase;
use Dive\Validation\UniqueValidator\UniqueRecordValidator;

/**
 * @author Steffen Zeidler <sigma_z@sigma-scripts.de>
 * @date   20.01.2017
 */
class UniqueRecordValidatorTest extends TestCase
{

    use RecordBehaviorTrait;

    /** @var Record */
    private $record;

    /** @var bool */
    private $isValid;


    public function testValidateCompositeUniqueNullConstraintedIsValid()
    {
        $this->givenIHaveARecordManager();
        $this->givenIHaveStoredAUniqueConstraintTestRecord('test', [
            'composite_unique_null_constrained1' => 'test',
            'composite_unique_null_constrained2' => null
        ]);
        $this->record = $this->givenIHaveCreatedAUniqueConstraintTestRecord('test2', [
            'composite_unique_null_constrained1' => 'test2',
            'composite_unique_null_constrained2' => null
        ]);

        $this->whenIValidateRecord($this->record);

        $this->thenTheRecordShouldBeUnique();
    }


    public function testValidateCompositeUniqueNullConstraintedIsViolated()
    {
        $this->givenIHaveARecordManager();
        $this->givenIHaveStoredAUniqueConstraintTestRecord('test', [
            'composite_unique_null_constrained1' => 'test',
            'composite_unique_null_constrained2' => null
        ]);
        $this->record = $this->givenIHaveCreatedAUniqueConstraintTestRecord('test2', [
            'composite_unique_null_constrained1' => 'test',
            'composite_unique_null_constrained2' => null
        ]);

        $this->whenIValidateRecord($this->record);

        $this->thenTheRecordShouldBeNotUnique();
    }


    public function testValidateCompositeUniqueNotNullConstraintedIdValid()
    {
        $this->givenIHaveARecordManager();
        $this->givenIHaveStoredAUniqueConstraintTestRecord('test');
        $this->record = $this->givenIHaveCreatedAUniqueConstraintTestRecord('test2', [
            'composite_unique1' => 'test',
            'composite_unique2' => null
        ]);
        $this->record = $this->givenIHaveCreatedARecord('unique_constraint_test', [
            'composite_unique1' => 'test',
            'composite_unique2' => null
        ]);

        $this->whenIValidateRecord($this->record);

        $this->thenTheRecordShouldBeUnique();
    }


    public function testValidateCompositeUniqueNotNullConstraintedIsViolated()
    {
        $this->givenIHaveARecordManager();
        $this->givenIHaveStoredAUniqueConstraintTestRecord('test');
        $this->record = $this->givenIHaveCreatedAUniqueConstraintTestRecord('test2', [
            'composite_unique1' => 'test',
            'composite_unique2' => 'test'
        ]);

        $this->whenIValidateRecord($this->record);

        $this->thenTheRecordShouldBeNotUnique();
    }


    public function testValidateSingleFieldUniqueNullConstraintedIsValid()
    {
        $this->givenIHaveARecordManager();
        $this->givenIHaveStoredAUniqueConstraintTestRecord('test');
        $this->record = $this->givenIHaveCreatedAUniqueConstraintTestRecord(
            'test2',
            ['single_unique_null_constrained' => null]
        );

        $this->whenIValidateRecord($this->record);

        $this->thenTheRecordShouldBeUnique();
    }


    public function testValidateSingleFieldUniqueNullConstraintedIsViolated()
    {
        $this->givenIHaveARecordManager();
        $this->givenIHaveStoredAUniqueConstraintTestRecord('test', ['single_unique_null_constrained' => null]);
        $this->record = $this->givenIHaveCreatedAUniqueConstraintTestRecord(
            'test2',
            ['single_unique_null_constrained' => null]
        );

        $this->whenIValidateRecord($this->record);

        $this->thenTheRecordShouldBeNotUnique();
    }


    public function testValidateSingleFieldUniqueNotNullConstraintedIsValid()
    {
        $this->givenIHaveARecordManager();
        $this->givenIHaveStoredAUniqueConstraintTestRecord('test', ['single_unique' => null]);
        $this->record = $this->givenIHaveCreatedAUniqueConstraintTestRecord(
            'test2',
            ['single_unique' => null]
        );

        $this->whenIValidateRecord($this->record);

        $this->thenTheRecordShouldBeUnique();
    }


    public function testValidateSingleFieldUniqueNotNullConstraintedIsViolated()
    {
        $this->givenIHaveARecordManager();
        $this->givenIHaveStoredAUniqueConstraintTestRecord('test', ['single_unique' => 'test']);
        $this->record = $this->givenIHaveCreatedAUniqueConstraintTestRecord(
            'test2',
            ['single_unique' => 'test']
        );

        $this->whenIValidateRecord($this->record);

        $this->thenTheRecordShouldBeNotUnique();
    }


    public function testValidateCompositeNotNullConstraintedForChangedRecord()
    {
        $this->givenIHaveARecordManager();
        $this->record = $this->givenIHaveStoredAUniqueConstraintTestRecord('test', ['composite_unique1' => null]);
        $this->record->composite_unique2 = 'test2';

        $this->whenIValidateRecord($this->record);

        $this->thenTheRecordShouldBeUnique();
    }


    /**
     * @param Record $record
     */
    private function whenIValidateRecord(Record $record)
    {
        $validator = new UniqueRecordValidator();
        $this->isValid = $validator->validate($record);
    }


    private function thenTheRecordShouldBeNotUnique()
    {
        $this->assertFalse($this->isValid, 'Record should be not unique');
    }


    private function thenTheRecordShouldBeUnique()
    {
        $this->assertTrue($this->isValid, 'Record should be unique');
    }

}