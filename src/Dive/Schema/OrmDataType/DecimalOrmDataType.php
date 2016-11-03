<?php
/*
 * This file is part of the Dive ORM framework.
 * (c) Steffen Zeidler <sigma_z@sigma-scripts.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Dive\Schema\OrmDataType;

/**
 * Class DecimalOrmDataType
 * @author  Steffen Zeidler <sigma_z@sigma-scripts.de>
 * @created 04.07.2014
 */
class DecimalOrmDataType extends OrmDataType
{

    /**
     * Validates whether the value matches the field type, or not
     *
     * @param  mixed $value
     * @param  array $field
     * @return bool
     */
    public function validateType($value, array $field)
    {
        return is_numeric($value);
    }


    /**
     * Validates whether the value fits to the field length, or not
     *
     * @param  mixed $value
     * @param  array $field
     * @return bool
     */
    public function validateLength($value, array $field)
    {
        if (!isset($field['length'])) {
            return true;
        }

        $length = $field['length'];
        $valueStringLength = strlen(ltrim($value, '-'));
        if ($valueStringLength > $length) {
            return false;
        }

        $scale = isset($field['scale']) ? $field['scale'] : 0;
        $unsigned = isset($field['unsigned']) ? $field['unsigned'] : false;

        $numberLength = $scale > 0 ? $length - $scale - 1 : $length;
        $maxValue = pow(10, $numberLength);
        $maxValue -= $scale ? pow(10, $scale * -1) : 1;
        $minValue = $unsigned ? 0 : $maxValue * -1;

        if ($maxValue < $value || $minValue > $value) {
            return false;
        }

        $commaPos = strpos($value, '.');
        if ($commaPos !== false && $scale < $valueStringLength - $commaPos - 1) {
            return false;
        }

        return true;
    }

}
