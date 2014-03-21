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
            'varchar'   => 'string',
            'text'      => 'string',

            'tinyint'   => 'integer',
            'smallint'  => 'integer',
            'mediumint' => 'integer',
            'int'       => 'integer',
            'bigint'    => 'integer',

            'tinyblob'  => 'blob',
            'blob'      => 'blob',
            'mediumblob' => 'blob',
            'longblob'  => 'blob',

            'datetime'  => 'datetime'
        );
        $lengths = array(
            'integer' => array(
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
            'blob' => array(
                'default' => 'blob'
            ),
            'string'    => array(
                'default'   => 'text',
                'unit'      => DataTypeMapper::UNIT_CHARS,
                'types'     => array(
                    'varchar'   => 255
                )
            ),
            'date' => 'date'
        );
        $this->dataTypeMapper = new DataTypeMapper($mapping, $lengths);
    }


    public function testAddDataType()
    {
        $this->dataTypeMapper->addDataType('test', 'string');
        $this->assertTrue($this->dataTypeMapper->hasDataType('test'));
    }


    public function testAddOrmType()
    {
        $this->dataTypeMapper->addOrmType('test', 'string');
        $this->assertTrue($this->dataTypeMapper->hasOrmType('test'));
    }


    public function testRemoveDataType()
    {
        $this->dataTypeMapper->removeDataType('tinyint');
        $this->assertFalse($this->dataTypeMapper->hasDataType('tinyint'));
    }


    public function testRemoveOrmType()
    {
        $this->dataTypeMapper->removeOrmType('integer');
        $this->assertFalse($this->dataTypeMapper->hasOrmType('integer'));
    }


    public function testGetDefaultMappedDataType()
    {
        // defined as default by special key '_default'
        $actual = $this->dataTypeMapper->getMappedDataType('blob');
        $this->assertEquals('blob', $actual);

        // defined as default by special key '_default'
        $actual = $this->dataTypeMapper->getMappedDataType('string');
        $this->assertEquals('text', $actual);

        // not defined, suppose that orm type is equal data type
        $actual = $this->dataTypeMapper->getMappedDataType('datetime');
        $this->assertEquals('datetime', $actual);

        // defined as key value
        $actual = $this->dataTypeMapper->getMappedDataType('date');
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
        $testCases[] = array(null,  'integer',  'int');
        $testCases[] = array(0,     'integer',  'int');
        $testCases[] = array(1,     'integer',  'tinyint');
        $testCases[] = array(2,     'integer',  'tinyint');
        $testCases[] = array(3,     'integer',  'smallint');
        $testCases[] = array(4,     'integer',  'smallint');
        $testCases[] = array(5,     'integer',  'mediumint');
        $testCases[] = array(6,     'integer',  'mediumint');
        $testCases[] = array(7,     'integer',  'mediumint');
        $testCases[] = array(8,     'integer',  'int');
        $testCases[] = array(9,     'integer',  'int');
        $testCases[] = array(10,    'integer',  'bigint');
        $testCases[] = array(19,    'integer',  'bigint');
        $testCases[] = array(20,    'integer',  'bigint');
        $testCases[] = array(255,   'string',   'varchar');
        $testCases[] = array(300,   'string',   'text');
        $testCases[] = array(null,  'blob',     'blob');

        return $testCases;
    }

}
