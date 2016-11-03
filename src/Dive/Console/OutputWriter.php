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
 * Class OutputWriter
 * @author  Steffen Zeidler <sigma_z@sigma-scripts.de>
 */
class OutputWriter implements OutputWriterInterface
{

    /** @var string */
    protected $level = self::LEVEL_NORMAL;


    /**
     * @param string $message
     * @param int    $level
     * @param string $prefix
     */
    public function write($message, $level = self::LEVEL_NORMAL, $prefix = '')
    {
        if ($level > $this->level) {
            return;
        }

        if ($prefix) {
            $prefix .= ' ';
        }
        echo $prefix . $message;
    }


    /**
     * @param string $message
     * @param int    $level
     * @param string $prefix
     */
    public function writeLine($message, $level = self::LEVEL_NORMAL, $prefix = '')
    {
        $this->write($message . "\n", $level, $prefix);
    }


    /**
     * @param int $level
     */
    public function setOutputLevel($level)
    {
        $this->level = $level;
    }
}
