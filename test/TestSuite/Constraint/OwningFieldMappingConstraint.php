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
use Dive\Relation\ReferenceMap;
use Dive\Relation\Relation;
use PHPUnit\Framework\Constraint\Constraint;

/**
 * @author  Steffen Zeidler <sigma_z@sigma-scripts.de>
 * @created 16.10.13
 */
class OwningFieldMappingConstraint extends Constraint
{

    /** @var Record */
    private $record = null;

    /** @var string */
    private $relationName = '';


    /**
     * @param Record $record
     * @param string $relationName
     */
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
        $referencedRecord = $isReferencedSide ? $other : $this->record;
        if ($referencedRecord->exists()) {
            return true;
        }

        $owningRecord = $isReferencedSide ? $this->record : $other;
        $owningOid = $owningRecord->getOid();
        $referencedOid = $referencedRecord->getOid();

        $referenceMap = $this->getReferenceMap($relation);
        if ($referencedOid) {
            return $referenceMap->isFieldMappedWith($owningOid, $referencedOid);
        }
        return $referenceMap->hasFieldMapping($owningOid) === false;
    }


    /**
     * Returns a string representation of the object.
     *
     * @return string
     */
    public function toString()
    {
        $oid = $this->record->getOid();
        $recordTableName = $this->record->getTable()->getTableName();
        $relationName = $this->relationName;

        return "{$recordTableName}[$oid]->{$relationName} has field mapping";
    }


    /**
     * @param Record $other
     *
     * @return string
     */
    protected function failureDescription($other)
    {
        $otherOid = $other && $other instanceof Record ? $other->getOid() : 'NULL';
        $relation = $this->record->getTableRelation($this->relationName);
        $otherTableName = $relation->isOwningSide($this->relationName)
            ? $relation->getOwningTable()
            : $relation->getReferencedTable();
        $msg = $this->toString() . " to {$otherTableName}[$otherOid]";
        if ($otherOid === $this->record->getOid()) {
            $msg .= ", both have the same oid";
        }
        return $msg;
    }


    /**
     * Gets the private ReferenceMap of a relation on a record
     *
     * @param  Relation $relation
     * @throws \PHPUnit_Framework_AssertionFailedError
     * @return ReferenceMap
     */
    private function getReferenceMap(Relation $relation)
    {
        $reflectionClass = new \ReflectionClass($relation);
        if (!$reflectionClass->hasProperty('map')) {
            throw new \PHPUnit_Framework_AssertionFailedError("Property 'map' not found on relation!");
        }
        $property = $reflectionClass->getProperty('map');
        $property->setAccessible(true);
        return $property->getValue($relation);
    }

}
