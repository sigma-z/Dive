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
        $this->dataTypeMapper = new DataTypeMapper($mapping, $lengths, array());
    }


    public function testAddDataType()
    {
        $this->dataTypeMapper->addDataType('test', DataTypeMapper::OTYPE_STRING);
        $this->assertTrue($this->dataTypeMapper->hasDataType('test'));
    }


    public function testAddOrmType()
    {
        /** @var \Dive\Schema\OrmDataType\OrmDataType $ormDataType */
        $ormDataType = $this->getMockForAbstractClass('\Dive\Schema\OrmDataType\OrmDataType', array('test'));
        $this->dataTypeMapper->addOrmType($ormDataType, DataTypeMapper::OTYPE_STRING);
        $this->assertTrue($this->dataTypeMapper->hasOrmType('test'));
    }


    public function testRemoveDataType()
    {
        $this->dataTypeMapper->removeDataType('tinyint');
        $this->assertFalse($this->dataTypeMapper->hasDataType('tinyint'));
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
     * @param int    $length
     * @param string $ormType
     * @param string $expectedType
     */
    public function testGetMappedDataType($length, $ormType, $expectedType)
    {
        $actual = $this->dataTypeMapper->getMappedDataType($ormType, $length);
        $this->assertEquals($expectedType, $actual);
    }


    /**
     * @return array[]
     */
    public function provideGetMappedDataType()
    {
        return [
            [null,  DataTypeMapper::OTYPE_INTEGER, 'int'],
            [0,     DataTypeMapper::OTYPE_INTEGER, 'int'],
            [1,     DataTypeMapper::OTYPE_INTEGER, 'tinyint'],
            [2,     DataTypeMapper::OTYPE_INTEGER, 'tinyint'],
            [3,     DataTypeMapper::OTYPE_INTEGER, 'tinyint'],
            [4,     DataTypeMapper::OTYPE_INTEGER, 'tinyint'],
            [5,     DataTypeMapper::OTYPE_INTEGER, 'smallint'],
            [6,     DataTypeMapper::OTYPE_INTEGER, 'smallint'],
            [7,     DataTypeMapper::OTYPE_INTEGER, 'smallint'],
            [8,     DataTypeMapper::OTYPE_INTEGER, 'mediumint'],
            [9,     DataTypeMapper::OTYPE_INTEGER, 'mediumint'],
            [10,    DataTypeMapper::OTYPE_INTEGER, 'int'],
            [19,    DataTypeMapper::OTYPE_INTEGER, 'bigint'],
            [20,    DataTypeMapper::OTYPE_INTEGER, 'bigint'],
            [255,   DataTypeMapper::OTYPE_STRING,  'varchar'],
            [300,   DataTypeMapper::OTYPE_STRING,  'text'],
            [null,  DataTypeMapper::OTYPE_BLOB,    'blob'],
        ];
    }

}
