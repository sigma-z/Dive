<?php
/*
 * This file is part of the Dive ORM framework.
 * (c) Steffen Zeidler <sigma_z@sigma-scripts.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dive\Util;

/**
 * @author  Ulrike Janich <mail@ujanich.de>
 * @created 29.03.17
 */
class Guid
{

    /**
     * @return string
     */
    public static function create()
    {
        $uniqueId = uniqid('', true);
        return str_replace('.', '', $uniqueId);
    }
}
