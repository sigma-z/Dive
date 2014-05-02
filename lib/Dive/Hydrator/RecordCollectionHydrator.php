<?php
/*
 * This file is part of the Dive ORM framework.
 * (c) Steffen Zeidler <sigma_z@sigma-scripts.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dive\Hydrator;

use Dive\Collection\RecordCollection;
use Dive\Table;


/**
 * @author Steffen Zeidler <sigma_z@sigma-scripts.de>
 * Date: 11.02.13
 */
class RecordCollectionHydrator extends RecordHydrator
{

    /**
     * Gets hydrated result
     *
     * @param   \Dive\Table $table
     * @return  \Dive\Collection\RecordCollection
     */
    public function getResult(Table $table = null)
    {
        if (!($table instanceof Table)) {
            self::throwMissingTableException($table);
        }

        $collection = new RecordCollection($table);
        while (($row = $this->statement->fetch(\PDO::FETCH_ASSOC))) {
            $record = $this->hydrateRecord($table, $row);
            $record->setResultCollection($collection);
            $collection->add($record);
        }
        return $collection;
    }

}
