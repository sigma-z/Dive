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
 * Class DateFieldValidatorTest
 * @author  Steffen Zeidler <sigma_z@sigma-scripts.de>
 * @created 25.04.2014
 */
class DateOrmDataTypeTest extends TestCase
{

    /** @var string */
    protected $type = DataTypeMapper::OTYPE_DATE;


    /**
     * @return array[]
     */
    public function provideValidationSucceeds()
    {
        $testCases = parent::provideValidationSucceeds();
        return array_merge($testCases, array(
            '2014-04-14' => array('2014-04-14'),
            '9999-12-31' => array('9999-12-31'),
        ));
    }


    /**
     * @return array[]
     */
    public function provideValidationFails()
    {
        return array(
            'string'        => array('string'),
            '0000-00-00'    => array('0000-00-00'),
            '2013-02-29'    => array('2013-02-29'),
            '2013-02-28 12:23:34' => array('2013-02-28 12:23:34'),
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
            '2014-04-14' => array(
                'value' => '2014-04-14',
                'field' => array(),
                'expected' => true
            ),
            '9999-12-31' => array(
                'value' => '9999-12-31',
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
