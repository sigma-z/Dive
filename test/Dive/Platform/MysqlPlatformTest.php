<?php
 /*
 * This file is part of the Dive ORM framework.
 * (c) Steffen Zeidler <sigma_z@sigma-scripts.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dive\TestSuite\Platform;

use Dive\Platform\MysqlPlatform;
use Dive\Schema\DataTypeMapper\MysqlDataTypeMapper;
use Dive\TestSuite\TestCase;

/**
 * Class MysqlPlatformTest
 *
 * @author  Steffen Zeidler <sigma_z@sigma-scripts.de>
 */
class MysqlPlatformTest extends TestCase
{

    /** @var MysqlPlatform */
    private $platform;


    protected function setUp()
    {
        parent::setUp();

        $dataTypeMapper = new MysqlDataTypeMapper();
        $this->platform = new MysqlPlatform($dataTypeMapper);
    }


    /**
     * @dataProvider provideColumnDefinitionSql
     */
    public function testColumnDefinitionSql(array $definition, $excepted)
    {
        $actual = $this->platform->getColumnDefinitionSql($definition);
        $this->assertEquals($excepted, $actual);
    }


    /**
     * @return array[]
     */
    public function provideColumnDefinitionSql()
    {
        $testCases = array();

        // <editor-fold desc="integer">
        $testCases[] = array(
            'definition' => array(
                'type' => 'integer'
            ),
            'expected' => 'int NOT NULL'
        );
        $testCases[] = array(
            'definition' => array(
                'type' => 'integer',
                'length' => 10
            ),
            'expected' => 'bigint(10) NOT NULL'
        );
        $testCases[] = array(
            'definition' => array(
                'type' => 'integer',
                'length' => 10,
                'default' => 1000
            ),
            'expected' => "bigint(10) DEFAULT 1000 NOT NULL"
        );
        $testCases[] = array(
            'definition' => array(
                'type' => 'integer',
                'length' => 10,
                'unsigned' => true
            ),
            'expected' => 'bigint(10) UNSIGNED NOT NULL'
        );
        // </editor-fold>

        // <editor-fold desc="boolean">
        $testCases[] = array(
            'definition' => array(
                'type' => 'boolean'
            ),
            'expected' => 'boolean NOT NULL'
        );
        // </editor-fold>

        // <editor-fold desc="decimal">
        $testCases[] = array(
            'definition' => array(
                'type' => 'decimal',
            ),
            'expected' => 'decimal NOT NULL'
        );
        $testCases[] = array(
            'definition' => array(
                'type' => 'decimal',
                'length' => 13,
                'scale' => 3
            ),
            'expected' => 'decimal(12,3) NOT NULL'
        );
        $testCases[] = array(
            'definition' => array(
                'type' => 'decimal',
                'length' => 13,
                'scale' => 3,
                'unsigned' => true
            ),
            'expected' => 'decimal(12,3) UNSIGNED NOT NULL'
        );
        // </editor-fold>

        // <editor-fold desc="string">
        $testCases[] = array(
            'definition' => array(
                'type' => 'string',
                'length' => 255,
                'nullable' => true
            ),
            'expected' => 'varchar(255)'
        );
        // </editor-fold>

        // <editor-fold desc="date and time">
        $testCases[] = array(
            'definition' => array(
                'type' => 'datetime',
            ),
            'expected' => 'datetime NOT NULL'
        );
        $testCases[] = array(
            'definition' => array(
                'type' => 'date',
            ),
            'expected' => 'date NOT NULL'
        );
        $testCases[] = array(
            'definition' => array(
                'type' => 'time',
            ),
            'expected' => 'char(8) NOT NULL'
        );
        $testCases[] = array(
            'definition' => array(
                'type' => 'timestamp',
            ),
            'expected' => 'timestamp NOT NULL'
        );
        // </editor-fold>

        // <editor-fold desc="blob">
        $testCases[] = array(
            'definition' => array(
                'type' => 'blob',
                'nullable' => true
            ),
            'expected' => 'blob'
        );
        // </editor-fold>

        // <editor-fold desc="enum">
        $testCases[] = array(
            'definition' => array(
                'type' => 'enum',
                'values' => array('abc', '123', '098', 'zyx')
            ),
            'expected' => "enum('abc','123','098','zyx') NOT NULL"
        );
        $testCases[] = array(
            'definition' => array(
                'type' => 'enum',
                'values' => array('abc', '123', '098', 'zyx'),
                'default' => 'zyx'
            ),
            'expected' => "enum('abc','123','098','zyx') DEFAULT 'zyx' NOT NULL"
        );
        // </editor-fold>

        return $testCases;
    }

}
