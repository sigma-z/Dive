<?php
/*
 * This file is part of the Dive ORM framework.
 * (c) Steffen Zeidler <sigma_z@sigma-scripts.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Dive\Validation;

/**
 * Class IntegerFieldValidator
 * @author  Steffen Zeidler <sigma_z@sigma-scripts.de>
 * @created 11.04.14
 */
class IntegerFieldValidator implements ValidatorInterface
{

    /**
     * @param  mixed $value
     * @return bool
     */
    public function validate($value)
    {
        if ($value === null) {
            return true;
        }
        if (is_int($value)) {
            return true;
        }
        if (is_string($value) && preg_match('/^-?\d+$/', $value)) {
            return true;
        }

        return false;
    }

}