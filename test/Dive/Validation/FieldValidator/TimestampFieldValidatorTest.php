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
 * Class TimestampFieldValidatorTest
 * @author  Steffen Zeidler <sigma_z@sigma-scripts.de>
 * @created 25.04.2014
 */
class TimestampFieldValidatorTest extends TestCase
{

    /**
     * @dataProvider provideValidationSucceeds
     */
    public function testValidationSucceeds($value)
    {
        $this->givenIHaveAFieldTypeValidatorWithType('TimestampFieldValidator');
        $this->whenIValidateValue($value);
        $this->thenValidationShouldSucceed();
    }


    /**
     * @dataProvider provideValidationFails
     */
    public function testValidationFails($value)
    {
        $this->givenIHaveAFieldTypeValidatorWithType('TimestampFieldValidator');
        $this->whenIValidateValue($value);
        $this->thenValidationShouldFail();
    }


    /**
     * @return array[]
     */
    public function provideValidationSucceeds()
    {
        return array(
            '2147483647' => array('2147483647'),
            '1500000000' => array('1500000000'),
        );
    }


    /**
     * @return array[]
     */
    public function provideValidationFails()
    {
        return array(
            'string'        => array('string'),
            '2014-04-14'    => array('2014-04-14'),
            '000:000:000'   => array('000:000:000'),
            '2147483648'    => array('2147483648'),
            '21474.83648'   => array('21474.83648'),
            '-21474'        => array('-21474'),
            '0:0:0'         => array('0:0:0'),
            '1:1:0'         => array('1:1:0'),
            '24:00:00'      => array('24:00:00'),
            '23:60:00'      => array('23:60:00'),
            '23:00:60'      => array('23:00:60'),
            'string-true'   => array('true'),
            'string-false'  => array('false'),
            'bool-true'     => array(true),
            'bool-false'    => array(false),
            'empty-string'  => array(''),
            'empty-array'   => array(array())
        );
    }

}