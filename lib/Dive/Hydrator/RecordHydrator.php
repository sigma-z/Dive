<?php
/*
 * This file is part of the Dive ORM framework.
 * (c) Steffen Zeidler <sigma_z@sigma-scripts.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dive\Hydrator;

use Dive\Record;
use Dive\Table;

/**
 * @author Steffen Zeidler <sigma_z@sigma-scripts.de>
 * Date: 11.02.13
 */
class RecordHydrator extends SingleHydrator
{

    /**
     * @return array|bool
     */
    protected function fetchNextRow()
    {
        return $this->statement->fetch(\PDO::FETCH_ASSOC);
    }


    /**
     * @param mixed $row
     * @param Table $table
     * @return Record|bool
     */
    protected function hydrateSingleRow($row, Table $table = null)
    {
        if ($row === false) {
            return false;
        }
        return $this->hydrateRecord($table, $row);
    }

}