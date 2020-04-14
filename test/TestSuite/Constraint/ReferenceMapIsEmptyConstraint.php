<?php
/*
 * This file is part of the Dive ORM framework.
 * (c) Steffen Zeidler <sigma_z@sigma-scripts.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dive\TestSuite\Constraint;

use Dive\Relation\ReferenceMap;
use Dive\Relation\Relation;
use PHPUnit\Framework\Constraint\Constraint;

/**
 * @author  Steffen Zeidler <sigma_z@sigma-scripts.de>
 * @created 17.10.13
 */
class ReferenceMapIsEmptyConstraint extends Constraint
{

    /**
     * @param Relation  $relation
     * @return bool|mixed
     */
    public function matches($relation)
    {
        $reflRelation = new \ReflectionClass($relation);
        $property = $reflRelation->getProperty('map');
        $property->setAccessible(true);
        /** @var ReferenceMap $map */
        $map = $property->getValue($relation);
        if ($map->getMapping()) {
            return false;
        }
        $relfReferenceMap = new \ReflectionClass($map);
        $property = $relfReferenceMap->getProperty('owningFieldOidMapping');
        $property->setAccessible(true);
        return !$property->getValue($map);
    }


    /**
     * Returns a string representation of the object.
     *
     * @return string
     */
    public function toString()
    {
        return 'relation map is empty';
    }


    /**
     * Returns the description of the failure
     *
     * The beginning of failure messages is "Failed asserting that" in most
     * cases. This method should return the second part of that sentence.
     *
     * @param  Relation $relation Evaluated value or object.
     * @return string
     */
    protected function failureDescription($relation)
    {
        $owningAlias = $relation->getOwningAlias();
        $owningTable = $relation->getOwningTable();
        $referencedAlias = $relation->getReferencedAlias();
        $referencedTable = $relation->getReferencedTable();
        return $this->toString()
            . "$owningTable->$referencedAlias: $referencedTable | $referencedTable->$owningAlias: $owningTable";
    }

}
