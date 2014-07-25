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
 * Class DateOrmDataType
 * @author  Steffen Zeidler <sigma_z@sigma-scripts.de>
 * @created 04.07.2014
 */
class DateOrmDataType extends OrmDataType
{

    const DEFAULT_FORMAT = 'Y-m-d';


    /** @var string */
    protected $format = self::DEFAULT_FORMAT;


    /**
     * @param string $type
     * @param string $format
     */
    public function __construct($type, $format = null)
    {
        $this->type = $type;
        if ($format !== null) {
            $this->format = $format;
        }
    }


    /**
     * Validates whether the value matches the field type, or not
     *
     * @param  mixed $value
     * @param  array $field
     * @return bool
     */
    public function validateType($value, array $field)
    {
        if (empty($value)) {
            return false;
        }

        $date = \DateTime::createFromFormat($this->format, $value);
        if ($date === false) {
            return false;
        }

        $lastErrors = $date->getLastErrors();
        if ($lastErrors['warning_count'] > 0 || $lastErrors['error_count'] > 0) {
            return false;
        }
        return true;
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
        return $value !== '';
    }
}
