<?php
 /*
 * This file is part of the Dive ORM framework.
 * (c) Steffen Zeidler <sigma_z@sigma-scripts.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dive\Console\Command;

use Dive\Console\OutputWriterInterface;

/**
 * Class ListCommand
 * @author  Steffen Zeidler <sigma_z@sigma-scripts.de>
 */
class ListCommand extends Command
{

    /**
     * constructor
     */
    public function __construct()
    {
        $this->description = 'List of available commands';
    }


    /**
     * @internal param \Dive\Console\OutputWriterInterface $outputWriter
     * @return bool
     */
    public function execute()
    {
        $commandLoader = $this->console->getCommandLoader();
        $classes = $commandLoader->getCommandClasses();
        $this->writeLine(
            'Command list (' . count($classes) . ')', OutputWriterInterface::LEVEL_LESS_INFO
        );
        foreach ($classes as $className) {
            $name = $this->getNameByClassName($className);
            $this->writeLine("  - $name", OutputWriterInterface::LEVEL_LESS_INFO);
        }
        return true;
    }

}
