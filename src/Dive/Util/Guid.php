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
    public static function createGUID()
    {
        if (function_exists('com_create_guid')) {
            return com_create_guid();
        }
        mt_srand((double)microtime() * 10000);//optional for php 4.2.0 and up.
        $charId = strtoupper(md5(uniqid(mt_rand(), true)));
        $hyphen = chr(45);// "-"
        $uuid = substr($charId, 0, 8) . $hyphen
            . substr($charId, 8, 4) . $hyphen
            . substr($charId, 12, 4) . $hyphen
            . substr($charId, 16, 4) . $hyphen
            . substr($charId, 20, 12);
        return $uuid;
    }
}
