<?php
/*
 * This file is part of the Dive ORM framework.
 * (c) Steffen Zeidler <sigma_z@sigma-scripts.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dive;

/**
 * @author Steffen Zeidler <sigma_z@sigma-scripts.de>
 * Date: 04.11.12
 */
class Expression
{

    protected $sql = '';


    /**
     * constructor
     *
     * @param string $sql
     */
    public function __construct($sql)
    {
        $this->sql = $sql;
    }


    /**
     * @return string
     */
    public function getSql()
    {
        return $this->sql;
    }

}