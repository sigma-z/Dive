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
 * Class BooleanDataType
 * @author  Steffen Zeidler <sigma_z@sigma-scripts.de>
 * @created 04.07.2014
 */
class BooleanOrmDataType extends OrmDataType
{

    /**
     * @param  mixed $value
     * @return bool
     */
    public function validate($value)
    {
        if (!$this->canValueBeValidated($value)) {
            return true;
        }

        if ($value === '1' || $value === '0') {
            return true;
        }
        if (is_bool($value)) {
            return true;
        }
        if ($value === 1 || $value === 0) {
            return true;
        }

        return false;
    }

}