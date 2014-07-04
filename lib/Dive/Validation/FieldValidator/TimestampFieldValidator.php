<?php
/*
 * This file is part of the Dive ORM framework.
 * (c) Steffen Zeidler <sigma_z@sigma-scripts.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Dive\Validation\FieldValidator;

use Dive\Validation\ValidatorInterface;

/**
 * Class TimestampFieldValidator
 * @author  Steffen Zeidler <sigma_z@sigma-scripts.de>
 * @created 25.04.2014
 */
class TimestampFieldValidator implements ValidatorInterface
{

    /**
     * @param  mixed $value
     * @return bool
     */
    public function validate($value)
    {
        if ($value > 0 && $value < 2147483648 && preg_match('/^\d+$/', $value)) {
            return true;
        }
        return false;
    }


}