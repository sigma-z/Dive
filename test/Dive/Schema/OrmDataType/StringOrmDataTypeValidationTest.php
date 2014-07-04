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
 * Class StringOrmDataTypeValidationTest
 * @author  Steffen Zeidler <sigma_z@sigma-scripts.de>
 * @created 04.07.2014
 */
class StringOrmDataTypeValidationTest extends TestCase
{

    protected $type = DataTypeMapper::OTYPE_STRING;


    /**
     * @return array[]
     */
    public function provideValidationSucceeds()
    {
        $testCases = parent::provideValidationSucceeds();
        return array_merge($testCases, array(
            'empty-string' => array(''),
            'string-12' => array('12'),
            'string'    => array('some kind of string')
        ));
    }


    /**
     * @return array[]
     */
    public function provideValidationFails()
    {
        return array(
            'bool-false'    => array(false),
            'bool-true'     => array(true),
            'int-12'        => array(12),
            'empty-array'   => array(array())
        );
    }

}