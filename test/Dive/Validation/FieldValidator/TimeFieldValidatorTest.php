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
 * Class TimeFieldValidatorTest
 * @author  Steffen Zeidler <sigma_z@sigma-scripts.de>
 * @created 25.04.2014
 */
class TimeFieldValidatorTest extends TestCase
{

    /**
     * @dataProvider provideValidationSucceeds
     */
    public function testValidationSucceeds($value)
    {
        $this->givenIHaveAFieldTypeValidatorWithType('TimeFieldValidator');
        $this->whenIValidateValue($value);
        $this->thenValidationShouldSucceed();
    }


    /**
     * @dataProvider provideValidationFails
     */
    public function testValidationFails($value)
    {
        $this->givenIHaveAFieldTypeValidatorWithType('TimeFieldValidator');
        $this->whenIValidateValue($value);
        $this->thenValidationShouldFail();
    }


    /**
     * @return array[]
     */
    public function provideValidationSucceeds()
    {
        return array(
            '12:23:34' => array('12:23:34'),
            '23:59:59' => array('23:59:59'),
            '00:00:00' => array('00:00:00'),
        );
    }


    /**
     * @return array[]
     */
    public function provideValidationFails()
    {
        $cases = array();
        $cases['string'] = array('string');
        $cases['2014-04-14'] = array('2014-04-14');
        $cases['000:000:000'] = array('000:000:000');
        if (version_compare(PHP_VERSION, '5.3.9', '>')) {
            // behavior introduced in 5.3.9 is desired, see http://3v4l.org/L1A7f
            $cases['0:0:0'] = array('0:0:0');
            $cases['1:1:0'] = array('1:1:0');
        }
        $cases['24:00:00'] = array('24:00:00');
        $cases['23:60:00'] = array('23:60:00');
        $cases['23:00:60'] = array('23:00:60');
        $cases['string-true'] = array('true');
        $cases['string-false'] = array('false');
        $cases['bool-true'] = array(true);
        $cases['bool-false'] = array(false);
        $cases['empty-string'] = array('');
        $cases['empty-array'] = array(array());
        return $cases;
    }

}
