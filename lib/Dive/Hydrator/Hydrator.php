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
 * Date: 11.02.13
 */

namespace Dive\Hydrator;

use \Dive\RecordManager;


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
     * @param \PDOStatement $stmt
     */
    public function setStatement(\PDOStatement $stmt)
    {
        $this->statement = $stmt;
    }

}
