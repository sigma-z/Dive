<?php
/*
 * This file is part of the Dive ORM framework.
 * (c) Steffen Zeidler <sigma_z@sigma-scripts.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Dive\Test\Validation;

require_once 'FieldValidatorTestCase.php';

/**
 * Class DateTimeFieldValidatorTest
 * @author  Steffen Zeidler <sigma_z@sigma-scripts.de>
 * @created 25.04.2014
 */
class DateTimeFieldValidatorTest extends FieldValidatorTestCase
{

    /**
     * @dataProvider provideValidationSucceeds
     */
    public function testValidationSucceeds($value)
    {
        $this->givenIHaveAFieldTypeValidatorWithType('DateTimeFieldValidator');
        $this->whenIValidateValue($value);
        $this->thenValidationShouldSucceed();
    }


    /**
     * @dataProvider provideValidationFails
     */
    public function testValidationFails($value)
    {
        $this->givenIHaveAFieldTypeValidatorWithType('DateTimeFieldValidator');
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
            '2013-02-28 12:23:34' => array('2013-02-28 12:23:34'),
            '2012-02-29 23:59:59' => array('2012-02-29 23:59:59'),
            '2012-02-29 00:00:00' => array('2012-02-29 00:00:00'),
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
            '2014-04-14 24:00:00' => array('2014-04-14 24:00:00'),
            '2014-04-14 23:60:00' => array('2014-04-14 23:60:00'),
            '2014-04-14 23:00:60' => array('2014-04-14 23:00:60'),
            '9999-12-31'    => array('9999-12-31'),
            '0000-00-00'    => array('0000-00-00'),
            '2013-02-29'    => array('2013-02-29'),
            'string-true'   => array('true'),
            'string-false'  => array('false'),
            'bool-true'     => array(true),
            'bool-false'    => array(false),
            'empty-string'  => array(''),
            'empty-array'   => array(array())
        );
    }

}