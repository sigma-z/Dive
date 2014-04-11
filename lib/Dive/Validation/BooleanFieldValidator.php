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
 * Class BooleanFieldValidator
 * @author  Steffen Zeidler <sigma_z@sigma-scripts.de>
 * @created 11.04.14
 */
class BooleanFieldValidator implements ValidatorInterface
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
        if (is_bool($value)) {
            return true;
        }
        if ($value === 1 || $value === 0) {
            return true;
        }
        if ($value === '1' || $value === '0') {
            return true;
        }

        return false;
    }

}