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


    /**
     * @param  mixed $value
     * @param  string $format
     * @return bool
     */
    public function validate($value, $format = null)
    {
        if (!$this->canValueBeValidated($value)) {
            return true;
        }

        if (empty($value)) {
            return false;
        }

        $format = $format ?: self::DEFAULT_FORMAT;
        $date = \DateTime::createFromFormat($format, $value);
        if ($date === false) {
            return false;
        }
        $lastErrors = $date->getLastErrors();
        if ($lastErrors['warning_count'] > 0 || $lastErrors['error_count'] > 0) {
            return false;
        }
        return true;
    }

}