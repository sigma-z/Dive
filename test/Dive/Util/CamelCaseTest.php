<?php
/*
 * This file is part of the Dive ORM framework.
 * (c) Steffen Zeidler <sigma_z@sigma-scripts.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
/**
 * @author Steffen Zeidler <sigma_z@sigma-scripts.de>
 * Date: 31.10.12
 */
namespace Dive\Test\Util;

use Dive\Util\CamelCase;

class CamelCaseTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @dataProvider provideToCamelCase
     *
     * @param $string
     * @param $expected
     */
    public function testToCamelCase($string, $expected)
    {
        $actual = CamelCase::toCamelCase($string);
        $this->assertEquals($expected, $actual);
    }


    public function provideToCamelCase()
    {
        $testCases = array();

        $testCases[] = array('test', 'Test');
        $testCases[] = array('hello world', 'HelloWorld');
        $testCases[] = array('hello123world', 'Hello123world');
        $testCases[] = array('hello-1-2-3-world', 'Hello123World');
        $testCases[] = array('hello-a-b-c-world', 'HelloABCWorld');
        $testCases[] = array('HELLO-A-B-C-World', 'HelloABCWorld');
        $testCases[] = array('HELLO-ABC-World', 'HelloAbcWorld');
        $testCases[] = array('HELLO_ABC_World', 'HelloAbcWorld');
        $testCases[] = array('Hello_Abc_World', 'HelloAbcWorld');
        $testCases[] = array('Hello_123_World', 'Hello123World');

        return $testCases;
    }


    /**
     * @dataProvider provideToLowerCaseSeparateWith
     *
     * @param string $string
     * @param string $separator
     * @param string $expected
     */
    public function testToLowerCaseSeparateWith($string, $separator, $expected)
    {
        $actual = CamelCase::toLowerCaseSeparateWith($string, $separator);
        $this->assertEquals($expected, $actual);
    }


    public function provideToLowerCaseSeparateWith()
    {
        $testCases = array();

        $testCases[] = array('Test', '_', 'test');
        $testCases[] = array('HelloWorld', ' ', 'hello world');
        $testCases[] = array('Hello123world', '.', 'hello123world');
        $testCases[] = array('Hello123World', '-', 'hello123-world');
        $testCases[] = array('HelloABCWorld', '-', 'hello-a-b-c-world');

        return $testCases;
    }

}