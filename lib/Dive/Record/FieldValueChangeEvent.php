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
 * @author  Steffen Zeidler <sigma_z@sigma-scripts.de>
 * @created 17.12.13
 */
class FieldValueChangeEvent extends Event
{
    /** @var \Dive\Record */
    private $record;

    /** @var string */
    private $fieldName;

    /** @var mixed */
    private $newValue;

    /** @var mixed */
    private $oldValue;



    public function __construct(Record $record, $fieldName, $newValue, $oldValue)
    {
        $this->record = $record;
        $this->fieldName = $fieldName;
        $this->newValue = $newValue;
        $this->oldValue = $oldValue;
    }


    /**
     * @return Record
     */
    public function getRecord()
    {
        return $this->record;
    }


    /**
     * @return string
     */
    public function getFieldName()
    {
        return $this->fieldName;
    }


    /**
     * @return mixed
     */
    public function getNewValue()
    {
        return $this->newValue;
    }


    /**
     * @return mixed
     */
    public function getOldValue()
    {
        return $this->oldValue;
    }
}
