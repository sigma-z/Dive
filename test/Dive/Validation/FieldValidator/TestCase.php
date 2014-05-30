<?php
/*
 * This file is part of the Dive ORM framework.
 * (c) Steffen Zeidler <sigma_z@sigma-scripts.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Dive\Test\Validation\FieldValidator;

use Dive\TestSuite\TestCase as BaseTestCase;
use Dive\Validation\ValidatorInterface;

/**
 * Class FieldValidatorTestCase
 * @author  Steffen Zeidler <sigma_z@sigma-scripts.de>
 * @created 11.04.14
 */
abstract class TestCase extends BaseTestCase
{

    /** @var  ValidatorInterface */
    protected $validator;

    /** @var bool */
    protected $validationResult;


    /**
     * @param string $className
     */
    protected function givenIHaveAFieldTypeValidatorWithType($className)
    {
        $className = '\\Dive\\Validation\\FieldValidator\\' . $className;
        $this->validator = new $className;
        $this->assertInstanceOf('\Dive\Validation\ValidatorInterface', $this->validator);
    }


    /**
     * @param mixed $value
     */
    protected function whenIValidateValue($value)
    {
        $this->validationResult = $this->validator->validate($value);
    }


    protected function thenValidationShouldFail()
    {
        $this->assertFalse($this->validationResult);
    }


    protected function thenValidationShouldSucceed()
    {
        $this->assertTrue($this->validationResult);
    }

}