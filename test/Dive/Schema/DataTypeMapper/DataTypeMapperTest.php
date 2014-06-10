<?php
/*
 * This file is part of the Dive ORM framework.
 * (c) Steffen Zeidler <sigma_z@sigma-scripts.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dive\Test\Schema\DataTypeMapper;

use Dive\Schema\DataTypeMapper\DataTypeMapper;

/**
 * @author Steffen Zeidler <sigma_z@sigma-scripts.de>
 * Date: 05.11.12
 */
class DataTypeMapperTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var DataTypeMapper
     */
    private $dataTypeMapper;


    protected function setUp()
    {
        parent::setUp();

        $mapping = array(
            'varchar'    => DataTypeMapper::OTYPE_STRING,
            'text'       => DataTypeMapper::OTYPE_STRING,

            'tinyint'    => DataTypeMapper::OTYPE_INTEGER,
            'smallint'   => DataTypeMapper::OTYPE_INTEGER,
            'mediumint'  => DataTypeMapper::OTYPE_INTEGER,
            'int'        => DataTypeMapper::OTYPE_INTEGER,
            'bigint'     => DataTypeMapper::OTYPE_INTEGER,

            'tinyblob'   => DataTypeMapper::OTYPE_BLOB,
            'blob'       => DataTypeMapper::OTYPE_BLOB,
            'mediumblob' => DataTypeMapper::OTYPE_BLOB,
            'longblob'   => DataTypeMapper::OTYPE_BLOB,

            'datetime'   => DataTypeMapper::OTYPE_DATETIME
        );
        $lengths = array(
            DataTypeMapper::OTYPE_INTEGER => array(
                'default'   => 'int',
                'unit'      => DataTypeMapper::UNIT_BYTES,
                'types'     => array(
                    'tinyint'   => 1,
                    'smallint'  => 2,
                    'mediumint' => 3,
                    'int'       => 4,
                    'bigint'    => 8
                )
            ),
            DataTypeMapper::OTYPE_BLOB => array(
                'default' => 'blob'
            ),
            DataTypeMapper::OTYPE_STRING => array(
                'default'   => 'text',
                'unit'      => DataTypeMapper::UNIT_CHARS,
                'types'     => array(
                    'varchar'   => 255
                )
            ),
            DataTypeMapper::OTYPE_DATE => 'date'
        );
        $this->dataTypeMapper = new DataTypeMapper($mapping, $lengths);
    }


    public function testAddDataType()
    {
        $this->dataTypeMapper->addDataType('test', DataTypeMapper::OTYPE_STRING);
        $this->assertTrue($this->dataTypeMapper->hasDataType('test'));
    }


    public function testAddOrmType()
    {
        $this->dataTypeMapper->addOrmType('test', DataTypeMapper::OTYPE_STRING);
        $this->assertTrue($this->dataTypeMapper->hasOrmType('test'));
    }


    public function testRemoveDataType()
    {
        $this->dataTypeMapper->removeDataType('tinyint');
        $this->assertFalse($this->dataTypeMapper->hasDataType('tinyint'));
    }


    public function testRemoveOrmType()
    {
        $this->dataTypeMapper->removeOrmType(DataTypeMapper::OTYPE_INTEGER);
        $this->assertFalse($this->dataTypeMapper->hasOrmType(DataTypeMapper::OTYPE_INTEGER));
    }


    public function testGetDefaultMappedDataType()
    {
        // defined as default by special key '_default'
        $actual = $this->dataTypeMapper->getMappedDataType(DataTypeMapper::OTYPE_BLOB);
        $this->assertEquals('blob', $actual);

        // defined as default by special key '_default'
        $actual = $this->dataTypeMapper->getMappedDataType(DataTypeMapper::OTYPE_STRING);
        $this->assertEquals('text', $actual);

        // not defined, suppose that orm type is equal data type
        $actual = $this->dataTypeMapper->getMappedDataType(DataTypeMapper::OTYPE_DATETIME);
        $this->assertEquals('datetime', $actual);

        // defined as key value
        $actual = $this->dataTypeMapper->getMappedDataType(DataTypeMapper::OTYPE_DATE);
        $this->assertEquals('date', $actual);
    }


    /**
     * @dataProvider provideGetMappedDataType
     */
    public function testGetMappedDataType($length, $ormType, $expected)
    {
        $actual = $this->dataTypeMapper->getMappedDataType($ormType, $length);
        $this->assertEquals($expected, $actual);
    }


    /**
     * @return array[]
     */
    public function provideGetMappedDataType()
    {
        $testCases = array();
        $testCases[] = array(null,  DataTypeMapper::OTYPE_INTEGER, 'int');
        $testCases[] = array(0,     DataTypeMapper::OTYPE_INTEGER, 'int');
        $testCases[] = array(1,     DataTypeMapper::OTYPE_INTEGER, 'tinyint');
        $testCases[] = array(2,     DataTypeMapper::OTYPE_INTEGER, 'tinyint');
        $testCases[] = array(3,     DataTypeMapper::OTYPE_INTEGER, 'smallint');
        $testCases[] = array(4,     DataTypeMapper::OTYPE_INTEGER, 'smallint');
        $testCases[] = array(5,     DataTypeMapper::OTYPE_INTEGER, 'mediumint');
        $testCases[] = array(6,     DataTypeMapper::OTYPE_INTEGER, 'mediumint');
        $testCases[] = array(7,     DataTypeMapper::OTYPE_INTEGER, 'mediumint');
        $testCases[] = array(8,     DataTypeMapper::OTYPE_INTEGER, 'int');
        $testCases[] = array(9,     DataTypeMapper::OTYPE_INTEGER, 'int');
        $testCases[] = array(10,    DataTypeMapper::OTYPE_INTEGER, 'bigint');
        $testCases[] = array(19,    DataTypeMapper::OTYPE_INTEGER, 'bigint');
        $testCases[] = array(20,    DataTypeMapper::OTYPE_INTEGER, 'bigint');
        $testCases[] = array(255,   DataTypeMapper::OTYPE_STRING,  'varchar');
        $testCases[] = array(300,   DataTypeMapper::OTYPE_STRING,  'text');
        $testCases[] = array(null,  DataTypeMapper::OTYPE_BLOB,    'blob');

        return $testCases;
    }

}
