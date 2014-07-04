<?php
/*
 * This file is part of the Dive ORM framework.
 * (c) Steffen Zeidler <sigma_z@sigma-scripts.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dive\Test\Validation;

use Dive\Validation\FieldLengthValidator;

/**
 * Class FieldLengthValidatorTest
 * @author  Steffen Zeidler <sigma_z@sigma-scripts.de>
 * @created 04.07.2014
 */
class FieldLengthValidatorTest extends \PHPUnit_Framework_TestCase
{

    /** @var FieldLengthValidator */
    private $fieldLengthValidator;

    /** @var array */
    private $field = array();

    /** @var bool */
    private $validationResult;


    /**
     * @dataProvider provideFieldLengthValidation
     * @param array  $field
     * @param string $fieldValue
     * @param bool   $expected
     */
    public function testFieldLengthValidation(array $field, $fieldValue, $expected)
    {
        $this->givenIHaveAFieldLengthValidator();
        $this->givenIHaveAFieldDefinedBy($field);
        $this->whenIValidateTheFieldLengthForFieldValue($fieldValue);
        $this->thenItShouldBeValidatedAs($expected);
    }


    /**
     * @return array
     */
    public function provideFieldLengthValidation()
    {
        $testCases = array();

        $testCases[] = array(
            'field' => array('type' => 'string'),
            'value' => null,
            'expected' => true
        );

        $testCases[] = array(
            'field' => array('type' => 'string'),
            'value' => 'Lorem ipsum',
            'expected' => true
        );

        $testCases[] = array(
            'field' => array('type' => 'string', 'length' => 5),
            'value' => 'Lorem ipsum',
            'expected' => true
        );

        return $testCases;
    }


    private function givenIHaveAFieldLengthValidator()
    {
        $this->fieldLengthValidator = new FieldLengthValidator();
    }


    /**
     * @param array $field
     */
    private function givenIHaveAFieldDefinedBy(array $field)
    {
        $this->field = $field;
    }


    /**
     * @param mixed $fieldValue
     */
    private function whenIValidateTheFieldLengthForFieldValue($fieldValue)
    {
        $this->validationResult = $this->fieldLengthValidator->validateLength($this->field, $fieldValue);
    }


    /**
     * @param bool $expected
     */
    private function thenItShouldBeValidatedAs($expected)
    {
        $this->assertEquals($expected, $this->validationResult);
    }

}
