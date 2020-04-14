<?php
/*
 * This file is part of the Dive ORM framework.
 * (c) Steffen Zeidler <sigma_z@sigma-scripts.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dive\Test\Util;

use PHPUnit\Framework\TestCase;

/**
 * @author Steffen Zeidler <sigma_z@sigma-scripts.de>
 * Date: 31.10.12
 */

class VarExportTest extends TestCase
{

    /**
     * @dataProvider provideExport
     *
     * @param string $var
     * @param array  $options
     * @param string $expected
     */
    public function testExport($var, $options, $expected)
    {
        $actual = \Dive\Util\VarExport::export($var, $options);
        $this->assertEquals($expected, $actual);
    }


    public function provideExport()
    {
        $testCases = array();

        $testCases[] = array(
            '',
            array(),
            "''"
        );

        $testCases[] = array(
            true,
            array(),
            "true"
        );

        $testCases[] = array(
            'test',
            array(),
            "'test'"
        );

        $testCases[] = array(
            array(1,2,3),
            array(),
            "array(\n    1,\n    2,\n    3,\n)"
        );

        $testCases[] = array(
            array(1 => 'huhu', 2 => 'abc', 3 => 'xyz'),
            array(),
            "array(\n    'huhu',\n    'abc',\n    'xyz',\n)"
        );

        $testCases[] = array(
            array(1 => 'huhu', 2 => 'abc', 3 => 'xyz'),
            array('exportNumIndexes' => true),
            "array(\n    1 => 'huhu',\n    2 => 'abc',\n    3 => 'xyz',\n)"
        );

        $testCases[] = array(
            array(
                1 => array(
                    'a',
                    3 => 'test',
                    'a' => 'b'
                ),
                2 => array(NULL, array()),
                3 => null
            ),
            array('exportNumIndexes' => true),
            "array(\n"
                . "    1 => array(\n"
                . "        0 => 'a',\n"
                . "        3 => 'test',\n"
                . "        'a' => 'b',\n"
                . "    ),\n"
                . "    2 => array(\n"
                . "        0 => NULL,\n"
                . "        1 => array(),\n"
                . "    ),\n"
                . "    3 => NULL,\n"
                . ")"
        );

        $testCases[] = array(
            array(array(array())),
            array(),
            "array(\n"
                . "    array(\n"
                . "        array(),\n"
                . "    ),\n"
                . ")"
        );

        $testCases[] = array(
            array(array(array())),
            array('removeLastComma' => true),
            "array(\n"
                . "    array(\n"
                . "        array()\n"
                . "    )\n"
                . ")"
        );

        $testCases[] = array(
            array('a' => array('a' => array('a' => 'b', 'b' => 'c'))),
            array(),
            "array(\n"
                . "    'a' => array(\n"
                . "        'a' => array(\n"
                . "            'a' => 'b',\n"
                . "            'b' => 'c',\n"
                . "        ),\n"
                . "    ),\n"
                . ")"
        );

        return $testCases;
    }

}

