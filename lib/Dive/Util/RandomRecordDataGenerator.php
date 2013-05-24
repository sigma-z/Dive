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
 * This class handles generating random values for fields (by field definition of schema) and records (by given field
 *  list).
 *
 * @author  Mike Gladysch <mail@mike-gladysch.de>
 * @created 20.03.13
 */
class RandomRecordDataGenerator
{
    const REQUIRED                      = 'required';
    const REQUIRED_AND_AUTOINCREMENT    = 'requiredAndAutoIncrement';
    const MAXIMAL_WITHOUT_AUTOINCREMENT = 'maximalWithoutAutoIncrement';
    const MAXIMAL                       = 'maximal';


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
     * @param array  $fieldDefinition
     * @param string $type              = self::REQUIRED
     * @return bool
     */
    public function matchType($fieldDefinition, $type = self::REQUIRED)
    {
        // check field is required
        $required       = !isset($fieldDefinition['nullable'])      || $fieldDefinition['nullable'] !== true;
        // check field is autoIncrement
        $autoIncrement  = isset($fieldDefinition['autoIncrement'])  && $fieldDefinition['autoIncrement'] === true;

        // default: do add field, when MAXIMAL was requested
        $addFieldValue = $type === self::MAXIMAL;
        // do add field, when MAXIMAL_WITHOUT_AUTOINCREMENT was requested and field is not autoIncrement
        $addFieldValue = $addFieldValue || ($type === self::MAXIMAL_WITHOUT_AUTOINCREMENT && !$autoIncrement);
        // required field, that is not autoIncrement is needed whenever we want to get recordData
        $addFieldValue = $addFieldValue  || ($required && !$autoIncrement);
        // autoIncrement field, when not REQUIRED requested
        $addFieldValue = $addFieldValue || ($type === self::REQUIRED_AND_AUTOINCREMENT && $required && !$autoIncrement);

        return $addFieldValue;
    }


    /**
     * This method generates a data-array for insert into table.
     *
     * @param array  $fields
     * @param array  $recordData    = array() - default values, that were not overwritten!
     * @param string $type          = self::REQUIRED - type of generating data, supports type constants of this class
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
     * @param array $fieldDefinition
     * @return string|int|float|null
     */
    public function getRandomFieldValue(array $fieldDefinition)
    {
        switch ($fieldDefinition['type']) {
            case 'datetime':
                return date('Y-m-d h:s:i');
            // TODO: create float/int really from supported interval with supported decimals
            case 'float':
            case 'integer':
                return mt_rand(0, 100000000);
            case 'string':
                // used chars - TODO: check more e.g. chars, that have to be quoted by inserting
                $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0987654321';
                // build min and max for calling random-function
                $min = 0;
                $max = strlen($chars) - 1;

                // init result, number of steps
                $string = '';
                $l = $fieldDefinition['length'];
                while ($l > 0) {
                    $x = mt_rand($min, $max);
                    $string .= $chars[$x];
                    $l--;
                }
                return $string;
        }

        return null;
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
        return $this->getRandomRecordData($fields, $recordData);
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
