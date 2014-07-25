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
class StringOrmDataTypeTest extends TestCase
{

    /** @var string */
    protected $type = DataTypeMapper::OTYPE_STRING;


    /**
     * @return array[]
     */
    public function provideValidationSucceeds()
    {
        return array(
            'empty-string' => array(''),
            'string-13' => array('13'),
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
            'int-13'        => array(13),
            'empty-array'   => array(array())
        );
    }


    /**
     * @return array[]
     */
    public function provideLengthValidation()
    {
        return array(
            'empty-string' => array(
                'value' => '',
                'field' => array('length' => 13),
                'expected' => true
            ),
            'string-13' => array(
                'value' => '13',
                'field' => array('length' => 13),
                'expected' => true
            ),
            'string'    => array(
                'value' => '13 chars long',
                'field' => array('length' => 13),
                'expected' => true
            ),
            'string-utf8' => array(
                'value' => 'äöüß',
                'field' => array(
                    'length' => 4,
                    'charset' => 'UTF8'
                ),
                'expected' => true
            ),
            'string-to-long' => array(
                'value' => '13 chars long',
                'field' => array('length' => 12),
                'expected' => false
            ),
            'string-utf8-handled-as-latin1' => array(
                'value' => 'äöüß',
                'field' => array('length' => 4, 'charset' => 'Latin1'),
                'expected' => false
            ),
        );
    }

}
