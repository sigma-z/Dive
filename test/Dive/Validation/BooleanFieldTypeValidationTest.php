<?php
/*
 * This file is part of the Dive ORM framework.
 * (c) Steffen Zeidler <sigma_z@sigma-scripts.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Dive\Test\Validation;

use Dive\TestSuite\TestCase;
use Dive\Validation\BooleanFieldValidator;

/**
 * Class BooleanFieldTypeValidationTest
 * @author  Steffen Zeidler <sigma_z@sigma-scripts.de>
 * @created 11.04.14
 */
class BooleanFieldTypeValidationTest extends TestCase
{

    /** @var BooleanFieldValidator */
    private $validator;

    /** @var bool */
    private $validationResult;


    /**
     * @dataProvider provideValidationFails
     */
    public function testValidationFails($value)
    {
        $this->givenIHaveABooleanFieldValidator();
        $this->whenIValidateValue($value);
        $this->thenValidationShouldFail();
    }


    /**
     * @dataProvider provideValidationSucceeds
     */
    public function testValidationSucceeds($value)
    {
        $this->givenIHaveABooleanFieldValidator();
        $this->whenIValidateValue($value);
        $this->thenValidationShouldSucceed();
    }


    /**
     * @return array[]
     */
    public function provideValidationSucceeds()
    {
        return array(
            array(null),
            array(1),
            array('1'),
            array(true),
            array(0),
            array('0'),
            array(false),
        );
    }


    /**
     * @return array[]
     */
    public function provideValidationFails()
    {
        return array(
            array('string'),
            array(''),
            array(array())
        );
    }


    private function givenIHaveABooleanFieldValidator()
    {
        $this->validator = new BooleanFieldValidator();
    }


    /**
     * @param mixed $value
     */
    private function whenIValidateValue($value)
    {
        $this->validationResult = $this->validator->validate($value);
    }


    private function thenValidationShouldFail()
    {
        $this->assertFalse($this->validationResult);
    }


    private function thenValidationShouldSucceed()
    {
        $this->assertTrue($this->validationResult);
    }


}