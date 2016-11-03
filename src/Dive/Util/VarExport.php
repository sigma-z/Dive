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
 * @author Steffen Zeidler <sigma_z@sigma-scripts.de>
 * Date: 31.10.12
 */

class VarExport
{

    /**
     * @static
     * @param  mixed $var
     * @param  array $options
     * @return string
     */
    public static function export($var, array $options = array())
    {
        if (null === $var) {
            return 'NULL';
        }

        switch (gettype($var)) {
            case 'array':
                return self::exportArray($var, $options);
            case 'bool':
            case 'boolean':
                return $var ? 'true' : 'false';
            case 'int':
            case 'integer':
            case 'float':
                return $var;
            default:
                return "'$var'";
        }
    }


    /**
     * @static
     * @param  array    $array
     * @param  array    $options
     * @param  string   $indent
     * @return string
     */
    public static function exportArray(array $array, array $options = array(), $indent = '')
    {
        if (count($array) == 0) {
            return "array()";
        }
        $code = 'array(' . "\n";
        $tab = str_repeat(' ', 4);

        foreach ($array as $key => $value) {
            $code .= $indent . $tab;
            if (is_string($key)) {
                $code .= "'$key' => ";
            }
            else if (isset($options['exportNumIndexes'])) {
                $code .= "$key => ";
            }
            if (is_array($value)) {
                $code .= self::exportArray($value, $options, $indent . $tab);
            }
            else {
                $code .= self::export($value);
            }
            $code .= ",\n";
        }
        if (isset($options['removeLastComma'])) {
            $code = substr($code, 0, -2) . "\n";
        }
        $code .= $indent . ")";

        return $code;
    }

}
