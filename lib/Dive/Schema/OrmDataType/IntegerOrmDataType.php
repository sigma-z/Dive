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
 * Class IntegerOrmDataType
 * @author  Steffen Zeidler <sigma_z@sigma-scripts.de>
 * @created 04.07.2014
 */
class IntegerOrmDataType extends OrmDataType
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
        if (is_int($value)) {
            return true;
        }
        if (is_string($value) && preg_match('/^-?\d+$/', $value)) {
            return true;
        }
        return false;
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
        $unsigned = isset($field['unsigned']) ? $field['unsigned'] : false;
        $minValue = 0;
        $maxValue = (pow(2, 8 * $length)) - 1;
        if (!$unsigned) {
            $maxValue = ($maxValue - 1) / 2;
            $minValue = ($maxValue + 1) * -1;
        }
        if ($value >= $minValue && $value <= $maxValue) {
            return true;
        }
        return false;
    }

}
