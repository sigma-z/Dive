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
use Dive\Console\Command\CommandLoader;
use Dive\Console\Command\CommandLoaderInterface;

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

    /** @var CommandLoaderInterface */
    protected $commandLoader;

    /** @var OutputWriterInterface */
    protected $outputWriter;


    /**
     * @param OutputWriterInterface $outputWriter
     */
    public function __construct(OutputWriterInterface $outputWriter = null)
    {
        $this->outputWriter = $outputWriter;
    }


    /**
     * @param Command $command
     */
    public function setCommand(Command $command)
    {
        $command->setConsole($this);
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


    /**
     * @param CommandLoaderInterface $commandLoader
     */
    public function setCommandLoader(CommandLoaderInterface $commandLoader)
    {
        $this->commandLoader = $commandLoader;
    }


    /**
     * @return CommandLoaderInterface
     */
    public function getCommandLoader()
    {
        if (!$this->commandLoader) {
            $this->commandLoader = new CommandLoader();
        }
        return $this->commandLoader;
    }


    /**
     * @param OutputWriterInterface $outputWriter
     */
    public function setOutputWriter(OutputWriterInterface $outputWriter)
    {
        $this->outputWriter = $outputWriter;
    }


    /**
     * @return OutputWriterInterface
     */
    public function getOutputWriter()
    {
        if (!$this->outputWriter) {
            $this->outputWriter = new OutputWriter();
        }
        return $this->outputWriter;
    }


    /**
     * @param array $arguments
     */
    public function run(array $arguments)
    {
        try {
            $this->setArguments($arguments);
            $commandName = 'list';
            if (isset($this->arguments[0])) {
                $commandName = array_shift($this->arguments);
            }
            $this->processCommand($commandName);
            $outputWriter = $this->getOutputWriter();
            $success = $this->command->execute($outputWriter);
            if ($success === true) {
                $outputWriter->writeLine(
                    "Command '$commandName' has run successfully",
                    OutputWriterInterface::LEVEL_VERBOSE
                );
            }
            else {
                $outputWriter->writeLine(
                    "Run of command '$commandName' has FAILED!",
                    OutputWriterInterface::LEVEL_LESS_INFO
                );
            }
        }
        catch (\Exception $e) {
            $this->handleException($e);
        }
    }


    /**
     * @param string $commandName
     */
    private function processCommand($commandName)
    {
        $command = $this->getCommandLoader()->createCommand($commandName);
        $this->setCommand($command);
        $this->populateCommandParams();
    }


    /**
     * @param \Exception $e
     */
    private function handleException(\Exception $e)
    {
        $outputWriter = $this->getOutputWriter();
        $outputWriter->writeLine(
            'EXCEPTION raised: "' . $e->getMessage() . '"',
            OutputWriterInterface::LEVEL_LESS_INFO,
            '>>>'
        );
        $outputWriter->writeLine($e->getTraceAsString(), OutputWriterInterface::LEVEL_LESS_INFO);
    }

}
