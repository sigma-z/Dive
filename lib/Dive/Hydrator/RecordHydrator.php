<?php
/*
 * This file is part of the Dive ORM framework.
 * (c) Steffen Zeidler <sigma_z@sigma-scripts.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dive\Hydrator;

use Dive\Table;

/**
 * @author Steffen Zeidler <sigma_z@sigma-scripts.de>
 * Date: 11.02.13
 */
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
        $this->statement->closeCursor();
        if ($row === false) {
            return false;
        }
        return $this->hydrateRecord($table, $row);
    }


    /**
     * hydrates record
     *
     * @param   \Dive\Table $table
     * @param   array       $row
     * @return  \Dive\Record
     */
    protected function hydrateRecord(Table $table, array $row)
    {
        $record = $this->recordManager->getOrCreateRecord($table->getTableName(), $row, true);
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
}