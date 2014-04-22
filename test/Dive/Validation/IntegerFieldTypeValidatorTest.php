<?php
/*
 * This file is part of the Dive ORM framework.
 * (c) Steffen Zeidler <sigma_z@sigma-scripts.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Dive\Test\Validation;

require_once __DIR__ . '/FieldTypeValidatorTestCase.php';

/**
 * Class IntegerFieldTypeValidatorTest
 * @author  Steffen Zeidler <sigma_z@sigma-scripts.de>
 * @created 11.04.14
 */
class IntegerFieldTypeValidatorTest extends FieldTypeValidatorTestCase
{


    /**
     * @dataProvider provideValidationSucceeds
     */
    public function testValidationSucceeds($value)
    {
        $this->givenIHaveAFieldTypeValidatorWithType('IntegerFieldValidator');
        $this->whenIValidateValue($value);
        $this->thenValidationShouldSucceed();
    }

    /**
     * @dataProvider provideValidationFails
     */
    public function testValidationFails($value)
    {
        $this->givenIHaveAFieldTypeValidatorWithType('IntegerFieldValidator');
        $this->whenIValidateValue($value);
        $this->thenValidationShouldFail();
    }


    /**
     * @return array[]
     */
    public function provideValidationSucceeds()
    {
        return array(
            'null'              => array(null),
            'int1'              => array(1),
            'string1'           => array('1'),
            'int0'              => array(0),
            'string0'           => array('0'),
            'negative-int'      => array(-1234),
            'string-negative-int' => array(-1234),
            'string-bigint'     => array('12345678987654321'),
            'string-negative-bigint' => array('-12345678987654321'),
        );
    }


    /**
     * @return array[]
     */
    public function provideValidationFails()
    {
        return array(
            'string'        => array('string'),
            'empty-string'  => array(''),
            'true'          => array(true),
            'false'         => array(false),
            'empty-array'   => array(array())
        );
    }


}
