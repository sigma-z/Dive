<?php
/*
 * This file is part of the Dive ORM framework.
 * (c) Steffen Zeidler <sigma_z@sigma-scripts.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Dive\Validation\FieldValidator;

/**
 * Class DateTimeFieldValidator
 * @author  Steffen Zeidler <sigma_z@sigma-scripts.de>
 * @created 25.04.2014
 */
class DateTimeFieldValidator extends DateFieldValidator
{

    const DEFAULT_FORMAT = 'Y-m-d H:i:s';


    /**
     * @param  mixed  $value
     * @param  string $format
     * @return bool
     */
    public function validate($value, $format = null)
    {
        $format = $format ?: self::DEFAULT_FORMAT;
        return parent::validate($value, $format);
    }

}