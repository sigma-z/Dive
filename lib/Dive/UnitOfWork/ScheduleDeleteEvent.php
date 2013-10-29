<?php
 /*
 * This file is part of the Dive ORM framework.
 * (c) Steffen Zeidler <sigma_z@sigma-scripts.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Dive\UnitOfWork;

use Dive\Event\Event;
use Dive\RecordManager;


/**
 * @author  Steven Nikolic <steven@nindoo.de>
 * @created 29.10.13 2013
 */
class ScheduleDeleteEvent extends Event
{

    /**
     * @var RecordManager
     */
    private $recordManager = null;


    /**
     * @param RecordManager $recordManager
     */
    public function __construct(RecordManager $recordManager)
    {
        $this->recordManager = $recordManager;
    }





} 