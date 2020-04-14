<?php
/*
 * This file is part of the Dive ORM framework.
 * (c) Steffen Zeidler <sigma_z@sigma-scripts.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Dive\Test\Schema\OrmDataType;

use Dive\Schema\OrmDataType\OrmDataType;
use Dive\TestSuite\TestCase as BaseTestCase;
use Dive\Util\CamelCase;
use Dive\Validation\FieldValidator\FieldValidatorInterface;

/**
 * Class FieldValidatorTestCase
 * @author  Steffen Zeidler <sigma_z@sigma-scripts.de>
 * @created 11.04.14
 */
abstract class TestCase extends BaseTestCase
{

    /** @var string */
    protected $type;

    /** @var FieldValidatorInterface */
    protected $validator;

    /** @var bool */
    protected $validationResult;


    /**
     * @dataProvider provideValidationSucceeds
     * @param mixed $value
     * @param array $field
     */
    public function testValidationSucceeds($value, array $field = array())
    {
        $this->givenIHaveADataTypeOfType($this->type);
        $this->whenIValidateValue($value, $field);
        $this->thenValidationShouldSucceed();
    }


    /**
     * @dataProvider provideValidationFails
     * @param mixed $value
     * @param array $field
     */
    public function testValidationFails($value, array $field = array())
    {
        $this->givenIHaveADataTypeOfType($this->type);
        $this->whenIValidateValue($value, $field);
        $this->thenValidationShouldFail();
    }


    /**
     * @dataProvider provideLengthValidation
     * @param mixed $value
     * @param array $field
     * @param bool  $expected
     */
    public function testLengthValidation($value, array $field, $expected)
    {
        $this->givenIHaveADataTypeOfType($this->type);
        $this->whenIValidateValueLength($value, $field);
        $this->thenValidationShouldBe($expected);
    }


    /**
     * @param string $ormDataType
     */
    protected function givenIHaveADataTypeOfType($ormDataType)
    {
        $class = '\\Dive\\Schema\\OrmDataType\\' . CamelCase::toCamelCase($ormDataType) . 'OrmDataType';
        $this->validator = new $class($ormDataType);
        $this->assertInstanceOf(OrmDataType::class, $this->validator);
    }


    /**
     * @param mixed $value
     * @param array $field
     */
    protected function whenIValidateValue($value, array $field)
    {
        $field['type'] = $this->type;
        $this->validationResult = $this->validator->validateType($value, $field);
    }


    /**
     * @param mixed $value
     * @param array $field
     */
    private function whenIValidateValueLength($value, array $field)
    {
        $field['type'] = $this->type;
        $this->validationResult = $this->validator->validateLength($value, $field);
    }


    protected function thenValidationShouldFail()
    {
        $this->assertFalse($this->validationResult);
    }


    protected function thenValidationShouldSucceed()
    {
        $this->assertTrue($this->validationResult);
    }


    /**
     * @param bool $expected
     */
    private function thenValidationShouldBe($expected)
    {
        $this->assertEquals($expected, $this->validationResult);
    }

}
