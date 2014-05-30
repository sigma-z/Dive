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
 * Class BooleanFieldValidationTest
 * @author  Steffen Zeidler <sigma_z@sigma-scripts.de>
 * @created 11.04.14
 */
class BooleanFieldValidationTest extends TestCase
{

    /**
     * @dataProvider provideValidationSucceeds
     */
    public function testValidationSucceeds($value)
    {
        $this->givenIHaveAFieldTypeValidatorWithType('BooleanFieldValidator');
        $this->whenIValidateValue($value);
        $this->thenValidationShouldSucceed();
    }


    /**
     * @dataProvider provideValidationFails
     */
    public function testValidationFails($value)
    {
        $this->givenIHaveAFieldTypeValidatorWithType('BooleanFieldValidator');
        $this->whenIValidateValue($value);
        $this->thenValidationShouldFail();
    }


    /**
     * @return array[]
     */
    public function provideValidationSucceeds()
    {
        return array(
            'null'       => array(null),
            'int1'       => array(1),
            'string1'    => array('1'),
            'bool-true'  => array(true),
            'int0'       => array(0),
            'string0'    => array('0'),
            'bool-false' => array(false),
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
            'empty-array'   => array(array())
        );
    }

}
