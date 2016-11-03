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
 * Date: 19.03.14
 */

require_once __DIR__ . '/../vendor/autoload.php';

$arguments = $_SERVER['argv'];
array_shift($arguments);

$console = new \Dive\Console\Console();
/** @var \Dive\Console\Command\CommandLoader $commandLoader */
$commandLoader = $console->getCommandLoader();
$commandLoader->addDirectory(__DIR__ . '/../src/Dive/Console/Command', '\\Dive\\Console\\Command');
$console->run($arguments);
