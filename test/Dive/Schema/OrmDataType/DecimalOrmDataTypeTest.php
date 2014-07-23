<?php
/*
 * This file is part of the Dive ORM framework.
 * (c) Steffen Zeidler <sigma_z@sigma-scripts.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Dive\Test\Schema\OrmDataType;

use Dive\Schema\DataTypeMapper\DataTypeMapper;

require_once __DIR__ . '/TestCase.php';

/**
 * @author  Steffen Zeidler <sigma_z@sigma-scripts.de>
 * @created 25.04.2014
 */
class DecimalOrmDataTypeTest extends TestCase
{

    /** @var string */
    protected $type = DataTypeMapper::OTYPE_DECIMAL;


    /**
     * @return array[]
     */
    public function provideValidationSucceeds()
    {
        $testCases = parent::provideValidationSucceeds();
        return array_merge($testCases, array(
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
        ));
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


    /**
     * @return array[]
     */
    public function provideLengthValidation()
    {
        return array(
            array(
                'value' => 99,
                'field' => array('length' => 2),
                'expected' => true
            ),
            array(
                'value' => 100,
                'field' => array('length' => 2),
                'expected' => false
            ),
            array(
                'value' => -99,
                'field' => array('length' => 2),
                'expected' => true
            ),
            array(
                'value' => -100,
                'field' => array('length' => 2),
                'expected' => false
            ),
            array(
                'value' => 0,
                'field' => array('length' => 2, 'unsigned' => true),
                'expected' => true
            ),
            array(
                'value' => -1,
                'field' => array('length' => 2, 'unsigned' => true),
                'expected' => false
            ),
            array(
                'value' => 99.999,
                'field' => array('length' => 6, 'scale' => 3),
                'expected' => true
            ),
            array(
                'value' => -99.999,
                'field' => array('length' => 6, 'scale' => 3),
                'expected' => true
            ),
            array(
                'value' => 100.000,
                'field' => array('length' => 6, 'scale' => 3),
                'expected' => false
            ),
            array(
                'value' => 99.9990,
                'field' => array('length' => 6, 'scale' => 3),
                'expected' => true
            ),
            array(
                'value' => -99.9990,
                'field' => array('length' => 6, 'scale' => 3),
                'expected' => true
            ),
            array(
                'value' => 99.9909,
                'field' => array('length' => 6, 'scale' => 3),
                'expected' => false
            ),
            array(
                'value' => -99.9909,
                'field' => array('length' => 6, 'scale' => 3),
                'expected' => false
            ),
        );
    }

}
