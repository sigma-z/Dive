<?php
 /*
 * This file is part of the Dive ORM framework.
 * (c) Steffen Zeidler <sigma_z@sigma-scripts.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dive\Console\Command;

/**
 * Class HelpCommand
 * @author  Steffen Zeidler <sigma_z@sigma-scripts.de>
 */
class HelpCommand extends Command
{

    /**
     * constructor
     */
    public function __construct()
    {
        $this->description = 'Help for commands';
        $this->optionalParams = array('command' => 'Name of command');
    }


    /**
     * @internal param \Dive\Console\OutputWriterInterface $outputWriter
     * @return bool
     */
    public function execute()
    {
        $commandName = $this->getParam('command');
        if ($commandName) {
            $usage = $this->getCommandUsage($commandName);
        }
        else {
            $usage = $this->getGeneralUsage();
        }
        $this->writeLine($usage);
        return true;
    }


    /**
     * @param  string $commandName
     * @return string
     */
    private function getCommandUsage($commandName)
    {
        $command = $this->console->getCommandLoader()->createCommand($commandName);
        return $command->getUsage();
    }


    private function getGeneralUsage()
    {
        $scriptName = $_SERVER['SCRIPT_NAME'];
        return
            "USAGE: $scriptName <command> [arg1] [arg2] ...\n\n"
            . "Example commands\n"
            . "  help  Prints usage of dive console\n"
            . "  list  Prints list of available commands\n";
    }
}
