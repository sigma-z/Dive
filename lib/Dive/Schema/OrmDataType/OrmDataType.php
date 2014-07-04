<?php
/*
 * This file is part of the Dive ORM framework.
 * (c) Steffen Zeidler <sigma_z@sigma-scripts.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Dive\Schema\OrmDataType;

use Dive\Expression;

/**
 * Class OrmDataType
 * @author  Steffen Zeidler <sigma_z@sigma-scripts.de>
 * @created 04.07.2014
 */
abstract class OrmDataType implements OrmDataTypeInterface
{

    /** @var string */
    protected $type;


    /**
     * @param string $type
     */
    public function __construct($type)
    {
        $this->type = $type;
    }


    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }


    /**
     * @param  mixed $value
     * @return bool
     */
    public function canValueBeValidated($value)
    {
        if ($value === null) {
            return false;
        }
        if ($value instanceof Expression) {
            return false;
        }
        return true;
    }

}