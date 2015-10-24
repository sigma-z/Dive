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
 * @created 25.07.14
 */
class EnumOrmDataTypeTest extends TestCase
{

    /** @var string */
    protected $type = DataTypeMapper::OTYPE_ENUM;


    /**
     * @return array[]
     */
    public function provideValidationSucceeds()
    {
        return array(
            'enum-numbers-as-strings' => array(
                'value' => '123',
                'field' => array(
                    'values' => array('123', '234', '345')
                )
            ),
            'enum-strings' => array(
                'value' => 'hello world',
                'field' => array(
                    'values' => array('abcd', 'hello world', 'dcba')
                )
            )
        );
    }


    /**
     * @return array[]
     */
    public function provideValidationFails()
    {
        return array(
            'enum-numbers-as-strings' => array(
                'value' => '123',
                'field' => array(
                    'values' => array('132', '234', '345')
                )
            ),
            'enum-strings' => array(
                'value' => 'HELLO WORLD',
                'field' => array(
                    'values' => array('abcd', 'hello world', 'dcba')
                )
            )
        );
    }


    /**
     * NOTE: this test does not make any real sense, correct length has been validated by type validation already
     *
     * @return array[]
     */
    public function provideLengthValidation()
    {
        return array(
            array(
                'value' => 'lorem ipsum',
                'field' => array(
                    'length' => 11,
                    'values' => array('abc', 'lorem ipsum', 'hello world')
                ),
                'expected' => true
            ),
        );
    }


}
