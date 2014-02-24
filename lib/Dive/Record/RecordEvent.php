<?php
 /*
 * This file is part of the Dive ORM framework.
 * (c) Steffen Zeidler <sigma_z@sigma-scripts.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


namespace Dive\Record;

use Dive\Event\Event;
use Dive\Record;

/**
 * Class RecordEvent
 *
 * @author  Steffen Zeidler <sigma_z@sigma-scripts.de>
 */
class RecordEvent extends Event
{

    /** @var \Dive\Record */
    private $record;


    /**
     * @param Record $record
     */
    public function __construct(Record $record)
    {
        $this->record = $record;
    }

    /**
     * @return Record
     */
    public function getRecord()
    {
        return $this->record;
    }

}
