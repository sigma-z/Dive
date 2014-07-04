<?php
/*
 * This file is part of the Dive ORM framework.
 * (c) Steffen Zeidler <sigma_z@sigma-scripts.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Dive\Test\Validation\FieldValidator;

use Dive\Record;
use Dive\RecordManager;
use Dive\Schema\DataTypeMapper\DataTypeMapper;
use Dive\TestSuite\TestCase as BaseTestCase;
use Dive\Validation\FieldValidator\FieldTypeValidator;

/**
 * Class FieldTypeValidatorTest
 * @author  Steffen Zeidler <sigma_z@sigma-scripts.de>
 * @created 10.06.2014
 */
class FieldTypeValidatorTest extends BaseTestCase
{

    /** @var FieldTypeValidator */
    private $validator;

    /** @var RecordManager */
    private $rm;

    /** @var Record */
    private $record;

    /** @var bool */
    private $validationResult;


    protected function setUp()
    {
        parent::setUp();
        $this->rm = self::createDefaultRecordManager();
    }


    public function testValidateWithNonRegisteredTypeValidators()
    {
        $this->givenIHaveAFieldTypeValidator();
        $this->givenIHaveARecordOfTable('article');
        $this->whenIValidateTheRecord();
        $this->thenTheResultShouldBeValid();
    }


    public function testValidateWithRegisteredDateTimeTypeValidator()
    {
        $this->givenIHaveAFieldTypeValidator();
        $this->givenIHaveARecordOfTable('article');
        $this->whenIValidateTheRecord();
        $this->thenTheResultShouldBeValid();
    }


    public function testValidateToFalseWithRegisteredDateTimeTypeValidator()
    {
        $this->givenIHaveAFieldTypeValidator();
        $this->givenIHaveARecordOfTable('article');
        $this->whenISetRecordField_to('created_on', 'invalid date');
        $this->whenIValidateTheRecord();
        $this->thenTheResultShouldBeInvalid();
    }


    public function testValidateWithRegisteredAndRemovedDateTimeTypeValidator()
    {
        $this->givenIHaveAFieldTypeValidator();
        $this->givenIHaveARecordOfTable('article');
        $this->whenISetRecordField_to('created_on', 'invalid date');
        $this->whenIRemoveDatetimeValidatorFromTheFieldTypeValidator();
        $this->whenIValidateTheRecord();
        $this->thenTheResultShouldBeValid();
    }


    private function givenIHaveAFieldTypeValidator()
    {
        $dataTypeMapper = $this->rm->getDriver()->getDataTypeMapper();
        $this->validator = new FieldTypeValidator($dataTypeMapper);
    }


    /**
     * @param string $tableName
     */
    private function givenIHaveARecordOfTable($tableName)
    {
        $this->record = $this->rm->getTable($tableName)->createRecord();
    }


    private function whenIRemoveDatetimeValidatorFromTheFieldTypeValidator()
    {
        $dataTypeMapper = $this->rm->getDriver()->getDataTypeMapper();
        $dataTypeMapper->removeOrmType(DataTypeMapper::OTYPE_DATETIME);
    }


    private function whenIValidateTheRecord()
    {
        $this->validationResult = $this->validator->validate($this->record);
    }


    /**
     * @param string $fieldName
     * @param string $value
     */
    private function whenISetRecordField_to($fieldName, $value)
    {
        $this->record->set($fieldName, $value);
    }


    private function thenTheResultShouldBeValid()
    {
        $this->assertTrue($this->validationResult);
    }


    private function thenTheResultShouldBeInvalid()
    {
        $this->assertFalse($this->validationResult);
    }

}