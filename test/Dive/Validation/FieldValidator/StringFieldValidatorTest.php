<?php
/*
 * This file is part of the Dive ORM framework.
 * (c) Steffen Zeidler <sigma_z@sigma-scripts.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Dive\Test\Validation\FieldValidator;

require_once __DIR__ . '/TestCase.php';

/**
 * Class StringFieldValidatorTest
 * @author  Steffen Zeidler <sigma_z@sigma-scripts.de>
 * @created 25.04.2014
 */
class StringFieldValidatorTest extends TestCase
{
    /**
     * @dataProvider provideValidationSucceeds
     */
    public function testValidationSucceeds($value)
    {
        $this->givenIHaveAFieldTypeValidatorWithType('StringFieldValidator');
        $this->whenIValidateValue($value);
        $this->thenValidationShouldSucceed();
    }


    /**
     * @dataProvider provideValidationFails
     */
    public function testValidationFails($value)
    {
        $this->givenIHaveAFieldTypeValidatorWithType('StringFieldValidator');
        $this->whenIValidateValue($value);
        $this->thenValidationShouldFail();
    }


    /**
     * @return array[]
     */
    public function provideValidationSucceeds()
    {
        return array(
            'null'      => array(null),
            'empty-string' => array(''),
            'string-12' => array('12'),
            'string'    => array('some kind of string')
        );
    }


    /**
     * @return array[]
     */
    public function provideValidationFails()
    {
        return array(
            'bool-false'    => array(false),
            'bool-true'     => array(true),
            'int-12'        => array(12),
            'empty-array'   => array(array())
        );
    }

}