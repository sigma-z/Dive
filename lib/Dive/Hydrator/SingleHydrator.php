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
 * @author  Mike Gladysch <mail@mike-gladysch.de>
 * @created 02.05.2014
 */
abstract class SingleHydrator extends Hydrator
{

    /**
     * @return array|bool
     */
    abstract protected function fetchNextRow();


    /**
     * @param Table $table
     * @return mixed
     */
    public function getResult(Table $table = null)
    {
        $row = $this->fetchNextRow();
        if ($row !== false) {
            $this->checkStatementShouldNotReturnANextRow();
        }

        return $this->hydrateSingleRow($row, $table);
    }


    /**
     * @param $row
     * @param $table
     * @return mixed
     */
    protected function hydrateSingleRow($row, Table $table = null)
    {
        return $row;
    }


    /**
     * @throws HydratorException
     */
    private function checkStatementShouldNotReturnANextRow()
    {
        $nextRow = $this->fetchNextRow();
        if ($nextRow !== false) {
            $this->statement->closeCursor();
            throw new HydratorException("Used a single hydrator, but statement returns more than one row.");
        }
    }
}