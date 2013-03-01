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
 * Date: 06.02.13
 */

namespace Dive\UnitOfWork;


use Dive\Record;
use Dive\RecordManager;

class UnitOfWork
{

    /**
     * @var RecordManager
     */
    private $rm = null;


    public function __construct(RecordManager $rm)
    {
        $this->rm = $rm;
    }


    /**
     * TODO implement save
     */
    public function saveGraph(Record $record, ChangeSet $changeSet)
    {
        return false;
    }


    /**
     * TODO implement delete
     */
    public function deleteGraph(Record $record, ChangeSet $changeSet)
    {
        return false;
    }

}