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
 * Date: 23.02.13
 */

namespace Dive\Util;


class StringExplode
{

    /**
     * explodes string at given positions
     *
     * @param  string    $haystack
     * @param  int[]|int $positions
     * @param  int       $shiftOnExplodePosition
     * @return array
     */
    public static function explodeAt($haystack, $positions, $shiftOnExplodePosition = 1)
    {
        if (empty($positions)) {
            return array($haystack);
        }
        if (!is_array($positions)) {
            $positions = array($positions);
        }
        else {
            sort($positions);
        }
        $parts = array();
        $cursor = 0;
        $strLength = strlen($haystack);
        foreach ($positions as $pos) {
            if ($strLength > $cursor) {
                $parts[] = substr($haystack, $cursor, $pos - $cursor);
                $cursor = $pos + $shiftOnExplodePosition;
            }
        }
        if ($strLength > $cursor) {
            $parts[] = substr($haystack, $cursor);
        }
        return $parts;
    }


    /**
     * explodes multi lines, trims them on the right, implodes them
     * @param string $text
     * @param string $trim
     * @param string $eol
     * @return string
     */
    public static function trimMultiLines($text, $trim = 'trim', $eol = PHP_EOL)
    {
        $trim = strtolower($trim);
        $lines = explode($eol, $text);
        if ($trim == 'rtrim') {
            foreach ($lines as &$line) {
                $line = rtrim($line);
            }
        }
        else if ($trim == 'ltrim') {
            foreach ($lines as &$line) {
                $line = ltrim($line);
            }
        }
        else {
            foreach ($lines as &$line) {
                $line = trim($line);
            }
        }
        return implode($eol, $lines);
    }

}