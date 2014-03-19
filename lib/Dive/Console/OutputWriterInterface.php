<?php
 /*
 * This file is part of the Dive ORM framework.
 * (c) Steffen Zeidler <sigma_z@sigma-scripts.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dive\Console;

/**
 * @author Steffen Zeidler <sigma_z@sigma-scripts.de>
 * Date: 19.03.14
 */
interface OutputWriterInterface
{

    const LEVEL_LESS_INFO = 0;
    const LEVEL_NORMAL    = 1;
    const LEVEL_VERBOSE   = 2;
    const LEVEL_DEBUG     = 3;


    /**
     * @param string $message
     * @param int    $level
     * @param string $prefix
     */
    public function write($message, $level = self::LEVEL_NORMAL, $prefix = '');


    /**
     * @param string $message
     * @param int    $level
     * @param string $prefix
     */
    public function writeLine($message, $level = self::LEVEL_NORMAL, $prefix = '');


    /**
     * @param int $level
     */
    public function setOutputLevel($level);

}
