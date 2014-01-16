<?php
/*
 * This file is part of the Dive ORM framework.
 * (c) Steffen Zeidler <sigma_z@sigma-scripts.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dive\Connection\Driver;

/**
 * @author  Steffen Zeidler <sigma_z@sigma-scripts.de>
 * @created 16.01.14
 */
class DriverFactory
{

    /**
     * @param  string $scheme
     * @throws DriverException
     * @return DriverInterface
     */
    public static function createByScheme($scheme)
    {
        $className = '\\Dive\\Connection\Driver\\' . ucfirst($scheme) . 'Driver';
        if (class_exists($className)) {
            return new $className;
        }
        throw new DriverException("Could not load driver for scheme '$scheme'!");
    }


    /**
     * @param  string $dsn
     * @return DriverInterface
     * @throws DriverException
     */
    public static function createByDsn($dsn)
    {
        $length = strpos($dsn, ':');
        if ($length === false) {
            throw new DriverException("Could not load driver for data source name (dsn) '$dsn'!");
        }
        $scheme = substr($dsn, 0, $length);
        return self::createByScheme($scheme);
    }

}