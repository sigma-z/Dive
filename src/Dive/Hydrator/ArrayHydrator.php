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
 * @author  Steffen Zeidler <sigma_z@sigma-scripts.de>
 * @created 14.03.13
 */
class ArrayHydrator extends Hydrator
{

    /**
     * Gets statement result
     *
     * @param  \Dive\Table|null $table
     * @return array
     */
    public function getResult(Table $table = null)
    {
        return $this->statement->fetchAll(\PDO::FETCH_ASSOC);
    }

}