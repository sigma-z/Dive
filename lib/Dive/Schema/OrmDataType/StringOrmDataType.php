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
 * Class StringOrmDataType
 * @author  Steffen Zeidler <sigma_z@sigma-scripts.de>
 * @created 04.07.2014
 */
class StringOrmDataType extends OrmDataType
{

    /**
     * @param  mixed $value
     * @return bool
     */
    public function validate($value)
    {
        return $this->canValueBeValidated($value)
            ? is_string($value)
            : true;
    }

}