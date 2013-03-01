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
 * Date: 11.02.13
 */

namespace Dive\Hydrator;

use Dive\Table;


class RecordHydrator extends Hydrator
{

    /**
     * Gets hydrated result
     *
     * @param  \Dive\Table $table
     * @return bool|\Dive\Record
     */
    public function getResult(Table $table = null)
    {
        if (!($table instanceof Table)) {
            self::throwMissingTableException($table);
        }

        $row = $this->statement->fetch(\PDO::FETCH_ASSOC);
        if ($row === false) {
            return false;
        }
        return $this->hydrateRecord($table, $row);
    }


    /**
     * hydrates record
     *
     * @param   \Dive\Table               $table
     * @param   array                       $row
     * @return  \Dive\Record
     */
    protected function hydrateRecord(Table $table, array $row)
    {
        $record = $this->recordManager->getRecord($table, $row, true);
//        $id = $record->getIdentifierAsString();
//        foreach ($referencingRelations as $relation) {
//            $referencingField = $relation->getReferencingField();
//            $referencingId = $row[$referencingField];
//            if ($relation->isOneToMany()) {
//                $relation->addReference($referencingId, $id);
//            }
//            else {
//                $relation->setReference($referencingId, $id);
//            }
//        }
        return $record;
    }


    /**
     * @param  \Dive\Table $table
     * @throws HydratorException
     */
    public static function throwMissingTableException($table = null)
    {
        $argumentType = is_object($table) ? get_class($table) : gettype($table);
        throw new HydratorException("Hydrator needs table instance! You gave me: " . $argumentType);
    }

}