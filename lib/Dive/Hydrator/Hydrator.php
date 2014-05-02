<?php
/*
 * This file is part of the Dive ORM framework.
 * (c) Steffen Zeidler <sigma_z@sigma-scripts.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dive\Hydrator;

use \Dive\RecordManager;

/**
 * @author Steffen Zeidler <sigma_z@sigma-scripts.de>
 * Date: 11.02.13
 */
abstract class Hydrator implements HydratorInterface
{

    /**
     * @var \PDOStatement
     */
    protected $statement;
    /**
     * @var \Dive\RecordManager
     */
    protected $recordManager;


    /**
     * @param \Dive\RecordManager $recordManager
     */
    public function __construct(RecordManager $recordManager)
    {
        $this->recordManager = $recordManager;
    }


    /**
     * Sets PDO statement
     *
     * @param \PDOStatement $stmt
     */
    public function setStatement(\PDOStatement $stmt)
    {
        $this->statement = $stmt;
    }


    /**
     * Throws missing table exception
     *
     * @param  \Dive\Table $table
     * @throws HydratorException
     */
    public static function throwMissingTableException($table = null)
    {
        $argumentType = is_object($table) ? get_class($table) : gettype($table);
        throw new HydratorException("Hydrator needs table instance! You gave me: " . $argumentType);
    }

}
