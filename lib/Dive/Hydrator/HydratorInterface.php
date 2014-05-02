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
interface HydratorInterface
{

    /**
     * Sets PDO statement
     *
     * @param \PDOStatement $stmt
     */
    public function setStatement(\PDOStatement $stmt);

    /**
     * Gets statement result
     *
     * @param  \Dive\Table|null $table
     * @return mixed
     */
    public function getResult(Table $table = null);

}
