<?php
 /*
 * This file is part of the Dive ORM framework.
 * (c) Steffen Zeidler <sigma_z@sigma-scripts.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


namespace Dive\Test\Console;

use Dive\Console\Command\Command;
use Dive\Console\Console;
use PHPUnit\Framework\TestCase;

/**
 * Class ConsoleTest
 * @author  Steffen Zeidler <sigma_z@sigma-scripts.de>
 */
class ConsoleTest extends TestCase
{

    /** @var Console */
    private $console;

    /** @var Command */
    private $command;


    /**
     * @dataProvider providePopulateCommandParams
     */
    public function testPopulateCommandParams(
        array $arguments,
        array $cmdRequiredParams,
        array $cmdOptionalParams,
        array $expectedParams
    ) {
        $properties = array('requiredParams' => $cmdRequiredParams, 'optionalParams' => $cmdOptionalParams);

        $this->givenConsole($arguments);
        $this->givenCommand($properties);
        $this->whenPopulatingCommandParams($arguments);
        $this->thenExceptPopulatedParams($expectedParams);
    }


    /**
     * @return array[]
     */
    public function providePopulateCommandParams()
    {
        $testCases = array();

        $testCases[] = array(
            'arguments' => array(),
            'cmdRequiredParams' => array(),
            'cmdOptionalParams' => array(),
            'expectedParams' => array()
        );

        $testCases[] = array(
            'arguments' => array('test123'),
            'cmdRequiredParams' => array('test' => 'Test required param'),
            'cmdOptionalParams' => array(),
            'expectedParams' => array('test' => 'test123')
        );

        $testCases[] = array(
            'arguments' => array('--test', 'test123'),
            'cmdRequiredParams' => array('test' => 'Test required param'),
            'cmdOptionalParams' => array(),
            'expectedParams' => array('test' => 'test123')
        );

        $testCases[] = array(
            'arguments' => array('-test', 'test123'),
            'cmdRequiredParams' => array('test' => 'Test required param'),
            'cmdOptionalParams' => array(),
            'expectedParams' => array('test' => '-test')
        );

        $testCases[] = array(
            'arguments' => array('test', 'test123'),
            'cmdRequiredParams' => array('test' => 'Test required param'),
            'cmdOptionalParams' => array('optional' => 'Test optional param'),
            'expectedParams' => array('test' => 'test', 'optional' => 'test123')
        );

        $testCases[] = array(
            'arguments' => array('--optional', 'test123'),
            'cmdRequiredParams' => array(),
            'cmdOptionalParams' => array(
                'someParamName' => 'Test optional param',
                'optional' => 'Test optional param'
            ),
            'expectedParams' => array('optional' => 'test123')
        );

        $testCases[] = array(
            'arguments' => array('abcd', '--optional', 'test123'),
            'cmdRequiredParams' => array(),
            'cmdOptionalParams' => array('optional' => 'Test optional param'),
            'expectedParams' => array('optional' => 'test123')
        );

        $testCases[] = array(
            'arguments' => array('abcd', '--optional', 'test123'),
            'cmdRequiredParams' => array(),
            'cmdOptionalParams' => array(
                'optionalOne' => 'Test optional param',
                'optionalTwo' => 'Test optional param',
                'optionalThree' => 'Test optional param'
            ),
            'expectedParams' => array('optionalOne' => 'abcd')
        );

        $testCases[] = array(
            'arguments' => array('1', '2', '3', '4', '--optionalTwo'),
            'cmdRequiredParams' => array('param1' => '', 'param2' => '', 'param3' => ''),
            'cmdOptionalParams' => array(
                'optionalOne' => 'Test optional param',
                'optionalTwo' => 'Test optional param',
                'optionalThree' => 'Test optional param'
            ),
            'expectedParams' => array(
                'param1' => '1',
                'param2' => '2',
                'param3' => '3',
                'optionalOne' => '4',
                'optionalTwo' => true
            )
        );

        $testCases[] = array(
            'arguments' => array('--flag'),
            'cmdRequiredParams' => array('flag' => ''),
            'cmdOptionalParams' => array(),
            'expectedParams' => array('flag' => true)
        );

        return $testCases;
    }


    /**
     * @expectedException \Dive\Console\ConsoleException
     * @dataProvider providePopulateCommandParamsThrowsMissingRequiredParameterException
     * @param array $arguments
     * @param array $cmdRequiredParams
     */
    public function testPopulateCommandParamsThrowsMissingRequiredParameterException(
        array $arguments,
        array $cmdRequiredParams
    ) {
        $properties = array('requiredParams' => $cmdRequiredParams);

        $this->givenConsole($arguments);
        $this->givenCommand($properties);
        $this->whenPopulatingCommandParams($arguments);
    }


    /**
     * @return array[]
     */
    public function providePopulateCommandParamsThrowsMissingRequiredParameterException()
    {
        $testCases = array();

        $testCases[] = array(
            'arguments' => array(),
            'cmdRequiredParams' => array('param1' => ''),
        );

        $testCases[] = array(
            'arguments' => array('1', '2'),
            'cmdRequiredParams' => array('param1' => '', 'param2' => '', 'param3' => ''),
        );

        $testCases[] = array(
            'arguments' => array('--optionalParam', 'test', '1', '2'),
            'cmdRequiredParams' => array('param1' => '', 'param2' => '', 'param3' => ''),
        );

        return $testCases;
    }


    private function givenConsole()
    {
        $this->console = new Console();
    }


    /**
     * @param array $properties
     */
    private function givenCommand(array $properties)
    {
        $this->command = $this->getMockForAbstractClass(Command::class);
        $reflectionCommand = new \ReflectionClass($this->command);
        foreach ($properties as $name => $value) {
            $property = $reflectionCommand->getProperty($name);
            $property->setAccessible(true);
            $property->setValue($this->command, $value);
        }

        $this->console->setCommand($this->command);
    }


    /**
     * @param array $arguments
     */
    private function whenPopulatingCommandParams(array $arguments)
    {
        $this->console->setArguments($arguments);
        $this->console->populateCommandParams();
    }


    /**
     * @param array $expectedParams
     */
    private function thenExceptPopulatedParams(array $expectedParams)
    {
        $this->assertEquals($expectedParams, $this->command->getParams());
    }
}
