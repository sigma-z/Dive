<?php
/*
 * This file is part of the Dive ORM framework.
 * (c) Steffen Zeidler <sigma_z@sigma-scripts.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dive\Hydrator;

use Dive\Record;
use Dive\RecordManager;
use Dive\Table;

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
     * hydrates record
     *
     * @param   Table $table
     * @param   array $row
     * @return  Record
     */
    protected function hydrateRecord(Table $table, array $row)
    {
        if (!($table instanceof Table)) {
            self::throwMissingTableException($table);
        }
        $record = $this->recordManager->getOrCreateRecord($table->getTableName(), $row, true);
        return $record;
    }


    /**
     * Throws missing table exception
     *
     * @param  \Dive\Table $table
     * @throws HydratorException
     */
    protected static function throwMissingTableException($table = null)
    {
        $argumentType = is_object($table) ? get_class($table) : gettype($table);
        throw new HydratorException("Hydrator needs table instance! You gave me: " . $argumentType);
    }

}
