<?php
/*
 * This file is part of the Dive ORM framework.
 * (c) Steffen Zeidler <sigma_z@sigma-scripts.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dive\TestSuite\Constraint;

use Dive\Record;

/**
 * @author  Steven Nikolic <steven@nindoo.de>
 * @created 01.11.13
 */
class RecordScheduleConstraint extends \PHPUnit_Framework_Constraint
{

    /**
     * @var string
     */
    private $operation;

    /**
     * @param string $operation
     */
    public function __construct($operation)
    {
        $this->operation = $operation;
    }


    /**
     * @param  Record $other
     * @return bool
     */
    public function matches($other)
    {
        return $other->getRecordManager()->isRecordScheduledForCommit($other, $this->operation);
    }


    /**
     * Returns a string representation of the object.
     * @return string
     */
    public function toString()
    {
        return "has to be scheduled for {$this->operation}";
    }


    /**
     * @param Record $other
     * @return string
     */
    protected function failureDescription($other)
    {
        $tableName = $other->getTable()->getTableName();
        return 'Record ' . $tableName . ' ' . $this->toString();
    }

}