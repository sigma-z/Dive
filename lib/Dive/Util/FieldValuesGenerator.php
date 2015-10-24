<?php
 /*
 * This file is part of the Dive ORM framework.
 * (c) Steffen Zeidler <sigma_z@sigma-scripts.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dive\Util;

use Dive\Schema\DataTypeMapper\DataTypeMapper;

/**
 * This class handles generating random values for fields (by field definition of schema) and records (by given field
 *  list).
 *
 * @author  Mike Gladysch <mail@mike-gladysch.de>
 * @created 20.03.13
 */
class FieldValuesGenerator
{
    const REQUIRED                      = 'required';
    const REQUIRED_AND_AUTOINCREMENT    = 'requiredAndAutoIncrement';
    const MAXIMAL_WITHOUT_AUTOINCREMENT = 'maximalWithoutAutoIncrement';
    const MAXIMAL                       = 'maximal';


    /**
     * check field is autoIncrement
     *
     * @param  array $fieldDefinition
     * @return bool
     */
    public static function fieldIsAutoIncrement($fieldDefinition)
    {
        return isset($fieldDefinition['autoIncrement']) && $fieldDefinition['autoIncrement'] === true;
    }


    /**
     * check field is required
     *
     * @param  array $fieldDefinition
     * @return bool
     */
    public static function fieldIsNullable($fieldDefinition)
    {
        return !isset($fieldDefinition['nullable']) || $fieldDefinition['nullable'] !== true;
    }


    /**
     * Delivers all type-constant values of this class
     * @return array
     */
    public function getTypes()
    {
        return array(
            self::REQUIRED,
            self::REQUIRED_AND_AUTOINCREMENT,
            self::MAXIMAL_WITHOUT_AUTOINCREMENT,
            self::MAXIMAL
        );
    }


    /**
     * Checks whether given type hits given fieldDefinition.
     *
     * @param  array  $fieldDefinition
     * @param  string $type = self::REQUIRED
     * @return bool
     */
    public function matchType($fieldDefinition, $type = self::REQUIRED)
    {
        $required = self::fieldIsNullable($fieldDefinition);
        $autoIncrement = self::fieldIsAutoIncrement($fieldDefinition);

        // default: do add field, when MAXIMAL was requested
        if ($type === self::MAXIMAL) {
            return true;
        }
        // do add field, when MAXIMAL_WITHOUT_AUTOINCREMENT was requested and field is not autoIncrement
        if ($type === self::MAXIMAL_WITHOUT_AUTOINCREMENT && !$autoIncrement) {
            return true;
        }
        // required field, that is not autoIncrement is needed whenever we want to get recordData
        if ($required && !$autoIncrement) {
            return true;
        }
        // autoIncrement field, when not REQUIRED requested
        if ($type === self::REQUIRED_AND_AUTOINCREMENT && $required && !$autoIncrement) {
            return true;
        }
        return false;
    }


    /**
     * This method generates a data-array for insert into table.
     *
     * @param  array  $fields
     * @param  array  $recordData = array() - default values, that were not overwritten!
     * @param  string $type       = self::REQUIRED - type of generating data, supports type constants of this class
     * @return array
     */
    public function getRandomRecordData(array $fields, array $recordData = array(), $type = self::REQUIRED)
    {
        foreach ($fields as $fieldName => $fieldDefinition) {
            if (array_key_exists($fieldName, $recordData)) {
                continue;
            }

            // when field is marked as to be added - add a random value for fieldName
            if ($this->matchType($fieldDefinition, $type)) {
                $recordData[$fieldName] = $this->getRandomFieldValue($fieldDefinition);
            }
        }
        return $recordData;
    }


    /**
     * This method generates a random value for a field, which is defined like given $fieldDefinition. If type is not
     *  supported it returns null.
     *
     * @param  array $fieldDefinition
     * @return string|int|float
     */
    public function getRandomFieldValue(array $fieldDefinition)
    {
        $type = $fieldDefinition['type'];
        switch ($type) {
            case DataTypeMapper::OTYPE_DATETIME:
            case DataTypeMapper::OTYPE_TIMESTAMP:
                return date('Y-m-d h:s:i');

            case DataTypeMapper::OTYPE_DATE:
                return date('Y-m-d');

            case DataTypeMapper::OTYPE_TIME:
                return date('h:s:i');

            case DataTypeMapper::OTYPE_ENUM:
                $index = array_rand($fieldDefinition['values']);
                return $fieldDefinition['values'][$index];

            case DataTypeMapper::OTYPE_DECIMAL:
                $length   = isset($fieldDefinition['length'])   ? $fieldDefinition['length']   : 1;
                $scale    = isset($fieldDefinition['scale'])    ? $fieldDefinition['scale']    : 0;
                $unsigned = isset($fieldDefinition['unsigned']) ? $fieldDefinition['unsigned'] : false;

                $numberLength = $scale > 0 ? $length - $scale - 1 : $length;
                $maxValue = pow(10, $numberLength);
                $maxValue -= $scale ? pow(10, $scale * -1) : 1;
                if ($maxValue > 100000000) {
                    $maxValue = 100000000;
                }
                $value = mt_rand(0, $maxValue);
                if (!$unsigned && rand(0, 1) === 1) {
                    $value *= -1;
                }
                return $value;

            case DataTypeMapper::OTYPE_INTEGER:
                $length   = isset($fieldDefinition['length'])   ? $fieldDefinition['length']   : 1;
                $unsigned = isset($fieldDefinition['unsigned']) ? $fieldDefinition['unsigned'] : false;
                $maxValue = (pow(2, 8 * $length)) - 1;
                if (!$unsigned) {
                    $maxValue = ($maxValue - 1) / 2;
                }
                if ($maxValue > 100000000) {
                    $maxValue = 100000000;
                }
                $value = mt_rand(0, $maxValue);
                if (!$unsigned && rand(0, 1) === 1) {
                    $value *= -1;
                }
                return $value;

            case DataTypeMapper::OTYPE_BOOLEAN:
                return mt_rand(0, 1) === 1;

            default:
                // used chars - TODO: check more e.g. chars, that have to be quoted by inserting
                $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0987654321';
                // build min and max for calling random-function
                $min = 0;
                $max = strlen($chars) - 1;

                // init result, number of steps
                $string = '';
                $length = isset($fieldDefinition['length']) ? $fieldDefinition['length'] : 1;
                while ($length > 0) {
                    $x = mt_rand($min, $max);
                    $string .= $chars[$x];
                    $length--;
                }
                return $string;
        }
    }


    /**
     * This method generates a minimal required data-array for insert into table.
     *
     * @param array $fields
     * @param array $recordData = array() - default values, that were not overwritten!
     * @return array
     */
    public function getRequiredRandomRecordData(array $fields, array $recordData = array())
    {
        return $this->getRandomRecordData($fields, $recordData, self::REQUIRED);
    }


    /**
     * This method generates a minimal required data-array for insert into table. This sub-method fills required
     *  autoIncrement fields (if not exist within default values)
     *
     * @param array $fields
     * @param array $recordData = array() - default values, that were not overwritten!
     * @return array
     */
    public function getRequiredRandomRecordDataWithAutoIncrementFields(array $fields, array $recordData = array())
    {
        return $this->getRandomRecordData($fields, $recordData, self::REQUIRED_AND_AUTOINCREMENT);
    }


    /**
     * This method generates a maximal data-array for insert into table. Each field will be filled
     *
     * @param array $fields
     * @param array $recordData = array() - default values, that were not overwritten!
     * @return array
     */
    public function getMaximalRandomRecordDataWithoutAutoIncrementFields(array $fields, array $recordData = array())
    {
        return $this->getRandomRecordData($fields, $recordData, self::MAXIMAL_WITHOUT_AUTOINCREMENT);
    }


    /**
     * This method generates a maximal data-array for insert into table. Each field will be filled
     *
     * @param array $fields
     * @param array $recordData = array() - default values, that were not overwritten!
     * @return array
     */
    public function getMaximalRandomRecordData(array $fields, array $recordData = array())
    {
        return $this->getRandomRecordData($fields, $recordData, self::REQUIRED_AND_AUTOINCREMENT);
    }
}
