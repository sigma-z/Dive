<?php
 /*
 * This file is part of the Dive ORM framework.
 * (c) Steffen Zeidler <sigma_z@sigma-scripts.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dive\Console\Command;

use Dive\Console\ConsoleException;

/**
 * @author Steffen Zeidler <sigma_z@sigma-scripts.de>
 * Date: 19.03.14
 */
interface CommandLoaderInterface
{

    /**
     * @param  string $commandName
     * @throws ConsoleException
     * @return Command
     */
    public function createCommand($commandName);


    /**
     * @param  string $commandName
     * @return null|string
     */
    public function getCommandClass($commandName);


    /**
     * @return array
     */
    public function getCommandClasses();
}
