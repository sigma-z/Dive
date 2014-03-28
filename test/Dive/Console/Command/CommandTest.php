<?php
 /*
 * This file is part of the Dive ORM framework.
 * (c) Steffen Zeidler <sigma_z@sigma-scripts.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dive\Test\Console\Command;

use Dive\Console\Command\Command;
use Dive\Console\ConsoleException;

/**
 * Class CommandTest
 * @author  Steffen Zeidler <sigma_z@sigma-scripts.de>
 */
class CommandTest extends \PHPUnit_Framework_TestCase
{

    /** @var MockCommand */
    private $command;

    /** @var ConsoleException */
    private $caughtException;


    public function testGetName()
    {
        $this->givenIHaveACommand();
        $this->thenTheNameShouldBe('mock');
    }


    public function testGetUsage()
    {
        $this->givenIHaveACommand();
        $this->givenIHaveRequiredParams(array(
            'paramOne' => 'This is parameter one',
            'paramTwo' => 'This is parameter two'
        ));
        $this->givenIHaveOptionalParams(array(
            'optParamOne' => 'This is optional parameter one',
            'optParamTwo' => 'This is optional parameter two'
        ));
        $usage = <<<USAGE
Mock command description

USAGE: mocked-script.php mock <paramOne> <paramTwo> [<optParamOne>] [<optParamTwo>]

Required params:
  paramOne: This is parameter one
  paramTwo: This is parameter two
Optional params:
  optParamOne: This is optional parameter one
  optParamTwo: This is optional parameter two

USAGE;
        $this->thenTheUsageShouldBe($usage);
    }


    /**
     * @dataProvider provideGetBooleanParam
     * @param string $value
     * @param mixed  $expected
     */
    public function testGetBooleanParam($value, $expected)
    {
        $this->givenIHaveACommand();
        $this->whenISetParam_To('paramOne', $value);
        $this->thenBooleanParam_ShouldBe('paramOne', $expected);
    }


    /**
     * @return array[]
     */
    public function provideGetBooleanParam()
    {
        $testCases = array();
        $testCases[] = array(true, true);
        $testCases[] = array(null, false);
        $testCases[] = array(false, false);
        $testCases[] = array(0, false);
        $testCases[] = array('0', false);
        $testCases[] = array('no', false);
        $testCases[] = array('NO', false);
        $testCases[] = array('no', false);
        $testCases[] = array('yes', true);
        $testCases[] = array('yEs', true);
        $testCases[] = array('YES', true);
        $testCases[] = array('On', true);
        $testCases[] = array('1', true);
        $testCases[] = array(1, true);

        return $testCases;
    }


    public function testSetParamsThrowsExceptionOnMissingRequiredParameter()
    {
        $this->givenIHaveACommand();
        $this->givenIHaveRequiredParams(array(
            'paramOne' => 'This is parameter one',
            'paramTwo' => 'This is parameter two'
        ));
        $this->whenITryToSetParams(array('paramTwo' => '123'));
        $this->thenAnExceptionShouldBeThrown();
    }


    private function givenIHaveACommand()
    {
        $this->command = new MockCommand();
    }


    /**
     * @param string $name
     */
    private function thenTheNameShouldBe($name)
    {
        $this->assertEquals($name, $this->command->getName());
    }


    /**
     * @param array $params
     */
    private function givenIHaveRequiredParams(array $params)
    {
        $this->command->setRequiredParams($params);
    }


    /**
     * @param array $params
     */
    private function givenIHaveOptionalParams(array $params)
    {
        $this->command->setOptionalParams($params);
    }


    /**
     * @param string $usage
     */
    private function thenTheUsageShouldBe($usage)
    {
        $this->assertEquals($usage, $this->command->getUsage());
    }


    /**
     * @param string $paramName
     * @param mixed  $value
     */
    private function whenISetParam_To($paramName, $value)
    {
        $this->command->setParam($paramName, $value);
    }


    /**
     * @param string $paramName
     * @param bool   $expected
     */
    private function thenBooleanParam_ShouldBe($paramName, $expected)
    {
        $this->assertEquals($expected, $this->command->getBooleanParam($paramName));
    }


    /**
     * @param array $params
     */
    private function whenITryToSetParams(array $params)
    {
        try {
            $this->command->setParams($params);
        }
        catch (ConsoleException $e) {
            $this->caughtException = $e;
        }
    }


    private function thenAnExceptionShouldBeThrown()
    {
        $this->assertInstanceOf('\Dive\Console\ConsoleException', $this->caughtException);
    }
}


/**
 * Class Mock_Command
 * @author  Steffen Zeidler <sigma_z@sigma-scripts.de>
 */
class MockCommand extends Command
{

    public function __construct()
    {
        $this->description = 'Mock command description';
    }


    /**
     * @return string
     */
    public function getScriptName()
    {
        return 'mocked-script.php';
    }


    /**
     * @param array $params
     */
    public function setRequiredParams(array $params)
    {
        $this->requiredParams = $params;
    }


    /**
     * @param array $params
     */
    public function setOptionalParams(array $params)
    {
        $this->optionalParams = $params;
    }


    /**
     * @internal param \Dive\Console\OutputWriterInterface $outputWriter
     * @return bool
     */
    public function execute()
    {
        return true;
    }
}
