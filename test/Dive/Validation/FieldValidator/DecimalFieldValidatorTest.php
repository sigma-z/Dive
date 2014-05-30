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
 * Class DecimalFieldValidatorTest
 * @author  Steffen Zeidler <sigma_z@sigma-scripts.de>
 * @created 25.04.2014
 */
class DecimalFieldValidatorTest extends TestCase
{

    /**
     * @dataProvider provideValidationSucceeds
     */
    public function testValidationSucceeds($value)
    {
        $this->givenIHaveAFieldTypeValidatorWithType('DecimalFieldValidator');
        $this->whenIValidateValue($value);
        $this->thenValidationShouldSucceed();
    }


    /**
     * @dataProvider provideValidationFails
     */
    public function testValidationFails($value)
    {
        $this->givenIHaveAFieldTypeValidatorWithType('DecimalFieldValidator');
        $this->whenIValidateValue($value);
        $this->thenValidationShouldFail();
    }


    /**
     * @return array[]
     */
    public function provideValidationSucceeds()
    {
        return array(
            'null'          => array(null),
            'int 0'         => array(0),
            'string 0'      => array('0'),
            'float .0'      => array(.0),
            'string .0'     => array('.0'),
            'int 1234'      => array(1243),
            'string 1234'   => array('1243'),
            'float .1234'   => array(.1243),
            'string .1234'  => array('.1243'),
            'int -1234'     => array(-1243),
            'string -1234'  => array('-1243'),
            'float -.1234'  => array(-.1243),
            'string -.1234' => array('-.1243'),
            'float 134567890.0987654321' => array(1234567890.0987654321),
            'string 1234567890.0987654321' => array('1234567890.0987654321')
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
            'string-true'   => array('true'),
            'string-false'  => array('false'),
            'bool-true'     => array(true),
            'bool-false'    => array(false),
            'empty-string'  => array(''),
            'empty-array'   => array(array())
        );
    }

}