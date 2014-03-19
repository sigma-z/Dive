<?php
 /*
 * This file is part of the Dive ORM framework.
 * (c) Steffen Zeidler <sigma_z@sigma-scripts.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


namespace Dive\Console;

use Dive\Console\Command\Command;

/**
 * Class Console
 * @author  Steffen Zeidler <sigma_z@sigma-scripts.de>
 */
class Console
{

    /** @var Command */
    protected $command;

    /** @var array */
    protected $arguments = array();


    /**
     * @param Command $command
     */
    public function setCommand(Command $command)
    {
        $this->command = $command;
    }


    /**
     * @param array $arguments
     */
    public function setArguments(array $arguments)
    {
        $this->arguments = $arguments;
    }


    public function populateCommandParams()
    {
        if (!$this->command) {
            throw new ConsoleException("Missing command to populate command params!");
        }
        $params = $this->parseArguments();
        $this->command->setParams($params);
    }


    /**
     * @return array
     */
    private function parseArguments()
    {
        $paramNameIndex = 0;
        $paramNames = array_keys(
            array_merge($this->command->getRequiredParams(), $this->command->getOptionalParams())
        );
        $useRequiredParamsForPopulation = true;
        $arguments = $this->arguments;
        $params = array();
        $currentParamName = '';
        while ($arguments) {
            $argument = array_shift($arguments);
            if (is_string($argument) && $argument !== '' && substr($argument, 0, 2) == '--') {
                $paramName = substr($argument, 2);
                if (in_array($paramName, $paramNames)) {
                    $params[$paramName] = true;
                    $currentParamName = $paramName;
                }
                $useRequiredParamsForPopulation = false;
            }
            else if ($currentParamName) {
                $params[$currentParamName] = $argument;
                $currentParamName = '';
            }
            else if ($useRequiredParamsForPopulation && isset($paramNames[$paramNameIndex])) {
                $paramName = $paramNames[$paramNameIndex];
                $params[$paramName] = $argument;
                $paramNameIndex++;
                $currentParamName = '';
            }
        }
        return $params;
    }
}
