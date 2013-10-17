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
 * @author  Steffen Zeidler <sigma_z@sigma-scripts.de>
 * @created 17.10.13
 */
class RelationReferenceMapConstraint extends \PHPUnit_Framework_Constraint
{

    /** @var \Dive\Record */
    private $record = null;

    /** @var string */
    private $relationName = '';


    public function __construct(Record $record, $relationName)
    {
        $this->record = $record;
        $this->relationName = $relationName;
    }


    /**
     * @param  Record $other
     * @return bool
     */
    public function matches($other)
    {
        $relation = $this->record->getTableRelation($this->relationName);
        $isReferencedSide = $relation->isReferencedSide($this->relationName);

        $recordId = $this->record->getInternalId();
        $otherId = $other->getInternalId();

        $owningId = $isReferencedSide ? $recordId : $otherId;
        $referencedId = $isReferencedSide ? $otherId : $recordId;

        return $this->matchByRecordIds($referencedId, $owningId);
    }


    private function matchByRecordIds($referencedId, $owningId)
    {
        $relation = $this->record->getTableRelation($this->relationName);
        $references = $relation->getReferences();
        if ($owningId === null) {
            $expected = $relation->isOneToMany() ? array() : null;
            if (array_key_exists($referencedId, $references)) {
                return $references[$referencedId] === $expected;
            }
            return true;
        }

        if (!array_key_exists($referencedId, $references)) {
            return false;
        }

        $reference = $references[$referencedId];
        if ($relation->isOneToMany()) {
            // DO NOT CHECK TYPE SAFE!!
            return in_array($owningId, $reference, false);
        }
        return $owningId === $reference;
    }


    /**
     * Returns a string representation of the object.
     *
     * @return string
     */
    public function toString()
    {
        $id = $this->record->getInternalId();
        $recordTableName = $this->record->getTable()->getTableName();
        $relationName = $this->relationName;

        return "{$recordTableName}[$id]->{$relationName} has reference";
    }


    protected function failureDescription($other)
    {
        $otherId = $other->getInternalId();
        $relation = $this->record->getTableRelation($this->relationName);
        $otherTableName = $relation->isOwningSide($this->relationName)
            ? $relation->getOwningTable()
            : $relation->getReferencedTable();
        return $this->toString() . " to {$otherTableName}[$otherId]";
    }


}