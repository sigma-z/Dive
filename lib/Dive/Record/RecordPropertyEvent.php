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
 * Class RecordPropertyEvent
 * @author  Steffen Zeidler <sigma_z@sigma-scripts.de>
 * @created 10.03.14
 */
class RecordPropertyEvent extends RecordEvent
{

    /** @var string */
    private $property;

    /** @var mixed */
    private $value;


    /**
     * @param Record $record
     * @param string $property
     * @param mixed  $value
     */
    public function __construct(Record $record, $property, $value = null)
    {
        parent::__construct($record);

        $this->property = $property;
        $this->value = $value;
    }


    /**
     * @return string
     */
    public function getProperty()
    {
        return $this->property;
    }


    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }


    /**
     * @param mixed $value
     */
    public function setValue($value)
    {
        $this->value = $value;
    }

}