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
 * @created 04.07.2014
 */
class BooleanOrmDataTypeTest extends TestCase
{

    /** @var string */
    protected $type = DataTypeMapper::OTYPE_BOOLEAN;


    /**
     * @return array[]
     */
    public function provideValidationSucceeds()
    {
        return array(
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


    /**
     * @return array[]
     */
    public function provideLengthValidation()
    {
        return array(
            'int-0' => array(
                'value' => 0,
                'field' => array(),
                'expected' => true
            ),
            'int-1' => array(
                'value' => 1,
                'field' => array(),
                'expected' => true
            ),
            'string-0' => array(
                'value' => '0',
                'field' => array(),
                'expected' => true
            ),
            'string-1' => array(
                'value' => '1',
                'field' => array(),
                'expected' => true
            ),
            'bool-true' => array(
                'value' => true,
                'field' => array(),
                'expected' => true
            ),
            'bool-false' => array(
                'value' => false,
                'field' => array(),
                'expected' => true
            ),
            'empty-string'  => array(
                'value' => '',
                'field' => array(),
                'expected' => false
            ),
        );
    }

}
