<?php
/*
 * This file is part of the Dive ORM framework.
 * (c) Steffen Zeidler <sigma_z@sigma-scripts.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Dive\Schema\OrmDataType;

use Dive\Validation\ValidatorInterface;

/**
 * Interface OrmDataTypeInterface
 * @author  Steffen Zeidler <sigma_z@sigma-scripts.de>
 * @created 04.07.14
 */
interface OrmDataTypeInterface extends ValidatorInterface
{

    /**
     * @return string
     */
    public function getType();

}