<?php
 /*
 * This file is part of the Dive ORM framework.
 * (c) Steffen Zeidler <sigma_z@sigma-scripts.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dive\Record;

use Dive\Record;

/**
 * @author  Steffen Zeidler <sigma_z@sigma-scripts.de>
 * @created 17.12.13
 */
class FieldValueChangeEvent extends RecordPropertyEvent
{

    /** @var mixed */
    private $oldValue;


    /**
     * @param Record $record
     * @param string $property
     * @param mixed  $value
     * @param mixed  $oldValue
     */
    public function __construct(Record $record, $property, $value, $oldValue)
    {
        parent::__construct($record, $property, $value);
        $this->oldValue = $oldValue;
    }


    /**
     * @return mixed
     */
    public function getOldValue()
    {
        return $this->oldValue;
    }
}
