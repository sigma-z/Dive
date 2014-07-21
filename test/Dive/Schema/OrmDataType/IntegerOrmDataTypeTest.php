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
 * Class IntegerOrmDataTest
 * @author  Steffen Zeidler <sigma_z@sigma-scripts.de>
 * @created 11.04.14
 */
class IntegerOrmDataTypeTest extends TestCase
{

    /** @var string */
    protected $type = DataTypeMapper::OTYPE_INTEGER;


    /**
     * @return array[]
     */
    public function provideValidationSucceeds()
    {
        $testCases = parent::provideValidationSucceeds();
        return array_merge($testCases, array(
            'int1'              => array(1),
            'string1'           => array('1'),
            'int0'              => array(0),
            'string0'           => array('0'),
            'negative-int'      => array(-1234),
            'string-negative-int' => array(-1234),
            'string-bigint'     => array('12345678987654321'),
            'string-negative-bigint' => array('-12345678987654321'),
        ));
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


    /**
     * @return array[]
     */
    public function provideLengthValidation()
    {
        return array(
            array(
                'value' => 127,
                'field' => array('length' => 1),
                'expected' => true
            ),
            array(
                'value' => -128,
                'field' => array('length' => 1),
                'expected' => true
            ),
            array(
                'value' => 128,
                'field' => array('length' => 1),
                'expected' => false
            ),
            array(
                'value' => 255,
                'field' => array('length' => 1, 'unsigned' => true),
                'expected' => true
            ),
            array(
                'value' => -1,
                'field' => array('length' => 1, 'unsigned' => true),
                'expected' => false
            ),
            array(
                'value' => 256,
                'field' => array('length' => 1, 'unsigned' => true),
                'expected' => false
            ),
            array(
                'value' => 65536,
                'field' => array('length' => 2, 'unsigned' => true),
                'expected' => false
            ),
            array(
                'value' => 65535,
                'field' => array('length' => 2, 'unsigned' => true),
                'expected' => true
            ),
            array(
                'value' => -32768,
                'field' => array('length' => 2),
                'expected' => true
            ),
            array(
                'value' => -32769,
                'field' => array('length' => 2),
                'expected' => false
            ),
            array(
                'value' => 32767,
                'field' => array('length' => 2),
                'expected' => true
            ),
            array(
                'value' => 32768,
                'field' => array('length' => 2),
                'expected' => false
            ),
        );
    }

}
