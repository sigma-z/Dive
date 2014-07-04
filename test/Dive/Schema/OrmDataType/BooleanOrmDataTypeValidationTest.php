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
 * Class BooleanDataTypeValidationTest
 * @author  Steffen Zeidler <sigma_z@sigma-scripts.de>
 * @created 04.07.2014
 */
class BooleanOrmDataTypeValidationTest extends TestCase
{

    protected $type = DataTypeMapper::OTYPE_BOOLEAN;


    /**
     * @return array[]
     */
    public function provideValidationSucceeds()
    {
        $testCases = parent::provideValidationSucceeds();
        return array_merge($testCases, array(
            'int1'       => array(1),
            'string1'    => array('1'),
            'bool-true'  => array(true),
            'int0'       => array(0),
            'string0'    => array('0'),
            'bool-false' => array(false),
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
            'empty-array'   => array(array())
        );
    }
}