<?php
/*
 * This file is part of the Dive ORM framework.
 * (c) Steffen Zeidler <sigma_z@sigma-scripts.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Dive\Test\Record;

use Dive\TestSuite\TestCase;

/**
 * Class RecordFieldValidationTest
 * @author  Steffen Zeidler <sigma_z@sigma-scripts.de>
 * @created 14.07.2014
 */
class RecordFieldValidationTest extends TestCase
{

    public function testRecordFieldValidation()
    {
        $fieldValues = array(
            'id' => 1,
            't_boolean' => 1,
            't_integer_signed' => -1,
            't_integer_unsigned' => 1,
            't_integer_unsigned_zerofilled' => '0001',
            't_decimal_signed' => '1111111111.99',
            't_decimal_unsigned' => '1111111111.99',
            't_string' => 'hello world!',
            't_datetime' => '2014-07-14 10:25:52',
            't_date' => '2014-07-14',
            't_time' => '10:25:52',
            't_timestamp' => '2014-07-14 23:25:52',
            't_blob' => 'hello world!',
            't_enum' => 'abc'
        );
        $rm = self::createDefaultRecordManager();
        $record = $rm->getOrCreateRecord('data_types', $fieldValues);
        $rm->scheduleSave($record)->commit();

        $this->assertTrue($record->exists());
    }

}
