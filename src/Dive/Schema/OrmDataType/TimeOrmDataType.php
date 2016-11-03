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
 * Class TimeOrmDataType
 * @author  Steffen Zeidler <sigma_z@sigma-scripts.de>
 * @created 04.07.2014
 */
class TimeOrmDataType extends DateOrmDataType
{

    const DEFAULT_FORMAT = 'H:i:s';


    /** @var string */
    protected $format = self::DEFAULT_FORMAT;

}
