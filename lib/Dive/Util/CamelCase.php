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
 * Date: 31.10.12
 */

namespace Dive\Util;

class CamelCase
{

    /**
     * gets camel cased string
     *
     * @param   string  $string
     * @return  string
     */
    public static function toCamelCase($string)
    {
        $string = strtolower($string);
        $string = preg_replace('/[^a-z0-9]+?([a-z0-9])/ie', 'ucfirst(\'\1\')', $string);
        $string = ucfirst($string);
        return $string;
    }


    /**
     * gets lower cased string that separates at up-cased characters
     *
     * @param   string  $string
     * @param   string  $separator
     * @return  string
     */
    public static function toLowerCaseSeparateWith($string, $separator = '_')
    {
        $string = preg_replace('/([A-Z])/', $separator . '\1', $string);
        $string = strtolower($string);
        return ltrim($string, $separator);
    }

}