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
 * Class EnumOrmDataType
 * @author  Steffen Zeidler <sigma_z@sigma-scripts.de>
 * @created 25.07.2014
 */
class EnumOrmDataType extends StringOrmDataType
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
        if (!parent::validateType($value, $field)) {
            return false;
        }
        $values = !empty($field['values']) ? $field['values'] : array();
        return in_array($value, $values);
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
        return true;
    }


}